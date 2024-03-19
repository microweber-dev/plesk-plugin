<?php

class TaskController extends pm_Controller_Action
{
    public function taskstatusesAction()
    {
        $taskStatuses = [];
        $taskManager = new pm_LongTask_Manager();
        $runningTasksCount = 0;
        $tasks = $taskManager->getTasks(['task_templatesdownload']);
        if (isset($tasks[0])) {
            $taskStatuses['templates_download'] = [
                'status' => $tasks[0]->getStatus(),
                'progress' => $tasks[0]->getProgress(),
            ];
            if ($tasks[0]->getStatus() == pm_LongTask_Task::STATUS_RUNNING) {
                $runningTasksCount++;
            }
        }

        $tasks = $taskManager->getTasks(['task_appdownload']);
        if (isset($tasks[0])) {
            $taskStatuses['app_download'] = [
                'status' => $tasks[0]->getStatus(),
                'progress' => $tasks[0]->getProgress(),
            ];
            if ($tasks[0]->getStatus() == pm_LongTask_Task::STATUS_RUNNING) {
                $runningTasksCount++;
            }
        }

        $tasks = $taskManager->getTasks(['task_domainreinstall']);
        if (isset($tasks[0])) {
            $taskStatuses['domain_reinstall'] = [
                'status' => $tasks[0]->getStatus(),
                'progress' => $tasks[0]->getProgress(),
            ];
            if ($tasks[0]->getStatus() == pm_LongTask_Task::STATUS_RUNNING) {
                $runningTasksCount++;
            }
        }

        $appInstalled = false;
        $currentVersionOfApp = Modules_Microweber_Helper::getCurrentVersionOfApp();
        if ($currentVersionOfApp !== 'unknown') {
            $appInstalled = true;
        }

        $this->_helper->json([
            'tasks' => $taskStatuses,
            'current_version' => $currentVersionOfApp,
            'app_installed' => $appInstalled,
            'running_tasks_count' => $runningTasksCount,
        ]);

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
