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
            case "reseller_delete":
            case "client_delete":
            case "domain_move":

                $domain = new pm_Domain($objectId);
                if (empty($domain->getName())) {
                    return;
                }
                
                $taskManager = new pm_LongTask_Manager();

                Modules_Microweber_Helper::stopTasks(['task_domainappinstallationcscan']);

                $task = new Modules_Microweber_Task_DomainAppInstallationScan();
                $task->hidden = true;
                $task->setParam('domainId', $domain->getId());
                $taskManager->start($task, NULL);
                break;

            case "phys_hosting_create":
            case "phys_hosting_update":

                $domain = new pm_Domain($objectId);
                if (empty($domain->getName())) {
                    return;
                }

                if (!$domain->hasHosting()) {
                    Modules_Microweber_Domain::setMwOption($domain, 'mwAppInstallations', false);
                    return false;
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
        $installationDomainPath = $domain->getName();
        $installationDirPath = $domain->getDocumentRoot();

        $whmcsConnector = new Modules_Microweber_WhmcsConnector();
        $whmcsConnector->setDomainName($domain->getName());
        $selectedTemplate = $whmcsConnector->getSelectedTemplate();

        $installationType = 'Standalone';
        if (pm_Settings::get('installation_type') == 'symlink') {
            $installationType = 'Symlinked';
        }

        Modules_Microweber_Domain::addAppInstallation($domain, [
            'domainNameUrl' => $installationDomainPath,
            'domainCreation' => $domain->getProperty('cr_date'),
            'installationType' => $installationType,
            'appVersion' => '-',
            'appInstallation' => $installationDirPath,
            'domainIsActive' => true,
            'manageDomainUrl' => '',
            'pending' => true,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        pm_Settings::set('mw_installations_count',  (Modules_Microweber_LicenseData::getAppInstallationsCount() + 1));

        $task = new Modules_Microweber_Task_DomainAppInstall();
        $task->setParam('domainId', $domain->getId());
        $task->setParam('domainName', $domain->getName());
        $task->setParam('domainDisplayName', $domain->getDisplayName());
        $task->setParam('type', pm_Settings::get('installation_type'));
        $task->setParam('databaseDriver', pm_Settings::get('installation_database_driver'));

        if (!empty($selectedTemplate)) {
            $task->setParam('template', $selectedTemplate);
        }

        $task->setParam('language', pm_Settings::get('installation_language'));
        $task->setParam('email', $newValues['System User']);
        $task->setParam('username', $newValues['System User']);
        $task->setParam('password', $newValues['System User Password']);

        $taskManager = new pm_LongTask_Manager();
        $taskManager->start($task, NULL);
    }
}

return new Modules_Microweber_EventListener();