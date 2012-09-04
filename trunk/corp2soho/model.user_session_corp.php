<?php

class UserSessionCorpMoodelStruct extends MoodelStruct {
	
	public function __construct() {
		
		parent::__construct(DatabaseConnectorManager::getDatabase('main'), 'UserSessionCorp', array(
		
				// Entry ID
				'id'				=> 'int:auto_increment,primary_key',
		
				// Session unique ID
				'sid'				=> 'char[128]:unique',
		
				// Session classname
				'type'				=> 'char[50]',
		
				// SAPI Name
				'api'				=> 'char[50]',
		
				// Instance of Corp_Agent
				'agent'				=> 'serial',
		
				// Instance of Corp_Request_QoP
				'qop'				=> 'serial',
		
				// Instance of Corp_Identity
				'identity'			=> 'serial',
		
				// Creation datetime (timestamp)
				'creation_time'		=> 'int',
		
				// Last request datetime (timestamp)
				'last_request_time'	=> 'int',
		
				// Expiration delay (sec)
				'expiration_delay'	=> 'int',
		
				// User data
				'user_data'			=> 'serial',
		
				// Session data
				'session_data'		=> 'serial'
		
		
		));
		
	}
}

?>