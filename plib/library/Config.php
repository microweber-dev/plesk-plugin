<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.

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
		
		try {
			$sfm = new pm_ServerFileManager();
			$listDir = $sfm->scandir(self::getAppSharedPath() . 'userfiles/templates/', true);
			foreach ($listDir as $file) {
				$upperText = $file;
				$upperText = ucfirst($upperText);
				$templates[trim($file)] = $upperText;
			}
		} catch (Exception $e) {
			// Cant get supported languages
			$templates['Default'] = 'Default';
		}
		
		return $templates;
		
	}
	
	public static function getSupportedLanguages()
	{
		
		$languages = [];
		
		try {
			$sfm = new pm_ServerFileManager();
			$listDir = $sfm->scandir(self::getAppSharedPath() . 'userfiles/modules/microweber/language', true);
			
			foreach ($listDir as $file) {
				$ext = Modules_Microweber_Helper::getFileExtension($file);
				if ($ext == 'json') {
					
					$upperText = $file;
					$upperText = str_replace('.json', false, $file);
					$upperText = strtoupper($upperText);
					
					$languages[trim(strtolower($upperText))] = $upperText;
				}
			}
		} catch (Exception $e) {
			// Cant get supported languages
			$languages['en'] = 'EN';
		}
		
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
