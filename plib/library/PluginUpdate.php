<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_PluginUpdate
{
    public static function getDownloadUrl()
    {
        return "https://github.com/microweber-dev/plesk-plugin/archive/refs/heads/master.zip";
    }

    public static function downloadPlugin()
    {
        $extPath = Modules_Microweber_Config::getExtensionVarPath();
        $latestPluginPath = $extPath . 'latest-plugin';

        $manager = new pm_ServerFileManager();
        if (!$manager->isDir($latestPluginPath)) {
            $manager->mkdir($latestPluginPath);
        }

        var_dump($latestPluginPath);

        $url = self::getDownloadUrl();
        $downloadStatus = self::_downloadZipFile($url, $latestPluginPath);

        var_dump($downloadStatus);

    }

    private static function _downloadZipFile($url, $filePath) {

        $unzip = pm_ApiCli::callSbin('unzip_app_module.sh', [
            base64_encode($url),
            $filePath
        ]);

        if ($unzip['code'] == 0) {
            return true;
        }

        return false;
    }
}
