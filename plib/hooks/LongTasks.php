<?php

class Modules_Microweber_LongTasks extends \pm_Hook_LongTasks
{

	public function getLongTasks()
	{
		pm_Log::info('getLongTasks.');

		return [
			new Modules_Microweber_TaskInstall(),
			new Modules_Microweber_TaskTemplateDownload(),
		];
	}
}
