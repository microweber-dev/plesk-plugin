<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskWhiteLabelBrandingUpdate extends \pm_LongTask_Task
{
    public $hidden = true;
	public $trackProgress = false;

	public function run()
	{

        $domain = Modules_Microweber_Domain::getUserDomainById($this->getParam('domainId'));
        if (!$domain->hasHosting()) {
            return;
        }

        $installations = json_decode($domain->getSetting('mwAppInstallations'), true);
        if (!$installations || !is_array($installations)) {
            return;
        }

        try {
            foreach ($installations as $installation) {

                $fileManager = new pm_FileManager($domain->getId());

                if ($fileManager->fileExists($installation['appInstallation'] . '/config/microweber.php')) {

                    $whitelabelSettings = [];
                    $currentBranding = $installation['appInstallation'] . '/storage/branding.json';
                    $currentBranding = $fileManager->fileGetContents($currentBranding);
                    $currentBranding = json_decode($currentBranding, true);
                    if (is_array($currentBranding)) {
                        $whitelabelSettings = $currentBranding;
                    }

                    foreach(Modules_Microweber_WhiteLabel::getWhiteLabelJson($domain) as $key=>$setting) {
                        $whitelabelSettings[$key] = $setting;
                    }

                    $fileManager->filePutContents($installation['appInstallation'] . '/storage/branding.json', $whitelabelSettings);
                }
            }
        } catch (Exception $e) {
            // Broken domain permissions
        }

	}

	public function onStart()
	{
		$this->setParam('onStart', 1);
	}

	public function onDone()
	{
		$this->setParam('onDone', 1);
	}
}