<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

pm_Context::init('microweber');

if (pm_Settings::get('update_templates_automatically') != 'yes') {
    return;
}

$taskManager = new pm_LongTask_Manager();

// Update templates
$task = new Modules_Microweber_Task_TemplatesDownload();
$taskManager->start($task, NULL);