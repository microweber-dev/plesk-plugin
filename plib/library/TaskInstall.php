<?php

class Modules_Microweber_TaskInstall extends \pm_LongTask_Task
{

	public $trackProgress = true;

	public function run()
	{
		$newInstallation = new Modules_Microweber_Install();
		$newInstallation->setDomainId($this->getParam('domainId'));
		$newInstallation->setType($this->getParam('type'));
		$newInstallation->setDatabaseDriver($this->getParam('databaseDriver'));
		$newInstallation->setPath($this->getParam('path'));
		
		if (!empty($this->getParam('email'))) {
			$newInstallation->setEmail($this->getParam('email'));
		}
		
		if (!empty($this->getParam('username'))) {
			$newInstallation->setUsername($this->getParam('username'));
		}
		
		if (!empty($this->getParam('password'))) {
			$newInstallation->setPassword($this->getParam('password'));
		}
		
		$newInstallation->setProgressLogger($this);
		$newInstallation->run();
		
	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Installing Microweber...';
			case static::STATUS_DONE:
				return 'Microweber is installed successfully on ' . $this->getParam('domainName', 'none') .'/'.$this->getParam('path'). '.';
			case static::STATUS_ERROR:
				return 'Error installing microweber.';
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