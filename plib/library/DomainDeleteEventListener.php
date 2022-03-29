<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_DomainDeleteEventListener implements EventListener
{
    public function filterActions()
    {
        return [
            'domain_delete',
            'domain_alias_delete',
            'site_delete',
        ];
    }

	public function handleEvent($objectType, $objectId, $action, $oldValue, $newValue)
	{
        $domain = new pm_Domain($objectId);

        $taskManager = new pm_LongTask_Manager();

        $task = new Modules_Microweber_TaskDomainAppInstallationScan();
        $task->setParam('domainId', $domain->getId());
        $taskManager->start($task, NULL);

        $task = new Modules_Microweber_TaskDomainAppInstallationCount();
        $taskManager->start($task, NULL);
	}

}

return new Modules_Microweber_DomainDeleteEventListener();