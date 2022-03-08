<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Helper
{
	public static function getRandomPassword($length = 16, $complex = false)
	{
		$alphabet = 'ghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		if ($complex) {
			$alphabet_complex = '!@#$%^&*?_~';
		}

		$pass = [];
		$alphaLength = strlen($alphabet) - 1;
		for ($i = 0; $i < $length; $i ++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}

        if ($complex) {
            $alphaLength = strlen($alphabet_complex) - 1;
            for ($i = 0; $i < $length; $i ++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet_complex[$n];
            }

            shuffle($pass);
        }

		return implode($pass);
	}

    public static function getContentFromUrl($url, $postfields = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if (!empty($postfields)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data = curl_exec($ch);

        curl_close($ch);

        return $data;
    }
	
	public static function getJsonFromUrl($url, $postfields = [])
	{
		$data = Modules_Microweber_Helper::getContentFromUrl($url, $postfields);
		
		return @json_decode($data, true);
	}
	
	public static function getFileExtension($path)
	{
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		
		return $ext;
	}

    public static function canIUpdateNewVersionOfApp()
    {
        $mwRelease = Modules_Microweber_Config::getRelease();
        $mwReleaseComposer = Modules_Microweber_Helper::getJsonFromUrl($mwRelease['composer_url']);
        $mwReleasePhpVersion = false;
        if (isset($mwReleaseComposer['require']['php'])) {
            $mwReleasePhpVersion = $mwReleaseComposer['require']['php'];
            $mwReleasePhpVersion = str_replace('<',false, $mwReleasePhpVersion);
            $mwReleasePhpVersion = str_replace('>',false, $mwReleasePhpVersion);
            $mwReleasePhpVersion = str_replace('=',false, $mwReleasePhpVersion);
        }

        $mwReleaseVersion = Modules_Microweber_Helper::getContentFromUrl($mwRelease['version_url']);

        $updateApp = true;
        $appSharedPath = Modules_Microweber_Config::getAppSharedPath();
        $appSharedPathVersion = $appSharedPath . 'version.txt';
        $sfm = new pm_ServerFileManager();
        if ($sfm->fileExists($appSharedPathVersion)) {
            $appSharedPathVersion = $sfm->fileGetContents($appSharedPathVersion);
            if ($appSharedPathVersion == $mwReleaseVersion) {
                // $updateApp = false;
            }
        }

        $outdatedDomains = [];
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {

            if (!$domain->hasHosting()) {
                continue;
            }

            $domainInstallations = $domain->getSetting('mwAppInstallations');
            $domainInstallations = json_decode($domainInstallations, true);
            if (empty($domainInstallations)) {
                continue;
            }

            $installationType = $domainInstallations[key($domainInstallations)]['installationType'];
            if ($installationType != 'Symlinked') {
                continue;
            }

            $hostingManager = new Modules_Microweber_HostingManager();
            $hostingManager->setDomainId($domain->getId());
            $hostingProperties = $hostingManager->getHostingProperties();
            if (!$hostingProperties['php']) {
                // PHP is not activated on selected domain.
                continue;
            }

            $phpHandler = $hostingManager->getPhpHandler($hostingProperties['php_handler_id']);
            if (version_compare($phpHandler['version'], $mwReleasePhpVersion, '<')) {
                // $error = 'PHP version ' . $phpHandler['version'] . ' is not supported by Microweber. You must install PHP '.$mwReleasePhpVersion.'.';
                $outdatedDomains[] = $domain->getName();
            }
        }
        if (!empty($outdatedDomains)) {
            $updateApp = false;
        }

        return ['update_app'=>$updateApp, 'outdated_domains'=>$outdatedDomains];
    }
}