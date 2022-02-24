<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_LicenseData
{

    private static function _getAppInstallationsCount()
    {
        $taskManager = new pm_LongTask_Manager();

        $installations = 0;
        foreach (Modules_Microweber_Domain::getDomains() as $domain) {

            if (!$domain->hasHosting()) {
                continue;
            }

            $domainInstallations = $domain->getSetting('mwAppInstallations');
            $domainInstallations = json_decode($domainInstallations, true);

            if (empty($domainInstallations)) {
                $task = new Modules_Microweber_TaskDomainAppInstallationScan();
                $task->setParam('domainId', $domain->getId());
                $taskManager->start($task, NULL);
                continue;
            }

            $installations++;
        }

        return $installations;
    }

    public static function getLimitations()
    {
        // Licenses
        $appInstallationsLimit = 'nolimit';

        $license = pm_License::getAdditionalKey('microweber');
        $keyBody = json_decode($license->getProperty('key-body'), true);
        if (!empty($keyBody)) {
            if (isset($keyBody['limit'])) {
                $appInstallationsLimit = $keyBody['limit'];
            }
        }

        $appInstallationsCount = self::_getAppInstallationsCount();

        // Freeze app installations
        $appInstallationsFreeze = true;
        if ($appInstallationsCount < $appInstallationsLimit) {
            $appInstallationsFreeze = false;
        }
        if ($appInstallationsLimit == 'nolimit') {
            $appInstallationsFreeze = false;
        }

        return [
            'app_installations_limit'=>$appInstallationsLimit,
            'app_installations_count'=>$appInstallationsCount,
            'app_installations_freeze'=>$appInstallationsFreeze,
        ];
    }

	public static function getLicenseData($whiteLabelKey = false, $relType = 'modules/white_label')
	{
		if ($whiteLabelKey) {

			$whiteLabelKey = trim($whiteLabelKey);

			$checkUrl = Modules_Microweber_Config::getUpdateAppUrl() . "/?api_function=validate_licenses&local_key=$whiteLabelKey&rel_type=$relType";
			$data = file_get_contents($checkUrl);
			$data = @json_decode($data, true);

			if ($data and isset($data[$relType])) {
				$keyData = $data[$relType];
				return $keyData;
			}
		}
	}
	
}