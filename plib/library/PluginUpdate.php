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

        $url = self::getDownloadUrl();
        $downloadStatus = self::_downloadZipFile($url, $latestPluginPath);



        $move = pm_ApiCli::callSbin('move_folder.sh', [
            $latestPluginPath . '/htdocs/',
            pm_Context::getHtdocsDir(),
        ]);


      /*

        $move = pm_ApiCli::callSbin('move_folder.sh', [
            $latestPluginPath . '/plib/',
            pm_Context::getPlibDir(),
        ]);*/

      //  var_dump(pm_Context::getPlibDir());
       // var_dump(pm_Context::getHtdocsDir());

        // htdocs path
        // usr/local/psa/admin/htdocs/modules/microweber

        // plib path
        // usr/local/psa/admin/plib/modules/microweber

        // sbin path
        // /usr/local/psa/admin/sbin/modules/microweber
        // /usr/local/psa/admin/bin/modules/microweber

    }

    private static function _downloadZipFile($url, $filePath) {

        $unzip = pm_ApiCli::callSbin('unzip_plugin.sh', [
            base64_encode($url),
            $filePath
        ]);

        if ($unzip['code'] == 0) {
            return true;
        }

        return false;
    }
}
