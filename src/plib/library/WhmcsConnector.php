<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

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
        
        $whmcsJson = json_encode($whmcsJson, JSON_PRETTY_PRINT);
        
        $whmFilePath = Modules_Microweber_Config::getAppSharedPath() . 'userfiles/modules/whmcs-connector/';
        $whmFileName = 'settings.json';
       
        $manager = new pm_ServerFileManager();
        if (!$manager->fileExists($whmFilePath)) {
        	$manager->mkdir($whmFilePath, null, true);
        }
        
        $manager->filePutContents($whmFilePath . $whmFileName, $whmcsJson);
        
    }

    public function getSelectedTemplate()
    {

        Modules_Microweber_Log::debug('Get selected template for domain: ' . $this->_domainName);

        $template = 'new-world';

        $url = Modules_Microweber_Config::getWhmcsUrl() . '/index.php?m=microweber_addon&function=get_domain_template_config&domain=' . $this->_domainName;

        $json = Modules_Microweber_Helper::getJsonFromUrl($url);

        Modules_Microweber_Log::debug('Recived json for domain: ' . $this->_domainName . print_r($json, true));

        if (isset($json['template'])) {
            $template = $json['template'];
        }

        return $template;
    }

    public function getWhitelabelSettings()
    {
        Modules_Microweber_Log::debug('Get whitelabel settings for domain: ' . $this->_domainName);

        $settings = array();

        $url = Modules_Microweber_Config::getWhmcsUrl() . '/index.php?m=microweber_server&function=get_whitelabel_settings&domain=' . $this->_domainName;

        $json = Modules_Microweber_Helper::getJsonFromUrl($url);

        Modules_Microweber_Log::debug('Recived json for domain: ' . $this->_domainName . print_r($json, true));

        if (isset($json['settings'])) {
            $settings = $json['settings'];
        }

        return $settings;
    }

}
