<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_HostingManager
{
	protected $_domainId;
	
	public function setDomainId($id) {
		$this->_domainId = $id;
	}
	
	public function getPhpHandler($phpId) {

		$apiRequest = <<<APICALL
<packet>
	<php-handler>
		<get>
		   <filter>
		      <id>$phpId</id>
		   </filter>
		</get>
	</php-handler>
</packet>
APICALL;
		
		$result = $this->_makeRequest($apiRequest);
		
		if (isset($result['php-handler']['get']['result'])) {
			return $result['php-handler']['get']['result'];
		}
		
		return false;
	}
	
	public function getHostingProperties() {
		
		$readyProperties = array();
		$readyProperties['php'] = false;
		
		$hostingSettings = $this->getHostingSettings();
		
		if (isset($hostingSettings['webspace']['get']['result']['data']['hosting']['vrt_hst']['property'])) {
			$properties = $hostingSettings['webspace']['get']['result']['data']['hosting']['vrt_hst']['property'];
			foreach($properties as $property) {
				$readyProperties[$property['name']] = $property['value'];
			}
		}
		
		return $readyProperties;
	}
	
	public function getHostingSettings() {
		
		$apiRequest = <<<APICALL
<packet>
	<webspace>
		<get>
		   <filter>
		      <id>$this->_domainId</id>
		   </filter>
		   <dataset>
		      <hosting/>
		   </dataset>
		</get>
	</webspace>
</packet>
APICALL;
		
		return $this->_makeRequest($apiRequest);
	}
	
	protected function _makeRequest($apiRequest) {
		
		if (empty($this->_domainId)) {
			throw new Exception('Domain id is not set.');
		}
		
		return json_decode(json_encode(pm_ApiRpc::getService()->call($apiRequest)), TRUE);
	}
}
