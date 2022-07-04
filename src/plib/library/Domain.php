<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_Domain
{
    public static function scanForAppInstallations($domain)
    {
        if (!$domain->hasHosting()) {
            $domain->setSetting('mwAppInstallations', false);
            return false;
        }

        $installationsFind = [];

        $domainDocumentRoot = $domain->getDocumentRoot();
        $domainName = $domain->getName();
        $domainDisplayName = $domain->getDisplayName();
        $domainIsActive = $domain->isActive();
        $domainCreation = $domain->getProperty('cr_date');

        $appVersion = 'unknown';

        $fileManager = new pm_FileManager($domain->getId());

        if (!$fileManager->isDir($domainDocumentRoot)) {
            $domain->setSetting('mwAppInstallations', false);
            return false;
        }

        $allDirs = $fileManager->scanDir($domainDocumentRoot, true);
        if (empty($allDirs)) {
            $domain->setSetting('mwAppInstallations', false);
            return false;
        }

        foreach ($allDirs as $dir) {
            if (!is_dir($domainDocumentRoot . '/' . $dir . '/config/')) {
                continue;
            }
            if (is_file($domainDocumentRoot . '/' . $dir . '/config/microweber.php')) {
                $installationsFind[] = $domainDocumentRoot . '/' . $dir . '/config/microweber.php';
            }
        }

        if (is_dir($domainDocumentRoot . '/config/')) {
            if (is_file($domainDocumentRoot . '/config/microweber.php')) {
                $installationsFind[] = $domainDocumentRoot . '/config/microweber.php';
            }
        }

        if (empty($installationsFind)) {
            $domain->setSetting('mwAppInstallations', false);
        }

        $installations = 0;

        if (!empty($installationsFind)) {

            foreach ($installationsFind as $appInstallationConfig) {

                if (strpos($appInstallationConfig, 'backup-files') !== false) {
                    continue;
                }

                $appInstallation = str_replace('/config/microweber.php', false, $appInstallationConfig);

                // Find app in main folder
                if ($fileManager->fileExists($appInstallation . '/version.txt')) {
                    $appVersion = $fileManager->fileGetContents($appInstallation . '/version.txt');
                }

                if (is_link($appInstallation . '/vendor')) {
                    $installationType = 'Symlinked';
                } else {
                    $installationType = 'Standalone';
                }

                $domainNameUrl = $appInstallation;
                $domainNameUrl = str_replace('/var/www/vhosts/', false, $domainNameUrl);
                $domainNameUrl = str_replace($domainName . '/httpdocs', $domainName, $domainNameUrl);
                $domainNameUrl = str_replace($domainName, $domainDisplayName, $domainNameUrl);

                $manageDomainUrl = '/smb/web/overview/id/d:' . $domain->getId();
                if (pm_Session::getClient()->isAdmin()) {
                    $manageDomainUrl = '/admin/subscription/login/id/' . $domain->getId() . '?pageUrl=' . $manageDomainUrl;
                } else {
                    $manageDomainUrl = $manageDomainUrl;
                }

                $hostingManager = new Modules_Microweber_HostingManager();
                $hostingManager->setDomainId($domain->getId());

                $subscription = $hostingManager->getDomainSubscription($domain->getName());
                if ($subscription['webspace'] == false) {
                    $manageDomainUrl = '/smb/web/view/id/' . $domain->getId() . '/type/domain';
                }

                $domainNameAppUrlPath = $domainName;
                $appInstallationExpByDomain = explode($domainName, $appInstallation);
                if ($appInstallationExpByDomain) {
                    $domainNameAppUrlPath = end($appInstallationExpByDomain);
                    $domainNameAppUrlPath = str_replace('/httpdocs', '', $domainNameAppUrlPath);
                    $domainNameAppUrlPath = $domainName . $domainNameAppUrlPath;
                }

                Modules_Microweber_Domain::addAppInstallation($domain, [
                    'domainNameUrl' => $domainNameAppUrlPath,
                    'domainCreation' => $domainCreation,
                    'installationType' => $installationType,
                    'appVersion' => $appVersion,
                    'appInstallation' => $appInstallation,
                    'domainIsActive' => $domainIsActive,
                    'manageDomainUrl' => $manageDomainUrl,
                ]);
                $installations++;
            }
        }

        return $installations;
    }

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
            if (!$domain->hasHosting()) {
                continue;
            }
            $planItems = $domain->getPlanItems();

            if (pm_Session::getClient()->hasAccessToDomain($domain->getId())) {

                if (is_array($planItems)
                    && count($planItems) > 0
                    && (in_array("microweber", $planItems)
                        || in_array("microweber_without_shop", $planItems)
                        || in_array("microweber_lite", $planItems))) {
                    $domains[] = $domain;
                }
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