<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Task_TemplatesDownload extends \pm_LongTask_Task
{
    const UID = 'templatesDownload';
    public $runningLog = '';
	public $trackProgress = true;

	public function run()
	{
        $this->runningLog = 'Downloading '.Modules_Microweber_WhiteLabel::getBrandName().' templates...';

        $templateVersions = pm_Settings::get('mw_templates_versions');
        if (!empty($templateVersions)) {
            $templateVersions = json_decode($templateVersions, true);
        } else {
            $templateVersions = [];
        }

        $getTemplatesUrls = $this->getTemplatesUrl();
        if (empty($getTemplatesUrls)) {
            return;
        }

        $updateProgress = 1;
        $this->updateProgress($updateProgress);

        $batchSize = (int) ceil(sizeof($getTemplatesUrls) / 100);
        $templatesBatch = array_chunk($getTemplatesUrls, $batchSize);

        foreach ($templatesBatch as $templatesUrls) {
            foreach ($templatesUrls as $templateData) {

                if (empty($templateData['download_url']) || empty($templateData['target_dir'])) {
                    continue;
                }

                $templateTargetDir = $templateData['target_dir'];
                $templateRequiredVersion = $templateData['version'];

                $this->runningLog = 'Downloading template: ' . $templateTargetDir . ' ...';

                $localTemplatePath = Modules_Microweber_Config::getAppSharedPath() . '/userfiles/templates/' . $templateTargetDir . '/';

                $unzip = pm_ApiCli::callSbin('unzip_app_template.sh', [
                    base64_encode($templateData['download_url']),
                    $localTemplatePath
                ])['code'];
                if ($unzip == 0) {
                    // Update to required version
                    $templateVersions[$templateTargetDir] = $templateRequiredVersion;
                    pm_Settings::set('mw_templates_versions', json_encode($templateVersions));
                }

                $this->runningLog = 'Unzipping template: ' . $templateTargetDir . ' ...';
            }

            $updateProgress++;
            $this->updateProgress($updateProgress);
        }

        $this->updateProgress(100);

        $taskManager = new pm_LongTask_Manager();

        Modules_Microweber_Helper::stopTasks(['task_domainreinstall']);

        $task = new Modules_Microweber_Task_DomainReinstall();
        $taskManager->start($task, NULL);
	}

    public function getTemplatesUrl()
    {
        $licenses = [];

        $whiteLabelKey =  pm_Settings::get('wl_key');
        if (!empty($whiteLabelKey)) {
            $licenses[] = [
                'rel_type'=>'plesk-ext',
                'local_key'=>$whiteLabelKey,
            ];
        }

        $pmLicense = pm_License::getAdditionalKey('microweber');
        if (!empty($pmLicense)) {
            $pmLicense = json_encode($pmLicense->getProperties('product'));
            $licenses[] = 'plesk|' . base64_encode($pmLicense);
        }

        $connector = new Modules_Microweber_MarketplaceConnector();
        
        if (pm_Settings::get('use_package_manager_urls_from_website_manager') == 'yes') {
            if (pm_Settings::get('website_manager') == 'whmcs') {
                $connector->set_whmcs_url(Modules_Microweber_Config::getWhmcsUrl());
            } elseif (pm_Settings::get('website_manager') == 'microweber_saas') {
                $connector->package_urls = Modules_Microweber_Config::getMicroweberSaasPackageManagerUrls();
            }
        }

        $connector->set_license($licenses);

        $templatesUrl = $connector->get_templates_download_urls();

        return $templatesUrl;
    }

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return $this->runningLog;
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

	
}