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
        
        $whmFilePath = Modules_Microweber_Config::getAppSharedPath() . 'userfiles/modules/whmcs_connector/';
        $whmFileName = 'settings.json';
       
        $manager = new pm_ServerFileManager();
        if (!$manager->fileExists($whmFilePath)) {
        	$manager->mkdir($whmFilePath, null, true);
        }
        
        $manager->filePutContents($whmFilePath . $whmFileName, $whmcsJson);
        
    }

    public function getSelectedTemplate()
    {

        pm_Log::debug('Get selected template for domain: ' . $this->_domainName);

        $template = 'dream';

        $url = Modules_Microweber_Config::getWhmcsUrl() . '/index.php?m=microweber_addon&function=get_domain_template_config&domain=' . $this->_domainName;

        $json = Modules_Microweber_Helper::getJsonFromUrl($url);

        pm_Log::debug('Recived json for domain: ' . $this->_domainName . print_r($json, true));

        if (isset($json['template'])) {
            $template = $json['template'];
        }

        return $template;
    }

}