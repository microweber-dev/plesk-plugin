<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Task_DomainReinstall extends \pm_LongTask_Task
{
    public $runningLog = 'Applying the new version of app on domains.';
    public $trackProgress = true;

    public function run()
    {
        $this->updateProgress(0);

        $updateProgress = 0;
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {
            if (!$domain->hasHosting()) {
                continue;
            }
            $domainDocumentRoot = $domain->getDocumentRoot();

            $this->runningLog = 'Applying the new version of app on ' . $domain->getName();

            Modules_Microweber_Reinstall::run($domain->getId(), $domainDocumentRoot);

            $updateProgress++;
            $this->updateProgress($updateProgress);
        }

        $this->updateProgress(100);

        return true;
    }

    public function statusMessage()
    {
        switch ($this->getStatus()) {

            case static::STATUS_RUNNING:

                return $this->runningLog;

            case static::STATUS_DONE:

                return 'Websites are up to date!';

            case static::STATUS_ERROR:

                return 'Error applying update on websites.';

            case static::STATUS_NOT_STARTED:

                return pm_Locale::lmsg('taskPingError', [
                    'id' => $this->getId()
                ]);
        }

        return '';
    }

}
