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

        sleep(3);

        if (!Modules_Microweber_Helper::isAvailableDiskSpace()) {
            throw new pm_Exception('No disk space available on the server. Can\'t download the app.');
        }

        sleep(3);

        $taskManager = new pm_LongTask_Manager();

        // Update app
        $status = Modules_Microweber_Helper::canIUpdateNewVersionOfApp();

        sleep(3);

        $this->updateProgress(30);

        if ($status['update_app']) {
            $mwRelease = Modules_Microweber_Config::getRelease();
            sleep(3);
            if (!empty($mwRelease)) {

                Modules_Microweber_Helper::stopTasks(['task_appdownload']);

//                $task = new Modules_Microweber_Task_AppDownload();
//                $taskManager->start($task, NULL);

                sleep(3);
            }
        } else {
            pm_Settings::set('show_php_version_wizard', true);
            $msg = 'There are domains with old php versions that prevent updating.';
            $msg .= ' ' . implode(', ', $status['outdated_domains']);
            throw new pm_Exception($msg);
        }


        sleep(3);

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

    public function getSteps()
    {
        $steps = [
            'isAvailableDiskSpace' => [
                'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                'title' => 'Checking available disk space',
                'progressStatus' => 'Processed 10 of 100 items',
                'progress' => 10,
            ],
            'canIUpdateNewVersionOfApp' => [
                'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                'title' => 'Checking compatability with new version of app',
                'progressStatus' => 'Processed 30 of 100 items',
                'progress' => 30,
            ],
            'updateApp' => [
                'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                'title' => 'Updating app',
                'progressStatus' => 'Processed 100 of 100 items',
                'progress' => 100,
            ],
        ];
        $showSteps = [];

        if ($this->getProgress() == 10) {
            $showSteps[] = $steps['isAvailableDiskSpace'];
        } elseif ($this->getProgress() == 30) {
            $showSteps[] = $steps['canIUpdateNewVersionOfApp'];
        } elseif ($this->getProgress() == 100) {
            $showSteps[] = $steps['updateApp'];
        }

        return $showSteps;
    }

}
