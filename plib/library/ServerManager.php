<?php

class Modules_Microweber_ServerManager
{
    public function getPhpHandlers()
    {
        $apiRequest = <<<APICALL
<packet>
  <php-handler>
    <get>
        <filter/>
    </get>
 </php-handler>
</packet>

APICALL;
        $requestResult = $this->_makeRequest($apiRequest);

        if (isset($requestResult['php-handler']['get']['result'])) {
            $result = $requestResult['php-handler']['get']['result'];
            if ($result['status'] !== 'error') {
                return $result;
            }
        }

        return [];
    }

    protected function _makeRequest($apiRequest) {
        return json_decode(json_encode(pm_ApiRpc::getService()->call($apiRequest, 'admin')), TRUE);
    }

}