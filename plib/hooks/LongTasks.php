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
			new Modules_Microweber_Task_DomainAppInstall(),
			new Modules_Microweber_Task_AppDownload(),
			new Modules_Microweber_Task_AppVersionCheck(),
			new Modules_Microweber_Task_TemplatesDownload(),
			new Modules_Microweber_Task_WhiteLabelBrandingUpdate(),
			new Modules_Microweber_Task_WhiteLabelBrandingRemove(),
			new Modules_Microweber_Task_DisableSelinux(),
			new Modules_Microweber_Task_UpdateHostingPlansPhpHandler(),
			new Modules_Microweber_Task_UpdateDomainsPhpHandler(),
			new Modules_Microweber_Task_DomainAppInstallationScan(),
			new Modules_Microweber_Task_DomainAppInstallationRepair(),
			new Modules_Microweber_Task_DomainReinstall(),
		];

	}
}
