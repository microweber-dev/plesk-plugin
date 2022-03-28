<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskUpdateDomainsPhpHandler extends \pm_LongTask_Task
{
    public $trackProgress = true;

    public function run()
    {
        $domainIds = $this->getParam('domain_ids');
        $phpHandlerId = $this->getParam('php_handler_id');




        $this->updateProgress(100);

        return $isUpdated;
    }

    public function statusMessage()
    {
        switch ($this->getStatus()) {

            case static::STATUS_RUNNING:

                return 'Updating php version on websites.';

            case static::STATUS_DONE:

                return '';

            case static::STATUS_ERROR:

                return '';

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