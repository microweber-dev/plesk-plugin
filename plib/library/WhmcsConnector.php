<?php

class Modules_Microweber_WhmcsConnector
{
    protected $_domainName;

    public function setDomainName($name)
    {
        $this->_domainName = $name;
    }

    public static function updateWhmcsConnector()
    {
        $whmcsJson = [];
        $whmcsJson['url'] = pm_Settings::get('whmcs_url');
        $whmcsJson['whmcs_url'] = pm_Settings::get('whmcs_url');

        $whmcsPackageUrls = Modules_Microweber_Config::getWhmcsPackageManagerUrls();
        if (!empty($whmcsPackageUrls)) {
        	$whmcsJson['marketplace_repositories_urls'] = $whmcsPackageUrls;
        }
        
        $whmcsJson = json_encode($whmcsJson, JSON_PRETTY_PRINT);

        $whmFilePath = Modules_Microweber_Config::getAppLatestVersionFolder() . '/userfiles/modules/whmcs_connector/settings.json';
       
        $manager = new pm_ServerFileManager();
        $manager->filePutContents($whmFilePath, $whmcsJson);
        
    }

    public function getSelectedTemplate()
    {

        pm_Log::debug('Get selected template for domain: ' . $this->_domainName);

        $template = 'dream';

        $url = Modules_Microweber_Config::getWhmcsUrl() . '/index.php?m=microweber_addon&function=get_domain_template_config&domain=' . $this->_domainName;

        $json = $this->_getJsonFromUrl($url);

        pm_Log::debug('Recived json for domain: ' . $this->_domainName . print_r($json, true));

        if (isset($json['template'])) {
            $template = $json['template'];
        }

        return $template;
    }

}
