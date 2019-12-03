<?php

class Modules_Microweber_Update
{

	protected $_domainId;
	protected $_shopActive = false;
	
	public function setDomainId($id)
	{
		$this->_domainId = $id;
	}

	public function setShopActive($active)
	{
		$this->_shopActive = $active;
	}

	public function update()
	{
		
		$domain = new pm_Domain($this->_domainId);
		if (empty($domain->getName())) {
			throw new \Exception('Domain not found.');
		}
		
		$fileManager = new \pm_FileManager($domain->getId());
		
		$hostingManager = new Modules_Microweber_HostingManager();
		$hostingManager->setDomainId($domain->getId());
		
		$hostingProperties = $hostingManager->getHostingProperties();
		if (!$hostingProperties['php']) {
			throw new \Exception('PHP is not activated on selected domain.');
		}
		
		$phpHandler = $hostingManager->getPhpHandler($hostingProperties['php_handler_id']);
		
		$args = [
			$domain->getSysUserLogin(),
			'exec',
			$fileManager->getFilePath('/httpdocs/'),
			$phpHandler['clipath'],
			'artisan',
			'microweber:module',
			'shop'
		];
		
		if ($this->_shopActive) {
			$args[] = 'install';
		} else {
			$args[] = 'uninstall';
		}
		
		$artisan = pm_ApiCli::callSbin('filemng', $args, pm_ApiCli::RESULT_FULL);
		
	}
}