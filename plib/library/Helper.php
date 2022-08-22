<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Helper
{
    public static function fixMissingConfigOnDomains()
    {
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {
            if (!$domain->hasHosting()) {
                continue;
            }

            $domainInstallations = Modules_Microweber_Domain::getMwOption($domain, 'mwAppInstallations');
            if (empty($domainInstallations)) {
                continue;
            }

            foreach ($domainInstallations as $installation) {

                if ($installation['installationType'] !== 'Symlinked') {
                    continue;
                }

                $appSharedPath = Modules_Microweber_Config::getAppSharedPath();

                $pleskDomainFileManager = new \MicroweberPackages\SharedServerScripts\FileManager\Adapters\PleskDomainFileManager();
                $pleskDomainFileManager->setDomainId($domain->getId());

                $mwReinstall = new \MicroweberPackages\SharedServerScripts\MicroweberReinstaller();
                $mwReinstall->setFileManager($pleskDomainFileManager);
                $mwReinstall->setPath($installation['appInstallation']);
                $mwReinstall->setSourcePath($appSharedPath);
                $mwReinstall->addMissingConfigFiles();

            }
        }
    }


    public static function showMicroweberButtons()
    {
        // Check app is installed
        $appInstalled = true;
        $currentVersion = Modules_Microweber_Helper::getCurrentVersionOfApp();
        if ($currentVersion == 'unknown') {
            $appInstalled = false;
        }

        // Check hosting plan items
        $hostingPlanItems = false;
        if (!pm_Session::getClient()->isAdmin()) {
            $domains = pm_Domain::getDomainsByClient(pm_Session::getClient());
            foreach ($domains as $domain) {
                if (!$domain->hasHosting()) {
                    continue;
                }
                $planItems = $domain->getPlanItems();
                if (is_array($planItems) && count($planItems) > 0 && (in_array("microweber", $planItems) || in_array("microweber_without_shop", $planItems) || in_array("microweber_lite", $planItems))) {
                    $hostingPlanItems = true;
                    break;
                }
            }
        }

        $showButtons = false;
        if (pm_Session::getClient()->isAdmin()) {
            $showButtons = true;
        }
        if (pm_Session::getClient()->isReseller()) {

            $createClients = false;
            $createDomains = false;

            $hostingManager = new Modules_Microweber_HostingManager();
            $getResellerPermissions = $hostingManager->getResellerPermissions(pm_Session::getClient()->getId());
            if (!empty($getResellerPermissions)) {
                foreach ($getResellerPermissions as $resellerPermission) {
                    if ($resellerPermission['name'] == 'create_clients'
                        && $resellerPermission['value'] == 'true') {
                        $createClients = true;
                    }
                    if ($resellerPermission['name'] == 'create_domains'
                        && $resellerPermission['value'] == 'true') {
                        $createDomains = true;
                    }
                }
            }
            if ($appInstalled && $createClients && $createDomains) {
                $showButtons = true;
            }
        }
        if (pm_Session::getClient()->isClient()) {
            if ($appInstalled && $hostingPlanItems) {
                $showButtons = true;
            }
        }
        return $showButtons;
    }

    public static function checkAndFixSchedulerTasks()
    {
        $requiredSchedules = [
            // Microweber app installations scan
            'microweber-periodic-task.php' => [
                'id'=>0,
                'cmd'=>'microweber-periodic-task.php',
                'schedule'=>false,
                'required_schedule'=>pm_Scheduler::$EVERY_HOUR,
                'founded'=>false,
                'is_ok'=>false,
            ],
            // Microweber check for update task
            'microweber-periodic-update-task.php' => [
                'id'=>0,
                'cmd'=>'microweber-periodic-update-task.php',
                'schedule'=>false,
                'required_schedule'=>pm_Scheduler::$EVERY_DAY,
                'founded'=>false,
                'is_ok'=>false,
            ]
        ];

        $listTasks = pm_Scheduler::getInstance()->listTasks();
        if (!empty($listTasks)) {
            foreach ($listTasks as $task) {

                $taskSchedule = $task->getSchedule();
                unset($taskSchedule['minute']);

                $taskCmd = $task->getCmd();

                $requiredSchedules[$taskCmd]['id'] = $task->getId();
                $requiredSchedules[$taskCmd]['founded'] = true;
                $requiredSchedules[$taskCmd]['cmd'] = $task->getCmd();
                $requiredSchedules[$taskCmd]['schedule'] = $taskSchedule;

                if (empty(array_diff($requiredSchedules[$taskCmd]['schedule'], $requiredSchedules[$taskCmd]['required_schedule']))) {
                    $requiredSchedules[$taskCmd]['is_ok'] = true;
                }
            }
        }

        foreach ($requiredSchedules as $requiredSchedule) {
            if ($requiredSchedule['founded'] == false) {
                // Must recreate task
                $task = new pm_Scheduler_Task();
                $task->setSchedule($requiredSchedule['required_schedule']);
                $task->setCmd($requiredSchedule['cmd']);
                pm_Scheduler::getInstance()->putTask($task);
                continue;
            }
            if ($requiredSchedule['is_ok'] == false) {
                // Must update task
                $getTask = pm_Scheduler::getInstance()->getTaskById($requiredSchedule['id']);
                $getTask->setSchedule($requiredSchedule['required_schedule']);
                $getTask->setCmd($requiredSchedule['cmd']);
                pm_Scheduler::getInstance()->putTask($getTask);
            }
        }

    }

    public static function getCurrentVersionOfApp()
    {
        $manager = new pm_ServerFileManager();

        $versionFile = $manager->fileExists(Modules_Microweber_Config::getAppSharedPath() . 'version.txt');

        $version = 'unknown';
        if ($versionFile) {
            $version = $manager->fileGetContents(Modules_Microweber_Config::getAppSharedPath() . 'version.txt');
            $version = strip_tags($version);
        }

        return $version;
    }

    public static function stopTasks(array $taskIds)
    {
        $taskManager = new pm_LongTask_Manager();

        // Cancel old tasks
        $tasks = $taskManager->getTasks($taskIds);

        $i = count($tasks) - 1;
        while ($i >= 0) {
            $taskManager->cancel($tasks[$i]);
            $i--;
        }
    }

    public static function getRequiredDiskSpace()
    {
        return 2;
    }

    public static function getAvailableDiskSpace()
    {
        $freeDiskSpace = pm_ApiCli::callSbin('check_disk_space.sh', [Modules_Microweber_Config::getExtensionVarPath()])['stdout'];
        $freeDiskSpace = str_ireplace(PHP_EOL, '', $freeDiskSpace);
        $freeDiskSpace = $freeDiskSpace / pow(1024, 2);

        return $freeDiskSpace;
    }

    public static function isAvailableDiskSpace()
    {
        $freeDiskSpace = self::getAvailableDiskSpace();

        $isOk = false;
        if ($freeDiskSpace > self::getRequiredDiskSpace()) {
            $isOk = true;
        }

        return $isOk;
    }

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

    public static function getRequiredPhpVersionOfSharedApp()
    {
        $mwReleaseVersion = 0;
        $mwReleasePhpVersion = 0;

        $appSharedPath = Modules_Microweber_Config::getAppSharedPath();

        $sfm = new pm_ServerFileManager();

        $appSharedPathComposer = $appSharedPath . 'composer.json';
        if ($sfm->fileExists($appSharedPathComposer)) {

            $appSharedPathComposer = $sfm->fileGetContents($appSharedPathComposer);
            $appSharedPathComposer = json_decode($appSharedPathComposer, true);

            if (isset($appSharedPathComposer['require']['php'])) {
                $mwReleasePhpVersion = $appSharedPathComposer['require']['php'];
                $mwReleasePhpVersion = str_replace('<',false, $mwReleasePhpVersion);
                $mwReleasePhpVersion = str_replace('>',false, $mwReleasePhpVersion);
                $mwReleasePhpVersion = str_replace('=',false, $mwReleasePhpVersion);

                $mwReleasePhpVersionExp = explode('.', $mwReleasePhpVersion);
                if (!empty($mwReleasePhpVersionExp)) {
                    $mwReleasePhpVersionExpSlice = array_slice($mwReleasePhpVersionExp, 0, 2);
                    $mwReleasePhpVersion = implode('.', $mwReleasePhpVersionExpSlice);
                }

            }
        }

        $appSharedPathVersion = $appSharedPath . 'version.txt';
        if ($sfm->fileExists($appSharedPathVersion)) {
            $mwReleaseVersion = $sfm->fileGetContents($appSharedPathVersion);
        }

        return ['mwReleaseVersion'=>$mwReleaseVersion,'mwReleasePhpVersion'=>$mwReleasePhpVersion];
    }

    public static function getLatestRequiredPhpVersionOfApp()
    {
        $mwRelease = Modules_Microweber_Config::getRelease();
        $mwReleaseComposer = Modules_Microweber_Helper::getJsonFromUrl($mwRelease['composer_url']);
        $mwReleasePhpVersion = false;

        if (isset($mwReleaseComposer['require']['php'])) {
            $mwReleasePhpVersion = $mwReleaseComposer['require']['php'];
            $mwReleasePhpVersion = str_replace('<',false, $mwReleasePhpVersion);
            $mwReleasePhpVersion = str_replace('>',false, $mwReleasePhpVersion);
            $mwReleasePhpVersion = str_replace('=',false, $mwReleasePhpVersion);

            $mwReleasePhpVersionExp = explode('.', $mwReleasePhpVersion);
            if (!empty($mwReleasePhpVersionExp)) {
                $mwReleasePhpVersionExpSlice = array_slice($mwReleasePhpVersionExp, 0, 2);
                $mwReleasePhpVersion = implode('.', $mwReleasePhpVersionExpSlice);
            }

        }

        $mwReleaseVersion = Modules_Microweber_Helper::getContentFromUrl($mwRelease['version_url']);

        return ['mwReleaseVersion'=>$mwReleaseVersion,'mwReleasePhpVersion'=>$mwReleasePhpVersion];
    }

    public static function canIUpdateNewVersionOfApp()
    {

        $latestRequirements = static::getLatestRequiredPhpVersionOfApp();

        $updateApp = true;
        $outdatedDomains = [];
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {

            if (!$domain->hasHosting()) {
                continue;
            }

            $domainInstallations = Modules_Microweber_Domain::getMwOption($domain, 'mwAppInstallations');
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
            if (version_compare($phpHandler['version'], $latestRequirements['mwReleasePhpVersion'], '<')) {
                // $error = 'PHP version ' . $phpHandler['version'] . ' is not supported by Microweber. You must install PHP '.$mwReleasePhpVersion.'.';
                $outdatedDomains[] = $domain->getName();
                $outdatedDomainsIds[] = $domain->getId();
            }
        }
        if (!empty($outdatedDomains)) {
            $updateApp = false;
        }

        if (count($outdatedDomains) > 10) {
            $outdatedDomains = array_slice($outdatedDomains, 0, 10);
        }

        return [
            'update_app'=>$updateApp,
            'outdated_domains'=>$outdatedDomains,
            'outdated_domains_ids'=>$outdatedDomainsIds
        ];
    }
}
