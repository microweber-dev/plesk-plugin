<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskAppVersionCheck extends \pm_LongTask_Task
{
    public $hidden = true;
	public $trackProgress = true;

	public function run()
	{
		$this->updateProgress(30);

        $taskManager = new pm_LongTask_Manager();

        // Update app
        $mwRelease = Modules_Microweber_Config::getRelease();
        if (!empty($mwRelease)) {
            $task = new Modules_Microweber_TaskAppDownload();
            $taskManager->start($task, NULL);
        }

        // Update templates
        $templates = $this->_getTemplatesUrl();
        foreach ($templates as $template) {

            $task = new Modules_Microweber_TaskTemplateDownload();
            $task->setParam('downloadUrl', $template['download_url']);
            $task->setParam('targetDir', $template['target_dir']);

            $taskManager->start($task, NULL);
        }

        $this->updateProgress(100);

	}

    private function _getTemplatesUrl()
    {
        $licenses = [];

        $whiteLabelKey =  pm_Settings::get('wl_key');;
        if (!empty($whiteLabelKey)) {
            $licenses[] = $whiteLabelKey;
        }

        $pmLicense = pm_License::getAdditionalKey('microweber');
        if (!empty($pmLicense)) {
            $pmLicense = json_encode($pmLicense->getProperties('product'));
            $licenses[] = 'plesk|' . base64_encode($pmLicense);
        }

        $connector = new MicroweberMarketplaceConnector();
        $connector->set_whmcs_url(Modules_Microweber_Config::getWhmcsUrl());
        $connector->set_license($licenses);

        $templatesUrl = $connector->get_templates_download_urls();

        return $templatesUrl;
    }

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Check versions...';
			case static::STATUS_DONE:
				return 'done!';
			case static::STATUS_ERROR:
				return 'Error when checking version';
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