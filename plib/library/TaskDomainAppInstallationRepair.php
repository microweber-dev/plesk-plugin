<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskDomainAppInstallationRepair extends \pm_LongTask_Task
{
    public $trackProgress = true;
    public $domain;

    public function run()
    {
        $this->updateProgress(10);

        if (empty($this->getParam('domainId')) || empty($this->getParam('domainDocumentRoot'))) {
            return;
        }

        $this->domain = Modules_Microweber_Domain::getUserDomainById($this->getParam('domainId'));

        if (!$this->domain->hasHosting()) {
            return;
        }

        Modules_Microweber_Reinstall::run($this->domain->getId(), $this->getParam('domainDocumentRoot'));

        $this->updateProgress(100);
    }

    public function statusMessage()
    {
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
                return 'Reinstalling ' . Modules_Microweber_WhiteLabel::getBrandName() . ' installation for domain ' . $this->domain->getName();
            case static::STATUS_DONE:
                return 'You can login to ' . $this->domain->getName();
            case static::STATUS_ERROR:
                return 'Error reinstalling ' . Modules_Microweber_WhiteLabel::getBrandName() . ' installation on domain ' . $this->domain->getName();
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