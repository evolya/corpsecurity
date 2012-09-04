<?php

/**
 * Identity representation in Corp service
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    evolya.corpsecurity.auth
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Auth_Identity implements Corp_Serializable {
	
	const TYPE_ANONYMOUS	= 'ANONYMOUS';
	const TYPE_USER			= 'USER';
	const TYPE_GROUP		= 'GROUP';
	const TYPE_PROCESS		= 'PROCESS';
	const TYPE_DEAMON		= 'DEAMON';
	
	protected $realm;
	protected $uid;
	protected $type;
	protected $name;

	/**
	 * @param string $realm
	 * @param int|string $uid
	 * @param string $type
	 * @param string $name
	 * @throws Corp_Exception_InvalidArgument
	 */
	public function __construct($realm = '', $uid = null, $type = self::TYPE_ANONYMOUS, $name = 'Anonymous') {
		/*$this->realm = '';
		$this->uid   = null;
		$this->type  = self::TYPE_ANONYMOUS;
		$this->name  = 'Anonymous';*/
		// REALM
		if (!is_string($realm)) {
			throw new Corp_Exception_InvalidArgument('$realm', $realm, 'string');
		}
		// UID
		if (!is_string($uid) && !is_int($uid) && $uid !== null) {
			throw new Corp_Exception_InvalidArgument('$uid', $uid, 'string');
		}
		// TYPE
		if (!in_array($type, array('ANONYMOUS', 'USER', 'GROUP', 'PROCESS', 'DEAMON'))) {
			throw new Corp_Exception_InvalidArgument('$type', $type, 'enum[USER,GROUP,PROCESS,DEAMON]');
		}
		$this->realm	= $realm;
		$this->uid		= $uid;
		$this->name 	= $name;
		$this->type 	= $type;
	}

	/**
	 * @return Corp_Auth_Identity
	 */
	public static function createAnonymous() {
		return new Corp_Auth_Identity();
	}
	
	//public static function createIdentity($realm, $uid, $type, $name)
	
	/**
	 * @return string
	 */
	public function getIdentityRealm() {
		$this->realm;
	}
	
	/**
	 * @return string Empty string mean anonymous/null identity
	 */
	public function getIdentityUID() {
		return $this->uid;
	}

	/**
	 * @return string Corp_Auth_Identity::TYPE_*
	 */
	public function getIdentityType() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getIdentityName() {
		return $this->name;
	}

	/**
	 * @return string[]
	 */
	public function __sleep() {
		return array('realm', 'uid', 'type', 'name');
	}
	
	/**
	 * @return void
	 */
	public function __wakeup() {
	
	}
	
	/**
	 * @return string
	 */
	public function __toString() {
		return get_class($this) . "[TYPE={$this->type}; UID={$this->uid}; NAME={$this->name}]";
	}
	
	/**
	 * @param string $realm
	 * @param string $uid
	 * @param string $password
	 * @return string
	 */
	public static function createPasswordHash($realm, $uid, $password) {
		return hash('sha256', "$realm:$uid:$password");
	}
	
}

?>