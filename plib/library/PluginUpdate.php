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
        $pluginZipPath = $latestPluginPath . '/master.zip';

        $manager = new pm_ServerFileManager();
        if (!$manager->isDir($latestPluginPath)) {
            $manager->mkdir($latestPluginPath);
        }

        $url = self::getDownloadUrl();
        $downloadStatus = self::_downloadZipFile($url, $pluginZipPath);

        var_dump($downloadStatus,$pluginZipPath);
    }

    private static function _downloadZipFile($url, $filePath) {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $rawFileData = curl_exec($ch);

        if(curl_errno($ch)){
            echo 'generated error:' . curl_error($ch);
        }
        curl_close($ch);

        $manager = new pm_ServerFileManager();
        $manager->filePutContents($filePath, $rawFileData);

        return (filesize($filePath) > 0)? true : false;
    }
}
