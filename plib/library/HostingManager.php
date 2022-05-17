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
          <gen_info/>
          <prefs/>
          <stat/>
       </dataset>
    </get>
</webspace>
APICALL;

        $requestResult = $this->_makeRequest($apiRequest);

        $webspace = false;
        $webspaceId = false;

        if (isset($requestResult['webspace']['get']['result']['status']) && $requestResult['webspace']['get']['result']['status'] == 'error') {
            $webspace = false;
        }

        if (isset($requestResult['webspace']['get']['result']['status']) && $requestResult['webspace']['get']['result']['status'] == 'ok') {
            $webspace = true;
            $webspaceId = $requestResult['webspace']['get']['result']['id'];
        }

        return [
          'webspace'=>$webspace,
          'webspaceId'=>$webspaceId,
        ];
    }

    public function setServicePlanPhpHandler($servicePlanId, $phpId) {
        $apiRequest = <<<APICALL
                <packet>
        <service-plan>
        <set>
           <filter>
              <id>$servicePlanId</id>
           </filter>
           <hosting>
              <vrt_hst>
                    <property>
                      <name>php_handler_id</name>
                      <value>$phpId</value>
                    </property>
              </vrt_hst>
           </hosting>
        </set>
        </service-plan>
        </packet>
APICALL;

        $requestResult = $this->_makeRequest($apiRequest);

        return $requestResult;
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

	public function getDatabaseServerByWebspaceId($webspaceId){
        $apiRequest = <<<APICALL
        <packet>
        <webspace>
<db-servers>
   <list>
      <filter>
          <id>$webspaceId</id>
      </filter>
   </list>
</db-servers>
</webspace>
</packet>
APICALL;

        $request =  $this->_makeRequest($apiRequest);

        if (isset($request['webspace']['db-servers']['list']['result']['db-server'])) {
            return $request['webspace']['db-servers']['list']['result']['db-server'];
        }

        return false;
    }

    public function getServicePlans()
    {
        $client = pm_Session::getClient();

        $apiRequest = "
<packet>
<service-plan>
   <get>
       <filter></filter>
       ";

        if ($client->isReseller()) {
            $apiRequest .= "<owner-login>".$client->getLogin()."</owner-login>";
        }

    $apiRequest .= "
</get>
</service-plan>
</packet>";

        $requestResult = $this->_makeRequest($apiRequest);

        if (isset($requestResult['service-plan']['get']['result'])) {

            if (!isset($requestResult['service-plan']['get']['result'][0])) {
                $requestResult['service-plan']['get']['result'][] = $requestResult['service-plan']['get']['result'];
            }

            $mwPlans = [];
            foreach ($requestResult['service-plan']['get']['result'] as $planKey=>$plan) {

                if (isset($plan['plan-items'])) {
                    foreach ($plan['plan-items'] as $planItem) {

                        if (isset($planItem['name'])) {
                            if (strpos($planItem['name'],'microweber') !== false) {
                                $mwPlans[] = $plan;
                            }
                        }
                        if (isset($planItem[0])) {
                            foreach ($planItem as $item) {
                                if (strpos($item['name'],'microweber') !== false) {
                                    $mwPlans[] = $plan;
                                }
                            }
                        }
                    }
                }
            }


            return $mwPlans;
        }

        return [];
    }

	protected function _makeRequest($apiRequest) {
		return json_decode(json_encode(pm_ApiRpc::getService()->call($apiRequest, 'admin')), TRUE);
	}
}
