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

        if (!$domain->hasHosting()) {
            return;
        }
		
		$fileManager = new \pm_FileManager($domain->getId());
        
        if (!is_link($appInstallationPath . '/vendor')) {
            // this is standalone website
            return;
        }

		if (!$fileManager->isDir($appInstallationPath)) {
			// Dir not exists
			return;
		}
		
		if (!$fileManager->fileExists($appInstallationPath . '/config/microweber.php')) {
			// This is not microweber installation
			return;
        }

        $appLatestVersionFolder = Modules_Microweber_Config::getAppSharedPath();

        // Repair domain permission
        // pm_ApiCli::callSbin('repair_domain_permissions.sh', [$domain->getName()], pm_ApiCli::RESULT_FULL);

		// Delete files
		foreach (self::_getFilesForDelete() as $deleteDirOrFile) {

            // Delete domain file
            pm_ApiCli::callSbin('filemng', [
                $domain->getSysUserLogin(),
                'exec',
                $domain->getDocumentRoot(),
                'rm',
                '-rf',
                $deleteDirOrFile

            ], pm_ApiCli::RESULT_FULL);
			
		}
		
		// Make dirs
		foreach (self::_getDirsToMake() as $dir) {
        	$fileManager->mkdir($appInstallationPath . '/' . $dir, '0755', true);
        }
		
        // Recopy files
        foreach (self::_getDirsOrFilesToRecopy() as $dirOrFilesToCopy) {

			$scriptDirOrFile = $appLatestVersionFolder . $dirOrFilesToCopy;
        	$domainDirOrFile = $appInstallationPath .'/'. $dirOrFilesToCopy;

            // Delete domain file
            pm_ApiCli::callSbin('filemng', [
                $domain->getSysUserLogin(),
                'exec',
                $domain->getDocumentRoot(),
                'rm',
                '-rf',
                $dirOrFilesToCopy

            ], pm_ApiCli::RESULT_FULL);
			
			try {
				$fileManager->copyFile($scriptDirOrFile, $domainDirOrFile);
			} catch (Exception $e) {
				//
			}
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
    	$files[] = 'resources';
    	$files[] = 'database';
    	$files[] = 'userfiles/elements';
		
        $sfm = new \pm_ServerFileManager();
        $listTemplates = $sfm->scanDir($appLatestFolder . '/userfiles/templates');
        if (!empty($listTemplates)) {
            foreach ($listTemplates as $template) {
                if ($template == '.' || $template == '..') {
                    continue;
                }
                $files[] = 'userfiles/templates/' . $template;
            }
        }

        $listModules = $sfm->scanDir($appLatestFolder . '/userfiles/modules');
        if (!empty($listModules)) {
            foreach ($listModules as $module) {
                if ($module == '.' || $module == '..') {
                    continue;
                }
                $files[] = 'userfiles/modules/' . $module;
            }
        }
		
        return $files; 
    }
	
	private static function _getFilesForDelete()
	{
		$files = [];
		$files[] = 'bootstrap';
		$files[] = 'vendor';
		$files[] = 'src';
		$files[] = 'resources';
		$files[] = 'database';

        return $files;
	}

	private static function _getDirsToMake() {

    	$dirs = [];
    	
    	// Storage dirs
    	$dirs[] = 'storage';
    	$dirs[] = 'storage/framework';
    	$dirs[] = 'storage/framework/sessions';
    	$dirs[] = 'storage/framework/views';
    	$dirs[] = 'storage/cache';
    	$dirs[] = 'storage/logs';
    	$dirs[] = 'storage/app';
    	
    	// User files dirs
    	$dirs[] = 'userfiles/media';
    	$dirs[] = 'userfiles/modules';
    	$dirs[] = 'userfiles/templates';
    	
    	return $dirs;
    }

    private static function _getDirsOrFilesToRecopy()
    {
        $files = [];
		$files[] = 'index.php';
		$files[] = 'composer.json';
		$files[] = 'bootstrap'; 
		$files[] = 'config/cors.php';


        return $files;
    }
}