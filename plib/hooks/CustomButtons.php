<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_CustomButtons extends pm_Hook_CustomButtons
{

    public function getButtons()
    {
        $showButtons = Modules_Microweber_Helper::showMicroweberButtons();
        if (!$showButtons) {
            return [];
        }

        $brandName = Modules_Microweber_WhiteLabel::getBrandName();
        $brandAppIcon = Modules_Microweber_WhiteLabel::getBrandAppIcon();
        $brandInvertedIcon = Modules_Microweber_WhiteLabel::getBrandInvertIcon();

        $places = [];
        $places[] = [
            'place' => [
                self::PLACE_DOMAIN,
                self::PLACE_DOMAIN_PROPERTIES,
                self::PLACE_DOMAIN_PROPERTIES_DYNAMIC,
                self::PLACE_RESELLER_TOOLS_AND_SETTINGS,
                self::PLACE_INSTALL_APPLICATION_DRAWER
            ],
            'title' => $brandName,
            'description' => 'View all ' . $brandName . ' websites.',
            'icon' => $brandAppIcon,
            'link' => pm_Context::getBaseUrl() . 'index.php/index/index',
            'contextParams' => true
        ];

        $places[] = [
            'place' => self::PLACE_ADMIN_NAVIGATION,
            'section' => self::SECTION_NAV_SERVER_MANAGEMENT,
            'order' => 15,
            'title' => $brandName,
            'description' => 'Install last version of ' . $brandName,
            'link' => pm_Context::getActionUrl('index', ''),
            'icon' => $brandInvertedIcon
        ];

        $places[] = [
            'place' => [self::PLACE_HOSTING_PANEL_TABS],
            'order' => 15,
            'title' => $brandName,
            'description' => 'Install last version of ' . $brandName,
            'link' => pm_Context::getActionUrl('index'),
            'icon' => $brandInvertedIcon
        ];

        $places[] = [
            'place' => [
                self::PLACE_RESELLER_NAVIGATION,
                self::PLACE_HOSTING_PANEL_NAVIGATION,
                self::PLACE_ADMIN_TOOLS_AND_SETTINGS,
            ],
            'order' => 15,
            'title' => $brandName,
            'description' => 'Install last version of ' . $brandName,
            'link' => pm_Context::getActionUrl('index', 'index'),
            'icon' => $brandInvertedIcon
        ];

        return $places;
    }

    public function isDomainPropertiesButtonVisible(array $params)
    {
        if (!isset($params['site_id'])) {
            return false;
        }

        if (isset($params['alias_id']) && !empty($params['alias_id'])) {
            return false;
        }

        $domain = pm_Domain::getByDomainId($params['site_id']);

        return $domain->hasHosting();
    }
}
