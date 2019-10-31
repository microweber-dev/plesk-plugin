<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.

class Modules_Microweber_Config
{

	public static function getAppLatestVersionFolder()
	{
		return '/usr/share/microweber/latest';
	}

	public static function getPlanItems()
	{
		return [
			'microweber' => 'Install Microweber',
			'microweber_lite' => 'Install Microweber Lite'
		];
	}

	public static function getUpdateAppUrl()
	{
		$updateAppUrl = pm_Settings::get('update_app_url');

		if (empty($updateAppUrl)) {
			return 'https://update.microweberapi.com/';
		}

		return $updateAppUrl;
	}

	public static function getWhmcsUrl()
	{
		$updateWhmcsUrl = pm_Settings::get('whmcs_url');

		if (empty($updateWhmcsUrl)) {
			return 'https://members.microweber.com/';
		}

		return $updateWhmcsUrl;
	}
	
}