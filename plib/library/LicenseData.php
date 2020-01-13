<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_LicenseData
{

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