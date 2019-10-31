<?php

class Modules_Microweber_DatabaseManager
{
	protected $_domainId;
	
	public function setDomainId($id) {
		$this->_domainId = $id;
	}
	
	public function createUser($databaseId, $login, $password, $role = 'readWrite') {
		$apiRequest = <<<APICALL
<packet>
	<database>
		<add-db-user>
			<db-id>$databaseId</db-id>
			<login>$login</login>
			<password>$password</password>
			<role>$role</role>
		</add-db-user>
	</database>
</packet>
APICALL;
		return $this->_makeRequest($apiRequest);
		
	}
	
	public function createDatabase($name) {
		
		$apiRequest = <<<APICALL
<packet>
	<database>
	<add-db>
	   <webspace-id>$this->_domainId</webspace-id>
	   <name>$name</name>
	   <type>mysql</type>
	</add-db>
	</database>
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
