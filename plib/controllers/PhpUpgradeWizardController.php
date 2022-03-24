<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

include_once 'BasePluginController.php';

class PhpUpgradeWizardController extends pm_Controller_Action
{
    private $currentStep = 1;
    private $maxSteps = 6;
    public function init()
    {
        parent::init();
        $this->currentStep = $this->getRequest()->get('step');
        $this->nextStep = $this->currentStep + 1;

        $this->view->step = $this->currentStep;
        $this->view->maxSteps = $this->maxSteps;
        $this->view->nextStep = $this->nextStep;
        $this->view->nextStepLink = pm_Context::getBaseUrl() . 'index.php/index/phpUpgradeWizard?step=' . ($this->currentStep + 1);

    }
    public function indexAction()
    {
        $this->view->pageTitle = $this->_moduleName . ' - PHP Upgrade wizard';

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/php-upgrade-wizard.js');
    }

    public function getoutdateddomainsAction()
    {
        $status = Modules_Microweber_Helper::canIUpdateNewVersionOfApp();
        $this->_helper->json($status);
    }

}