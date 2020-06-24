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
                    $fileManager->filePutContents($installation['appInstallation'] . '/storage/branding.json', Modules_Microweber_WhiteLabel::getWhiteLabelJson($domain));
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