<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Task_DomainAppInstall extends \pm_LongTask_Task
{
	public $trackProgress = true; 

	public function run()
	{
        if (pm_Settings::get('installation_notifications') !== 'yes') {
            $this->hidden = true;
        }
        
		$newInstallation = new Modules_Microweber_Install();
		$newInstallation->setDomainId($this->getParam('domainId'));
		$newInstallation->setType($this->getParam('type'));
		$newInstallation->setDatabaseDriver($this->getParam('databaseDriver'));
		//$newInstallation->setDatabaseServerId($this->getParam('databaseServerId'));
		$newInstallation->setPath($this->getParam('path'));

        if (!empty($this->getParam('ssl'))) { 
            $newInstallation->setSsl($this->getParam('ssl'));
        }

		if (!empty($this->getParam('email'))) {
			$newInstallation->setEmail($this->getParam('email'));
		}
		
		if (!empty($this->getParam('username'))) {
			$newInstallation->setUsername($this->getParam('username'));
		}
		
		if (!empty($this->getParam('password'))) {
			$newInstallation->setPassword($this->getParam('password'));
		}
		
		if (!empty($this->getParam('template'))) {
			$newInstallation->setTemplate($this->getParam('template'));
		}
		
		if (!empty($this->getParam('language'))) {
			$newInstallation->setLanguage($this->getParam('language'));
		}
		
		$newInstallation->setProgressLogger($this);
		$status = $newInstallation->run();

        $this->startDomainScan();

        if (isset($status['error']) && $status['error']) {
            throw new pm_Exception($status['log']);
        }
	}

    public function startDomainScan()
    {
        Modules_Microweber_Helper::stopTasks(['task_domainappinstallationcscan']);

        $task = new Modules_Microweber_Task_DomainAppInstallationScan();
        $task->hidden = true;
        $task->setParam('domainId', $this->getParam('domainId'));

        $taskManager = new pm_LongTask_Manager();
        $taskManager->start($task, NULL);
    }

	public function statusMessage()
	{
		switch ($this->getStatus()) {

			case static::STATUS_RUNNING:

				return 'Installing '.Modules_Microweber_WhiteLabel::getBrandName().' on ' .  $this->getParam('domainDisplayName', 'none') .'/'.$this->getParam('path');

			case static::STATUS_DONE:

				return ''.Modules_Microweber_WhiteLabel::getBrandName().' is installed successfully on ' . $this->getParam('domainDisplayName', 'none') .'/'.$this->getParam('path');

			case static::STATUS_ERROR:

				return 'Error installing '.Modules_Microweber_WhiteLabel::getBrandName().' on ' . $this->getParam('domainDisplayName', 'none') .'/'.$this->getParam('path');

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