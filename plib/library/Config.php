<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Config
{
	public static function getSbinVarPath() {

		$plib = trim(pm_Context::getPlibDir());
		$sbin = str_replace('plib', 'sbin', $plib); 
		
		return $sbin;
	}
	
	public static function getModuleVarPath() 
	{
		return trim(pm_Context::getVarDir());
	}
	
	public static function getAppSharedPath() 
	{
		return self::getModuleVarPath() . 'latest/';
	}
	
	public static function getSupportedTemplates()
	{
		$templates = [];
        $templatesPath = self::getAppSharedPath() . 'userfiles/templates/';

        $sfm = new pm_ServerFileManager();
        if ($sfm->fileExists($templatesPath)) {
            $listDir = $sfm->scandir($templatesPath, true);
            foreach ($listDir as $file) {
                $upperText = $file;
                $upperText = ucfirst($upperText);
                $templates[trim($file)] = $upperText;
            }
        } else {
            $templates['Default'] = 'Default';
        }

        asort($templates);
		
		return $templates;
	}
	
	public static function getSupportedLanguages()
	{
		
		$languages = [];

		$languagesPath = self::getAppSharedPath() . 'userfiles/modules/microweber/language';

        $sfm = new pm_ServerFileManager();
        if ($sfm->fileExists($languagesPath)) {
            $listDir = $sfm->scandir($languagesPath, true);
            foreach ($listDir as $file) {
                $ext = Modules_Microweber_Helper::getFileExtension($file);
                if ($ext == 'json') {

                    $upperText = str_replace('.json', false, $file);
                    $upperText = strtoupper($upperText);

                    $languages[trim(strtolower($upperText))] = $upperText;
                }
            }
        } else {
			$languages['en'] = 'EN';
		}

        asort($languages);
		
		return $languages;
		
	}

	public static function getPlanItems()
	{
		return [
			'microweber' => 'Install Microweber',
			'microweber_without_shop' => 'Install Microweber Without Shop',
			'microweber_lite' => 'Install Microweber Lite'
		];
	}

	public static function getUpdateAppUrl()
	{
		$updateAppUrl = pm_Settings::get('update_app_url');

		if (empty($updateAppUrl)) {
			return 'https://update.microweberapi.com/';
		}

		return $updateAppUrl;
	}

	public static function getWhmcsUrl()
	{
		return pm_Settings::get('whmcs_url');
	}
	
	public static function getWhmcsPackageManagerUrls()
	{
		$whmcsUrl = self::getWhmcsUrl();
		
		return Modules_Microweber_Helper::getJsonFromUrl($whmcsUrl . '/index.php?m=microweber_addon&function=get_package_manager_urls');
	}

    public static function getRelease()
    {
        $licenseKey = '';

        $pmLicense = pm_License::getAdditionalKey();
        if ($pmLicense && isset($pmLicense->getProperties('product')['name'])) {
            $licenseKey = $pmLicense->getProperties('product')['number'];
        }

        if (pm_Settings::get('update_app_channel') == 'dev') {

            return [
                'version'=>'Latest development version',
                'url'=>'http://updater.microweberapi.com/microweber-dev.zip'
            ];

        } else {
            $releaseUrl = Modules_Microweber_Config::getUpdateAppUrl();
            $releaseUrl .= '?api_function=get_download_link&get_last_version=1&license_key=' . $licenseKey . '&license_type=plesk';

            return Modules_Microweber_Helper::getJsonFromUrl($releaseUrl);
        }
    }
}
