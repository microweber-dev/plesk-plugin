<?php

abstract class BasepluginController extends pm_Controller_Action {

    protected $devMode = false;
    protected $taskManager = NULL;
    protected $_moduleName = 'Microweber';

    public function init()
    {
        parent::init();

        if (is_null($this->taskManager)) {
            $this->taskManager = new pm_LongTask_Manager();
        }

        $this->view->moduleUpdateLink = pm_Context::getBaseUrl() . 'index.php/index/update';
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

    protected function _isWhiteLabelAllowed()
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

}