<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_TaskTemplatesDownload extends \pm_LongTask_Task
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

        $templatesUrls = $this->getParam('templatesUrls');
        if (empty($templatesUrls)) {
            return;
        }

        $updateProgress = 1;
        $this->updateProgress($updateProgress);

        foreach ($templatesUrls as $templateData) {

            if (empty($templateData['downloadUrl']) || empty($templateData['targetDir'])) {
                continue;
            }

            $templateTargetDir = $templateData['targetDir'];
            $templateRequiredVersion = $templateData['version'];

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
                    base64_encode($templateData['downloadUrl']),
                    $localTemplatePath
                ])['code'];
                if ($unzip == 0) {
                    // Update to required version
                    $templateVersions[$templateTargetDir] = $templateRequiredVersion;
                    pm_Settings::set('mw_templates_versions', json_encode($templateVersions));
                }
            }

            $updateProgress++;
            $this->updateProgress($updateProgress);
        }

        $this->updateProgress(100);
	}

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return 'Downloading '.Modules_Microweber_WhiteLabel::getBrandName().' templates...';
			case static::STATUS_DONE:
				return Modules_Microweber_WhiteLabel::getBrandName().' templates is up to date.';
			case static::STATUS_ERROR:
				return 'Error downloading '.Modules_Microweber_WhiteLabel::getBrandName().' templates.';
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