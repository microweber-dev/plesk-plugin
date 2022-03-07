<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
pm_Context::init('microweber-app');

$tasks = pm_Scheduler::getInstance()->listTasks();
foreach ($tasks as $task) {
    if ('microweber-periodic-task.php' == $task->getCmd()) {
        pm_Settings::set('microweber_periodic_task_id', $task->getId());
        return;
    }
}
$task = new pm_Scheduler_Task();
$task->setSchedule(pm_Scheduler::$EVERY_HOUR);
$task->setCmd('microweber-periodic-task.php');
pm_Scheduler::getInstance()->putTask($task);
pm_Settings::set('microweber_periodic_task_id', $task->getId());
