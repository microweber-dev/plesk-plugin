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

        return $taskStatuses;
    }
}
