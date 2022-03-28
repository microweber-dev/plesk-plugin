<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Domain
{
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
        $domains = [];

        $allDomains = pm_Domain::getAllDomains();
        foreach ($allDomains as $domain) {
            if (pm_Session::getClient()->hasAccessToDomain($domain->getId())) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    public static function updatePhpHandler($domainId, $phpHandlerId)
    {
        $request = <<<APICALL
        <packet>
<site>
    <set>
       <filter>
         <id>$domainId</id>
       </filter>
       <values>
            <hosting>
              <vrt_hst>
              <property>
                <name>php_handler_id</name>
                <value>$phpHandlerId</value>
              </property>
              </vrt_hst>
            </hosting>
       </values>
    </set>
</site>
</packet>
APICALL;

        $requestResult = static::_makeRequest($request);

        return $requestResult;

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

    public static function _makeRequest($request)
    {
        pm_Log::debug("Ready to make a request to XML API: $request");

        $response = pm_ApiRpc::getService()->call($request);

        pm_Log::debug("Request is finished, response is: " . $response->asXML());

        return $response;
    }
}
