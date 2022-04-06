<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_EventListener implements EventListener
{
    public function filterActions()
    {
        return [
            'license_expired',
            'license_update',
            'additional_license_expired',

            'domain_delete',
            'domain_alias_delete',
            'site_delete',

            'phys_hosting_create',
            'phys_hosting_update'
        ];
    }

    public function handleEvent($objectType, $objectId, $action, $oldValue, $newValue)
    {
        switch ($action) {

            case "license_update":

                $limitations = Modules_Microweber_LicenseData::getLimitations();

                if ($limitations['app_installations_freeze']) {
                    Modules_Microweber_Helper::stopTasks(['task_whitelabelbrandinremove']);
                    $taskManager = new pm_LongTask_Manager();
                    // Start new task
                    $task = new Modules_Microweber_Task_WhiteLabelBrandingRemove();
                    $taskManager->start($task, NULL);
                } else {
                    Modules_Microweber_Helper::stopTasks(['task_whitelabelbrandinupdate']);
                    $taskManager = new pm_LongTask_Manager();
                    // Start new task
                    $task = new Modules_Microweber_Task_WhiteLabelBrandingUpdate();
                    $taskManager->start($task, NULL);
                }

                break;

            case "license_expired":
            case "additional_license_expired":

                Modules_Microweber_Helper::stopTasks(['task_whitelabelbrandinremove']);

                $taskManager = new pm_LongTask_Manager();

                // Start new task
                $task = new Modules_Microweber_Task_WhiteLabelBrandingRemove();
                $taskManager->start($task, NULL);

                break;

            case "domain_delete":
            case "domain_alias_delete":
            case "site_delete":

                $domain = new pm_Domain($objectId);
                if (empty($domain->getName())) {
                    return;
                }
                
                $taskManager = new pm_LongTask_Manager();

                Modules_Microweber_Helper::stopTasks(['task_domainappinstallationcscan']);

                $task = new Modules_Microweber_Task_DomainAppInstallationScan();
                $task->setParam('domainId', $domain->getId());
                $taskManager->start($task, NULL);

                Modules_Microweber_Helper::stopTasks(['task_domainappinstallationcount']);

                $task = new Modules_Microweber_Task_DomainAppInstallationCount();
                $taskManager->start($task, NULL);
                break;

            case "phys_hosting_create":
            case "phys_hosting_update":

                $domain = new pm_Domain($objectId);
                if (empty($domain->getName())) {
                    return;
                }

                if (!$domain->hasHosting()) {
                    $domain->setSetting('mwAppInstallations', false);
                    return false;
                }

                if ($action == 'phys_hosting_update') {
                    $taskManager = new pm_LongTask_Manager();
                    Modules_Microweber_Helper::stopTasks(['task_domainappinstallationcount']);
                    $task = new Modules_Microweber_Task_DomainAppInstallationCount();
                    $taskManager->start($task, NULL);
                }

                $planItems = $domain->getPlanItems();

                if (is_array($planItems)
                    && count($planItems) > 0
                    && (in_array("microweber", $planItems)
                        || in_array("microweber_without_shop", $planItems)
                        || in_array("microweber_lite", $planItems))) {

                    if ($action == 'phys_hosting_create') {
                        // Install microweber
                        $this->_installMicroweber($domain, $newValue);
                        // Enable or disable shop
                        $this->_updateMicroweber($domain, $newValue);
                    }

                    if ($action == 'phys_hosting_update') {
                        $this->_updateMicroweber($domain, $newValue);
                    }
                }
                break;
        }

    }

    private function _updateMicroweber($domain, $newValues)
    {
        $planItems = $domain->getPlanItems();

        if (is_array($planItems) && count($planItems) > 0) {

            $microweber = new Modules_Microweber_Update();
            $microweber->setDomainId($domain->getId());

            if (in_array("microweber", $planItems)) {
                $microweber->setShopActive(true);
            }

            if (in_array("microweber_without_shop", $planItems)) {
                $microweber->setShopActive(false);
            }

            if (in_array("microweber_lite", $planItems)) {
                $microweber->setShopActive(false);
            }

            $microweber->update();
        }

    }

    private function _installMicroweber($domain, $newValues)
    {
        try {
            $whmcsConnector = new Modules_Microweber_WhmcsConnector();
            $whmcsConnector->setDomainName($domain->getName());
            $selectedTemplate = $whmcsConnector->getSelectedTemplate();

            $newInstallation = new Modules_Microweber_Install();
            $newInstallation->setDomainId($domain->getId());
            $newInstallation->setType(pm_Settings::get('installation_type'));
            $newInstallation->setDatabaseDriver(pm_Settings::get('installation_database_driver'));
            $newInstallation->setUsername($newValues['System User']);
            $newInstallation->setEmail($newValues['System User']);
            $newInstallation->setPassword($newValues['System User Password']);

            if (!empty($selectedTemplate)) {
                $newInstallation->setTemplate($selectedTemplate);
            }

            $newInstallation->run();

        } catch (pm_Exception $e) {
            pm_Settings::set('domain_issue_' . $domain->getId(), pm_Locale::lmsg('microweberError', [
                'domain' => $domain->getDisplayName(),
                'package' => $domain->getPlanItems()[0],
                'error' => $e->getMessage()
            ]));
        }
    }
}

return new Modules_Microweber_EventListener();