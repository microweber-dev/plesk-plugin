<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

pm_Context::init('microweber');

$taskManager = new pm_LongTask_Manager();

$task = new Modules_Microweber_TaskDomainAppInstallationScan();
$task->setParam('hiddenTask', true);
$taskManager->start($task, NULL);

$task = new Modules_Microweber_TaskDomainAppInstallationCount();
$task->setParam('hiddenTask', true);
$taskManager->start($task, NULL);
