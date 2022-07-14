<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_WhiteLabel
{

    public static function getBrandName()
    {
        $name = 'Microweber';

        
        if (Modules_Microweber_LicenseData::hasActiveLicense()) {
            $setting = Modules_Microweber_WhiteLabelSettings::get('wl_brand_name');
            $setting = trim($setting);

            if (!empty($setting)) {
                $name = $setting;
            }
        }

        return $name;
    }

    public static function getBrandInvertIcon()
    {
        $icon = pm_Context::getBaseUrl() . 'images/logo_small_white.svg';

        if (Modules_Microweber_LicenseData::hasActiveLicense()) {
            $setting = Modules_Microweber_WhiteLabelSettings::get('wl_plesk_logo_invert');
            $setting = trim($setting);

            if (!empty($setting)) {
                $icon = $setting;
            }
        }

        return $icon;
    }

    public static function getBrandAppIcon()
    {
        $icon = pm_Context::getBaseUrl() . 'images/logo_small.svg';

        if (Modules_Microweber_LicenseData::hasActiveLicense()) {
            $setting = Modules_Microweber_WhiteLabelSettings::get('wl_plesk_logo_app');
            $setting = trim($setting);

            if (!empty($setting)) {
                $icon = $setting;
            }
        }

        return $icon;
    }

	public static function updateWhiteLabelDomains()
    {
        $taskManager = new pm_LongTask_Manager();

        Modules_Microweber_Helper::stopTasks(['task_whitelabelbrandingupdate']);

        // Start new task
        $task = new Modules_Microweber_Task_WhiteLabelBrandingUpdate();
        $taskManager->start($task, NULL);

	}
	
	public static function getWhiteLabelJson($domain = false)
	{
        $whiteLabelSettings = [];

	    $whmcs = new Modules_Microweber_WhmcsConnector();
	    $whmcs->setDomainName($domain->getName());
	    $whiteLabelWhmcsSettings = $whmcs->getWhitelabelSettings();

	    if (!empty($whiteLabelWhmcsSettings)) {

	        if (isset($whiteLabelWhmcsSettings['wl_brand_name'])) {
                $whiteLabelSettings['brand_name'] = $whiteLabelWhmcsSettings['wl_brand_name'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_brand_favicon'])) {
                $whiteLabelSettings['brand_favicon'] = $whiteLabelWhmcsSettings['wl_brand_favicon'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_admin_login_url'])) {
                $whiteLabelSettings['admin_logo_login_link'] = $whiteLabelWhmcsSettings['wl_admin_login_url'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_contact_page'])) {
                $whiteLabelSettings['custom_support_url'] = $whiteLabelWhmcsSettings['wl_contact_page'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_logo_admin_panel'])) {
                $whiteLabelSettings['logo_admin'] = $whiteLabelWhmcsSettings['wl_logo_admin_panel'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_logo_live_edit_toolbar'])) {
                $whiteLabelSettings['logo_live_edit'] = $whiteLabelWhmcsSettings['wl_logo_live_edit_toolbar'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_logo_login_screen'])) {
                $whiteLabelSettings['logo_login'] = $whiteLabelWhmcsSettings['wl_logo_login_screen'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_powered_by_link'])) {
                $whiteLabelSettings['powered_by_link'] = $whiteLabelWhmcsSettings['wl_powered_by_link'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_disable_microweber_marketplace'])) {
                $whiteLabelSettings['disable_marketplace'] = $whiteLabelWhmcsSettings['wl_disable_microweber_marketplace'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_hide_powered_by_link'])) {
                $whiteLabelSettings['disable_powered_by_link'] = $whiteLabelWhmcsSettings['wl_hide_powered_by_link'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_enable_support_links'])) {
                $whiteLabelSettings['enable_support_links'] = $whiteLabelWhmcsSettings['wl_enable_support_links'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_external_login_server_button_text'])) {
                $whiteLabelSettings['external_login_server_button_text'] = $whiteLabelWhmcsSettings['wl_external_login_server_button_text'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_external_login_server_enable'])) {
                $whiteLabelSettings['external_login_server_enable'] = $whiteLabelWhmcsSettings['wl_external_login_server_enable'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_enable_service_links'])) {
                $whiteLabelSettings['enable_service_links'] = $whiteLabelWhmcsSettings['wl_enable_service_links'];
            }

            if (isset($whiteLabelWhmcsSettings['wl_admin_colors_sass'])) {
                $whiteLabelSettings['admin_colors_sass'] = $whiteLabelWhmcsSettings['wl_admin_colors_sass'];
            }

        } else {
            $whiteLabelSettings['brand_name'] = Modules_Microweber_WhiteLabelSettings::get('wl_brand_name');
            $whiteLabelSettings['brand_favicon'] = Modules_Microweber_WhiteLabelSettings::get('wl_brand_favicon');
            $whiteLabelSettings['admin_logo_login_link'] = Modules_Microweber_WhiteLabelSettings::get('wl_admin_login_url');
            $whiteLabelSettings['custom_support_url'] = Modules_Microweber_WhiteLabelSettings::get('wl_contact_page');

            $whiteLabelSettings['logo_admin'] = Modules_Microweber_WhiteLabelSettings::get('wl_logo_admin_panel');
            $whiteLabelSettings['logo_live_edit'] = Modules_Microweber_WhiteLabelSettings::get('wl_logo_live_edit_toolbar');
            $whiteLabelSettings['logo_login'] = Modules_Microweber_WhiteLabelSettings::get('wl_logo_login_screen');

            $whiteLabelSettings['powered_by_link'] = Modules_Microweber_WhiteLabelSettings::get('wl_powered_by_link');

            $whiteLabelSettings['disable_marketplace'] = Modules_Microweber_WhiteLabelSettings::get('wl_disable_microweber_marketplace');
            $whiteLabelSettings['disable_powered_by_link'] = Modules_Microweber_WhiteLabelSettings::get('wl_hide_powered_by_link');

            $whiteLabelSettings['enable_support_links'] = Modules_Microweber_WhiteLabelSettings::get('wl_enable_support_links');
            $whiteLabelSettings['enable_service_links'] = Modules_Microweber_WhiteLabelSettings::get('wl_enable_service_links');

            $whiteLabelSettings['external_login_server_button_text'] = Modules_Microweber_WhiteLabelSettings::get('wl_external_login_server_button_text');
            $whiteLabelSettings['external_login_server_enable'] = Modules_Microweber_WhiteLabelSettings::get('wl_external_login_server_enable');
            $whiteLabelSettings['admin_colors_sass'] = Modules_Microweber_WhiteLabelSettings::get('wl_admin_colors_sass');

            $whmcsPackageUrls = Modules_Microweber_Config::getWhmcsPackageManagerUrls();
            if (!empty($whmcsPackageUrls)) {
                $whiteLabelSettings['marketplace_repositories_urls'] = $whmcsPackageUrls;
            }
        }

        $whiteLabelSettings['whmcs_url'] = Modules_Microweber_Config::getWhmcsUrl();

		return json_encode($whiteLabelSettings, JSON_PRETTY_PRINT);
	}
}
