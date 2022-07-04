<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

pm_Context::init('microweber');

$tasks = pm_Scheduler::getInstance()->listTasks();
foreach ($tasks as $task) {
    if ('microweber-periodic-task.php' == $task->getCmd()) {
        pm_Settings::set('microweber_periodic_task_id', $task->getId());
        return;
    }
    if ('microweber-periodic-update-task.php' == $task->getCmd()) {
        pm_Settings::set('microweber_periodic_update_task_id', $task->getId());
        return;
    }
}

Modules_Microweber_Helper::checkAndFixSchedulerTasks();