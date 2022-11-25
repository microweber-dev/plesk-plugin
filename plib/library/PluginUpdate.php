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
        $latestPluginUnzipedPath = $latestPluginPath . '/unzipped';
        $pluginZipPath = $latestPluginPath . '/master.zip';

        $manager = new pm_ServerFileManager();
        if (!$manager->isDir($latestPluginPath)) {
            $manager->mkdir($latestPluginPath);
        }

        $url = self::getDownloadUrl();
        $downloadStatus = self::_downloadZipFile($url, $pluginZipPath);


    }

    private static function _downloadZipFile($url, $filePath) {



    }
}
