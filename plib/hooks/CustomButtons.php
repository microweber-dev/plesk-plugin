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
        $showButtons = false;

        if (!pm_Session::getClient()->isAdmin()) {
            $domains = pm_Domain::getDomainsByClient(pm_Session::getClient());
            foreach ($domains as $domain) {
                if (!$domain->hasHosting()) {
                    continue;
                }
                $planItems = $domain->getPlanItems();
                if (is_array($planItems) && count($planItems) > 0 && (in_array("microweber", $planItems) || in_array("microweber_without_shop", $planItems) || in_array("microweber_lite", $planItems))) {
                    $showButtons = true;
                    break;
                }
            }
        }

        if (pm_Session::getClient()->isAdmin()) {
            $showButtons = true;
        }

        if (pm_Session::getClient()->isReseller()) {
            $showButtons = true;
        }

        if (!$showButtons) {
            return [];
        }

        $places = [];
        $places[] = [
            'place' => [
                self::PLACE_DOMAIN,
                self::PLACE_DOMAIN_PROPERTIES,
                self::PLACE_RESELLER_TOOLS_AND_SETTINGS
            ],
            'title' => Modules_Microweber_WhiteLabel::getBrandName(),
            'description' => 'View all ' . Modules_Microweber_WhiteLabel::getBrandName() . ' websites.',
            'icon' => Modules_Microweber_WhiteLabel::getBrandAppIcon(),
            'link' => pm_Context::getBaseUrl() . 'index.php/index/index',
            'contextParams' => true
        ];

        $places[] = [
            'place' => self::PLACE_ADMIN_NAVIGATION,
            'section' => self::SECTION_NAV_SERVER_MANAGEMENT,
            'order' => 15,
            'title' => Modules_Microweber_WhiteLabel::getBrandName(),
            'description' => 'Install last version of ' . Modules_Microweber_WhiteLabel::getBrandName(),
            'link' => pm_Context::getActionUrl('index', ''),
            'icon' => Modules_Microweber_WhiteLabel::getBrandInvertIcon()
        ];

        $places[] = [
            'place' => [self::PLACE_HOSTING_PANEL_TABS],
            'order' => 15,
            'title' => Modules_Microweber_WhiteLabel::getBrandName(),
            'description' => 'Install last version of ' . Modules_Microweber_WhiteLabel::getBrandName(),
            'link' => pm_Context::getActionUrl('index'),
            'icon' => Modules_Microweber_WhiteLabel::getBrandInvertIcon()
        ];

        $places[] = [
            'place' => [
                self::PLACE_RESELLER_NAVIGATION,
                self::PLACE_HOSTING_PANEL_NAVIGATION,
                self::PLACE_ADMIN_TOOLS_AND_SETTINGS,
            ],
            'order' => 15,
            'title' => Modules_Microweber_WhiteLabel::getBrandName(),
            'description' => 'Install last version of ' . Modules_Microweber_WhiteLabel::getBrandName(),
            'link' => pm_Context::getActionUrl('index', 'index'),
            'icon' => Modules_Microweber_WhiteLabel::getBrandInvertIcon()
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
