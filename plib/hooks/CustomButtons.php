<?php

class Modules_Microweber_CustomButtons extends pm_Hook_CustomButtons
{

	public function getButtons()
	{
		return [
			[
				'place' => self::PLACE_DOMAIN,
				'title' => 'Microweber toolkit',
				'description' => 'Install last version of microweber',
				'icon' => pm_Context::getBaseUrl() . 'images/logo_small.svg',
				'link' => pm_Context::getBaseUrl() . 'index.php/index/install'
			],
			[
				'place' => self::PLACE_ADMIN_NAVIGATION, 
				'section' => self::SECTION_NAV_SERVER_MANAGEMENT,
				'order' => 3,
				'title' => 'Microweber',
				'description' => 'Install last version of microweber',
				'link' => pm_Context::getActionUrl('index', ''),
				'icon' => pm_Context::getBaseUrl() . 'images/logo_small_white.svg'
			],
			[
				'place' => self::PLACE_HOSTING_PANEL_TABS,
				'order' => 3,
				'title' => 'Microweber',
				'description' => 'Install last version of microweber',
				'link' => pm_Context::getActionUrl('index'), 
				'icon' => pm_Context::getBaseUrl() . 'images/logo_small_white.svg',
			],
			[
				'place' => [
					self::PLACE_HOSTING_PANEL_NAVIGATION,
					self::PLACE_ADMIN_TOOLS_AND_SETTINGS,
					self::PLACE_RESELLER_TOOLS_AND_SETTINGS,
				],
				'order' => 3,
				'title' => 'Microweber',
				'description' => 'Install last version of microweber',
				'link' => pm_Context::getActionUrl('index', 'install'),
				'icon' => pm_Context::getBaseUrl() . 'images/logo_small_white.svg' 
			],
		];
	}
}