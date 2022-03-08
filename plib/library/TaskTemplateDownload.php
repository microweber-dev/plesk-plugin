<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskTemplateDownload extends \pm_LongTask_Task
{

    public $hidden = true;
	public $trackProgress = true;

	public function run()
	{
        $templateVersions = pm_Settings::get('mw_templates_versions');
        if (!empty($templateVersions)) {
            $templateVersions = json_decode($templateVersions, true);
        } else {
            $templateVersions = [];
        }

		$this->updateProgress(10);
		
		if (!empty($this->getParam('downloadUrl')) && !empty($this->getParam('targetDir'))) {
		
			$this->updateProgress(30);
			$this->updateProgress(40);
			$this->updateProgress(60);

            $templateTargetDir = $this->getParam('targetDir');
            $templateRequiredVersion = $this->getParam('version');

            $localTemplatePath = Modules_Microweber_Config::getAppSharedPath() . '/userfiles/templates/' . $templateTargetDir . '/';
            $localTemplateVersion = 0;
            if (isset($templateVersions[$templateTargetDir])) {
                $localTemplateVersion = $templateVersions[$templateTargetDir];
            }

            $updateTemplate = false;
            if ($localTemplateVersion != $templateRequiredVersion) {
                $updateTemplate = true;
            }

            $sfm = new pm_ServerFileManager();
            if (!$sfm->fileExists($localTemplatePath)) {
                $updateTemplate = true;
            }

            if ($updateTemplate) {
                $unzip = pm_ApiCli::callSbin('unzip_app_template.sh', [
                    base64_encode($this->getParam('downloadUrl')),
                    $localTemplatePath
                ])['code'];
                if ($unzip == 0) {
                    // Update to required version
                    $templateVersions[$templateTargetDir] = $templateRequiredVersion;
                    pm_Settings::set('mw_templates_versions', json_encode($templateVersions));
                }
            }
			
		}
		
		$this->updateProgress(100);
	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Installing '.Modules_Microweber_WhiteLabel::getBrandName().' '.$this->getParam('targetDir').' template...';
			case static::STATUS_DONE:
				return Modules_Microweber_WhiteLabel::getBrandName().' '.$this->getParam('targetDir').' template is updated successfully.';
			case static::STATUS_ERROR:
				return 'Error installing '.Modules_Microweber_WhiteLabel::getBrandName().' '.$this->getParam('targetDir').' template.';
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