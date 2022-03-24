<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

include_once 'BasepluginController.php';

class PhpupgradewizardController extends BasepluginController
{
    private $currentStep = 0;
    private $maxSteps = 6;
    private $latestRequirements = 6;

    public function init()
    {
        parent::init();

        $this->_generateSteps();

        $this->view->phpUpgradeLink = '/admin/php-handler/list';

        $this->latestRequirements = Modules_Microweber_Helper::getLatestRequiredPhpVersionOfApp();
        $this->view->latestAppVersion = $this->latestRequirements['mwReleaseVersion'];
        $this->view->requiredPhpVersion = $this->latestRequirements['mwReleasePhpVersion'];

    }

    public function indexAction()
    {
        $this->view->pageTitle = $this->_moduleName . ' - PHP Upgrade wizard';

    }


    public function step1Action()
    {
        $this->currentStep = 1;
        $this->_generateSteps();

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/php-upgrade-wizard/step1.js');

    }

        public function checkserversupportphpversionAction()
    {
        $serverManager = new Modules_Microweber_ServerManager();
        $phpHandlers = $serverManager->getPhpHandlers();

        $supportedPhpVersions = [];
        foreach ($phpHandlers as $phpHandler) {

            if ($phpHandler['status'] !== 'ok') {
                continue;
            }

            if (version_compare($phpHandler['version'], $this->latestRequirements['mwReleasePhpVersion'], '>')) {
                $supportedPhpVersions[] = $phpHandler;
            }
        }

        $isSupported = false;
        if (!empty($supportedPhpVersions)) {
            $isSupported = true;
        }

        $this->_helper->json([
            'supported'=>$isSupported,
            'supported_php_versions'=>$supportedPhpVersions,
        ]);

    }

    public function step2Action()
    {
        $this->currentStep = 2;
        $this->_generateSteps();

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/php-upgrade-wizard/step2.js');

    }

    public function checkhostingplanssupportphpversionAction()
    {
        $serverManager = new Modules_Microweber_ServerManager();
        $phpHandlers = $serverManager->getHostingPlans();

    }

    public function step3Action()
    {
        $this->currentStep = 3;
        $this->_generateSteps();

    }

    public function getoutdateddomainsAction()
    {
        $status = Modules_Microweber_Helper::canIUpdateNewVersionOfApp();
        $this->_helper->json($status);
    }

    private function _generateSteps()
    {
        $this->nextStep = $this->currentStep + 1;

        $this->view->currentStep = $this->currentStep;
        $this->view->maxSteps = $this->maxSteps;
        $this->view->nextStep = $this->nextStep;
        $this->view->nextStepLink = pm_Context::getBaseUrl() . 'index.php/phpupgradewizard/step-' . ($this->currentStep + 1);
    }

}