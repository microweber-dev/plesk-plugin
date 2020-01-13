<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_WhiteLabel
{

	public static function updateWhiteLabelDomainById($domainId)
	{
		$domain = Modules_Microweber_Domain::getUserDomainById($domainId);

		$fileManager = new pm_FileManager($domain->getId());

		if ($fileManager->fileExists($domain->getDocumentRoot() . '/config/microweber.php')) {
			$fileManager->filePutContents($domain->getDocumentRoot() . '/storage/branding.json', self::getWhiteLabelJson());
		}
	}

	public static function updateWhiteLabelDomains()
	{
		foreach (Modules_Microweber_Domain::getDomains() as $domain) {
			self::updateWhiteLabelDomainById($domain->getId());
		}
	}
	
	public static function getWhiteLabelJson()
	{
		$whiteLabelSettings = [];
		$whiteLabelSettings['brand_name'] = pm_Settings::get('wl_brand_name');
		$whiteLabelSettings['admin_logo_login_link'] = pm_Settings::get('wl_admin_login_url');
		$whiteLabelSettings['custom_support_url'] = pm_Settings::get('wl_contact_page');
		
		$whiteLabelSettings['logo_admin'] = pm_Settings::get('wl_logo_admin_panel');
		$whiteLabelSettings['logo_live_edit'] = pm_Settings::get('wl_logo_live_edit_toolbar');
		$whiteLabelSettings['logo_login'] = pm_Settings::get('wl_logo_login_screen');
		
		$whiteLabelSettings['powered_by_link'] = pm_Settings::get('wl_powered_by_link');
		
		$whiteLabelSettings['disable_marketplace'] = pm_Settings::get('wl_disable_microweber_marketplace');
		$whiteLabelSettings['disable_powered_by_link'] = pm_Settings::get('wl_hide_powered_by_link');
		
		$whiteLabelSettings['enable_service_links'] = pm_Settings::get('wl_enable_support_links');
		
		$whiteLabelSettings['external_login_server_button_text'] = pm_Settings::get('wl_external_login_server_button_text');
		$whiteLabelSettings['external_login_server_enable'] = pm_Settings::get('wl_external_login_server_enable');

		
		$whmcsPackageUrls = Modules_Microweber_Config::getWhmcsPackageManagerUrls();
		if (!empty($whmcsPackageUrls)) {
			$whiteLabelSettings['marketplace_repositories_urls'] = $whmcsPackageUrls;
		}
		
		return json_encode($whiteLabelSettings, JSON_PRETTY_PRINT);
	}
}