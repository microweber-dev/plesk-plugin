<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.

class Modules_Microweber_Config
{

	public static function getAppLatestVersionFolder()
	{
		return '/usr/share/microweber/latest';
	}
	
	public static function getSupportedLanguages()
	{
		
		$languages = array();
		
		$sfm = new pm_ServerFileManager();
		$listDir = $sfm->scandir(self::getAppLatestVersionFolder() . '/userfiles/modules/microweber/language', true);
		
		foreach ($listDir as $file) {
			$ext = Modules_Microweber_Helper::getFileExtension($file);
			if ($ext == 'json') {
				
				$upperText = $file;
				$upperText = str_replace('.json', false, $file);
				$upperText = strtoupper($upperText);
				
				$languages[trim(strtolower($upperText))] = $upperText;
			}
		}
		
		return $languages;
		
	}

	public static function getPlanItems()
	{
		return [
			'microweber' => 'Install Microweber',
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
		$updateWhmcsUrl = pm_Settings::get('whmcs_url');

		if (empty($updateWhmcsUrl)) {
			return 'https://members.microweber.com/';
		}

		return $updateWhmcsUrl;
	}
	
	public static function getWhmcsPackageManagerUrls()
	{
		$whmcsUrl = self::getWhmcsUrl();
		
		return Modules_Microweber_Helper::getJsonFromUrl($whmcsUrl . '/index.php?m=microweber_addon&function=get_package_manager_urls');
	}
	
}