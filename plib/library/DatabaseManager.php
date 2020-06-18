<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_DatabaseManager
{
    protected $_domainId;

    public function setDomainId($id)
    {
        $this->_domainId = $id;
    }

    public function createUser($databaseId, $login, $password, $role = 'readWrite')
    {
        $apiRequest = <<<APICALL
<packet>
	<database>
		<add-db-user>
			<db-id>$databaseId</db-id>
			<login><![CDATA[$login]]></login>
			<password><![CDATA[$password]]></password>
			<role>$role</role>
		</add-db-user>
	</database>
</packet>
APICALL;
        return $this->_makeRequest($apiRequest);

    }

    public function createDatabase($name)
    {
        $apiRequest = <<<APICALL
<packet>
	<database>
	<add-db>
	   <webspace-id>$this->_domainId</webspace-id>
	   <name><![CDATA[$name]]></name>
	   <type>mysql</type>
	</add-db>
	</database>
</packet>
APICALL;

        return $this->_makeRequest($apiRequest);

    }

    public function getDatabaseServerById($id)
    {
        $apiRequest = <<<APICALL
<packet>
<db_server>
  <get>
   <filter>
    <id>$id</id>
   </filter>
   </get>
  </db_server>
</packet>
APICALL;

        $request =  $this->_makeRequest($apiRequest);

        if (isset($request['db_server']['get']['result'])) {
            return $request['db_server']['get']['result'];
        }

        return false;
    }

    public function getDatabaseServers()
    {
        $apiRequest = <<<APICALL
<packet>
<db_server>
	<get>
	<filter />
	</get>
</db_server>
</packet>
APICALL;

        $request =  $this->_makeRequest($apiRequest);
        if (isset($request['db_server']['get']['result'])) {
            return $request['db_server']['get']['result'];
        }

        return false;
    }

    protected function _makeRequest($apiRequest)
    {
        if (empty($this->_domainId)) {
            throw new Exception('Domain id is not set.');
        }

        return json_decode(json_encode(pm_ApiRpc::getService()->call($apiRequest)), TRUE);
    }
}
