<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */


class Modules_Microweber_Task_AppVersionCheck extends \pm_LongTask_Task
{
    const UID = 'appVersionCheck';
    public $hidden = false;
	public $trackProgress = true;
    public $statusErrorMessage = 'App version not supported';

	public function run()
	{
		$this->updateProgress(10);

        if (!Modules_Microweber_Helper::isAvailableDiskSpace()) {
            throw new pm_Exception('No disk space available on the server. Can\'t download the app.');
        }

        $taskManager = new pm_LongTask_Manager();

        // Update app
        $status = Modules_Microweber_Helper::canIUpdateNewVersionOfApp();

        $this->updateProgress(30);

        if ($status['update_app']) {
            $mwRelease = Modules_Microweber_Config::getRelease();
            if (!empty($mwRelease)) {

                Modules_Microweber_Helper::stopTasks(['task_appdownload']);

                $task = new Modules_Microweber_Task_AppDownload();
                $taskManager->start($task, NULL);
            }
        } else {
            pm_Settings::set('show_php_version_wizard', true);
            $msg = 'There are domains with old php versions that prevent updating.';
            $msg .= ' ' . implode(', ', $status['outdated_domains']);
            throw new pm_Exception($msg);
        }

        $this->updateProgress(100);

	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Checking server compatibility with new version...';
			case static::STATUS_DONE:
				return 'Checking is complete!';
			case static::STATUS_ERROR:
				return $this->statusErrorMessage;
			case static::STATUS_NOT_STARTED:
				return pm_Locale::lmsg('taskPingError', [
					'id' => $this->getId()
				]);
		}

	}


}
