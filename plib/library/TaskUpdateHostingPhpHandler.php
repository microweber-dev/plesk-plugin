<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskUpdateHostingPhpHandler extends \pm_LongTask_Task
{

    public $trackProgress = true;

    public function run()
    {
        $phpHandlerId = $this->getParam('php_handler_id');
        $hostingPlanIds = $this->getParam('hosting_plan_ids');
        $hostingManager = new Modules_Microweber_HostingManager();

        $isUpdated = false;
        if (!empty($hostingPlanIds)) {
            foreach ($hostingPlanIds as $hostingPlanId) {
                $updateStatus = $hostingManager->setServicePlanPhpHandler($hostingPlanId, $phpHandlerId);
                if ($updateStatus) {
                    $updatedHostings[] = true;
                }
            }
        }
        if (!empty($updatedHostings)) {
            $isUpdated = true;
        }

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