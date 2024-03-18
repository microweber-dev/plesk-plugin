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
        $downloadLog = '';

        $this->updateProgress(10);

        $release = Modules_Microweber_Config::getRelease();

        $this->updateProgress(20);

        $appSharedPath = Modules_Microweber_Config::getAppSharedPath();

        if (true) {
            throw new pm_Exception('Some error message');
            $this->updateProgress(100);
            return;
        }

        $downloadLog .= pm_ApiCli::callSbin('unzip_app_version.sh', [base64_encode($release['url']), $appSharedPath])['stdout'];

        $this->updateProgress(30);

        // Download the server modules
        $connector = new Modules_Microweber_MarketplaceConnector();
        $connector->package_urls = ['https://market.microweberapi.com/packages/microweberserverpackages/packages.json'];
        $downloadModuleUrls = $connector->get_modules_download_urls();

        if (!empty($downloadModuleUrls)) {
            foreach ($downloadModuleUrls as $moduleUrl) {
                $modulesPath = Modules_Microweber_Config::getAppSharedPath().'userfiles/modules/'.$moduleUrl['target_dir'];
                pm_ApiCli::callSbin('unzip_app_module.sh', [base64_encode($moduleUrl['download_url']), $modulesPath])['stdout'];
            }
        }

        $this->updateProgress(80);

        Modules_Microweber_WhmcsConnector::updateWhmcsConnector();

        $this->updateProgress(90);

        pm_Settings::set('show_php_version_wizard', false);

        $this->updateProgress(100);

        $taskManager = new pm_LongTask_Manager();

        Modules_Microweber_Helper::stopTasks(['task_templatesdownload']);

        // Update templates
        $task = new Modules_Microweber_Task_TemplatesDownload();
        $taskManager->start($task, NULL);

        // Fix missing configs
        $task = new Modules_Microweber_Task_AppFixMissingConfigs();
        $taskManager->start($task, NULL);
	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Download '.Modules_Microweber_WhiteLabel::getBrandName().' '.$this->getParam('targetDir').' app...';
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
        return [
            'example-step' => [
                'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                'title' => 'Example Processing',
                'progressStatus' => 'Processed 10 of 100 items',
                'progress' => 10,
            ],
            'example-step2' => [
                'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                'title' => 'Example Processing 2',
                'progressStatus' => 'Processed 20 of 100 items',
                'progress' => 20,
            ],
            'example-step3' => [
                'icon' => pm_Context::getBaseUrl() . 'images/icon.png',
                'title' => 'Example Processing 3',
                'progressStatus' => 'Processed 30 of 100 items',
                'progress' => 30,
            ],
        ];

    }


}
