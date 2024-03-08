<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Task_UpdateDomainsPhpHandler extends \pm_LongTask_Task
{
    public $runningLog = 'Updating php version on websites.';
    public $trackProgress = true;

    public function run()
    {
        $updateApp = $this->getParam('update_app');
        $domainIds = $this->getParam('domain_ids');
        $phpHandlerId = $this->getParam('php_handler_id');

        $iProgress = 0;
        if (!empty($domainIds)) {
            foreach ($domainIds as $domainId) {

                $domain = new pm_Domain($domainId);

                Modules_Microweber_Domain::updatePhpHandler($domainId, $phpHandlerId);
                $this->runningLog = 'Updating php version on ' . $domain->getName();

                $iProgress++;
                $this->updateProgress($iProgress);
            }
        }

        if ($updateApp) {

            Modules_Microweber_Helper::stopTasks(['task_appversioncheck']);

            $taskManager = new pm_LongTask_Manager();
            $task = new Modules_Microweber_Task_AppVersionCheck();
            $taskManager->start($task, NULL);
        }

        $this->updateProgress(100);

        return true;
    }

    public function statusMessage()
    {
        switch ($this->getStatus()) {

            case static::STATUS_RUNNING:
                return $this->runningLog;

            case static::STATUS_NOT_STARTED:
                return pm_Locale::lmsg('taskPingError', [
                    'id' => $this->getId()
                ]);
        }

    }

}
