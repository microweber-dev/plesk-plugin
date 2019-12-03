<?php

/**
 * Author: Bozhidar Slaveykov
 * @email: info@credocart.com
 * Plesk auto app installer
 */
class Modules_Microweber_EventListener implements EventListener
{

	public function handleEvent($objectType, $objectId, $action, $oldValue, $newValue)
	{
		
		if ($action == 'phys_hosting_create' || $action == 'phys_hosting_update') {
			
			$domain = new pm_Domain($objectId);
			$planItems = $domain->getPlanItems();
			
			if (is_array($planItems) && count($planItems) > 0 && (in_array("microweber", $planItems) || in_array("microweber_without_shop", $planItems) || in_array("microweber_lite", $planItems))) {
			
				if ($action == 'phys_hosting_create' && pm_Settings::get('installation_settings') == 'auto') {
					$this->_installMicroweber($domain, $newValue);
				}
				
				if ($action == 'phys_hosting_update') {
					$this->_updateMicroweber($domain, $newValue);
				}
				
			}
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
			
			if (! empty($selectedTemplate)) {
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