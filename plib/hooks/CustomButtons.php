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

        if (!$showButtons) {
            return false;
        }

        $places = [];

        $places[] = [
            'place' => [
                self::PLACE_DOMAIN,
                self::PLACE_DOMAIN_PROPERTIES
            ],
            'title' => 'Microweber',
            'description' => 'View all microweber websites.',
            'icon' => pm_Context::getBaseUrl() . 'images/logo_small.svg',
            'link' => pm_Context::getBaseUrl() . 'index.php/index/index',
            'contextParams' => true
        ];

        $places[] = [
            'place' => self::PLACE_ADMIN_NAVIGATION,
            'section' => self::SECTION_NAV_SERVER_MANAGEMENT,
            'order' => 15,
            'title' => 'Microweber',
            'description' => 'Install last version of microweber',
            'link' => pm_Context::getActionUrl('index', ''),
            'icon' => pm_Context::getBaseUrl() . 'images/logo_small_white.svg'
        ];

        $places[] = [
            'place' => [self::PLACE_HOSTING_PANEL_TABS],
            'order' => 15,
            'title' => 'Microweber',
            'description' => 'Install last version of microweber',
            'link' => pm_Context::getActionUrl('index'),
            'icon' => pm_Context::getBaseUrl() . 'images/logo_small_white.svg',
        ];

        $places[] = [
            'place' => [
                self::PLACE_HOSTING_PANEL_NAVIGATION,
                self::PLACE_ADMIN_TOOLS_AND_SETTINGS,
                self::PLACE_RESELLER_TOOLS_AND_SETTINGS,
            ],
            'order' => 15,
            'title' => 'Microweber',
            'description' => 'Install last version of microweber',
            'link' => pm_Context::getActionUrl('index', 'index'),
            'icon' => pm_Context::getBaseUrl() . 'images/logo_small_white.svg'
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