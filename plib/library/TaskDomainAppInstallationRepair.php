<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskDomainAppInstallationRepair extends \pm_LongTask_Task
{
    public $hidden = false;
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


        sleep(15);




		
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
				return 'Error repair '.Modules_Microweber_WhiteLabel::getBrandName().' domain';
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