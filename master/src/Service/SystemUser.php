<?php

class Corp_SystemUser {

	private $uid = null;

	private static $sysuser = null;

	protected function __construct($uid) {
		if ($uid === 0) {
			throw new Corp_Exception("Illegal use of Corp_SystemUser::__construct()");
		}
		$this->uid = $uid;
	}

	public static function getSystemUser() {
		if (self::$sysuser !== null) {
			throw new Corp_Exception("Illegal use of Corp_SystemUser::getSystemUser()");
		}
		self::$sysuser = new Corp_SystemUser(-1);
		self::$sysuser->uid = 0;
		return self::$sysuser;
	}

	

}

?>