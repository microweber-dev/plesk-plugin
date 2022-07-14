<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Task_DomainAppInstallationScan extends \pm_LongTask_Task
{
    const UID = 'domainAppInstallationScan';
    public $runningLog = 'Starting domain app installations scan...';
    public $trackProgress = false;
    public $hidden = true;

	public function run()
	{
		$this->updateProgress(10);

        $domainId = $this->getParam('domainId');
        if ($domainId !== null) {

            $domain = Modules_Microweber_Domain::getUserDomainById($domainId);
            if (!$domain->hasHosting()) {
                Modules_Microweber_Domain::setMwOption($domain, 'mwAppInstallations', false);
                $this->updateProgress(100);
                return false;
            }

            $this->updateProgress(20);
            Modules_Microweber_Domain::scanForAppInstallations($domain);
            $this->updateProgress(50);

        } else {
            $i=0;
            $installations=0;

            foreach (Modules_Microweber_Domain::getDomains() as $domain) {
                if (!$domain->hasHosting()) {
                    continue;
                }
                $this->runningLog = 'Scanning '.Modules_Microweber_WhiteLabel::getBrandName().' installations on domain: ' . $domain->getName();

                $i++;
                $this->updateProgress($i);
                $installationsFound = Modules_Microweber_Domain::scanForAppInstallations($domain);
                if ($installationsFound) {
                    $installations = $installations + $installationsFound;
                }
            }

            pm_Settings::set('mw_installations_count', $installations);
        }

		
		$this->updateProgress(100);
	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return $this->runningLog;
			case static::STATUS_DONE:
				return 'Domain scanning complete!';
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