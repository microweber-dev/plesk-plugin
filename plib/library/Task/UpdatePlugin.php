<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Task_UpdatePlugin extends \pm_LongTask_Task
{
    const UID = 'updatePlugin';
    public $runningLog = '';
	public $trackProgress = true;

	public function run()
	{
        Modules_Microweber_PluginUpdate::downloadPlugin();
    }

	public function statusMessage()
	{
		switch ($this->getStatus()) {
			case static::STATUS_RUNNING:
				return $this->runningLog;
			case static::STATUS_DONE:
				return Modules_Microweber_WhiteLabel::getBrandName().' plugin is up to date.';
			case static::STATUS_ERROR:
				return 'Error updating '.Modules_Microweber_WhiteLabel::getBrandName().' plugin.';
			case static::STATUS_NOT_STARTED:
				return pm_Locale::lmsg('taskPingError', [
					'id' => $this->getId()
				]);
		}

	}


}
