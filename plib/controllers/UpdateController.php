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
        $error = false;
        $messages = [];

        if (!Modules_Microweber_Helper::isAvailableDiskSpace()) {
            $error = true;
            $messages[] = ['error'=>true, 'message'=>'No disk space available on the server.'];
            $messages[] = ['error'=>true, 'message'=>'Can\'t download the app.'];
        } else {
            $messages[] = ['message'=>'Checking disk space..'];
            $messages[] = ['message'=>'Disk space is ok..'];
        }

        $this->_helper->json([
            'messages' => $messages,
            'error' => $error,
            'started' => true,
        ]);
    }


    public function startUpdateAction() {

        Modules_Microweber_Helper::stopTasks(['task_appversioncheck','task_appdownload','task_templatesdownload']);

        $task = new Modules_Microweber_Task_AppVersionCheck();
        $this->taskManager->start($task, NULL);

        $this->_status->addMessage('info', 'Update task has been started');

        return $this->_redirect('index/versions');
    }
}