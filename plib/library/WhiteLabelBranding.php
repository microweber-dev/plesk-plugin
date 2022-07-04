<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_WhiteLabelBranding
{
    public static function removeFromInstallation($domain, $appInstallation) {

        if (empty($domain)) {
            throw new Exception('Please, set domain object');
        }

        if (empty($appInstallation)) {
            throw new Exception('Please, set app installation');
        }

        $fileManager = new pm_FileManager($domain->getId());

        if ($fileManager->fileExists($appInstallation . '/config/microweber.php')) {
            $fileManager->removeFile($appInstallation . '/storage/branding.json');
        }
    }

    public static function applyToInstallation($domain, $appInstallation)
    {
        if (empty($domain)) {
            throw new Exception('Please, set domain object');
        }

        if (empty($appInstallation)) {
            throw new Exception('Please, set app installation');
        }

        $fileManager = new pm_FileManager($domain->getId());

        if ($fileManager->fileExists($appInstallation . '/config/microweber.php')) {

            $whitelabelSettings = [];
            $currentBranding = $appInstallation . '/storage/branding.json';
            if ($fileManager->fileExists($currentBranding)) {
                $currentBranding = $fileManager->fileGetContents($currentBranding);
                $currentBranding = json_decode($currentBranding, true);
                if (is_array($currentBranding)) {
                    $whitelabelSettings = $currentBranding;
                }
            }

            $whiteLabelJson = Modules_Microweber_WhiteLabel::getWhiteLabelJson($domain);
            $whiteLabelJson = json_decode($whiteLabelJson, true);
            if (!empty($whiteLabelJson)) {
                foreach ($whiteLabelJson as $key => $setting) {
                    $whitelabelSettings[$key] = $setting;
                }
            }
            $whitelabelSettingsEncoded = json_encode($whitelabelSettings);
            $fileManager->filePutContents($appInstallation . '/storage/branding.json', $whitelabelSettingsEncoded);
        }
    }
}