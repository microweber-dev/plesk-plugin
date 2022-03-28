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
        $supportedPhpVersions = $this->_getSupportedPhpVersions();

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
        $hostingManager = new Modules_Microweber_HostingManager();
        $hostingPlans = $hostingManager->getHostingPlans();

        $isSupported = false;
        $supportedPlans = [];
        if (!empty($hostingPlans)) {
            foreach ($hostingPlans as &$hostingPlan) {
                if(isset($hostingPlan['hosting']['vrt_hst']['property'])) {
                    foreach ($hostingPlan['hosting']['vrt_hst']['property'] as $property) {
                        if ($property['name'] == 'php_handler_id') {
                            $phpHandler = $hostingManager->getPhpHandler($property['value']);
                            $hostingPlan['php-handler'] = $phpHandler;
                            if (version_compare($phpHandler['version'], $this->latestRequirements['mwReleasePhpVersion'], '>')) {
                                $supportedPlans[] = $hostingPlan;
                            }
                        }
                    }
                }
            }
        }

        if (count($supportedPlans) == count($hostingPlans)) {
            $isSupported = true;
        }

        $this->_helper->json([
            'supported'=>$isSupported,
            'supported_plans'=>$supportedPlans,
            'hosting_plans'=>$hostingPlans,
            'supported_php_versions'=>$this->_getSupportedPhpVersions()
        ]);
        
    }

    public function updatehostingplansphpversionAction()
    {
        $phpHandlerId = $this->getRequest()->get('php_handler_id');
        $hostingPlanIds = $this->getRequest()->get('hosting_plan_ids');

        $task = new Modules_Microweber_TaskUpdateHostingPlansPhpHandler();
        $task->setParam('php_handler_id', $phpHandlerId);
        $task->setParam('hosting_plan_ids', $hostingPlanIds);
        $this->taskManager->start($task, NULL);

        $this->_helper->json([
            'updated' => true,
        ]);
    }

    public function step3Action()
    {
        $this->currentStep = 3;
        $this->_generateSteps();

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/php-upgrade-wizard/step3.js');

    }

    public function updatewebsitesphpversionAction()
    {

    }

    public function step4Action()
    {
        $this->currentStep = 4;
        $this->_generateSteps();

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/php-upgrade-wizard/step4.js');


    }

    public function getoutdateddomainsAction()
    {
        $status = Modules_Microweber_Helper::canIUpdateNewVersionOfApp();
        $this->_helper->json($status);
    }

    private function _getSupportedPhpVersions()
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

        return $supportedPhpVersions;
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