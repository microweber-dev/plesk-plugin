<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Domain
{

	protected static $_excludeDomains = array(
		'microweber.com', 
		'microweberapi.com',
		'microweber.bg',
		'members.microweber.bg',
		'web.microweber.bg'
	);

	public static function addAppInstallation($domain, $appInstallation)
    {
        $appInstallation['domainId'] = $domain->getId();
        $appInstallation['appInstallationId'] = md5($appInstallation['appInstallation']);

        $mwAppInstallations = $domain->getSetting('mwAppInstallations');
        $mwAppInstallations = json_decode($mwAppInstallations, true);
        if (!is_array($mwAppInstallations)) {
            $mwAppInstallations = [];
        }

        $mwAppInstallations[$appInstallation['appInstallationId']] = $appInstallation;

        $domain->setSetting('mwAppInstallations', json_encode($mwAppInstallations));
    }
	
	public static function getDomains()
	{
	/*	$httpHost = '';
		if (isset($_SERVER['HTTP_HOST'])) {
			$httpHost = $_SERVER['HTTP_HOST'];
			$exp = explode(":", $httpHost);
			if (isset($exp[0])) {
				$httpHost = $exp[0];
				
				self::$_excludeDomains[] = $httpHost;
			}
		}*/
		
		if (pm_Session::getClient()->isAdmin()) {
			$domains = pm_Domain::getAllDomains();
		} else {
			$domains = pm_Domain::getDomainsByClient(pm_Session::getClient());
		}
		
		/*$readyDomains = [];
		foreach ($domains as $domain) {
			if (in_array($domain->getName(), self::$_excludeDomains)) {
				continue;
			}
			$readyDomains[] = $domain;
		}*/

		return $domains;
	}

	public static function getUserDomainById($domainId)
	{
		foreach (self::getDomains() as $domain) {
			if ($domain->getId() == $domainId) {
				return $domain;
			}
		}

		throw new Exception('You don\'t have permission to manage this domain');
	}
}
