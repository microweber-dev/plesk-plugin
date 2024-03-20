<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Task_AppDownload extends \pm_LongTask_Task
{
    const UID = 'appDownload';
	public $trackProgress = true;
    public $finished = false;

	public function run()
	{

        $this->updateProgress(10);

        if (!Modules_Microweber_Helper::isAvailableDiskSpace()) {
            throw new pm_Exception('No disk space available on the server. Can\'t download the app.');
        }

        // Update app
        $status = Modules_Microweber_Helper::canIUpdateNewVersionOfApp();

        $this->updateProgress(20);

        $canIContinue = false;
        if ($status['update_app']) {
            $mwRelease = Modules_Microweber_Config::getRelease();
            if (!empty($mwRelease)) {
                $canIContinue = true;
            }
        } else {
            pm_Settings::set('show_php_version_wizard', true);
            $msg = 'There are domains with old php versions that prevent updating.';
            $msg .= ' ' . implode(', ', $status['outdated_domains']);
            throw new pm_Exception($msg);
        }

        if (!$canIContinue) {
            throw new pm_Exception('Can\'t continue with the update.');
        }

        $this->updateProgress(30);

        $release = Modules_Microweber_Config::getRelease();

        $this->updateProgress(40);

        $appSharedPath = Modules_Microweber_Config::getAppSharedPath();

        pm_ApiCli::callSbin('unzip_app_version.sh', [
            base64_encode($release['url']),
            $appSharedPath
        ])['stdout'];

        $this->updateProgress(50);

        // Download the server modules
        $connector = new Modules_Microweber_MarketplaceConnector();
        $connector->package_urls = ['https://market.microweberapi.com/packages/microweberserverpackages/packages.json'];
        $downloadModuleUrls = $connector->get_modules_download_urls();

        if (!empty($downloadModuleUrls)) {
            $modulesI = 50;
            foreach ($downloadModuleUrls as $moduleUrl) {
                $modulesPath = Modules_Microweber_Config::getAppSharedPath().'userfiles/modules/'.$moduleUrl['target_dir'];
                pm_ApiCli::callSbin('unzip_app_module.sh', [base64_encode($moduleUrl['download_url']), $modulesPath])['stdout'];

                $modulesI += 5;
                $this->updateProgress($modulesI);
            }
        }

        $this->updateProgress(80);

        Modules_Microweber_WhmcsConnector::updateWhmcsConnector();

        $this->updateProgress(90);

        pm_Settings::set('show_php_version_wizard', false);

        $taskManager = new pm_LongTask_Manager();
        Modules_Microweber_Helper::stopTasks(['task_templatesdownload']);

        // Update templates
        $task = new Modules_Microweber_Task_TemplatesDownload();
        $taskManager->start($task, NULL);

        // Fix missing configs
        $task = new Modules_Microweber_Task_AppFixMissingConfigs();
        $taskManager->start($task, NULL);

        $this->updateProgress(100);
	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Install '.Modules_Microweber_WhiteLabel::getBrandName();
			case static::STATUS_DONE:
                $this->finished = true;
				return Modules_Microweber_WhiteLabel::getBrandName().' '.$this->getParam('targetDir').' app is updated successfully.';
			case static::STATUS_ERROR:
				return 'Error installing '.Modules_Microweber_WhiteLabel::getBrandName().' '.$this->getParam('targetDir').' app.';
			case static::STATUS_NOT_STARTED:
				return pm_Locale::lmsg('taskPingError', [
					'id' => $this->getId()
				]);
		}

	}

    public function getSteps()
    {
        if ($this->getProgress() < 40) {
            $steps = [
                'isAvailableDiskSpace' => [
               //     'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                    'title' => 'Checking available disk space',
                    'progressStatus' => 'Processed 10 of 100 items',
                    'progress' => 10,
                ],
                'canIUpdateNewVersionOfApp' => [
              //      'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                    'title' => 'Checking compatability with new version of ' . Modules_Microweber_WhiteLabel::getBrandName(),
                    'progressStatus' => 'Processed 30 of 100 items',
                    'progress' => 30,
                ]
            ];

            return $steps;
        }

        if ($this->getProgress() == 40) {
            $steps = [];
            $steps['checks'] = [
              //  'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                'title' => 'All checks passed',
                'progressStatus' => 'Processed 4 of 4 checks',
                'progress' => 100,
            ];
            $steps['downloadApp'] = [
              //  'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                'title' => 'Downloading ' . Modules_Microweber_WhiteLabel::getBrandName(),
                'progressStatus' => 'Processed 40 of 100 items',
                'progress' => 40,
            ];
            return $steps;
        }

        if ($this->getProgress() == 100) {
            $steps = [];
            $steps['downloadApp'] = [
             //   'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                'title' => "Latest version of " . Modules_Microweber_WhiteLabel::getBrandName() . ' is installed successfully',
                'progressStatus' => 'Processed 100 of 100 items',
                'progress' => 100,
            ];
            return $steps;
        }

        return [];
    }


}
