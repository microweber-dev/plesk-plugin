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
	
	public static function getExtensionVarPath()
	{
		return trim(pm_Context::getVarDir());
	}
	
	public static function getAppSharedPath() 
	{
		return self::getExtensionVarPath() . 'latest/';
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

		$languagesPath = self::getAppSharedPath() . 'src/MicroweberPackages/Translation/resources/lang';

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

        $firstLangs = [
            'en_us'=>'en_US'
        ];

        asort($languages);

        $languages = array_merge($firstLangs, $languages);
		
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
		return Modules_Microweber_Helper::getJsonFromUrl(self::getWhmcsUrl() . '/index.php?m=microweber_addon&function=get_package_manager_urls');
	}

    public static function getRelease()
    {
        if (pm_Settings::get('update_app_channel') == 'dev') {
            $dev = 'dev'; //$dev = 'laravel9-php8';
            return [
                'version'=>'Latest development version',
                'composer_url'=>'http://updater.microweberapi.com/builds/'.$dev.'/composer.json',
                'version_url'=>'http://updater.microweberapi.com/builds/'.$dev.'/version.txt',
                'url'=>'http://updater.microweberapi.com/builds/'.$dev.'/microweber.zip'
            ];
        }

        return [
            'version'=>'Latest production version',
            'composer_url'=>'http://updater.microweberapi.com/builds/master/composer.json',
            'version_url'=>'http://updater.microweberapi.com/builds/master/version.txt',
            'url'=>'http://updater.microweberapi.com/builds/master/microweber.zip'
        ];
    }
}
