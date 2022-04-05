<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskDomainAppInstallationScan extends \pm_LongTask_Task
{
    public $hidden = true;
	public $trackProgress = true;

	public function run()
	{
		$this->updateProgress(10);

		if (empty($this->getParam('domainId'))) {
		    return;
        }

		$domain = Modules_Microweber_Domain::getUserDomainById($this->getParam('domainId'));

        if (!$domain->hasHosting()) {
            return;
        }

        $installationsFind = [];

        $domainDocumentRoot = $domain->getDocumentRoot();
        $domainName = $domain->getName();
        $domainDisplayName = $domain->getDisplayName();
        $domainIsActive = $domain->isActive();
        $domainCreation = $domain->getProperty('cr_date');

        $appVersion = 'unknown';

        $fileManager = new pm_FileManager($domain->getId());

        $allDirs = $fileManager->scanDir($domainDocumentRoot, true);
        foreach ($allDirs as $dir) {
            if (!is_dir($domainDocumentRoot . '/' . $dir . '/config/')) {
                continue;
            }
            if (is_file($domainDocumentRoot . '/' . $dir . '/config/microweber.php')) {
                $installationsFind[] = $domainDocumentRoot . '/' . $dir . '/config/microweber.php';
            }
        }

        if (is_dir($domainDocumentRoot . '/config/')) {
            if (is_file($domainDocumentRoot . '/config/microweber.php')) {
                $installationsFind[] = $domainDocumentRoot . '/config/microweber.php';
            }
        }

        if (empty($installationsFind)) {
            $domain->setSetting('mwAppInstallations', false);
        }

        if (!empty($installationsFind)) {

            foreach ($installationsFind as $appInstallationConfig) {

                if (strpos($appInstallationConfig, 'backup-files') !== false) {
                    continue;
                }

                $appInstallation = str_replace('/config/microweber.php', false, $appInstallationConfig);

                // Find app in main folder
                if ($fileManager->fileExists($appInstallation . '/version.txt')) {
                    $appVersion = $fileManager->fileGetContents($appInstallation . '/version.txt');
                }

                if (is_link($appInstallation . '/vendor')) {
                    $installationType = 'Symlinked';
                } else {
                    $installationType = 'Standalone';
                }

                $domainNameUrl = $appInstallation;
                $domainNameUrl = str_replace('/var/www/vhosts/', false, $domainNameUrl);
                $domainNameUrl = str_replace($domainName . '/httpdocs', $domainName, $domainNameUrl);
                $domainNameUrl = str_replace($domainName, $domainDisplayName, $domainNameUrl);

                $manageDomainUrl = '/smb/web/overview/id/d:' . $domain->getId();
                if (pm_Session::getClient()->isAdmin()) {
                    $manageDomainUrl = '/admin/subscription/login/id/' . $domain->getId() . '?pageUrl=' . $manageDomainUrl;
                } else {
                    $manageDomainUrl = $manageDomainUrl;
                }

                $hostingManager = new Modules_Microweber_HostingManager();
                $hostingManager->setDomainId($domain->getId());

                $subscription = $hostingManager->getDomainSubscription($domain->getName());
                if ($subscription['webspace'] == false) {
                    $manageDomainUrl = '/smb/web/view/id/' . $domain->getId() . '/type/domain';
                }

                $domainNameAppUrlPath = $domainName;
                $appInstallationExpByDomain = explode($domainName, $appInstallation);
                if($appInstallationExpByDomain){
                    $domainNameAppUrlPath = end($appInstallationExpByDomain);
                    $domainNameAppUrlPath = str_replace( '/httpdocs', '', $domainNameAppUrlPath);
                    $domainNameAppUrlPath = $domainName . $domainNameAppUrlPath;
                }

                Modules_Microweber_Domain::addAppInstallation($domain, [
                    'domainNameUrl'=>$domainNameAppUrlPath,
                    'domainCreation'=>$domainCreation,
                    'installationType'=>$installationType,
                    'appVersion'=>$appVersion,
                    'appInstallation'=>$appInstallation,
                    'domainIsActive'=>$domainIsActive,
                    'manageDomainUrl'=>$manageDomainUrl,
                ]);
            }
        }

        $taskManager = new pm_LongTask_Manager();

        $task = new Modules_Microweber_TaskWhiteLabelBrandingUpdate();
        $task->setParam('domainId', $domain->getId());

        $taskManager->start($task, NULL);
		
		$this->updateProgress(100);
	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return '';
			case static::STATUS_DONE:
				return '';
			case static::STATUS_ERROR:
				return 'Error scan '.Modules_Microweber_WhiteLabel::getBrandName().' domain';
			case static::STATUS_NOT_STARTED:
				return pm_Locale::lmsg('taskPingError', [
					'id' => $this->getId()
				]);
		}

		return '';
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