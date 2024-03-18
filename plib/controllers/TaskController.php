<?php

class TaskController extends pm_Controller_Action
{
    public function taskstatusesAction()
    {
        $taskStatuses = [];
        $taskManager = new pm_LongTask_Manager();

        $tasks = $taskManager->getTasks(['task_templatesdownload']);
        if (isset($tasks[0])) {
            $taskStatuses['templates_download'] = [
                'status' => $tasks[0]->getStatus(),
                'progress' => $tasks[0]->getProgress(),
            ];
        }

        $tasks = $taskManager->getTasks(['task_appdownload']);
        if (isset($tasks[0])) {
            $taskStatuses['app_download'] = [
                'status' => $tasks[0]->getStatus(),
                'progress' => $tasks[0]->getProgress(),
            ];
        }

        $tasks = $taskManager->getTasks(['task_domainreinstall']);
        if (isset($tasks[0])) {
            $taskStatuses['app_download'] = [
                'status' => $tasks[0]->getStatus(),
                'progress' => $tasks[0]->getProgress(),
            ];
        }

        $this->_helper->json($taskStatuses);
    }


    public function appupdatecheckAction()
    {
        Modules_Microweber_Helper::stopTasks(['task_appdownload']);

        $taskManager = new pm_LongTask_Manager();

        $task = new Modules_Microweber_Task_AppDownload();
        $taskManager->start($task, NULL);

        $this->_helper->json(['started' => 1]);

    }

}
