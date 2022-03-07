<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class IndexController extends pm_Controller_Action
{

    private $devMode = false;
    private $taskManager = NULL;
    protected $_moduleName = 'Microweber';

    public function init()
    {
        parent::init();

        if (is_null($this->taskManager)) {
            $this->taskManager = new pm_LongTask_Manager();
        }

        $this->view->newLicenseLink = '/server/additional_keys.php?key_type=additional';
        $this->view->buyLink = pm_Context::getBuyUrl();
        $this->view->upgradeLink = pm_Context::getUpgradeLicenseUrl();

        $this->view->limitations = Modules_Microweber_LicenseData::getLimitations();
        $this->_moduleName = Modules_Microweber_WhiteLabel::getBrandName();

        // Set module name to views
        $this->view->moduleName = $this->_moduleName;

        // Init tabs for all actions
        $this->view->tabs = [
            [
                'title' => 'Domains',
                'action' => 'index'
            ]
        ];

        $this->view->tabs[] = [
            'title' => 'Install',
            'action' => 'install'
        ];

        if (pm_Session::getClient()->isAdmin()) {
            $this->view->tabs[] = [
                'title' => 'Versions',
                'action' => 'versions'
            ];
        }

        if ($this->_isWhiteLabelAllowed()) {
            $this->view->tabs[] = [
                'title' => 'White Label',
                'action' => 'whitelabel'
            ];
        }

        if (pm_Session::getClient()->isAdmin()) {
            $this->view->tabs[] = [
                'title' => 'Settings',
                'action' => 'settings',
            ];
        }

        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl() . 'css/app.css');

        if ($this->view->limitations['app_installations_freeze']) {
            $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl() . 'css/reached-plan.css');
        }
    }

    public function indexAction()
    {
        $this->_checkAppSettingsIsCorrect();

        $this->view->errorMessage = false;
        if (isset($_GET['message'])) {
           $this->view->errorMessage =  $_GET['message'];
        }

        $this->view->refreshDomainLink = pm_Context::getBaseUrl() . 'index.php/index/refreshdomains';
        $this->view->pageTitle = $this->_moduleName . ' - Domains';
        $this->view->list = $this->_getDomainsList();

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/index.js');
    }


    public function refreshdomainsAction()
    {
        $this->_queueRefreshDomains();

        return $this->_redirect('index/index');
    }

    public function versionsAction()
    {
        if ($this->view->limitations['app_installations_freeze']) {
            return $this->_redirect('index/index');
        }

        if (!pm_Session::getClient()->isAdmin()) {
            return $this->_redirect('index/error?type=permission');
        }

        $this->_checkAppSettingsIsCorrect();

        $release = Modules_Microweber_Config::getRelease();

        $availableTemplates = Modules_Microweber_Config::getSupportedTemplates();
        if (!empty($availableTemplates)) {
            $availableTemplates = implode(', ', $availableTemplates);
        } else {
            $availableTemplates = 'No templates available';
        }

        $this->view->pageTitle = $this->_moduleName . ' - Versions';

        $this->view->latestVersion = 'unknown';
        $this->view->currentVersion = $this->_getCurrentVersion();
        $this->view->latestDownloadDate = $this->_getCurrentVersionLastDownloadDateTime();
        $this->view->availableTemplates = $availableTemplates;

        if (!empty($release)) {
            $this->view->latestVersion = $release['version'];
        }

        $this->view->updateLink = pm_Context::getBaseUrl() . 'index.php/index/update';
       // $this->view->updateTemplatesLink = pm_Context::getBaseUrl() . 'index.php/index/update_templates';
    }

    public function whitelabelAction()
    {
        if ($this->view->limitations['app_installations_freeze']) {
            return $this->_redirect('index/index');
        }

        $savingWhiteLabelKey = false;

        if (!$this->_isWhiteLabelAllowed()) {
            return $this->_redirect('index/error?type=permission');
        }

        $this->_checkAppSettingsIsCorrect();

        $this->view->pageTitle = $this->_moduleName . ' - White Label';

        // WL - white label
        $form = new pm_Form_Simple();
        $formMwKey = new pm_Form_Simple();

        $form->addElement('text', 'wl_brand_name', [
            'label' => 'Brand Name',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_brand_name'),
            'placeholder' => 'Enter the name of your company.'
        ]);
        $form->addElement('text', 'wl_brand_favicon', [
            'label' => 'Brand Favicon',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_brand_favicon'),
            'placeholder' => 'Enter favicon url of your company.'
        ]);
        $form->addElement('text', 'wl_admin_login_url', [
            'label' => 'Admin login - White Label URL?',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_admin_login_url'),
            'placeholder' => 'Enter website url of your company.'
        ]);
        $form->addElement('text', 'wl_contact_page', [
            'label' => 'Enable support links?',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_contact_page'),
            'placeholder' => 'Enter url of your contact page'
        ]);
        $form->addElement('checkbox', 'wl_enable_support_links',
            [
                'label' => 'Enable support links', 'value' => Modules_Microweber_WhiteLabelSettings::get('wl_enable_support_links')
            ]
        );
        $form->addElement('textarea', 'wl_powered_by_link',
            [
                'label' => 'Enter "Powered by" text',
                'value' => Modules_Microweber_WhiteLabelSettings::get('wl_powered_by_link'),
                'rows' => 3
            ]
        );
        $form->addElement('checkbox', 'wl_hide_powered_by_link',
            [
                'label' => 'Hide "Powered by" link', 'value' => Modules_Microweber_WhiteLabelSettings::get('wl_hide_powered_by_link')
            ]
        );
        $form->addElement('text', 'wl_logo_admin_panel', [
            'label' => 'Logo for Admin panel (size: 180x35px)',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_logo_admin_panel'),
            'placeholder' => ''
        ]);
        $form->addElement('text', 'wl_logo_live_edit_toolbar', [
            'label' => 'Logo for Live-Edit toolbar (size: 50x50px)',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_logo_live_edit_toolbar'),
            'placeholder' => ''
        ]);
        $form->addElement('text', 'wl_logo_login_screen', [
            'label' => 'Logo for Login screen (max width: 290px)',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_logo_login_screen'),
            'placeholder' => ''
        ]);
        $form->addElement('checkbox', 'wl_disable_microweber_marketplace',
            [
                'label' => 'Disable Microweber Marketplace', 'value' => Modules_Microweber_WhiteLabelSettings::get('wl_disable_microweber_marketplace')
            ]
        );
        $form->addElement('text', 'wl_external_login_server_button_text', [
            'label' => 'External Login Server Button Text',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_external_login_server_button_text'),
            'placeholder' => 'Login with Microweber Account'
        ]);
        $form->addElement('checkbox', 'wl_external_login_server_enable',
            [
                'label' => 'Enable External Login Server', 'value' => Modules_Microweber_WhiteLabelSettings::get('wl_external_login_server_enable')
            ]
        );
        $form->addElement('checkbox', 'wl_enable_service_links',
            [
                'label' => 'Enable Microweber Service Links',
                'value' => Modules_Microweber_WhiteLabelSettings::get('wl_enable_service_links'),
            ]
        );
        $form->addElement('text', 'wl_plesk_logo_invert', [
            'label' => 'Plesk Logo for sidebar',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_plesk_logo_invert'),
            'placeholder' => ''
        ]);
        $form->addElement('text', 'wl_plesk_logo_app', [
            'label' => 'Plesk Logo App',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_plesk_logo_app'),
            'placeholder' => ''
        ]);
        $form->addElement('textarea', 'wl_admin_colors_sass',
            [
                'label' => 'Enter "Admin colors" sass',
                'value' => Modules_Microweber_WhiteLabelSettings::get('wl_admin_colors_sass'),
                'rows' => 6
            ]
        );

        $form->addControlButtons([
            'cancelLink' => pm_Context::getBaseUrl() . 'index.php/index/whitelabel',
        ]);

        $this->view->form = $form;

        $formMwKey->addElement('text', 'wl_key', [
            'label' => 'White Label Key',
            'value' => pm_Settings::get('wl_key'),
            'placeholder' => 'Place your microweber white label key.'
        ]);
        $formMwKey->addControlButtons([
            'cancelLink' => pm_Context::getBaseUrl() . 'index.php/index/whitelabel',
        ]);
        $this->view->formMwKey = $formMwKey;

        if ($this->getRequest()->isPost() && $formMwKey->isValid($this->getRequest()->getPost()) && !empty($formMwKey->getValue('wl_key'))) {
            $savingWhiteLabelKey = true;

            // Check license and save it to pm settings
            $licenseCheck = Modules_Microweber_LicenseData::getLicenseData($formMwKey->getValue('wl_key'));
            if (isset($licenseCheck['status']) && $licenseCheck['status'] == 'active') {

                pm_Settings::set('wl_key', $formMwKey->getValue('wl_key'));
                pm_Settings::set('wl_license_data', json_encode($licenseCheck));

            } else {
                pm_Settings::set('wl_license_data', false);
                $this->_status->addMessage('error', 'The license key is wrong or expired.');
            }

            $this->_helper->json(['redirect' => pm_Context::getBaseUrl() . 'index.php/index/whitelabel']);
        }

        $this->view->change_whitelabel_key = false;
        if ($this->getRequest()->getParam('change_whitelabel_key') == '1') {
            $this->view->change_whitelabel_key = true;
        }

        if ($this->getRequest()->getParam('delete_whitelabel_key') == '1') {
            pm_Settings::set('wl_key', false);
            pm_Settings::set('wl_license_data', false);
        }

        if (!$savingWhiteLabelKey && $this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

            Modules_Microweber_WhiteLabelSettings::set('wl_brand_name', $form->getValue('wl_brand_name'));
            Modules_Microweber_WhiteLabelSettings::set('wl_brand_favicon', $form->getValue('wl_brand_favicon'));
            Modules_Microweber_WhiteLabelSettings::set('wl_admin_login_url', $form->getValue('wl_admin_login_url'));
            Modules_Microweber_WhiteLabelSettings::set('wl_contact_page', $form->getValue('wl_contact_page'));
            Modules_Microweber_WhiteLabelSettings::set('wl_enable_support_links', $form->getValue('wl_enable_support_links'));
            Modules_Microweber_WhiteLabelSettings::set('wl_powered_by_link', $form->getValue('wl_powered_by_link'));
            Modules_Microweber_WhiteLabelSettings::set('wl_hide_powered_by_link', $form->getValue('wl_hide_powered_by_link'));
            Modules_Microweber_WhiteLabelSettings::set('wl_logo_admin_panel', $form->getValue('wl_logo_admin_panel'));
            Modules_Microweber_WhiteLabelSettings::set('wl_logo_live_edit_toolbar', $form->getValue('wl_logo_live_edit_toolbar'));
            Modules_Microweber_WhiteLabelSettings::set('wl_logo_login_screen', $form->getValue('wl_logo_login_screen'));
            Modules_Microweber_WhiteLabelSettings::set('wl_disable_microweber_marketplace', $form->getValue('wl_disable_microweber_marketplace'));
            Modules_Microweber_WhiteLabelSettings::set('wl_external_login_server_button_text', $form->getValue('wl_external_login_server_button_text'));
            Modules_Microweber_WhiteLabelSettings::set('wl_external_login_server_enable', $form->getValue('wl_external_login_server_enable'));
            Modules_Microweber_WhiteLabelSettings::set('wl_enable_service_links', $form->getValue('wl_enable_service_links'));
            Modules_Microweber_WhiteLabelSettings::set('wl_plesk_logo_invert', $form->getValue('wl_plesk_logo_invert'));
            Modules_Microweber_WhiteLabelSettings::set('wl_plesk_logo_app', $form->getValue('wl_plesk_logo_app'));
		    Modules_Microweber_WhiteLabelSettings::set('wl_admin_colors_sass', $form->getValue('wl_admin_colors_sass'));

            Modules_Microweber_WhiteLabel::updateWhiteLabelDomains();

            $this->_status->addMessage('info', 'Settings was successfully saved.');
        }

        // Show is licensed
        $this->_getLicensedView();

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/whitelabel.js');
    }

    public function updateAction()
    {
        if (!pm_Session::getClient()->isAdmin()) {
            return $this->_redirect('index/error?type=permission');
        }

        $task = new Modules_Microweber_TaskAppVersionCheck();
        $this->taskManager->start($task, NULL);

        $this->_status->addMessage('info', 'Update task has been started');

        return $this->_redirect('index/versions');
    }

/*
    public function activatesymlinkingAction()
    {

        $task = new Modules_Microweber_TaskDisableSelinux();
        $this->taskManager->start($task, NULL);

        return $this->_redirect('index/install');
    }*/

/*
	public function reinstallAction()
	{
		
		$domainId = 39;
		
		$domain = Modules_Microweber_Domain::getUserDomainById($domainId);
        if (empty($domain->getName())) { 
            throw new \Exception($domain->getName() . ' domain not found.');
        }

        $domainDocumentRoot = $domain->getDocumentRoot();
		
		Modules_Microweber_Reinstall::run($domainId, $domainDocumentRoot);
		
		
		die(); 
	}*/

    public function installAction()
    {
        if ($this->view->limitations['app_installations_freeze']) {
            return $this->_redirect('index/index');
        }

        /*
        $this->view->selinuxError = false;
        $this->view->activateSymlinking = false;

        $fileManager = new pm_ServerFileManager();
        if ($fileManager->fileExists('/usr/sbin/getenforce')) {
            $checkSymlinkIsAllowed = pm_ApiCli::callSbin('symlinking_status.sh', []);
            if (stripos($checkSymlinkIsAllowed['stdout'], 'enforcing') !== false) {
                $this->view->selinuxError = true;
            }
            if (stripos($checkSymlinkIsAllowed['stdout'], 'permissive') !== false) {
                $this->view->selinuxError = true;
            }
            $this->view->activateSymlinking = pm_Context::getBaseUrl() . 'index.php/index/activatesymlinking';
        }*/

        $this->_checkAppSettingsIsCorrect();

        $this->view->pageTitle = $this->_moduleName . ' - Install';

        $domainsSelect = ['no_select' => 'Select domain to install..'];
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {

            if (!$domain->hasHosting()) {
                continue;
            }

            $domainId = $domain->getId();
            $domainName = $domain->getDisplayName();

            $domainsSelect[$domainId] = $domainName;
        }

        $form = new pm_Form_Simple();

        $form->addElement('select', 'installation_domain', [
            'label' => 'Domain',
            'multiOptions' => $domainsSelect,
            'required' => true,
        ]);

        $form->addElement(
            new Zend_Form_Element_Note('create_new_domain_link',
                ['value' => '<a href="/smb/web/add-domain" style="margin-left:175px;top: -15px;position:relative;">Create New Domain</a>']
            )
        );

        $form->addElement('select', 'installation_language', [
            'label' => 'Installation Language',
            'multiOptions' => Modules_Microweber_Config::getSupportedLanguages(),
            'value' => pm_Settings::get('installation_language'),
            'required' => true,
        ]);

        $form->addElement('select', 'installation_template', [
            'label' => 'Installation Template',
            'multiOptions' => Modules_Microweber_Config::getSupportedTemplates(),
            'value' => pm_Settings::get('installation_template'),
            'required' => true,
        ]);

        $chooseInstallationType = pm_Settings::get('installation_type_allow_customers');
        $chooseInstallationType = trim($chooseInstallationType);

        if (pm_Session::getClient()->isAdmin()) {
            $chooseInstallationType = 'yes';
        }
        if ($chooseInstallationType == '' || !$chooseInstallationType) {
            $chooseInstallationType = 'yes';
        }

        if ($chooseInstallationType == 'yes') {
            $form->addElement('radio', 'installation_type', [
                'label' => 'Installation Type',
                'multiOptions' =>
                    [
                        'default' => 'Default',
                        'symlink' => 'Sym-Linked'
                    ],
                'value' => pm_Settings::get('installation_type'),
                'required' => true,
            ]);
        } else {
            $form->addElement('hidden', 'installation_type', [
                'value' => pm_Settings::get('installation_type')
            ]);
        }

        /*$dbManager = new Modules_Microweber_DatabaseManager();
        $dbManager->setDomainId($domain->getId());

        $hostingManager = new Modules_Microweber_HostingManager();
        $hostingManager->setDomainId($domain->getId());*/

        /*
         *      $servers = $dbManager->getDatabaseServers();
                if (pm_Session::getClient()->isAdmin()) {
                    $serversOptions = [];
                    if ($servers) {
                        foreach ($servers as $server) {
                            if ($server['data']['type'] != 'mysql') {
                                continue;
                            }
                            $dbServerDetails = $dbManager->getDatabaseServerById($server['id']);
                            $dbServerHostAndIp = $dbServerDetails['data']['host'] . ':' . $dbServerDetails['data']['port'];
                            $serversOptions[$server['id']] = $dbServerHostAndIp;
                        }
                    } else {
                        $serversOptions[0] = 'localhost:3306';
                    }


                    $form->addElement('select', 'installation_database_server_id', [
                        'label' => 'Database Server',
                        'multiOptions' => $serversOptions,
                        'value' => pm_Settings::get('installation_database_server_id'),
                        'required' => true,
                    ]);
                }*/

        $chooseInstallationDatabaseDriver = pm_Settings::get('installation_database_driver_allow_customers');
        $chooseInstallationDatabaseDriver = trim($chooseInstallationDatabaseDriver);

        if (pm_Session::getClient()->isAdmin()) {
            $chooseInstallationDatabaseDriver = 'yes';
        }
        if ($chooseInstallationDatabaseDriver == '' || !$chooseInstallationDatabaseDriver) {
            $chooseInstallationDatabaseDriver = 'yes';
        }

        if ($chooseInstallationDatabaseDriver == 'yes') {
            $form->addElement('select', 'installation_database_driver', [
                'label' => 'Database Driver',
                'multiOptions' => ['mysql' => 'MySQL', 'sqlite' => 'SQLite'],
                'value' => pm_Settings::get('installation_database_driver'),
                'required' => true,
            ]);
        } else {
            $form->addElement('hidden', 'installation_database_driver', [
                'value' => pm_Settings::get('installation_database_driver')
            ]);
        }

        $httpHost = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            $httpHost = $_SERVER['HTTP_HOST'];
            $exp = explode(":", $httpHost);
            if (isset($exp[0])) {
                $httpHost = $exp[0];
            }
        }

        $client = pm_Session::getClient();
        $adminEmail = $client->getProperty('email');
        $adminPassword = $this->_getRandomPassword(12, true);
        $adminUsername = str_replace(strrchr($adminEmail, '@'), '', $adminEmail);
        $adminUsername = $adminUsername . '_' . $this->_getRandomPassword(9);

        $form->addElement('text', 'installation_email', [
            'label' => 'Admin Email',
            'value' => $adminEmail,
        ]);
        $form->addElement('text', 'installation_username', [
            'label' => 'Admin Username',
            'value' => $adminUsername,
        ]);
        $form->addElement('text', 'installation_password', [
            'label' => 'Admin Password',
            'value' => $adminPassword,
        ]);

        $form->addControlButtons([
            'cancelLink' => pm_Context::getModulesListUrl(),
        ]);

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

            $post = $this->getRequest()->getPost();

            $currentVersion = $this->_getCurrentVersion();
            if ($currentVersion == 'unknown') {
                $this->_updateApp();
                $this->_updateTemplates();
            }

            $currentVersion = $this->_getCurrentVersion();
            if ($currentVersion == 'unknown') {
                $this->_status->addMessage('error', 'Can\'t install app because not releases found.');
                $this->_helper->json(['redirect' => pm_Context::getBaseUrl() . 'index.php/index/index']);
            }

            $domain = new pm_Domain($post['installation_domain']);
            if (!$domain->getName()) {
                $this->_status->addMessage('error', 'Please, select domain to install microweber.');
                $this->_helper->json(['redirect' => pm_Context::getBaseUrl() . 'index.php/index/install']);
            }

            $hostingManager = new Modules_Microweber_HostingManager();
            $hostingManager->setDomainId($domain->getId());
            $hostingProperties = $hostingManager->getHostingProperties();
            if (!$hostingProperties['php']) {
                $this->_status->addMessage('error', 'PHP is not activated on selected domain.');
                $this->_helper->json(['redirect' => pm_Context::getBaseUrl() . 'index.php/index/install']);
            }

            $phpHandler = $hostingManager->getPhpHandler($hostingProperties['php_handler_id']);
            if (version_compare($phpHandler['version'], '7.2', '<')) {
                $this->_status->addMessage('error', 'PHP version ' . $phpHandler['version'] . ' is not supported by Microweber. You must install PHP 7.2 or newer.');
                $this->_helper->json(['redirect' => pm_Context::getBaseUrl() . 'index.php/index/install']);
            }

            if (!pm_Session::getClient()->isAdmin()) {
                $dbServerIdDefault = pm_Settings::get('installation_database_server_id');
                $dbServerIdDefault = trim($dbServerIdDefault);

                if (!empty($dbServerIdDefault)) {
                    $post['installation_database_server_id'] = $dbServerIdDefault;
                }
            }

            if (!$this->devMode) {

                $task = new Modules_Microweber_TaskInstall();
                $task->setParam('domainId', $domain->getId());
                $task->setParam('domainName', $domain->getName());
                $task->setParam('domainDisplayName', $domain->getDisplayName());
                $task->setParam('type', $post['installation_type']);
                $task->setParam('databaseDriver', $post['installation_database_driver']);
                //$task->setParam('databaseServerId', $post['installation_database_server_id']);
                $task->setParam('path', $post['installation_folder']);
                $task->setParam('template', $post['installation_template']);
                $task->setParam('language', $post['installation_language']);
                $task->setParam('email', $post['installation_email']);
                $task->setParam('username', $post['installation_username']);
                $task->setParam('password', $post['installation_password']);

                if (pm_Session::getClient()->isAdmin()) {
                    // Run global
                    $this->taskManager->start($task, NULL);
                } else {
                    // Run for domain
                    $this->taskManager->start($task, $domain);
                }

                $this->_helper->json(['redirect' => pm_Context::getBaseUrl() . 'index.php/index/index']);
            } else {

                $newInstallation = new Modules_Microweber_Install();
                $newInstallation->setDomainId($post['installation_domain']);
                $newInstallation->setType($post['installation_type']);
                $newInstallation->setDatabaseDriver($post['installation_database_driver']);
                //$newInstallation->setDatabaseServerId($post['installation_database_server_id']);
                $newInstallation->setPath($post['installation_folder']);
                $newInstallation->setTemplate($post['installation_template']);
                $newInstallation->setLanguage($post['installation_language']);

                if (!empty($post['installation_email'])) {
                    $newInstallation->setEmail($post['installation_email']);
                }

                if (!empty($post['installation_username'])) {
                    $newInstallation->setUsername($post['installation_username']);
                }

                if (!empty($post['installation_password'])) {
                    $newInstallation->setPassword($post['installation_password']);
                }

                var_dump($newInstallation->run());
                die();
            }

        }

        $this->view->form = $form;
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/install.js');
    }

    public function checkinstallpathAction()
    {

        $json = [];
        $json['found_app'] = false;
        $json['found_thirdparty_app'] = false;

        try {

            $domainId = (int)$_GET['installation_domain'];
            $domainInstallPath = trim($_GET['installation_folder']);

            $domain = Modules_Microweber_Domain::getUserDomainById($domainId);
            $fileManager = new pm_FileManager($domain->getId());

            if (!empty($domainInstallPath)) {
                $domainInstallPath = $domain->getDocumentRoot() . '/' . $domainInstallPath;
            } else {
                $domainInstallPath = $domain->getDocumentRoot();
            }

            if ($fileManager->fileExists($domainInstallPath . '/index.php')) {
                $json['found_thirdparty_app'] = true;
            }

            if ($fileManager->fileExists($domainInstallPath . '/index.html')) {
                $json['found_thirdparty_app'] = true;
            }

            if ($fileManager->fileExists($domainInstallPath . '/vendor')) {
                $json['found_thirdparty_app'] = true;
            }

            if ($fileManager->fileExists($domainInstallPath . '/config/microweber.php')) {
                $json['found_app'] = true;
            }

            $json['domain_found'] = true;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
            $json['domain_found'] = false;
        }

        die(json_encode($json, JSON_PRETTY_PRINT));
    }

    public function startupAction()
    {
        if (!pm_Session::getClient()->isAdmin()) {
            return $this->_redirect('index/error?type=permission');
        }

        $release = Modules_Microweber_Config::getRelease();

        $this->view->pageTitle = $this->_moduleName;

        $this->view->latestVersion = 'unknown';
        $this->view->currentVersion = $this->_getCurrentVersion();
        $this->view->latestDownloadDate = $this->_getCurrentVersionLastDownloadDateTime();

        if ($this->view->currentVersion !== 'unknown') {
            return $this->_redirect('index');
        }

        if (!empty($release)) {
            $this->view->latestVersion = $release['version'];
        }

        $this->view->updateLink = pm_Context::getBaseUrl() . 'index.php/index/update';

        ///$this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/startup.js');
    }

    public function settingsAction()
    {
        if ($this->view->limitations['app_installations_freeze']) {
            return $this->_redirect('index/index');
        }

        if (!pm_Session::getClient()->isAdmin()) {
            return $this->_redirect('index/error?type=permission');
        }

        $this->view->pageTitle = $this->_moduleName . ' - Settings';

        $form = new pm_Form_Simple();

        $form->addElement('select', 'installation_template', [
            'label' => 'Default Installation template',
            'multiOptions' => Modules_Microweber_Config::getSupportedTemplates(),
            'value' => pm_Settings::get('installation_template'),
            'required' => true,
        ]);

        $form->addElement('select', 'installation_language', [
            'label' => 'Default Installation language',
            'multiOptions' => Modules_Microweber_Config::getSupportedLanguages(),
            'value' => pm_Settings::get('installation_language'),
            'required' => true,
        ]);

        $form->addElement('radio', 'installation_type', [
            'label' => 'Default Installation type',
            'multiOptions' =>
                [
                    'default' => 'Default',
                    'symlink' => 'Sym-Linked (saves a big amount of disk space)'
                ],
            'value' => pm_Settings::get('installation_type'),
            'required' => true,
        ]);

        $form->addElement('radio', 'installation_type_allow_customers', [
            'label' => 'Allow customers to choose installation type',
            'multiOptions' =>
                [
                    'yes' => 'Yes',
                    'no' => 'No'
                ],
            'value' => pm_Settings::get('installation_type_allow_customers'),
            'required' => true,
        ]);

        $form->addElement('radio', 'installation_database_driver_allow_customers', [
            'label' => 'Allow customers to choose installation database driver',
            'multiOptions' =>
                [
                    'yes' => 'Yes',
                    'no' => 'No'
                ],
            'value' => pm_Settings::get('installation_database_driver_allow_customers'),
            'required' => true,
        ]);

        $form->addElement('select', 'installation_database_driver', [
            'label' => 'Database Driver',
            'multiOptions' => ['mysql' => 'MySQL', 'sqlite' => 'SQLite'],
            'value' => pm_Settings::get('installation_database_driver'),
            'required' => true,
        ]);

        /*
        $dbManager = new Modules_Microweber_DatabaseManager();

        if (pm_Session::getClient()->isAdmin()) {
            $servers = $dbManager->getDatabaseServers();
        } else {
            $servers = $dbManager->getDatabaseServersDefault();
        }

        $serversOptions = [];
        if ($servers) {
            foreach($servers as $server) {
                if ($server['data']['type'] != 'mysql') {
                    continue;
                }
                $dbServerDetails = $dbManager->getDatabaseServerById($server['id']);
                $dbServerHostAndIp = $dbServerDetails['data']['host'].':'.$dbServerDetails['data']['port'];
                $serversOptions[$server['id']] = $dbServerHostAndIp;
            }
        } else {
            $serversOptions[0] = 'localhost:3306';
        }

        $form->addElement('select', 'installation_database_server_id', [
            'label' => 'Database Server',
            'multiOptions' => $serversOptions,
            'value' => pm_Settings::get('installation_database_server_id'),
            'required' => true,
        ]);
        */

      /*  $form->addElement('text', 'update_app_url', [
            'label' => 'Update App Url',
            'value' => Modules_Microweber_Config::getUpdateAppUrl(),
            //'required' => true,
        ]);*/

        $form->addElement('select', 'update_app_channel', [
            'label' => 'Update App Channel',
            'multiOptions' => ['stable' => 'Last stable version', 'dev' => 'Last developer version'],
            'value' => pm_Settings::get('update_app_channel'),
            'required' => true,
        ]);

        $form->addElement('select', 'update_app_automatically', [
            'label' => 'Update App Automatically',
            'multiOptions' => ['no' => 'No', 'yes' => 'Yes, when new version is available'],
            'value' => pm_Settings::get('update_app_automatically'),
            'required' => true,
        ]);

        $form->addElement('text', 'whmcs_url', [
            'label' => 'WHMCS Url',
            'value' => pm_Settings::get('whmcs_url'),
            //'required' => true,
        ]);

        $form->addElement('select', 'allow_reseller_whitelabel', [
            'label' => 'Allow resellers to use their own White Label?',
            'multiOptions' => ['yes' => 'Yes', 'no' => 'No'],
            'value' => pm_Settings::get('allow_reseller_whitelabel'),
            'required' => true,
        ]);

        $form->addControlButtons([
            'cancelLink' => pm_Context::getModulesListUrl(),
        ]);

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

            $success = true;

            // Form proccessing
            pm_Settings::set('installation_language', $form->getValue('installation_language'));
            pm_Settings::set('installation_template', $form->getValue('installation_template'));
            pm_Settings::set('installation_type', $form->getValue('installation_type'));
            pm_Settings::set('installation_database_driver', $form->getValue('installation_database_driver'));
            //      pm_Settings::set('installation_database_server_id', $form->getValue('installation_database_server_id'));
            pm_Settings::set('installation_type_allow_customers', $form->getValue('installation_type_allow_customers'));
            pm_Settings::set('installation_database_driver_allow_customers', $form->getValue('installation_database_driver_allow_customers'));

            //pm_Settings::set('update_app_url', $form->getValue('update_app_url'));
            pm_Settings::set('update_app_channel', $form->getValue('update_app_channel'));
            pm_Settings::set('update_app_automatically', $form->getValue('update_app_automatically'));
            pm_Settings::set('whmcs_url', $form->getValue('whmcs_url'));
            pm_Settings::set('allow_reseller_whitelabel', $form->getValue('allow_reseller_whitelabel'));


            $release = Modules_Microweber_Config::getRelease();
            if (empty($release)) {
                $this->_status->addMessage('error', 'Can\'t get latest version from selected download url.');
                $success = false;
            }

            Modules_Microweber_WhmcsConnector::updateWhmcsConnector();

            if ($success) {
                $this->_status->addMessage('info', 'Settings was successfully saved.');
            }

            $this->_helper->json(['redirect' => pm_Context::getBaseUrl() . 'index.php/index/settings']);
        }

        $this->view->form = $form;
    }

    public function listDataAction()
    {
        $list = $this->_getDomainsList();

        $this->_helper->json($list->fetchData());
    }

    public function domaindetailsAction()
    {
        $json = [];
        $domainFound = false;
        $domainId = (int)$_POST['domain_id'];
        $websiteUrl = $_POST['website_url'];
        $domainDocumentRoot = $_POST['document_root'];
        $domainDocumentRootHash = md5($domainDocumentRoot);

        try {
            $domain = Modules_Microweber_Domain::getUserDomainById($domainId);
        } catch (Exception $e) {
            $domainFound = false;
        }
        if ($domain) {
            $domainFound = true;
        }

        if ($domainFound) {

            $json['languages'] = Modules_Microweber_Config::getSupportedLanguages();

            $json['admin_email'] = 'No information';
            $json['admin_username'] = 'No information';
            $json['admin_password'] = 'No information';
            $json['admin_url'] = 'admin';
            $json['language'] = 'en';

            $domainSettings = $domain->getSetting('mw_settings_' . $domainDocumentRootHash);
            $domainSettings = unserialize($domainSettings);

            if (isset($domainSettings['admin_email']) && !empty($domainSettings['admin_email'])) {
                $json['admin_email'] = $domainSettings['admin_email'];
            }

            if (isset($domainSettings['admin_username']) && !empty($domainSettings['admin_username'])) {
                $json['admin_username'] = $domainSettings['admin_username'];
            }

            if (isset($domainSettings['admin_password']) && !empty($domainSettings['admin_password'])) {
                $json['admin_password'] = $domainSettings['admin_password'];
            }

            if (isset($domainSettings['admin_url']) && !empty($domainSettings['admin_url'])) {
                $json['admin_url'] = $domainSettings['admin_url'];
            }

            if (isset($domainSettings['language']) && !empty($domainSettings['language'])) {
                $json['language'] = $domainSettings['language'];
            }

            $json['domain_id'] = $domainId;

        } else {
            $json['message'] = 'Domain not found.';
            $json['status'] = 'error';
        }

        $this->_helper->json($json);
    }

    public function domainupdateAction()
    {
        $json = [];
        $domainFound = false;
        $domainId = (int)$_POST['domain_id'];
        $adminUsername = $_POST['admin_username'];
        $adminPassword = $_POST['admin_password'];
        $adminEmail = $_POST['admin_email'];
        $adminUrl = $_POST['admin_url'];
        // $websiteUrl = $_POST['website_url'];
        $websiteLanguage = $_POST['website_language'];
        $domainDocumentRoot = $_POST['document_root'];
        $domainDocumentRootHash = md5($domainDocumentRoot);

        try {
            $domain = Modules_Microweber_Domain::getUserDomainById($domainId);
        } catch (Exception $e) {
            $domainFound = false;
        }
        if ($domain) {
            $domainFound = true;
        }

        if ($domainFound) {

            $artisan = new Modules_Microweber_ArtisanExecutor();
            $artisan->setDomainId($domain->getId());
            $artisan->setDomainDocumentRoot($domainDocumentRoot);

            // Change Language
            $artisan->exec([
                'microweber:option',
                'language',
                $websiteLanguage,
                'website'
            ]);

            // Change Admin Details
            $commandAdminDetailsResponse = $artisan->exec([
                'microweber:change-admin-details',
                '--username=' . $adminUsername,
                '--newPassword=' . $adminPassword,
                '--newEmail=' . $adminEmail
            ]);

            // Update Server details
            $artisan->exec([
                'microweber:server-set-config',
                '--key=admin_url',
                '--value=' . $adminUrl
            ]);

            $artisan->exec([
                'microweber:server-set-config',
                '--key=site_lang',
                '--value=' . $websiteLanguage
            ]);

            // Cache clear
            $artisan->exec([
                'microweber:server-clear-cache'
            ]);

            $successChange = false;
            if (isset($commandAdminDetailsResponse['stdout'])) {
                if (strpos(strtolower($commandAdminDetailsResponse['stdout']), 'done') !== false) {
                    $successChange = true;
                }
            }

            if ($successChange) {

                $domainSettings = $domain->getSetting('mw_settings_' . $domainDocumentRootHash);
                $domainSettings = unserialize($domainSettings);

                $domainSettings['admin_email'] = $adminEmail;
                $domainSettings['admin_password'] = $adminPassword;
                $domainSettings['admin_url'] = $adminUrl;
                $domainSettings['website_language'] = $websiteLanguage;

                $domain->setSetting('mw_settings_' . $domainDocumentRootHash, serialize($domainSettings));

                $json['message'] = 'Domain settings are updated successfully.';
                $json['status'] = 'success';
            } else {
                $json['message'] = 'Can\'t change domain settings.';
                $json['status'] = 'error';
            }
        } else {
            $json['message'] = 'Domain not found.';
            $json['status'] = 'error';
        }

        $this->_helper->json($json);
    }

    public function domainloginAction()
    {
        $domainFound = false;
        $domainId = (int)$_POST['domain_id'];
        $websiteUrl = $_POST['website_url'];
        $domainDocumentRoot = $_POST['document_root'];

        try {
            $domain = Modules_Microweber_Domain::getUserDomainById($domainId);
        } catch (Exception $e) {
            $domainFound = false;
        }
        if ($domain) {
            $domainFound = true;
        }

        if (!$domainFound) {
            return $this->_redirect('index/error?type=permission');
        }

        $fileManager = new pm_FileManager($domain->getId());

        // Check microweber isntallation is STANDALONE
        $loginWithTokenModulePathShared = Modules_Microweber_Config::getAppSharedPath() . 'userfiles/modules/login_with_token/';
        $loginWithTokenModulePath = $domainDocumentRoot . '/userfiles/modules/login_with_token/';

        if (!$fileManager->isDir($loginWithTokenModulePath)) {
            // Must copy the login with token plugin
            try {
                if (!$fileManager->isDir('login_with_token')) {
                    $fileManager->mkdir('login_with_token');
                }
            } catch (Exception $e) {
                return $this->_redirect('index/index?message=Can\'t login to this website. '. $e->getMessage());
            }
            try {
                if (!$fileManager->fileExists($loginWithTokenModulePath . 'index.php')) {
                    $fileManager->copyFile($loginWithTokenModulePathShared . 'index.php', $loginWithTokenModulePath . 'index.php');
                }
            } catch (Exception $e) {
                return $this->_redirect('index/index?message=Can\'t login to this website. '. $e->getMessage());
            }
            try {
                if (!$fileManager->fileExists($loginWithTokenModulePath . 'config.php')) {
                    $fileManager->copyFile($loginWithTokenModulePathShared . 'config.php', $loginWithTokenModulePath . 'config.php');
                }
            } catch (Exception $e) {
                return $this->_redirect('index/index?message=Can\'t login to this website. '. $e->getMessage());
            }
            try {
                if (!$fileManager->fileExists($loginWithTokenModulePath . 'functions.php')) {
                    $fileManager->copyFile($loginWithTokenModulePathShared . 'functions.php', $loginWithTokenModulePath . 'functions.php');
                }
            } catch (Exception $e) {
                return $this->_redirect('index/index?message=Can\'t login to this website. '. $e->getMessage());
            }
        }

        $artisan = new Modules_Microweber_ArtisanExecutor();
        $artisan->setDomainId($domain->getId());
        $artisan->setDomainDocumentRoot($domainDocumentRoot);

       /// Modules_Microweber_Reinstall::run($domain->getId(), $domainDocumentRoot);

        $commandResponse = $artisan->exec(['microweber:module', 'login_with_token', '1']);
        if (!empty($commandResponse['stdout'])) {
            if (strpos($commandResponse['stdout'], 'PHP Warning:') !== false) {

                $task = new Modules_Microweber_TaskDomainAppInstallationRepair();
                $task->setParam('domainId', $domainId);
                $task->setParam('domainDocumentRoot', $domainDocumentRoot);
                $this->taskManager->start($task, NULL);

                return $this->_redirect('index/index?message=Application is broken. Reinstalling app... please, try again later.');
            }
        }

        $commandResponse = $artisan->exec(['cache:clear']);
        $commandResponse = $artisan->exec(['microweber:generate-admin-login-token']);

        if (!empty($commandResponse['stdout'])) {

            $token = $commandResponse['stdout'];
            $token = str_replace(' ', false, $token);
            $token = str_replace(PHP_EOL, false, $token);
            $token = trim($token);

            if (strpos($token, 'SQLSTATE') !== false) {
                Modules_Microweber_Log::debug('Can\'t login to website: ' . $commandResponse['stdout']);
                return $this->_redirect('index/index?message=Can\'t login to this domain. The app installation is broken.');
            }

            if ((strpos($token, 'isnotdefined') !== false) || (strpos($token, 'Couldnotopeninputfile') !== false)) {

                $task = new Modules_Microweber_TaskDomainAppInstallationRepair();
                $task->setParam('domainId', $domainId);
                $task->setParam('domainDocumentRoot', $domainDocumentRoot);
                $this->taskManager->start($task, NULL);

                return $this->_redirect('index/index?message=Login module is not found. Reinstalling login module... please, try again after one minute.');
            }

            return $this->_redirect('http://' . $websiteUrl . '/api/user_login?secret_key=' . $token);
        }

        return $this->_redirect('index/index');
    }

    public function errorAction()
    {
        $this->view->pageTitle = $this->_moduleName . ' - Error';
        $this->view->errorMessage = 'You don\'t have permissions to see this page.';
    }

    private function _isWhiteLabelAllowed()
    {
        $isAllowedWhiteLabel = false;

        if (pm_Session::getClient()->isReseller()) {

            $allowResellerWhiteLabel = pm_Settings::get('allow_reseller_whitelabel');

            if (!$allowResellerWhiteLabel || empty($allowResellerWhiteLabel)) {
                $allowResellerWhiteLabel = 'yes';
            }

            if ($allowResellerWhiteLabel == 'yes') {
                $isAllowedWhiteLabel = true;
            }
        }

        if (pm_Session::getClient()->isAdmin()) {
            $isAllowedWhiteLabel = true;
        }

        return $isAllowedWhiteLabel;
    }

    private function _getRandomPassword($length = 16, $complex = false)
    {
        $alphabet = 'ghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        if ($complex) {
            $alphabet .= '-=~!@#$%^&*()_+,./<>?;:[]{}\|';
        }

        $pass = [];
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }

    private function _getLicensedView()
    {
        $this->view->isLicensed = false;
        $this->view->isMwLicensed = false;
        $this->view->showRegisteredDetails = true;

        $licenseData = pm_Settings::get('wl_license_data');
        if (!empty($licenseData)) {
            $licenseData = json_decode($licenseData, TRUE);
            if ($licenseData['status'] == 'active') {

                $this->view->isLicensed = true;
                $this->view->isMwLicensed = true;
                $this->view->dueOn = $licenseData['due_on'];
                $this->view->registeredName = $licenseData['registered_name'];
                $this->view->relName = $licenseData['rel_name'];
                $this->view->regOn = date("Y-m-d", strtotime($licenseData['reg_on']));
                $this->view->billingCycle = $licenseData['billing_cycle'];

            }
        }

        $pmLicense = pm_License::getAdditionalKey();
        if ($pmLicense && isset($pmLicense->getProperties('product')['name'])) {
            $this->view->isLicensed = true;
        }

        if ($this->_isWhiteLabelAllowed()) {
            $this->view->showRegisteredDetails = false;
        }

        if (pm_Session::getClient()->isAdmin()) {
            $this->view->showRegisteredDetails = true;
        }
    }

    private function _checkAppIsLicensed()
    {
        $isLicensed = false;

        $licenseData = pm_Settings::get('wl_license_data');
        if (!empty($licenseData)) {
            $licenseData = json_decode($licenseData, TRUE);
            if ($licenseData['status'] == 'active') {
                $isLicensed = true;
            }
        }

        $pmLicense = pm_License::getAdditionalKey();
        if ($pmLicense && isset($pmLicense->getProperties('product')['name'])) {
            $isLicensed = true;
        }

        return $isLicensed;
    }

    private function _checkAppSettingsIsCorrect()
    {
        $currentVersion = $this->_getCurrentVersion();
        if ($currentVersion == 'unknown') {

            if (empty(pm_Settings::get('installation_language'))) {
                pm_Settings::set('installation_language', 'en');
            }

            if (empty(pm_Settings::get('installation_type'))) {
                pm_Settings::set('installation_type', 'symlink');
            }

            if (empty(pm_Settings::get('installation_database_driver'))) {
                pm_Settings::set('installation_database_driver', 'sqlite');
            }

            header("Location: " . pm_Context::getBaseUrl() . 'index.php/index/startup');
            exit;
        }
    }

    private function _getCurrentVersionLastDownloadDateTime()
    {
        $manager = new pm_ServerFileManager();

        $version_file = $manager->fileExists(Modules_Microweber_Config::getAppSharedPath() . 'version.txt');
        if ($version_file) {
            $version = filectime(Modules_Microweber_Config::getAppSharedPath() . 'version.txt');
            if ($version) {
                return date('Y-m-d H:i:s', $version);
            }
        }
    }

    private function _getCurrentVersion()
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

    private function _queueRefreshDomains()
    {
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {

            if (!$domain->hasHosting()) {
                continue;
            }

            $task = new Modules_Microweber_TaskDomainAppInstallationScan();
            $task->setParam('domainId', $domain->getId());
            $this->taskManager->start($task, NULL);
        }
    }

    private function _getAppInstallations()
    {
        $data = [];

        foreach (Modules_Microweber_Domain::getDomains() as $domain) {

            if (!$domain->hasHosting()) {
                continue;
            }

            $domainInstallations = $domain->getSetting('mwAppInstallations');
            $domainInstallations = json_decode($domainInstallations, true);

            if (empty($domainInstallations)) {

                $task = new Modules_Microweber_TaskDomainAppInstallationScan();
                $task->setParam('domainId', $domain->getId());
                $this->taskManager->start($task, NULL);

                continue;
            }

            foreach ($domainInstallations as $installation) {

                $pleskMainUrl = '//' . $_SERVER['HTTP_HOST'];

                $loginToWebsite = '<form method="post" class="js-open-settings-domain" action="' . pm_Context::getBaseUrl() . 'index.php/index/domainlogin" target="_blank">';
                $loginToWebsite .= '<a href="' . $pleskMainUrl . $installation['manageDomainUrl'] . '" class="btn btn-info"><img src="' . pm_Context::getBaseUrl() . 'images/publish.png" alt=""> Manage Domain</a>';
                $loginToWebsite .= '<input type="hidden" name="website_url" value="' . $installation['domainNameUrl'] . '" />';
                $loginToWebsite .= '<input type="hidden" name="domain_id" value="' . $domain->getId() . '" />';
                $loginToWebsite .= '<input type="hidden" name="document_root" value="' . $installation['appInstallation'] . '" />';
                $loginToWebsite .= '<button type="submit" name="login" value="1" class="btn btn-info"><img src="' . pm_Context::getBaseUrl() . 'images/open-in-browser.png" alt=""> Login to website</button>';
                $loginToWebsite .= '<button type="button" onclick="openSetupForm(this)" name="setup" value="1" class="btn btn-info"><img src="' . pm_Context::getBaseUrl() . 'images/setup.png" /> Setup</button>';
                $loginToWebsite .= '</form>';

                $data[] = [
                    'domain' => '<a href="http://' . $installation['domainNameUrl'] . '" target="_blank">' . $installation['domainNameUrl'] . '</a> ',
                    'created_date' => $installation['domainCreation'],
                    'type' => $installation['installationType'],
                    'app_version' => $installation['appVersion'],
                    'document_root' => $installation['appInstallation'],
                    'active' => ($installation['domainIsActive'] ? 'Yes' : 'No'),
                    'action' => $loginToWebsite
                ];
            }

        }

        return $data;
    }

    private function _getDomainsList()
    {

        $options = [
            'pageable' => true,
            'defaultSortField' => 'active',
            'defaultSortDirection' => pm_View_List_Simple::SORT_DIR_DOWN,
        ];

        $list = new pm_View_List_Simple($this->view, $this->_request, $options);
        $list->setData($this->_getAppInstallations());
        $list->setColumns([
            // pm_View_List_Simple::COLUMN_SELECTION,
            'domain' => [
                'title' => 'Domain',
                'noEscape' => true,
                'searchable' => true,
            ],
            'created_date' => [
                'title' => 'Created at',
                'noEscape' => true,
                'searchable' => true,
            ],
            'type' => [
                'title' => 'Type',
                'noEscape' => true,
                'sortable' => false,
            ],
            'app_version' => [
                'title' => 'App Version',
                'noEscape' => true,
                'sortable' => false,
            ],
            'active' => [
                'title' => 'Active',
                'noEscape' => true,
                'sortable' => false,
            ],
            'document_root' => [
                'title' => 'Document Root',
                'noEscape' => true,
                'sortable' => false,
            ],
            'action' => [
                'title' => 'Action',
                'noEscape' => true,
                'searchable' => false,
            ]
        ]);

        // Take into account listDataAction corresponds to the URL /list-data/
        $list->setDataUrl(['action' => 'list-data']);

        return $list;
    }


}
