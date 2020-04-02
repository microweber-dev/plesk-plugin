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

        $downloadLog .= pm_ApiCli::callSbin('unzip_app_version.sh', [base64_encode($release['url']), Modules_Microweber_Config::getAppSharedPath()])['stdout'];

        $this->updateProgress(60);

        // Whm Connector
        $downloadUrl = 'https://github.com/microweber-dev/whmcs-connector/archive/master.zip';
        $downloadLog .= pm_ApiCli::callSbin('unzip_app_modules.sh', [base64_encode($downloadUrl), Modules_Microweber_Config::getAppSharedPath()])['stdout'];

        $this->updateProgress(70);


        // Login with token
        $downloadUrl = 'https://github.com/microweber-modules/login_with_token/archive/master.zip';
        $downloadLog .= pm_ApiCli::callSbin('unzip_app_modules.sh', [base64_encode($downloadUrl), Modules_Microweber_Config::getAppSharedPath()])['stdout'];

        $this->updateProgress(80);

        Modules_Microweber_WhmcsConnector::updateWhmcsConnector();

        $this->updateProgress(100);
	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Download Microweber '.$this->getParam('targetDir').' app...';
			case static::STATUS_DONE:
				return 'Microweber '.$this->getParam('targetDir').' app is downloaded successfully.';
			case static::STATUS_ERROR:
				return 'Error installing microweber '.$this->getParam('targetDir').' app.';
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