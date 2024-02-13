<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Install {

    protected $appLatestVersionFolder = false;
    protected $_overwrite = true;
    protected $_domainId;
    protected $_type = 'default';
    protected $_databaseDriver = 'mysql';
    protected $_databaseServerId = false;
    protected $_email = 'admin@microweber.com';
    protected $_username = '';
    protected $_password = '';
    protected $_path = false;
    protected $_progressLogger = false;
    protected $_template = false;
    protected $_language = false;
    protected $_ssl = false;

    public function __construct() {
    	$this->appLatestVersionFolder = Modules_Microweber_Config::getAppSharedPath();
    }


    public function setProgressLogger($logger) {
    	$this->_progressLogger = $logger;
    }

    public function setProgress($progress) {
    	if (is_object($this->_progressLogger) && method_exists($this->_progressLogger, 'updateProgress')) {
    		$this->_progressLogger->updateProgress($progress);
    	}
    }

    public function setPath($path) {
    	$this->_path = $path;
    }

    public function setSsl($ssl) {
    	$this->_ssl = $ssl;
    }

    public function setDomainId($id) {
        $this->_domainId = $id;
    }

    public function setType($type) {
        $this->_type = $type;
    }

    public function setLanguage($language) {
    	$this->_language = $language;
    }

    public function setTemplate($template) {
    	$this->_template = $template;
    }

    public function setDatabaseDriver($driver) {
    	$this->_databaseDriver = $driver;
    }

    public function setDatabaseServerId($serverId) {
    	$this->_databaseServerId = $serverId;
    }

    public function setEmail($email) {
    	$this->_email = $email;
    }

    public function setUsername($username) {
    	$this->_username = $username;
    }

    public function setPassword($password) {
    	$this->_password = $password;
    }

    private function _generateIniFile($array, $i = 0) {
        $str = "";
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $str .= str_repeat(" ",$i*2)."[$k]".PHP_EOL;
                $str .= $this->_generateIniFile($v, $i+1);
            } else {
                $str .= str_repeat(" ", $i * 2) . "$k = $v" . PHP_EOL;
            }
        }
        return $str;
    }

    public function run() {

    	$this->setProgress(5);

    	$domain = Modules_Microweber_Domain::getUserDomainById($this->_domainId);
        if (empty($domain->getName())) {
            throw new \Exception($domain->getName() . ' domain not found.');
        }

        if ($this->_ssl) {
            if (!$this->checkSsl($domain->getName())) {
                $this->addDomainEncryption($domain);
            } else {
                Modules_Microweber_Log::debug('Domain already have a SSL.');
            }
        }

        $sharedAppRequirements = Modules_Microweber_Helper::getRequiredPhpVersionOfSharedApp();

        $domainDocumentRoot = $domain->getDocumentRoot();
        $installationDirPath = $domain->getDocumentRoot();
        if ($this->_path && !empty($this->_path)) {
            $installationDirPath = $domainDocumentRoot . '/' . $this->_path;
        }

        $fileManager = new \pm_FileManager($domain->getId());

        $hostingManager = new Modules_Microweber_HostingManager();
        $hostingManager->setDomainId($domain->getId());

        if (!$fileManager->isDir($domain->getDocumentRoot())) {
            $fileManager->mkdir($domain->getDocumentRoot());
        }

        // Prepare php domain settings for symlinking
        if ($this->_type == 'symlink') {

            $currentPhpIni = '/var/www/vhosts/system/' . $domain->getName() . '/etc/php.ini';
            $tempPhpIni = $domainDocumentRoot . DIRECTORY_SEPARATOR . 'php.ini';

            $parseOldIniFile = parse_ini_file($currentPhpIni);
            $parseOldIniFile['open_basedir'] = 'none';

            $generateIniFile = $this->_generateIniFile($parseOldIniFile);
            $fileManager->filePutContents($tempPhpIni, $generateIniFile);

            $log = pm_ApiCli::callSbin('update_domain_phpini.sh', [$domain->getName(), $tempPhpIni])['stdout'];
            $fileManager->removeFile($tempPhpIni);

			// Symlink domain enable
            pm_ApiCli::callSbin('update_domain_server_settings.sh', [$domain->getName(), '-apache-restrict-follow-sym-links','false'])['stdout'];
        }

        $hostingProperties = $hostingManager->getHostingProperties();
        if (!$hostingProperties['php']) {
            return [
                'success'=>false,
                'error'=>true,
                'log'=> 'PHP is not activated on selected domain.'
            ];
        }

        $phpHandler = $hostingManager->getPhpHandler($hostingProperties['php_handler_id']);
        if (version_compare($phpHandler['version'], $sharedAppRequirements['mwReleasePhpVersion'], '<')) {
            return [
                'success'=>false,
                'error'=>true,
                'log'=> 'PHP version ' . $phpHandler['version'] . ' is not supported by Microweber. You must install PHP '.$sharedAppRequirements['mwReleasePhpVersion'].'.'
            ];
        }

        $this->setProgress(10);

        Modules_Microweber_Log::debug('Start installing Microweber on domain: ' . $domain->getName());

        $dbName =  str_replace('.', '', $domain->getName());
        $dbName = substr($dbName, 0, 9);
        $dbName .= '_'.date('His');
        $dbUsername = $dbName;
        $dbPassword = Modules_Microweber_Helper::getRandomPassword(12, true);

        if ($this->_databaseDriver == 'mysql') {

            $domainSubscription = $hostingManager->getDomainSubscription($domain->getName());
            if (!$domainSubscription['webspace']) {
                return [
                    'success'=>false,
                    'error'=>true,
                    'log'=> 'Webspace is not found. Domain: ' . $domain->getName()
                ];
            }

            $databaseServerDetails = $hostingManager->getDatabaseServerByWebspaceId($domainSubscription['webspaceId']);
            if (!$databaseServerDetails) {
                return [
                    'success'=>false,
                    'error'=>true,
                    'log'=> 'Cannot find database servers for webspace. WebspaceId:' . $domainSubscription['webspaceId']
                ];
            }

            $this->_databaseServerId = $databaseServerDetails['id'];

        	Modules_Microweber_Log::debug('Create database for domain: ' . $domain->getName());

        	$dbManager = new Modules_Microweber_DatabaseManager();
        	$dbManager->setDomainId($domain->getId());
        	$newDb = $dbManager->createDatabase($dbName, $this->_databaseServerId);

	        if (isset($newDb['database']['add-db']['result']['errtext'])) {
                return [
                    'success'=>false,
                    'error'=>true,
                    'log'=> $newDb['database']['add-db']['result']['errtext']
                ];
	        }

	        $this->setProgress(30);

	        if (isset($newDb['database']['add-db']['result']['id'])) {
	            $dbId = $newDb['database']['add-db']['result']['id'];
	        }

	        if (!$dbId) {
                return [
                    'success'=>false,
                    'error'=>true,
                    'log'=> 'Can\'t create database.'
                ];
	        }

	        if ($dbId) {
	        	$newUser = $dbManager->createUser($dbId, $dbUsername, $dbPassword);
	        }

	        if (isset($newUser['database']['add-db-user']['result']['errtext'])) {
	            throw new \Exception($newUser['database']['add-db-user']['result']['errtext']);
	        }

	        $this->setProgress(40);
        }

        if ($this->_path) {
            if (!$fileManager->fileExists($installationDirPath)) {
                $fileManager->mkdir($installationDirPath);
            }
        }

        Modules_Microweber_Log::debug('Clear old folder on domain: ' . $domain->getName());

        // Clear domain files if exists
        $this->_prepairDomainFolder($fileManager, $installationDirPath, $domain->getHomePath());

        $this->setProgress(60);

        // First we will make a directories
        foreach ($this->_getDirsToMake() as $dir) {
        	$fileManager->mkdir($installationDirPath . '/' . $dir, '0755', true);
        }

        $this->setProgress(65);

        foreach ($this->_getFilesForSymlinking($this->appLatestVersionFolder, $this->_template, $this->_type) as $folder) {
        	$scriptDirOrFile = $this->appLatestVersionFolder . $folder;
        	$domainDirOrFile = $installationDirPath .'/'. $folder;

        	if ($this->_type == 'symlink') {

        		// Delete domain file
        		pm_ApiCli::callSbin('filemng', [
        			$domain->getSysUserLogin(),
        			'exec',
                    $installationDirPath,
        			'rm',
        			'-rf',
        			$domainDirOrFile

        		], pm_ApiCli::RESULT_FULL);

        		// Create symlink
        		pm_ApiCli::callSbin('filemng', [
                    $domain->getSysUserLogin(),
                    'exec',
                    $installationDirPath,
                    'ln',
                    '-s',
                    $scriptDirOrFile,
                    $domainDirOrFile

                ], pm_ApiCli::RESULT_FULL);

        	} else {
        		$fileManager->copyFile($scriptDirOrFile, dirname($domainDirOrFile));
        	}
        }

        $this->setProgress(70);


        // And then we will copy folders
        foreach ($this->_getDirsToCopy() as $folder) {
            $scriptDirOrFile = $this->appLatestVersionFolder . $folder;
            $domainDirOrFile = $installationDirPath .'/'. $folder;
            $fileManager->copyFile($scriptDirOrFile, dirname($domainDirOrFile));
        }

        // And then we will copy files
        foreach ($this->_getFilesForCopy() as $file) {
        	$fileManager->copyFile($this->appLatestVersionFolder . $file, dirname($installationDirPath . '/' . $file));
        }

        $this->setProgress(75);

        if ($this->_type == 'symlink') {
        	$this->_fixHtaccess($fileManager, $installationDirPath);
        }

        $this->setProgress(85);

        $adminEmail = 'admin@microweber.com';
        $adminPassword = md5(time().rand(1111,9999));
        $adminUsername = md5(time().rand(1111,9999));

        if (!empty($this->_email)) {
        	$adminEmail = $this->_email;
        }
        if (!empty($this->_password)) {
        	$adminPassword = $this->_password;
        }
        if (!empty($this->_username)) {
        	$adminUsername = $this->_username;
        }

        if ($this->_databaseDriver == 'mysql') {

            $dbHost = 'localhost:3306';
            if (isset($databaseServerDetails['host']) && isset($databaseServerDetails['port'])) {
                $dbHost = $databaseServerDetails['host'] . ':' . $databaseServerDetails['port'];
            }

            // Database settings Mysql
            $installArguments[] = '--db-host='.trim($dbHost);
            $installArguments[] = '--db-name=' . trim($dbName);
            $installArguments[] = '--db-username='.trim($dbUsername);
            $installArguments[] = '--db-password='.trim($dbPassword);
            $installArguments[] = '--db-driver=' .trim($this->_databaseDriver);

        } else {
        	$dbName = $installationDirPath . '/storage/database1.sqlite';

            // Database settings Sqlite
            $installArguments[] = '--db-name=' . trim($dbName);
            $installArguments[] = '--db-username='.trim($dbUsername);
            $installArguments[] = '--db-password='.trim($dbPassword);
            $installArguments[] = '--db-driver=' .trim($this->_databaseDriver);

        }

        $this->setProgress(90);

        $installArguments = [];

        // Admin details
        $installArguments[] = '--email='.trim($adminEmail);
        $installArguments[] = '--username='.trim($adminUsername);
        $installArguments[] = '--password='.trim($adminPassword);

        if ($this->language) {
            $installArguments[] = '--language=' . trim($this->_language);
        }

        $installArguments[] = '--db-prefix=site_';

        if (!empty($this->_template)) {
        	$installArguments[] = '--template='.trim($this->_template);
            $installArguments[] = '--default-content=1';
        }

        try {
            $args = [
                $domain->getSysUserLogin(),
                'exec',
                $installationDirPath,
                $phpHandler['clipath'],
                '-d opcache.enable=0',
                '-d opcache.enable_cli=0',
                'artisan',
                'microweber:install',
            ];
            $args = array_merge($args, $installArguments);
            $artisan = pm_ApiCli::callSbin('filemng', $args, pm_ApiCli::RESULT_FULL);

            pm_ApiCli::callSbin('filemng', [
                $domain->getSysUserLogin(),
                'exec',
                $installationDirPath,
                $phpHandler['clipath'],
                '-d opcache.enable=0',
                '-d opcache.enable_cli=0',
                'artisan',
                'microweber:reload-database',
            ], pm_ApiCli::RESULT_FULL);

            $this->setProgress(95);

            Modules_Microweber_Log::debug('Microweber install log for: ' . $domain->getName() . '<br />' . $artisan['stdout']. '<br /><br />');

            // Set branding json
            Modules_Microweber_WhiteLabelBranding::applyToInstallation($domain, $installationDirPath);

            // Save domain settings
            $saveDomainSettings = [
                'admin_email'=>$adminEmail,
                'admin_password'=>$adminPassword,
                'admin_username'=>$adminUsername,
                'admin_url'=>'admin',
                'language'=>$this->_language,
                'created_at'=> date('Y-m-d H:i:s')
            ];
            Modules_Microweber_Domain::setMwOption($domain, 'mw_settings_' . md5($installationDirPath), $saveDomainSettings);

            return [
                'success'=>true,
                'log'=> $artisan['stdout']
            ];
        } catch (Exception $e) {
        	return [
		    	'success'=>false,
                'error'=>true,
                'log'=> $e->getMessage()
            ];
        }

    }

    private function checkSsl($domainName)
    {
        $g = @stream_context_create (array("ssl" => array("capture_peer_cert" => true)));
        $r = @stream_socket_client("ssl://www.".$domainName.":443", $errno, $errstr, 30,
            STREAM_CLIENT_CONNECT, $g);
        $cont = @stream_context_get_params($r);
        if (isset($cont["options"]["ssl"]["peer_certificate"])) {
            return true;
        }

        return false;
    }

    private function addDomainEncryption($domain)
    {
    	$artisan = false;

    	$sslEmail = 'admin@microweber.com';

    	$encryptOptions = [];
    	$encryptOptions[] = '--domain';
    	$encryptOptions[] = $domain->getName();
    	$encryptOptions[] = '--email';
    	$encryptOptions[] = $sslEmail;

    	 // Add SSL
    	 try {
    	 	Modules_Microweber_Log::debug('Start installign SSL for domain: ' . $domain->getName() . '; SSL Email: ' . $sslEmail);

    	 	$artisan = \pm_ApiCli::call('extension', array_merge(['--exec', 'letsencrypt', 'cli.php'], $encryptOptions), \pm_ApiCli::RESULT_FULL);

    		Modules_Microweber_Log::debug('Encrypt domain log for: ' . $domain->getName() . '<br />' . $artisan['stdout']. '<br /><br />');
    	 	Modules_Microweber_Log::debug('Success instalation SSL for domain: ' . $domain->getName());

    	 } catch(\Exception $e) {

    	 	Modules_Microweber_Log::debug('Can\'t install SSL for domain: ' . $domain->getName());
    	 	Modules_Microweber_Log::debug('Error: ' . $e->getMessage());

    	 }

    	 return $artisan;
    }

    private function _fixHtaccess($fileManager, $installPath)
    {
    	try {

    		$content = $fileManager->fileGetContents($installPath . '/.htaccess');

    		$content = str_replace('-MultiViews -Indexes', 'FollowSymLinks', $content);

    		$fileManager->filePutContents($installPath . '/.htaccess', $content);

    	} catch (Exception $e) {
    		\pm_Log::warn($e);
    	}
    }

    private function _prepairDomainFolder($fileManager, $installPath, $backupPath)
    {
    	try {
    		$findedFiles = [];
    		foreach ($fileManager->scanDir($installPath) as $file) {
    			if ($file == '.' || $file == '..') {
    				continue;
    			}
    			$findedFiles[] = $file;
    		}

    		if (!empty($findedFiles)) {
    			// Make backup dir
    			$backupFilesPath = $backupPath . '/backup_files/backup-' . date('Y-m-d-H-i-s');
    			$fileManager->mkdir($backupFilesPath, null, true);

    			// Move files to backup dir
    			foreach ($findedFiles as $file) {
    				$fileManager->moveFile($installPath . '/' . $file, $backupFilesPath . '/' . $file);
    			}
    		}

    	} catch (Exception $e) {
    		\pm_Log::warn($e);
    	}

    }

    private function _getDirsToMake() {

    	$dirs = [];

    	// Storage dirs
    	$dirs[] = 'storage';
    	$dirs[] = 'storage/framework';
    	$dirs[] = 'storage/framework/sessions';
    	$dirs[] = 'storage/framework/views';
    	$dirs[] = 'storage/cache';
    	$dirs[] = 'storage/logs';
    	$dirs[] = 'storage/app';

    	// Bootstrap dirs
    	$dirs[] = 'bootstrap';
    	$dirs[] = 'bootstrap/cache';

    	// User files dirs
    	$dirs[] = 'userfiles';
    	$dirs[] = 'userfiles/media';
    	$dirs[] = 'userfiles/modules';
    	$dirs[] = 'userfiles/templates';

        // Public dir
	$dirs[] = 'public';

    	return $dirs;
    }

    private function _getDirsToCopy() {

        $dirs = [];

        // Config dir
        $dirs[] = 'config';

        return $dirs;
    }

    private function _getFilesForSymlinking($appLatestFolder, $desiredTemplate = null, $installType = null) {

    	$files = [];
    	$files[] = 'version.txt';
    	$files[] = 'vendor';
    	$files[] = 'src';
    	$files[] = 'resources';
    	$files[] = 'database';
    	$files[] = 'userfiles/elements';
	    $files[] = 'public/build';

        $files[] = '/userfiles/templates/default';

    	$sfm = new \pm_ServerFileManager();
    	$listTemplates = $sfm->scanDir($appLatestFolder . '/userfiles/templates');
        if (!empty($listTemplates)) {
            foreach ($listTemplates as $template) {
                if ($template == '.' || $template == '..') {
                    continue;
                }
                if ($installType != 'symlink') {
                    if ($desiredTemplate) {
                        if ($desiredTemplate != $template) {
                            continue;
                        }
                    }
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

    /**
     * This is the files when symlinking app.
     * @return string[]
     */
    private function _getFilesForCopy() {

    	$files = [];

    	// Index
    	$files[] = 'index.php';
    	$files[] = '.htaccess';
    	$files[] = 'favicon.ico';
    	$files[] = 'composer.json';
    	$files[] = 'artisan';
    	/*
        // Config folder
        $files[] = 'config/.htaccess';
        $files[] = 'config/database.php';
        $files[] = 'config/app.php';
        $files[] = 'config/auth.php';
        $files[] = 'config/cache.php';
        $files[] = 'config/compile.php';
        $files[] = 'config/filesystems.php';
        $files[] = 'config/queue.php';
        $files[] = 'config/services.php';
        $files[] = 'config/view.php';
        $files[] = 'config/workbench.php';
        $files[] = 'config/hashing.php';
        $files[] = 'config/mail.php';
        $files[] = 'config/session.php';*/

    	// Bootstrap folder
    	$files[] = 'bootstrap/.htaccess';
    	$files[] = 'bootstrap/app.php';
    	$files[] = 'bootstrap/autoload.php';

    	return $files;
    }

}
