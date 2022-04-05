<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskDomainAppInstallationCount extends \pm_LongTask_Task
{
    public $hidden = true;
	public $trackProgress = true;

	public function run()
	{
        if ($this->getParam('hiddenTask')) {
            $this->hidden = true;
            $this->trackProgress = false;
        }

		$this->updateProgress(30);

        $installations = 0;
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {
            if (!$domain->hasHosting()) {
                continue;
            }
            $domainInstallations = $domain->getSetting('mwAppInstallations');
            $domainInstallations = json_decode($domainInstallations, true);
            if (empty($domainInstallations)) {
                continue;
            }

            $installations++;
        }

        pm_Settings::set('mw_installations_count', $installations);

        $this->updateProgress(100);

	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Counting app installations...';
			case static::STATUS_DONE:
				return 'counted!';
			case static::STATUS_ERROR:
				return 'Error when counting installations';
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