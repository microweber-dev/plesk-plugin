<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Reinstall
{

    public static function run($domainId, $appInstallationPath)
    {
        $domain = Modules_Microweber_Domain::getUserDomainById($domainId);
        if (empty($domain->getName())) {
            throw new \Exception($domain->getName() . ' domain not found.');
        }

        $appLatestVersionFolder = Modules_Microweber_Config::getAppSharedPath();

        // Repair domain permission
        pm_ApiCli::callSbin('repair_domain_permissions.sh', [$domain->getName()], pm_ApiCli::RESULT_FULL);

        $fileManager = new \pm_FileManager($domain->getId());

        // First we will make a directories
        foreach (self::_getDirsToMake() as $dir) {

            // Delete domain file
            $deleteFileOrPath = pm_ApiCli::callSbin('filemng', [
                $domain->getSysUserLogin(),
                'exec',
                $domain->getDocumentRoot(),
                'rm',
                '-rf',
                $dir

            ], pm_ApiCli::RESULT_FULL);

            $fileManager->mkdir($appInstallationPath . '/' . $dir, '0755', true);
        }

        foreach (self::_getFilesForSymlinking($appLatestVersionFolder) as $folder) {

            $scriptDirOrFile = $appLatestVersionFolder . $folder;
            $domainDirOrFile = $appInstallationPath . '/' . $folder;

            // Delete domain file
            $deleteFileOrPath = pm_ApiCli::callSbin('filemng', [
                $domain->getSysUserLogin(),
                'exec',
                $domain->getDocumentRoot(),
                'rm',
                '-rf',
                $domainDirOrFile

            ], pm_ApiCli::RESULT_FULL);

            // Create symlink
            pm_ApiCli::callSbin('filemng', [
                $domain->getSysUserLogin(),
                'exec',
                $domain->getDocumentRoot(),
                'ln',
                '-s',
                $scriptDirOrFile,
                $domainDirOrFile

            ], pm_ApiCli::RESULT_FULL);

        }
    }

    private static function _getFilesForSymlinking($appLatestFolder) {

        $files = [];
        $files[] = 'version.txt';
        $files[] = 'vendor';
        $files[] = 'src';

        $sfm = new \pm_ServerFileManager();
        $listTemplates = $sfm->scanDir($appLatestFolder . '/userfiles/templates');
        if (!empty($listTemplates)) {
            foreach ($listTemplates as $template) {
                if ($template == '.' || $template == '..') {
                    continue;
                }
                $files[] = '/userfiles/templates/' . $template;
            }
        }

        $listModules = $sfm->scanDir($appLatestFolder . '/userfiles/modules');
        if (!empty($listModules)) {
            foreach ($listModules as $module) {
                if ($module == '.' || $module == '..') {
                    continue;
                }
                $files[] = '/userfiles/modules/' . $module;
            }
        }

        return $files;
    }

    private static function _getDirsToMake()
    {
        $dirs = [];

        // Storage dirs
        $dirs[] = 'storage/cache';

        // User files dirs
        $dirs[] = 'userfiles/modules';
        $dirs[] = 'userfiles/templates';

        return $dirs;
    }
}