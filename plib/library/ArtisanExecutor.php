<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_ArtisanExecutor
{

    public $domainId = false;
    public $domainDocumentRoot = false;

    public function setDomainId($domainId){
        $this->domainId = $domainId;
    }

    public function setDomainDocumentRoot($documentRoot) {
        $this->domainDocumentRoot = $documentRoot;
    }

    public function exec($params = false)
    {
        if (!$params || !is_array($params)) {
            throw new \Exception('No parameters are set.');
        }

        if (!$this->domainId) {
            throw new \Exception('No domain id are set.');
        }

        if (!$this->domainDocumentRoot) {
            throw new \Exception('No domain document root are set.');
        }

        $domain = Modules_Microweber_Domain::getUserDomainById($this->domainId);

        $hostingManager = new Modules_Microweber_HostingManager();
        $hostingManager->setDomainId($domain->getId());

        $hostingProperties = $hostingManager->getHostingProperties();
        if (!$hostingProperties['php']) {
            throw new \Exception('PHP is not activated on selected domain.');
        }

        $phpHandler = $hostingManager->getPhpHandler($hostingProperties['php_handler_id']);

        $defaultParams = [
            $domain->getSysUserLogin(),
            'exec',
            $this->domainDocumentRoot,
            $phpHandler['clipath'],
            'artisan',

        ];
        $sendParams = array_merge($defaultParams, $params);

        return pm_ApiCli::callSbin('filemng', $sendParams, pm_ApiCli::RESULT_FULL);
    }

}