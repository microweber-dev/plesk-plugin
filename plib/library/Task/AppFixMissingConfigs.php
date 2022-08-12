<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */


class Modules_Microweber_Task_AppFixMissingConfigs extends \pm_LongTask_Task
{
    const UID = 'appFixMissingConfigs';
    public $hidden = true;
    public $trackProgress = false;
    public $statusErrorMessage = 'Can\'t fix missing configs.';

    public function run()
    {
        $this->updateProgress(10);

        Modules_Microweber_Helper::fixMissingConfigOnDomains();

        $this->updateProgress(100);

    }

    public function statusMessage()
    {
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
                return 'Checking missing configs...';
            case static::STATUS_DONE:
                return 'Config fixing is complete!';
            case static::STATUS_ERROR:
                return $this->statusErrorMessage;
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
