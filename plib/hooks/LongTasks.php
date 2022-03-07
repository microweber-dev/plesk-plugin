<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_LongTasks extends \pm_Hook_LongTasks
{

	public function getLongTasks()
	{
		pm_Log::info('getLongTasks.');

		return [
			new Modules_Microweber_TaskInstall(),
			new Modules_Microweber_TaskAppDownload(),
			new Modules_Microweber_TaskAppVersionCheck(),
			new Modules_Microweber_TaskTemplateDownload(),
			new Modules_Microweber_TaskWhiteLabelBrandingUpdate(),
			new Modules_Microweber_TaskDisableSelinux(),
			new Modules_Microweber_TaskDomainAppInstallationScan(),
			new Modules_Microweber_TaskDomainAppInstallationRepair(),
			new Modules_Microweber_TaskDomainAppInstallationCount(),
		];

	}
}
