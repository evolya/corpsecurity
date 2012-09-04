<?php

class MyIdentityManager implements Corp_Auth_Identity_Manager {

	/**
	 * @var string
	 */
	public $realm = 'default';

	/**
	 * @var string[]
	 */
	protected $identites;

	/**
	 * Constructor
	 */
	public function __construct() {

	}
	
	protected function updateIdentities() {
		$this->identites = array(
			'remi' => md5("remi:{$this->realm}:toor"),
			'ted' => md5("ted:{$this->realm}:ted")
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_Identity_Database::exists()
	 */
	public function exists($realm, $uid, $passwordHash) {
		$this->updateIdentities();
		return in_array($passwordHash, $this->identites) ? $uid : false;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_IdentityManager::getHash()
	 */
	public function getHash($realm, $uid) {
		$this->updateIdentities();
		if ($realm !== $this->realm) {
			return false;
		}
		if (!isset($this->identites[$uid])) {
			return false;
		}
		return $this->identites[$uid];
	}

	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_Identity_Database::getIdentityByUID()
	 */
	public function getIdentityByUID($realm, $uid) {
		$this->updateIdentities();
		if ($realm !== $this->realm) {
			return null;
		}	
		if (!isset($this->identites[$uid])) {
			return null;
		}
		return new Corp_Auth_Identity(
				$realm,							// Realm
				$uid,							// User unique ID
				Corp_Auth_Identity::TYPE_USER,	// Identity Type
				$uid							// User name
		);
	}

}

?>