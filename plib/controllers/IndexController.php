<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class IndexController extends Modules_Microweber_BasepluginController
{
    public function init()
    {
        parent::init();

        $showButtons = Modules_Microweber_Helper::showMicroweberButtons();
        if ($showButtons == false) {
            $this->view->tabs = [];
            return $this->_redirect('error/index?type=permission');
        }

        // Init tabs for all actions
        $this->view->tabs = [];
        $this->view->tabs[] = [
            'title' => 'Installations',
            'action' => 'index'
        ];

        $showInstallTab = true;
        if ($this->view->limitations['app_installations_freeze']) {
            if (pm_Session::getClient()->isClient()) {
                $showInstallTab = false;
            }
        }

        if ($showInstallTab) {
            $this->view->tabs[] = [
                'title' => 'Install',
                'action' => 'install'
            ];
        }

        if (pm_Session::getClient()->isAdmin()) {
            $this->view->tabs[] = [
                'title' => 'Versions',
                'action' => 'versions'
            ];
        }

        if ($this->_isWhiteLabelAllowed()) {
            $checkAppIsLicensed = $this->_checkAppIsLicensed();
            $showWhitelabelTab = false;
            if ($checkAppIsLicensed && pm_Session::getClient()->isReseller()) {
                $showWhitelabelTab = true;
            }
            if (pm_Session::getClient()->isAdmin()) {
                $showWhitelabelTab = true;
            }
            if ($showWhitelabelTab) {
                $this->view->tabs[] = [
                    'title' => 'White Label',
                    'action' => 'whitelabel'
                ];
            }
        }

        if (pm_Session::getClient()->isAdmin()) {
            $this->view->tabs[] = [
                'title' => 'Settings',
                'action' => 'settings',
            ];
        }

        $this->view->brandName = Modules_Microweber_WhiteLabel::getBrandName();
        $this->view->sharedAppRequirements = Modules_Microweber_Helper::getRequiredPhpVersionOfSharedApp();
    }

    public function indexAction()
    {
        if (pm_Session::getClient()->isAdmin()) {
            Modules_Microweber_Helper::checkAndFixSchedulerTasks();
        }

        $this->_checkAppSettingsIsCorrect();

        $this->view->errorMessage = false;
        if (isset($_GET['message'])) {
           $this->view->errorMessage =  $_GET['message'];
        }

        $this->view->indexLink = pm_Context::getBaseUrl() . 'index.php/index';
        $this->view->refreshDomainLink = pm_Context::getBaseUrl() . 'index.php/index/refreshdomains';
        $this->view->pageTitle = $this->_moduleName . ' - Installations';
        $this->view->list = $this->_getDomainsList();

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/index.js');
    }


    public function refreshdomainsAction()
    {
        $this->_queueRefreshDomains();

        return $this->_redirect('index/index?queue_refresh=1');
    }

    public function versionsAction()
    {
        if ($this->view->limitations['app_installations_freeze']) {
            return $this->_redirect('index/index');
        }

        if (!pm_Session::getClient()->isAdmin()) {
            return $this->_redirect('index/error?type=permission');
        }

        $this->view->showPhpVersionWizard = pm_Settings::get('show_php_version_wizard', false);
        if ($this->view->showPhpVersionWizard) {
            $this->view->phpUpgradeWizardLink = pm_Context::getBaseUrl() . 'index.php/phpupgradewizard/index';
        }

        $templateVersions = pm_Settings::get('mw_templates_versions');
        if (!empty($templateVersions)) {
            $templateVersions = json_decode($templateVersions, true);
        } else {
            $templateVersions = [];
        }

        $this->_checkAppSettingsIsCorrect();

        $mwRelease = Modules_Microweber_Config::getRelease();
        $mwReleaseVersion = Modules_Microweber_Helper::getContentFromUrl($mwRelease['version_url']);

        $availableTemplatesWithVersions = [];
        $availableTemplates = Modules_Microweber_Config::getSupportedTemplates();
        if (!empty($availableTemplates)) {
            foreach ($availableTemplates as $availableTemplateTargetDir=>$availableTemplate){
                if (isset($templateVersions[$availableTemplateTargetDir])) {
                    $availableTemplatesWithVersions[$availableTemplateTargetDir] = [
                        'version'=>$templateVersions[$availableTemplateTargetDir],
                        'name'=>$availableTemplate
                    ];
                } else {
                    $availableTemplatesWithVersions[$availableTemplateTargetDir] = [
                        'version'=>false,
                        'name'=>$availableTemplate
                    ];
                }
            }
        }

        $this->view->pageTitle = $this->_moduleName . ' - Versions';

        $this->view->latestVersion = 'unknown';
        $this->view->currentVersion = $this->_getCurrentVersion();
        $this->view->latestDownloadDate = $this->_getCurrentVersionLastDownloadDateTime();
        $this->view->availableTemplates = $availableTemplatesWithVersions;

        if (!empty($mwReleaseVersion)) {
            $this->view->latestVersion = $mwReleaseVersion;
        }

        $this->view->updateLink = pm_Context::getBaseUrl() . 'index.php/task/appupdatecheck';

        $this->view->pluginUpdateLink = false;
        if (is_file('/usr/local/psa/admin/sbin/modules/microweber/download_and_update_plugin.sh')) {
            $this->view->pluginUpdateLink = pm_Context::getBaseUrl() . 'index.php/index/pluginupdate';
        }

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/versions.js');
    }

    public function checkserverdiskspaceAction()
    {
        $json = [];
        $json['is_ok'] = Modules_Microweber_Helper::isAvailableDiskSpace();
        $json['required_disk_space'] = Modules_Microweber_Helper::getRequiredDiskSpace() . "GB";
        $json['available_disk_space'] = round(Modules_Microweber_Helper::getAvailableDiskSpace(), 2);

        $this->_helper->json($json);
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
            'placeholder' => 'https://example.com/favicon.ico'
        ]);
        $form->addElement('text', 'wl_admin_login_url', [
            'label' => 'Admin login - White Label URL?',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_admin_login_url'),
            'placeholder' => 'https://example.com'
        ]);
        $form->addElement('text', 'wl_contact_page', [
            'label' => 'Enable support links?',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_contact_page'),
            'placeholder' => 'https://example.com/contact-us'
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
            'placeholder' => 'https://example.com/logo.png'
        ]);
        $form->addElement('text', 'wl_logo_live_edit_toolbar', [
            'label' => 'Logo for Live-Edit toolbar (size: 50x50px)',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_logo_live_edit_toolbar'),
            'placeholder' => 'https://example.com/logo.png'
        ]);
        $form->addElement('text', 'wl_logo_login_screen', [
            'label' => 'Logo for Login screen (max width: 290px)',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_logo_login_screen'),
            'placeholder' => 'https://example.com/logo.png'
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
            'placeholder' => 'https://example.com/logo-invert.png'
        ]);
        $form->addElement('text', 'wl_plesk_logo_app', [
            'label' => 'Plesk Logo App',
            'value' => Modules_Microweber_WhiteLabelSettings::get('wl_plesk_logo_app'),
            'placeholder' => 'https://example.com/logo.png'
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

            if (isset($licenseCheck['active']) && $licenseCheck['active']) {

                pm_Settings::set('wl_key', $formMwKey->getValue('wl_key'));
                pm_Settings::set('wl_license_data', json_encode($licenseCheck));

                Modules_Microweber_WhiteLabel::setEnabled();

            } else {
                Modules_Microweber_WhiteLabel::setDisabled();

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

            Modules_Microweber_WhiteLabel::setDisabled();

            pm_Settings::set('wl_key', false);
            pm_Settings::set('wl_license_data', false);

            Modules_Microweber_Helper::stopTasks(['task_whitelabelbrandinremove']);

            $taskManager = new pm_LongTask_Manager();

            // Start new task
            $task = new Modules_Microweber_Task_WhiteLabelBrandingRemove();
            $taskManager->start($task, NULL);
        }

        if (!$savingWhiteLabelKey && $this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

            Modules_Microweber_WhiteLabel::setEnabled();

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

/*
    public function activatesymlinkingAction()
    {

        $task = new Modules_Microweber_Task_DisableSelinux();
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

        $requestParams = $this->getRequest()->getParams();

        if (isset($requestParams['dom_id'])
            && isset($requestParams['site_id'])) {
            $domain = pm_Domain::getByDomainId($requestParams['dom_id']);
            if (pm_Session::getClient()->hasAccessToDomain($domain->getId())) {
                $installationQueue = [];
                $installationQueue['installation_domain'] = $requestParams['dom_id'];
                $installationQueue['installation_folder'] = '';
                $response = $this->installMicroweberOnDomainQueue($installationQueue);
                if (isset($response['redirect'])) {
                    return $this->_redirect($response['redirect']);
                }
            }
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
        $domainsCount = 0;
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {

            if (!$domain->hasHosting()) {
                continue;
            }

            $domainId = $domain->getId();
            $domainName = $domain->getDisplayName();

            $domainsSelect[$domainId] = $domainName;
            $domainsCount++;
        }

        $this->view->hasServicePlan = false;
        $this->view->hasDomains = false;
        if ($domainsCount > 0) {
            $this->view->hasDomains = true;
        }

        $hostingManager = new Modules_Microweber_HostingManager();
        $servicePlans = $hostingManager->getServicePlans();

        $this->view->showInstallForm = false;
        $this->view->isPhpSupported = false;
        $supportedPlans = [];
        $notSupportedPlans = [];

        if (!empty($servicePlans)) {
            foreach ($servicePlans as &$hostingPlan) {
                if(isset($hostingPlan['hosting']['vrt_hst']['property'])) {
                    foreach ($hostingPlan['hosting']['vrt_hst']['property'] as $property) {
                        if ($property['name'] == 'php_handler_id') {
                            $phpHandler = $hostingManager->getPhpHandler($property['value']);
                            $hostingPlan['php-handler'] = $phpHandler;
                            if (version_compare($phpHandler['version'], $this->view->sharedAppRequirements['mwReleasePhpVersion'], '>=')) {
                                $supportedPlans[] = $hostingPlan;
                            } else {
                                $editPlanLink = '/admin/customer-service-plan/edit/id/' . $hostingPlan['id'];

                                $notSupportedPlans[] = [
                                    'name'=>$hostingPlan['name'],
                                    'php_version'=>$phpHandler['version'],
                                    'id'=>$hostingPlan['id'],
                                    'edit_link'=> $editPlanLink,
                                ];
                            }
                        }
                    }
                }
            }
        }

        $this->view->notSupportedPlans = $notSupportedPlans;
        if (count($supportedPlans) == count($servicePlans)) {
            $this->view->isPhpSupported = true;
            $this->view->showInstallForm = true;
        }

        if (!$this->view->hasDomains) {
            if (!empty($servicePlans)) {
                $this->view->hasServicePlan = true;
            }
        }

        $form = new pm_Form_Simple();

        $form->addElement('select', 'installation_domain', [
            'label' => 'Domain',
            'multiOptions' => $domainsSelect,
            'required' => true,
        ]);

        // service plan link
        $createNewServicePlanLink = "/admin/customer-service-plan/create";
        if (pm_Session::getClient()->isClient()) {
            $createNewServicePlanLink = "/admin/customer-service-plan/create";
        }
        $this->view->createNewServicePlanLink = $createNewServicePlanLink;

        // add domain link
        $createNewDomainLink = "/admin/domain/add-domain";
        if (pm_Session::getClient()->isClient()) {
            $createNewDomainLink = "/smb/web/add-domain";
        }
        $this->view->createNewDomainLink = $createNewDomainLink;

        // subscription link
        $createNewSubscriptionLink = "/admin/subscription/create";
        if (pm_Session::getClient()->isClient()) {
            $createNewSubscriptionLink = "/smb/web/subscription";
        }
        $this->view->createNewSubscriptionLink = $createNewSubscriptionLink;

        $form->addElement(
            new Zend_Form_Element_Note('create_new_domain_link',
                ['value' => '<a href="'.$createNewDomainLink.'" style="margin-left:175px;top: -15px;position:relative;">Create New Domain</a>']
            )
        );

        $installationLanguagesOptions = [];
        $installationLanguagesOptions['none'] = 'Don\'t install language (let user to choose)';
        $supportedLanguages = Modules_Microweber_Config::getSupportedLanguages();
        $installationLanguagesOptions = array_merge($installationLanguagesOptions, $supportedLanguages);

        $form->addElement('select', 'installation_language', [
            'label' => 'Installation Language',
            'multiOptions' => $installationLanguagesOptions,
            'value' => pm_Settings::get('installation_language'),
            'required' => true,
        ]);

        $installationTemplatesOptions = [];
        $installationTemplatesOptions['none'] = 'Don\'t install template (let user to choose)';
        $supportedTemplates = Modules_Microweber_Config::getSupportedTemplates();
        $installationTemplatesOptions = array_merge($installationTemplatesOptions, $supportedTemplates);

        $form->addElement('select', 'installation_template', [
            'label' => 'Installation Template',
            'multiOptions' => $installationTemplatesOptions,
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
            $responseDomainQueue = $this->installMicroweberOnDomainQueue($post);
            if (isset($responseDomainQueue['redirect'])) {
                return $this->_redirect($responseDomainQueue['redirect']);
            }
            if (isset($responseDomainQueue['error'])) {
                $this->_status->addMessage('error', $responseDomainQueue['error']);
            }
        }

        $this->view->form = $form;
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/install.js');
    }

    public function installMicroweberOnDomainQueue($post)
    {
        $currentVersion = $this->_getCurrentVersion();
        if ($currentVersion == 'unknown') {
            $this->_updateApp();
            $this->_updateTemplates();
        }

        $currentVersion = $this->_getCurrentVersion();
        if ($currentVersion == 'unknown') {
            return [
                'redirect' => 'index/index',
                'error' => 'Can\'t install app because not releases found.'
            ];
        }

        $domain = new pm_Domain($post['installation_domain']);
        if (!$domain->getName()) {
            return [
                'redirect' => 'index/install',
                'error' => 'Please, select domain to install microweber.'
            ];
        }

        $hostingManager = new Modules_Microweber_HostingManager();
        $hostingManager->setDomainId($domain->getId());
        $hostingProperties = $hostingManager->getHostingProperties();
        if (!$hostingProperties['php']) {
            return [
                'redirect' => 'index/install',
                'error' => 'PHP is not activated on selected domain.'
            ];
        }

        $phpHandler = $hostingManager->getPhpHandler($hostingProperties['php_handler_id']);
        if (version_compare($phpHandler['version'], $this->view->sharedAppRequirements['mwReleasePhpVersion'], '<')) {

            return [
                'redirect' =>  'index/install',
                'error' => 'Domain '.$post['installation_domain'].' has PHP ' . $phpHandler['version'] . ' and is not supported by Microweber. You must install PHP '.$this->view->sharedAppRequirements['mwReleasePhpVersion'].' or newer.'
            ];
        }

        if (!pm_Session::getClient()->isAdmin()) {
            $dbServerIdDefault = pm_Settings::get('installation_database_server_id');
            $dbServerIdDefault = trim($dbServerIdDefault);

            if (!empty($dbServerIdDefault)) {
                $post['installation_database_server_id'] = $dbServerIdDefault;
            }
        }

        // Save pending installation
        $installationDomainPath = $domain->getName();
        $installationDirPath = $domain->getDocumentRoot();
        $installationType = 'Standalone';
        if (!empty($post['installation_folder'])) {
            $installationDirPath = $domain->getDocumentRoot() . '/' . $post['installation_folder'];
            $installationDomainPath = $domain->getName() . '/' . $post['installation_folder'];
        }
        if ($post['installation_type'] == 'symlink') {
            $installationType = 'Symlinked';
        }

        Modules_Microweber_Domain::addAppInstallation($domain, [
            'domainNameUrl' => $installationDomainPath,
            'domainCreation' => $domain->getProperty('cr_date'),
            'installationType' => $installationType,
            'appVersion' => '-',
            'appInstallation' => $installationDirPath,
            'domainIsActive' => true,
            'manageDomainUrl' => '',
            'pending' => true,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        pm_Settings::set('mw_installations_count',  (Modules_Microweber_LicenseData::getAppInstallationsCount() + 1));

        $task = new Modules_Microweber_Task_DomainAppInstall();
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

        return ['redirect' => 'index/index'];

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

        $mwRelease = Modules_Microweber_Config::getRelease();
        $mwReleaseVersion = Modules_Microweber_Helper::getContentFromUrl($mwRelease['version_url']);

        $this->view->pageTitle = $this->_moduleName;

        $this->view->latestVersion = 'unknown';
        $this->view->currentVersion = $this->_getCurrentVersion();
        $this->view->latestDownloadDate = $this->_getCurrentVersionLastDownloadDateTime();

        if (!empty($mwRelease)) {
            $this->view->latestVersion = $mwReleaseVersion;
        }

        $this->view->updateLink = pm_Context::getBaseUrl() . 'index.php/task/appupdatecheck';

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
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

        $installationTemplatesOptions = [];
        $installationTemplatesOptions['none'] = 'Don\'t install template (let user to choose)';
        $supportedTemplates = Modules_Microweber_Config::getSupportedTemplates();
        $installationTemplatesOptions = array_merge($installationTemplatesOptions, $supportedTemplates);

        $form->addElement('select', 'installation_template', [
            'label' => 'Default Installation template',
            'multiOptions' => $installationTemplatesOptions,
            'value' => pm_Settings::get('installation_template'),
            'required' => true,
        ]);

        $installationLanguagesOptions = [];
        $installationLanguagesOptions['none'] = 'Don\'t install language (let user to choose)';
        $supportedLanguages = Modules_Microweber_Config::getSupportedLanguages();
        $installationLanguagesOptions = array_merge($installationLanguagesOptions, $supportedLanguages);

        $form->addElement('select', 'installation_language', [
            'label' => 'Default Installation language',
            'multiOptions' => $installationLanguagesOptions,
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

        $form->addElement('radio', 'installation_notifications', [
            'label' => 'Show installing app notifications',
            'multiOptions' =>
                [
                    'no' => 'No',
                    'yes' => 'Yes'
                ],
            'value' => pm_Settings::get('installation_notifications'),
            'required' => false,
        ]);

        $form->addElement('radio', 'installation_ssl', [
            'label' => 'Instantly install SSL certificate',
            'multiOptions' =>
                [
                    'no' => 'No',
                    'yes' => 'Yes'
                ],
            'value' => pm_Settings::get('installation_ssl'),
            'required' => false,
        ]);

        $installationTypeAllowCustomers = pm_Settings::get('installation_type_allow_customers');
        if (!$installationTypeAllowCustomers) {
            $installationTypeAllowCustomers = 'no';
        }
        $form->addElement('radio', 'installation_type_allow_customers', [
            'label' => 'Allow customers to choose installation type',
            'multiOptions' =>
                [
                    'yes' => 'Yes',
                    'no' => 'No'
                ],
            'value' => $installationTypeAllowCustomers,
            'required' => true,
        ]);

        $installationDatabaseDriverAllowCustomers = pm_Settings::get('installation_database_driver_allow_customers');
        if (!$installationDatabaseDriverAllowCustomers) {
            $installationDatabaseDriverAllowCustomers = 'no';
        }
        $form->addElement('radio', 'installation_database_driver_allow_customers', [
            'label' => 'Allow customers to choose installation database driver',
            'multiOptions' =>
                [
                    'yes' => 'Yes',
                    'no' => 'No'
                ],
            'value' => $installationDatabaseDriverAllowCustomers,
            'required' => true,
        ]);

        $form->addElement('select', 'installation_database_driver', [
            'label' => 'Database Driver',
            'multiOptions' => ['mysql' => 'MySQL', 'sqlite' => 'SQLite'],
            'value' => pm_Settings::get('installation_database_driver'),
            'required' => true,
        ]);

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

        $form->addElement('select', 'update_templates_automatically', [
            'label' => 'Update Templates Automatically',
            'multiOptions' => ['no' => 'No', 'yes' => 'Yes, when new version is available'],
            'value' => pm_Settings::get('update_templates_automatically'),
            'required' => true,
        ]);

        $form->addElement('select', 'website_manager', [
            'label' => 'Website manager',
            'multiOptions' => ['none', 'microweber_saas' => 'Microweber SaaS', 'whmcs' => 'WHMCS'],
            'value' => pm_Settings::get('website_manager'),
            'required' => true,
        ]);

        $form->addElement('text', 'website_manager_url', [
            'label' => 'Website Manager Url',
            'value' => pm_Settings::get('website_manager_url'),
            //'required' => true,
        ]);

        $form->addElement('select', 'use_package_manager_urls_from_website_manager', [
            'label' => 'Get package manager urls from website manager',
            'multiOptions' => ['no' => 'No', 'yes' => 'Yes'],
            'value' => pm_Settings::get('use_package_manager_urls_from_website_manager'),
            'required' => false,
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
            pm_Settings::set('installation_ssl', $form->getValue('installation_ssl'));
            pm_Settings::set('installation_notifications', $form->getValue('installation_notifications'));
            pm_Settings::set('installation_database_driver', $form->getValue('installation_database_driver'));
            //      pm_Settings::set('installation_database_server_id', $form->getValue('installation_database_server_id'));
            pm_Settings::set('installation_type_allow_customers', $form->getValue('installation_type_allow_customers'));
            pm_Settings::set('installation_database_driver_allow_customers', $form->getValue('installation_database_driver_allow_customers'));

            //pm_Settings::set('update_app_url', $form->getValue('update_app_url'));
            pm_Settings::set('update_app_channel', $form->getValue('update_app_channel'));
            pm_Settings::set('update_app_automatically', $form->getValue('update_app_automatically'));
            pm_Settings::set('update_templates_automatically', $form->getValue('update_templates_automatically'));


            pm_Settings::set('website_manager', $form->getValue('website_manager'));
            pm_Settings::set('website_manager_url', $form->getValue('website_manager_url'));
            pm_Settings::set('use_package_manager_urls_from_website_manager', $form->getValue('use_package_manager_urls_from_website_manager'));

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

            $domainSettings = Modules_Microweber_Domain::getMwOption($domain, 'mw_settings_' . $domainDocumentRootHash);

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

    public function domainapperrorlogAction()
    {
        $domainId = (int)$_POST['domain_id'];
        $appInstallationPath = $_POST['document_root'];

        try {
            $domain = Modules_Microweber_Domain::getUserDomainById($domainId);
        } catch (Exception $e) {
            $this->_helper->json(['message' => $e->getMessage()]);
            return;
        }

        if (!$domain) {
            $this->_helper->json(['message' => 'Domain not found.']);
            return;
        }

        $fileManager = new \pm_FileManager($domain->getId());

        if (!$fileManager->fileExists($appInstallationPath . '/userfiles/modules')) {
            $this->_helper->json(['message' => 'This is not microweber installation']);
            return;
        }

        $log = 'No error log found.';
        if ($fileManager->fileExists($appInstallationPath . '/storage/logs/laravel.log')) {
            $log = $fileManager->fileGetContents($appInstallationPath . '/storage/logs/laravel.log');
        }
        $this->_helper->json(['status'=>'success', 'log' => $log]);
    }

    public function domainappuninstallAction()
    {
        $domainId = (int)$_POST['domain_id'];
        $appInstallationPath = $_POST['document_root'];

        try {
            $domain = Modules_Microweber_Domain::getUserDomainById($domainId);
        } catch (Exception $e) {
            $this->_helper->json(['message'=>$e->getMessage()]);
            return;
        }

        if (!$domain) {
            $this->_helper->json(['message'=>'Domain not found.']);
            return;
        }

        $fileManager = new \pm_FileManager($domain->getId());

        if (!$fileManager->fileExists($appInstallationPath . '/userfiles/modules')) {
            $this->_helper->json(['message'=>'This is not microweber installation']);
            return;
        }

        if (!$fileManager->isDir($appInstallationPath)) {
            $this->_helper->json(['message'=>'Domain directory not found.']);
            return;
        }

        $strposCheck = false;
        if (strpos($appInstallationPath, $domain->getDocumentRoot()) !== false) {
            $strposCheck = true;
        }

        if (!$strposCheck) {
            $this->_helper->json(['message'=>'Domain invalid directory.']);
            return;
        }

        $filesForDelete = [
          'src',
          'resources',
          'vendor',
          'storage',
          'userfiles',
          'bootstrap',
          'config',
          'database',
          '.env',
          '.htaccess',
          'artisan',
          'composer.json',
          'favicon.ico',
          'index.php',
          'version.txt',
        ];

        if (!empty($filesForDelete)) {
            foreach ($filesForDelete as $deleteFile) {

                // Delete domain file
                pm_ApiCli::callSbin('filemng', [
                    $domain->getSysUserLogin(),
                    'exec',
                    $domain->getDocumentRoot(),
                    'rm',
                    '-rf',
                    $appInstallationPath . '/' . $deleteFile

                ], pm_ApiCli::RESULT_FULL);

            }
        }

        Modules_Microweber_Domain::removeAppInstallation($domain, $appInstallationPath);

        Modules_Microweber_Helper::stopTasks(['task_domainappinstallationscan']);

        $task = new Modules_Microweber_Task_DomainAppInstallationScan();
        $task->hidden = true;
        $task->setParam('domainId', $domain->getId());
        $this->taskManager->start($task, NULL);

        pm_Settings::set('mw_installations_count',  (Modules_Microweber_LicenseData::getAppInstallationsCount() - 1));

        sleep(3);

        $this->_helper->json(['status'=>'success']);
    }

    public function domainupdateAction()
    {
        $json = [];
        $domain = false;
        $domainId = (int)$_POST['domain_id'];
        $adminPassword = $_POST['admin_password'];
        $adminUsername = $_POST['admin_username'];
        $adminEmail = $_POST['admin_email'];
        $adminUrl = $_POST['admin_url'];
        $websiteLanguage = $_POST['website_language'];
        $domainDocumentRoot = $_POST['document_root'];
        $domainDocumentRootHash = md5($domainDocumentRoot);

        if (empty(trim($adminPassword))) {
            $json['message'] = 'Admin password is required.';
            $json['status'] = 'error';
            $this->_helper->json($json);
            return;
        }

        if (empty(trim($adminUrl))) {
            $json['message'] = 'Admin url is required.';
            $json['status'] = 'error';
            $this->_helper->json($json);
            return;
        }

        if (empty(trim($adminEmail))) {
            $json['message'] = 'Admin email is required.';
            $json['status'] = 'error';
            $this->_helper->json($json);
            return;
        }

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $json['message'] = 'Admin email is not valid.';
            $json['status'] = 'error';
            $this->_helper->json($json);
            return;
        }

        try {
            $domain = Modules_Microweber_Domain::getUserDomainById($domainId);
        } catch (Exception $e) {
            // Domain not found
        }

        if (!$domain) {
            $json['message'] = 'Domain not found.';
            $json['status'] = 'error';
            $this->_helper->json($json);
            return;
        }

        $currentDomainSettings = Modules_Microweber_Domain::getMwOption($domain, 'mw_settings_' . $domainDocumentRootHash);

        $changes = [];

        $artisan = new Modules_Microweber_ArtisanExecutor();
        $artisan->setDomainId($domain->getId());
        $artisan->setDomainDocumentRoot($domainDocumentRoot);

        $changeAdminDetails = false;

        // If admin email is changed
        $currentAdminEmail = false;
        if (isset($currentDomainSettings['admin_email'])) {
            $currentAdminEmail = $currentDomainSettings['admin_email'];
        }
        if ($adminEmail !== $currentAdminEmail) {
            $changeAdminDetails = true;
        }
        // If admin password is changed
        $currentAdminPassword = false;
        if (isset($currentDomainSettings['admin_password'])) {
            $currentAdminPassword = $currentDomainSettings['admin_password'];
        }
        if ($adminPassword !== $currentAdminPassword) {
            $changeAdminDetails = true;
        }

        // If admin username is changed
        $currentAdminUsername = false;
        if (isset($currentDomainSettings['admin_username'])) {
            $currentAdminUsername = $currentDomainSettings['admin_username'];
        }
//        if ($adminUsername !== $currentAdminUsername) {
//            $changeAdminDetails = true;
//        }

        // If change admin details
        if ($changeAdminDetails) {
            $artisan->exec([
                'microweber:change-admin-details',
                '--username=' . $currentAdminUsername,
                '--new_password=' . $adminPassword,
                '--new_email=' . $adminEmail
            ]);
            $changes[] = 'Admin details are changed.';
        }

        // If language is changed
        $currentWebsiteLanguage = false;
        if (isset($currentDomainSettings['language'])) {
            $currentWebsiteLanguage = $currentDomainSettings['language'];
        }
        if ($websiteLanguage !== $currentWebsiteLanguage) {
            $artisan->exec([
                'microweber:server-set-config',
                '--config=microweber',
                '--key=site_lang',
                '--value=' . $websiteLanguage
            ]);
            $artisan->exec([
                'microweber:server-set-config',
                '--config=app',
                '--key=locale',
                '--value=' . $websiteLanguage
            ]);
            $changes[] = 'Website language are changed.';
        }

        // If admin url is changed
        $currentAdminUrl = false;
        if (isset($currentDomainSettings['admin_url'])) {
            $currentAdminUrl = $currentDomainSettings['admin_url'];
        }
        if ($adminUrl !== $currentAdminUrl) {
            // Update Server details
           $artisan->exec([
                'microweber:server-set-config',
                '--config=microweber',
                '--key=admin_url',
                '--value=' . $adminUrl
            ]);
            $changes[] = 'Admin url are changed.';
        }

        if (empty($changes)) {
            $json['message'] = 'No new changes have been made.';
            $json['status'] = 'error';
            $this->_helper->json($json);
            return;
        }

        $artisan->exec([
            'microweber:reload-database'
        ]);

        // Cache clear
        $artisan->exec([
            'microweber:server-clear-cache'
        ]);

        $currentDomainSettings['admin_email'] = $adminEmail;
        $currentDomainSettings['admin_password'] = $adminPassword;
        $currentDomainSettings['admin_url'] = $adminUrl;
        $currentDomainSettings['language'] = $websiteLanguage;

        Modules_Microweber_Domain::setMwOption($domain, 'mw_settings_' . $domainDocumentRootHash, $currentDomainSettings);

        $json['message'] = '';
        foreach ($changes as $message) {
            $json['message'] .= $message . '<br />';
        }

        $json['status'] = 'success';
        $json['changes'] = $changes;

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
        $loginWithTokenModulePathShared = Modules_Microweber_Config::getAppSharedPath() . 'userfiles/modules/login-with-token/';
        $loginWithTokenModulePath = $domainDocumentRoot . '/userfiles/modules/login-with-token/';

        if (!$fileManager->isDir($loginWithTokenModulePath)) {
            // Must copy the login with token plugin
            try {
                if (!$fileManager->isDir('login-with-token')) {
                    $fileManager->mkdir('login-with-token');
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

        $commandResponse = $artisan->exec(['microweber:module', 'login-with-token', '1']);
        if (!empty($commandResponse['stdout'])) {
            if (strpos($commandResponse['stdout'], 'PHP Warning:') !== false) {

                $task = new Modules_Microweber_Task_DomainAppInstallationRepair();
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

                $task = new Modules_Microweber_Task_DomainAppInstallationRepair();
                $task->setParam('domainId', $domainId);
                $task->setParam('domainDocumentRoot', $domainDocumentRoot);
                $this->taskManager->start($task, NULL);

                return $this->_redirect('index/index?message=Login module is not found. Reinstalling login module... please, try again after one minute.');
            }

            return $this->_redirect('http://' . $websiteUrl . '/api/login_with_secret_key?key=' . $token);
        }

        return $this->_redirect('index/index');
    }

    public function pluginupdateAction()
    {
        $this->view->pageTitle = $this->_moduleName . ' - Plugin Update';

        $this->view->updatePluginLink = pm_Context::getBaseUrl() . 'index.php/index/runupdate';
        $this->view->currentPluginVersion = '-';
        $this->view->latestPluginVersion = '-';
        $this->view->latestPluginUpdateDate = '-';

        // Latest plugin version
        $latestMeta = Modules_Microweber_PluginUpdate::getLatestMeta();
        if (isset($latestMeta['version'])) {
            $this->view->latestPluginVersion = $latestMeta['version'];
        }

        // Current Plugin version
        $metaXml = pm_Context::getPlibDir() . 'meta.xml';

        $manager = new pm_ServerFileManager();
        $xmlContent = $manager->fileGetContents($metaXml);

        $this->view->latestPluginUpdateDate = date('Y-m-d H:i:s', filemtime($metaXml));

        $xmlDecoded = simplexml_load_string($xmlContent);
        $xmlDecoded = json_decode(json_encode($xmlDecoded), true);

        if (isset($xmlDecoded['version'])) {
            $this->view->currentPluginVersion = $xmlDecoded['version'];
        }

    }

    public function runupdateAction()
    {

        $task = new Modules_Microweber_Task_UpdatePlugin();
        $this->taskManager->start($task, NULL);

        return $this->_redirect('index/pluginupdate');

    }

    public function errorAction()
    {
        $this->view->pageTitle = $this->_moduleName . ' - Error';
        $this->view->errorMessage = 'You don\'t have permissions to see this page.';
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
            if (isset($licenseData['active']) && $licenseData['active']) {

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
        if (!Modules_Microweber_WhiteLabel::isEnabled()) {
            return false;
        }

        $isLicensed = false;

        // Microweber license
        $licenseData = pm_Settings::get('wl_license_data');
        if (!empty($licenseData)) {
            $licenseData = json_decode($licenseData, TRUE);
            if (isset($licenseData['active']) && $licenseData['active']) {
                $isLicensed = true;
            }
        }

        // Plesk license
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
                pm_Settings::set('installation_language', 'en_US');
            }

            if (empty(pm_Settings::get('installation_template'))) {
                pm_Settings::set('installation_template', 'big');
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
        return Modules_Microweber_Helper::getCurrentVersionOfApp();
    }

    private function _queueRefreshDomains()
    {
        Modules_Microweber_Helper::stopTasks(['task_domainappinstallationscan']);

        $task = new Modules_Microweber_Task_DomainAppInstallationScan();
        $this->taskManager->start($task, NULL);
    }

    private function _getAppInstallations()
    {
        $data = [];

        $installationsCount = 0;

        $sfm = new pm_ServerFileManager();
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {

            if (!$domain->hasHosting()) {
                continue;
            }

            $domainInstallations = Modules_Microweber_Domain::getMwOption($domain, 'mwAppInstallations');

            if (empty($domainInstallations)) {
                continue;
            }

            foreach ($domainInstallations as $installation) {

                $createdAt = $installation['domainCreation'];

                if (isset($installation['created_at'])) {
                    $createdAt = $installation['created_at'];
                }

                if (isset($installation['error']) && $installation['error'] == true) {

                    $data[] = [
                        'domain' => '<a href="http://' . $installation['domainNameUrl'] . '" target="_blank">' . $installation['domainNameUrl'] . '</a> ',
                        'created_date' => $createdAt,
                        'type' => $installation['installationType'],
                        'app_version' => $installation['appVersion'],
                        'document_root' => $installation['appInstallation'],
                        'active' => ($installation['domainIsActive'] ? 'Yes' : 'No'),
                        'action' => '<form><div style="color:#f66e6e;">Error when installing the application.</div><input type="hidden" value="'.$domain->getId().'" name="domain_id"><input type="hidden" value="'.$installation['appInstallation'].'" name="document_root"><a onclick="removeDomainAppInstallation(this)" class="btn btn-info">Remove</a>&nbsp;&nbsp;<a onclick="openErrorLogDomainAppInstallation(this)" class="btn btn-info">Open Error Log</a></form>'
                    ];

                    $installationsCount++;
                    continue;
                }

                if (isset($installation['pending']) && $installation['pending'] == true) {
                    $data[] = [
                        'domain' => '<a href="http://' . $installation['domainNameUrl'] . '" target="_blank">' . $installation['domainNameUrl'] . '</a> ',
                        'created_date' => $createdAt,
                        'type' => $installation['installationType'],
                        'app_version' => $installation['appVersion'],
                        'document_root' => $installation['appInstallation'],
                        'active' => ($installation['domainIsActive'] ? 'Yes' : 'No'),
                        'action' => '<img src="'.pm_Context::getBaseUrl() . 'images/loading.gif'.'" /> Installing... <script>setTimeout(function () {window.location.href=window.location.href}, 60000);</script>'
                    ];

                    $installationsCount++;
                    continue;
                }

                if (!$sfm->fileExists($installation['appInstallation'])) {
                    continue;
                }

                $pleskMainUrl = '//' . $_SERVER['HTTP_HOST'];

                $loginToWebsite = '<form method="post" class="js-open-settings-domain" action="' . pm_Context::getBaseUrl() . 'index.php/index/domainlogin" target="_blank">';
                $loginToWebsite .= '<a href="' . $pleskMainUrl . $installation['manageDomainUrl'] . '" class="btn btn-info"><img src="' . pm_Context::getBaseUrl() . 'images/publish.png" alt=""> Manage Domain</a>';
                $loginToWebsite .= '<input type="hidden" name="website_url" value="' . $installation['domainNameUrl'] . '" />';
                $loginToWebsite .= '<input type="hidden" name="domain_id" value="' . $domain->getId() . '" />';
                $loginToWebsite .= '<input type="hidden" name="document_root" value="' . $installation['appInstallation'] . '" />';
                $loginToWebsite .= '<button type="submit" name="login" value="1" class="btn btn-info js-website-login"><img src="' . pm_Context::getBaseUrl() . 'images/open-in-browser.png" alt=""> Login to website</button>';
                $loginToWebsite .= '<button type="button" onclick="openSetupForm(this)" name="setup" value="1" class="btn btn-info js-website-setup"><img src="' . pm_Context::getBaseUrl() . 'images/setup.png" /> Setup</button>';
                $loginToWebsite .= '</form>';

                $data[] = [
                    'domain' => '<a href="http://' . $installation['domainNameUrl'] . '" target="_blank">' . $installation['domainNameUrl'] . '</a> ',
                    'created_date' => $createdAt,
                    'type' => $installation['installationType'],
                    'app_version' => $installation['appVersion'],
                    'document_root' => $installation['appInstallation'],
                    'active' => ($installation['domainIsActive'] ? 'Yes' : 'No'),
                    'action' => $loginToWebsite
                ];

                $installationsCount++;
            }

        }

        // Update installations count if is broken
        if (pm_Session::getClient()->isAdmin()) {
            if ($this->view->limitations['app_installations_count'] != $installationsCount) {
                pm_Settings::set('mw_installations_count', $installationsCount);
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
