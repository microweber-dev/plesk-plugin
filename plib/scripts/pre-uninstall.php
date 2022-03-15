<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

pm_Context::init('microweber');

pm_Scheduler::getInstance()->removeAllTasks();

pm_Settings::clean();
