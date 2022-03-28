<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskAppDownload extends \pm_LongTask_Task
{
	public $trackProgress = true;

	public function run()
	{
        $downloadLog = '';

        $this->updateProgress(10);

        $release = Modules_Microweber_Config::getRelease();

        $this->updateProgress(20);

        $appSharedPath = Modules_Microweber_Config::getAppSharedPath();

        $downloadLog .= pm_ApiCli::callSbin('unzip_app_version.sh', [base64_encode($release['url']), $appSharedPath])['stdout'];

        $this->updateProgress(30);

        // Whm Connector
        $downloadUrl = 'https://github.com/microweber-dev/whmcs-connector/archive/master.zip';
        $downloadLog .= pm_ApiCli::callSbin('unzip_app_modules.sh', [base64_encode($downloadUrl), Modules_Microweber_Config::getAppSharedPath()])['stdout'];

        $this->updateProgress(70);

        // Login with token
        $downloadUrl = 'https://github.com/microweber-modules/login_with_token/archive/master.zip';
        $downloadLog .= pm_ApiCli::callSbin('unzip_app_modules.sh', [base64_encode($downloadUrl), Modules_Microweber_Config::getAppSharedPath()])['stdout'];

        $this->updateProgress(80);

        Modules_Microweber_WhmcsConnector::updateWhmcsConnector();

        $this->updateProgress(90);

        pm_Settings::set('show_php_version_wizard', false);

        $this->updateProgress(100);

        $taskManager = new pm_LongTask_Manager();
        $task = new Modules_Microweber_TaskDomainReinstall();
        $taskManager->start($task, NULL);
	}

    private function _queueRefreshDomains()
    {
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {

            if (!$domain->hasHosting()) {
                continue;
            }

            $taskManager = new pm_LongTask_Manager();

            $task = new Modules_Microweber_TaskDomainAppInstallationScan();
            $task->setParam('domainId', $domain->getId());

            $taskManager->start($task, NULL);
        }
    }

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Download '.Modules_Microweber_WhiteLabel::getBrandName().' '.$this->getParam('targetDir').' app...';
			case static::STATUS_DONE:
				return Modules_Microweber_WhiteLabel::getBrandName().' '.$this->getParam('targetDir').' app is updated successfully.';
			case static::STATUS_ERROR:
				return 'Error installing '.Modules_Microweber_WhiteLabel::getBrandName().' '.$this->getParam('targetDir').' app.';
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

        $this->_queueRefreshDomains();
	}
}