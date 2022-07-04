<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

pm_Context::init('microweber');

if (pm_Settings::get('update_app_automatically') != 'yes') {
    return;
}

$taskManager = new pm_LongTask_Manager();
$task = new Modules_Microweber_Task_AppVersionCheck();
$taskManager->start($task, NULL);
