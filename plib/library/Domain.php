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

        if (pm_Session::getClient()->isAdmin()) {
            $domains = pm_Domain::getAllDomains();
        } else if(pm_Session::getClient()->isReseller()) {
            $domains = self::getResellerDomains(pm_Session::getClient()->getId());
        } else {
            $domains = pm_Domain::getDomainsByClient(pm_Session::getClient());
        }

        return $domains;
    }

    public static function getResellerDomains($resellerId)
    {
        $domains = [];

        $request = <<<APICALL
<customer>
    <get>
      <filter>
          <owner-id>$resellerId</owner-id>
      </filter>
      <dataset>
        <gen_info>
        </gen_info>
      </dataset>
    </get>
</customer>
APICALL;

        $response = self::_xmlApi($request);

        if ('ok' == $response->customer->get->result->status) {

            $filter = [];

            foreach ($response->customer->get->result as $customer) {
                if (isset($customer->id)) {
                    $filter[] = "<get-domain-list><filter><id>" . $customer->id->__toString() . "</id></filter></get-domain-list>";
                }
            }

            if (!empty($filter)) {
                $filter = implode("", $filter);

                $request = <<<APICALL
<customer>
    $filter
</customer>
APICALL;

                $response = self::_xmlApi($request);

                if ("ok" == $response->customer->{"get-domain-list"}->{result}->{status}) {
                    foreach ($response->customer->children() as $customerDomains) {
                        foreach ($customerDomains->result->domains as $domain) {
                            foreach ($domain->children() as $item) {
                                $domains[] = pm_Domain::getByDomainId($item->id->__toString());
                            }
                        }
                    }
                }
            }
        }

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

    public static function _xmlApi($request)
    {
        pm_Log::debug("Ready to make a request to XML API: $request");

        $response = pm_ApiRpc::getService()->call($request);

        pm_Log::debug("Request is finished, response is: " . $response->asXML());

        return $response;
    }
}
