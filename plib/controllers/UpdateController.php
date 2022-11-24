<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class UpdateController extends Modules_Microweber_BasepluginController
{
    public function init()
    {
        parent::init();

        $this->view->tabs = [];

    }

    public function indexAction()
    {
        if (!pm_Session::getClient()->isAdmin()) {
            return $this->_redirect('index/error?type=permission');
        }

        $this->view->headScript()->appendFile(pm_Context::getBaseUrl() . 'js/jquery.min.js');
        $this->view->pageTitle = $this->_moduleName . ' - Update';

    }

    public function startAction()
    {
        $messages = [];

        $messages[] = ['message'=>'Update channel: ' . ucfirst(pm_Settings::get('update_app_channel'))];

        $whmcsPackageManagerUrls = pm_Settings::get('use_package_manage_urls_from_whmcs');
        if ($whmcsPackageManagerUrls == 'yes') {
            $messages[] = ['message' => 'Getting data from WHMCS package managers urls'];
        } else {
            $messages[] = ['message' => 'Getting data from official package managers'];
        }

        $mwRelease = Modules_Microweber_Config::getRelease();

        if (!isset($mwRelease['version_url']) || empty($mwRelease['version_url'])) {

            $messages[] = ['error'=>true, 'message'=>'Error code: 444 - Can\'t get the download url from releases.'];

            $this->_helper->json([
                'messages' => $messages,
                'error' => true,
            ]);

            return;
        }

        $messages[] = ['message'=>'Getting new releases...'];

        $mwReleaseVersion = Modules_Microweber_Helper::getContentFromUrl($mwRelease['version_url']);
        if (empty($mwReleaseVersion)) {
            $messages[] = ['error'=>true, 'message'=>'Error code: 445 - Can\'t get the version from releases.'];

            $this->_helper->json([
                'messages' => $messages,
                'error' => true,
            ]);

            return;
        }

        $messages[] = ['message'=>'Last release version is '.$mwReleaseVersion.''];

        if (!Modules_Microweber_Helper::isAvailableDiskSpace()) {

            $messages[] = ['error'=>true, 'message'=>'No disk space available on the server.'];
            $messages[] = ['error'=>true, 'message'=>'Error code: 446 - Can\'t download the app.'];

            $this->_helper->json([
                'messages' => $messages,
                'error' => true,
            ]);

            return;
        }

        $messages[] = ['message'=>'Checking disk space..'];
        $messages[] = ['message'=>'Disk space is ok.'];

        $messages[] = ['message'=>'Getting template urls...'];

        $task = new Modules_Microweber_Task_TemplatesDownload();
        $getTemplates = $task->getTemplatesUrl();

        if (empty($getTemplates)) {
            $messages[] = ['error'=>true, 'message'=>'Can\'t get download urls for templates.'];

            $this->_helper->json([
                'messages' => $messages,
                'error' => true,
            ]);

            return;
        }

        $messages[] = ['message'=>count($getTemplates) . ' templates found.'];
        $messages[] = ['message'=>'Checking new version of templates...'];

        $templateDirs = [];
        $availableTemplates = Modules_Microweber_Config::getSupportedTemplates();
        if (!empty($availableTemplates)) {
            foreach ($availableTemplates as $availableTemplateTargetDir => $availableTemplate) {
                $templateDirs[] = $availableTemplateTargetDir;
            }
        }

        $newTemplates = [];
        foreach ($getTemplates as $template) {
            if (!in_array($template['target_dir'], $templateDirs)) {
                $newTemplates[] = $template['target_dir'];
            }
        }

        if (!empty($newTemplates)) {
            $messages[] = ['message'=>'New templates found: ' . implode(',', $newTemplates)];
        } else {
            $messages[] = ['message'=>'No new templates found.'];
        }

        $messages[] = ['next'=>true, 'message'=>'Starting update task...'];

        $this->_helper->json([
            'messages' => $messages
        ]);
    }


    public function queueAction() {

        /*Modules_Microweber_Helper::stopTasks(['task_appversioncheck','task_appdownload','task_templatesdownload']);

        $task = new Modules_Microweber_Task_AppVersionCheck();
        $this->taskManager->start($task, NULL);*/

        $this->_helper->json([
            'messages' => 'Update task has been started',
        ]);

    }
}