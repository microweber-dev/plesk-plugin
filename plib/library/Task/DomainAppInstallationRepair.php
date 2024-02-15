<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Task_DomainAppInstallationRepair extends \pm_LongTask_Task
{
    public $trackProgress = true;

    public function run() {

        if (empty($this->getParam('domainId')) || empty($this->getParam('domainDocumentRoot'))) {
            return;
        }

        $domain = Modules_Microweber_Domain::getUserDomainById($this->getParam('domainId'));

        if (!$domain->hasHosting()) {
            return;
        }

        $this->updateProgress(10);

        Modules_Microweber_Reinstall::run($domain->getId(), $this->getParam('domainDocumentRoot'));

        $this->updateProgress(100);
    }

    public function statusMessage()
    {
        $domain = Modules_Microweber_Domain::getUserDomainById($this->getParam('domainId'));

        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
                return 'Reinstalling ' . Modules_Microweber_WhiteLabel::getBrandName() . ' installation for domain ' . $domain->getName();
            case static::STATUS_DONE:
                return 'You can login to ' . $domain->getName();
            case static::STATUS_ERROR:
                return 'Error reinstalling ' . Modules_Microweber_WhiteLabel::getBrandName() . ' installation on domain ' . $domain->getName();
            case static::STATUS_NOT_STARTED:
                return pm_Locale::lmsg('taskPingError', [
                    'id' => $this->getId()
                ]);
        }

        return '';
    }

}
