<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
pm_Context::init('microweber');

if (pm_Settings::get('update_app_automatically') != 'yes') {
    return;
}

$taskManager = new pm_LongTask_Manager();
$task = new Modules_Microweber_TaskAppVersionCheck();
$taskManager->start($task, NULL);