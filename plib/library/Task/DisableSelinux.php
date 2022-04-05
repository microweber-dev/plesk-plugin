<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Task_DisableSelinux extends \pm_LongTask_Task
{

    public $hidden = true;
	public $trackProgress = true;

	public function run()
	{
		$this->updateProgress(40);

		sleep(5);

        $this->updateProgress(100);

        pm_ApiCli::callSbin('symlinking_enable.sh',[]);
	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Disabling selinux...';
			case static::STATUS_DONE:
				return 'Selinux is disabled.';
			case static::STATUS_ERROR:
				return 'Error disabling selinux.';
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