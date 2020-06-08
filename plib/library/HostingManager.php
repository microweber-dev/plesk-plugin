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


    public function getDomainSubscription($domainName) {

        $apiRequest = <<<APICALL
<webspace>
    <get>
       <filter>
          <name>$domainName</name>
       </filter>
       <dataset>
          <hosting/>
       </dataset>
    </get>
</webspace>
APICALL;

        $requestResult = $this->_makeRequest($apiRequest);

        $webspace = false;

        if (isset($requestResult['webspace']['get']['result']['status']) && $requestResult['webspace']['get']['result']['status'] == 'error') {
            $webspace = false;
        }

        if (isset($requestResult['webspace']['get']['result']['status']) && $requestResult['webspace']['get']['result']['status'] == 'ok') {
            $webspace = true;
        }

        return [
          'webspace'=>$webspace
        ];
    }
	
	public function getPhpHandler($phpId) {

		$apiRequest = <<<APICALL
	<php-handler>
		<get>
		   <filter>
		      <id>$phpId</id>
		   </filter>
		</get>
	</php-handler>
APICALL;

        $requestResult = $this->_makeRequest($apiRequest);
		
		if (isset($requestResult['php-handler']['get']['result'])) {
			$result = $requestResult['php-handler']['get']['result'];
			if ($result['status'] !== 'error') {
			    return $result;
           		}
		}

		for ($i = 5; $i <= 10; $i++) {
		    for ($i2 = 1; $i2 <= 10; $i2++) {
			if (strpos($phpId, 'php' . $i . $i2) !== false) {
			    return [
				'version' => $i.'.'. $i2,
				'clipath' => '/opt/plesk/php/'.$i.'.'. $i2 . '/bin/php',
			    ];
			}
		    }
		}
		
		return false;
	}
	
	public function getHostingProperties() {
		
		$readyProperties = array();
		$readyProperties['php'] = false;
		
		$hostingSettings = $this->getHostingSettings();

		if (isset($hostingSettings['site']['get']['result']['data']['hosting']['vrt_hst']['property'])) {
			$properties = $hostingSettings['site']['get']['result']['data']['hosting']['vrt_hst']['property'];
			foreach($properties as $property) {
				$readyProperties[$property['name']] = $property['value'];
			}
		}
		
		return $readyProperties;
	}
	
	public function getHostingSettings() {
		
		$apiRequest = <<<APICALL
<site>
    <get>
       <filter>
          <id>$this->_domainId</id>
       </filter>
       <dataset>
          <hosting/>
       </dataset>
    </get>
</site>
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
