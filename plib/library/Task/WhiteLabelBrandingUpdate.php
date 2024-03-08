<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Task_WhiteLabelBrandingUpdate extends \pm_LongTask_Task
{
    const UID = 'whiteLabelBrandingUpdate';
    public $runningLog = 'Whitelabel branding update has started...';
	public $trackProgress = true;

	public function run()
	{
        $domainId = $this->getParam('domainId');

        if ($domainId !== null) {

            $this->hidden = true;
            $this->trackProgress = false;

            $domain = Modules_Microweber_Domain::getUserDomainById($domainId);
            $this->updateDomainBranding($domain);
            $this->updateProgress(50);

        } else {
            $i = 0;
            foreach (Modules_Microweber_Domain::getDomains() as $domain) {
                $i++;
                $this->updateProgress($i);
                $this->updateDomainBranding($domain);
            }
        }

        $this->updateProgress(100);
	}

    private function updateDomainBranding($domain)
    {
        if (!$domain->hasHosting()) {
            return;
        }

        $installations = Modules_Microweber_Domain::getMwOption($domain, 'mwAppInstallations');
        if (!$installations || !is_array($installations)) {
            return;
        }

        try {
            foreach ($installations as $installation) {
                $this->runningLog = 'Applying whitelabel settings on domain: ' . $domain->getName();
                Modules_Microweber_WhiteLabelBranding::applyToInstallation($domain, $installation['appInstallation']);
            }
        } catch (Exception $e) {
            // Broken domain permissions
            $this->runningLog = $e->getMessage() . ' - Applying whitelabel settings on domain: ' . $domain->getName();
        }
    }


    public function statusMessage()
    {
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
                return $this->runningLog;
            case static::STATUS_DONE:
                return 'Whitelabel settings are applied on all domains.';
            case static::STATUS_ERROR:
                return 'Error when applying whitelabel settings on domains.';
            case static::STATUS_NOT_STARTED:
                return pm_Locale::lmsg('taskPingError', [
                    'id' => $this->getId()
                ]);
        }

    }

}
