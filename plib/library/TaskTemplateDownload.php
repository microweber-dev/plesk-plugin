<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskTemplateDownload extends \pm_LongTask_Task
{

    public $hidden = true;
	public $trackProgress = true;

	public function run()
	{
		$this->updateProgress(10);
		
		if (!empty($this->getParam('downloadUrl')) && !empty($this->getParam('targetDir'))) {
		
			$this->updateProgress(30);
			
			$this->updateProgress(40);
			
			$this->updateProgress(60);
			
			$downloadLog = pm_ApiCli::callSbin('unzip_app_template.sh',[
				base64_encode($this->getParam('downloadUrl')),
				Modules_Microweber_Config::getAppSharedPath() . '/userfiles/templates/' . $this->getParam('targetDir') . '/'
			])['stdout'];
			
		}
		
		$this->updateProgress(100);
	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Installing Microweber '.$this->getParam('targetDir').' template...';
			case static::STATUS_DONE:
				return 'Microweber '.$this->getParam('targetDir').' template is downloaded successfully.';
			case static::STATUS_ERROR:
				return 'Error installing microweber '.$this->getParam('targetDir').' template.';
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