<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Install {

    protected $_appLatestVersionFolder = false;
    protected $_overwrite = true;
    protected $_domainId;
    protected $_type = 'default';
    protected $_databaseDriver = 'mysql';
    protected $_email = 'admin@microweber.com';
    protected $_username = '';
    protected $_password = '';
    protected $_path = false;
    protected $_progressLogger = false;
    protected $_template = false;
    protected $_language = false;
    
    public function __construct() {
    	$this->_appLatestVersionFolder = Modules_Microweber_Config::getAppSharedPath();
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
    
    public function setEmail($email) {
    	$this->_email = $email;
    }
    
    public function setUsername($username) {
    	$this->_username = $username;
    }
    
    public function setPassword($password) {
    	$this->_password = $password;
    }

    public function run() {
    	
    	$this->setProgress(5);
    	
    	$domain = Modules_Microweber_Domain::getUserDomainById($this->_domainId);
        if (empty($domain->getName())) {
            throw new \Exception('Domain not found.');
        }
	    
	    $whmcs = new Modules_Microweber_WhmcsConnector();
        $whmcs->setDomainName($domain->getName());
        $whiteLabelWhmcsSettings = $whmcs->getWhitelabelSettings();

        if (!empty($whiteLabelWhmcsSettings)) {
            if (isset($whiteLabelWhmcsSettings['wl_installation_language'])) {
                $this->_language = $whiteLabelWhmcsSettings['wl_installation_language'];
            }
            if (isset($whiteLabelWhmcsSettings['wl_installation_template'])) {
                $this->_template = $whiteLabelWhmcsSettings['wl_installation_template'];
            }
        }
	    
        $hostingManager = new Modules_Microweber_HostingManager();
        $hostingManager->setDomainId($domain->getId());
        $hostingProperties = $hostingManager->getHostingProperties();
        if (!$hostingProperties['php']) {
        	throw new \Exception('PHP is not activated on selected domain.');
        }
        $phpHandler = $hostingManager->getPhpHandler($hostingProperties['php_handler_id']);
        
        $this->setProgress(10);
        
        $fileManager = new \pm_FileManager($domain->getId());
        
		$this->setProgress(20);
	    
        pm_Log::debug('Start installing Microweber on domain: ' . $domain->getName());
        
        $dbName =  str_replace('.', '', $domain->getName());
        $dbName = substr($dbName, 0, 9);
        $dbName .= '_'.date('His');  
        $dbUsername = $dbName;
        $dbPassword = Modules_Microweber_Helper::getRandomPassword(12, true);
        
        if ($this->_databaseDriver == 'mysql') {
        	
        	pm_Log::debug('Create database for domain: ' . $domain->getName());
        	
        	$dbManager = new Modules_Microweber_DatabaseManager();
        	$dbManager->setDomainId($domain->getId());
        	
	        $newDb = $dbManager->createDatabase($dbName);
	        
	        if (isset($newDb['database']['add-db']['result']['errtext'])) {
	            throw new \Exception('You have reached the limit of your allowed databases.');
	        }
	        
	        $this->setProgress(30);
	
	        if (isset($newDb['database']['add-db']['result']['id'])) {
	            $dbId = $newDb['database']['add-db']['result']['id'];
	        }
	
	        if (!$dbId) {
	            throw new \Exception('Can\'t create database.');
	        }
	
	        if ($dbId) {
	        	$newUser = $dbManager->createUser($dbId, $dbUsername, $dbPassword);
	        }
			
	        if (isset($newUser['database']['add-db-user']['result']['errtext'])) {
	            throw new \Exception($newUser['database']['add-db-user']['result']['errtext']);
	        }
	        
	        $this->setProgress(40);
        }
        
        $domainDocumentRoot = $domain->getDocumentRoot(); 
        
        if ($this->_path) {
            $domainDocumentRoot = $domainDocumentRoot . '/' . $this->_path;
            if (!$fileManager->fileExists($domainDocumentRoot)) {
                $fileManager->mkdir($domainDocumentRoot);
            }
        }
        
        $domainName = $domain->getName();
        $domainIsActive = $domain->isActive();
        $domainCreation = $domain->getProperty('cr_date');
        
        pm_Log::debug('Clear old folder on domain: ' . $domain->getName());
        
        // Clear domain files if exists
        $this->_prepairDomainFolder($fileManager, $domainDocumentRoot, $domain->getHomePath());
        
        $this->setProgress(60);
        
        // First we will make a directories
        foreach ($this->_getDirsToMake() as $dir) {
        	$fileManager->mkdir($domainDocumentRoot . '/' . $dir, '0755', true);
        }
        	
        $this->setProgress(65);

        foreach ($this->_getFilesForSymlinking($this->_appLatestVersionFolder) as $folder) {
        	$scriptDirOrFile = $this->_appLatestVersionFolder . $folder;
        	$domainDirOrFile = $domainDocumentRoot .'/'. $folder;
        	
        	if ($this->_type == 'symlink') {
        		
        		// Delete domain file
        		pm_ApiCli::callSbin('filemng', [
        			$domain->getSysUserLogin(),
        			'exec',
        			$domainDocumentRoot,
        			'rm', 
        			'-rf', 
        			$domainDirOrFile
        			
        		], pm_ApiCli::RESULT_FULL);
        		
        		// Create symlink
        		pm_ApiCli::callSbin('filemng', [
        			$domain->getSysUserLogin(),
        			'exec',
        			$domainDocumentRoot,
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
        	
        // And then we will copy files
        foreach ($this->_getFilesForCopy() as $file) {
        	$fileManager->copyFile($this->_appLatestVersionFolder . $file, dirname($domainDocumentRoot . '/' . $file));
        }
        	
        $this->setProgress(75);
        
        if ($this->_type == 'symlink') {
        	$this->_fixHtaccess($fileManager, $domainDocumentRoot); 
        }
        
        $this->setProgress(85);
        
        $adminEmail = 'admin@microweber.com';
        $adminPassword = '1';
        $adminUsername = '1';
        
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
	        $dbHost = '127.0.0.1';
	        $dbPort = '3306';
        } else {
        	$dbHost = 'localhost';
        	$dbPort = '';
        	$dbName = $domainDocumentRoot . '/storage/database1.sqlite';
        }
        
        $this->setProgress(90);
        
        $installArguments = [];

        // Admin details
        $installArguments[] =  $adminEmail;
        $installArguments[] =  $adminUsername;
        $installArguments[] =  $adminPassword;
        
        // Database settings
        $installArguments[] = $dbHost;
        $installArguments[] = $dbName;
        $installArguments[] = $dbUsername;
        $installArguments[] = $dbPassword;
        $installArguments[] = $this->_databaseDriver;
			
		if ($this->_language) {
			$installationLanguage = $this->_language;
		} else {
			$installationLanguage = pm_Settings::get('installation_language');
		}
		
		if (!empty($installationLanguage)) { 
			$installArguments[] = '-l';
			$installArguments[] = trim($installationLanguage);
    	}
    	
        $installArguments[] = '-p'; 
        $installArguments[] = 'mw_';
        
        if ($this->_template) {
       		$installArguments[] = '-t';
        	$installArguments[] = $this->_template;
        }
        
        $installArguments[] = '-d';
        $installArguments[] = '1';
        
        if (!pm_Session::getClient()->isAdmin()) {
       		$installArguments[] = '-c';
       		$installArguments[] = '1';
        }

        try {
        	$args = [
        		$domain->getSysUserLogin(),
        		'exec',
                $domainDocumentRoot,
        		$phpHandler['clipath'],
                'artisan',
        		'microweber:install',
        	];
        	$args = array_merge($args, $installArguments);
        	$artisan = pm_ApiCli::callSbin('filemng', $args, pm_ApiCli::RESULT_FULL);

        	$this->setProgress(95);
 
        	pm_Log::debug('Microweber install log for: ' . $domain->getName() . '<br />' . $artisan['stdout']. '<br /><br />');
        	
        	Modules_Microweber_WhiteLabel::updateWhiteLabelDomainById($domain->getId()); 
        	
        	$this->addDomainEncryption($domain);

        	// Save domain settings
            $saveDomainSettings = [
                'admin_email'=>$adminEmail,
                'admin_password'=>$adminPassword,
                'admin_username'=>$adminUsername,
                'admin_url'=>'admin',
                'language'=>$this->_language,
                'created_at'=> date('Y-m-d H:i:s')
            ];
            $domain->setSetting('mw_settings_' . md5($domainDocumentRoot), serialize($saveDomainSettings));
        	
        	return ['success'=>true, 'log'=> $artisan['stdout']];
        } catch (Exception $e) {
        	return ['success'=>false, 'error'=>true, 'log'=> $e->getMessage()];
        }
        
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
    	 	pm_Log::debug('Start installign SSL for domain: ' . $domain->getName() . '; SSL Email: ' . $sslEmail);
    	 	
    	 	$artisan = \pm_ApiCli::call('extension', array_merge(['--exec', 'letsencrypt', 'cli.php'], $encryptOptions), \pm_ApiCli::RESULT_FULL);
    	 	
    		pm_Log::debug('Encrypt domain log for: ' . $domain->getName() . '<br />' . $artisan['stdout']. '<br /><br />');
    	 	pm_Log::debug('Success instalation SSL for domain: ' . $domain->getName());
    	 	
    	 } catch(\Exception $e) {
    	 	
    	 	pm_Log::debug('Can\'t install SSL for domain: ' . $domain->getName());
    	 	pm_Log::debug('Error: ' . $e->getMessage());
    	 	
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
    	
    	// Config dir
    	$dirs[] = 'config';
    	
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
    	
    	return $dirs;
    }
    
    private function _getFilesForSymlinking($appLatestFolder) {
    	
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
    	$files[] = 'config/session.php';
    	
    	// Bootstrap folder
    	$files[] = 'bootstrap/.htaccess';
    	$files[] = 'bootstrap/app.php';
    	$files[] = 'bootstrap/autoload.php';
    	
    	return $files;
    }
    
}
