<?php

/**
 * Basic implementation of a session 
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
 * @package    evolya.corpsecurity.persistence
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Persistence_SessionBasic implements Corp_Persistence_Session {
	
	/**
	 * @var string[]
	 */
	protected static $persistentData = array('sid', 'type', 'agent', 'qop', 'identity', 'creationTime', 'lastRequestTime', 'expirationDelay', 'sessionData');
	
	/**
	 * @var string
	 */
	protected $sid;
	
	/**
	 * @var string
	 */
	protected $type;
	
	/**
	 * @var Corp_Agent
	 */
	protected $agent;
	
	/**
	 * @var Corp_Request_QoP
	 */
	protected $qop;
	
	/**
	 * @var Corp_Auth_Identity
	 */
	protected $identity;
	
	/**
	 * @var mixed[]
	 */
	protected $data = array();
	
	/**
	 * @var mixed[]
	 */
	protected $sessionData = array();
	
	/**
	 * @var int
	 */
	protected $creationTime = 0;
	
	/**
	 * @var int
	 */
	protected $lastRequestTime = 0;
	
	/**
	 * @var int
	 */
	protected $expirationDelay = 0;
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::__construct()
	 */
	public function __construct($sid, $type, Corp_Agent $agent, Corp_Request_QoP $qop, Corp_Auth_Identity $identity, $expirationDelay) {
		$this->sid				= $sid;
		$this->type				= $type;
		$this->agent			= $agent;
		$this->qop				= $qop;
		$this->identity			= $identity;
		$this->expirationDelay	= $expirationDelay;
		$this->creationTime		= $_SERVER['REQUEST_TIME'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getSID()
	 */
	public function getSID() {
		return $this->sid;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getSessionType()
	 */
	public function getSessionType() {
		return $this->type;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getCreationTime()
	 */
	public function getCreationTime() {
		return $this->creationTime;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getLastRequestTime()
	 */
	public function getLastRequestTime() {
		return $this->lastRequestTime;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getExpirationDelay()
	 */
	public function getExpirationDelay() {
		return $this->expirationDelay;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getExpirationTime()
	 */
	public function getExpirationTime($fromNow = true) {
		if ($fromNow) {
			return $_SERVER['REQUEST_TIME'] + $this->expirationDelay;
		}
		else {
			return $this->lastRequestTime + $this->expirationDelay;
		}
	}
	
	/**
	 * @param int $creationTime
	 * @param int $lastRequestTime
	 */
	public function setTimes($creationTime, $lastRequestTime) {
		$this->creationTime = (int) $creationTime;
		$this->lastRequestTime = (int) $lastRequestTime;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::isExpired()
	 */
	public function isExpired($now = 0) {
		if ($now < 1) {
			$now = $_SERVER['REQUEST_TIME'];
		}
		return $this->lastRequestTime + $this->expirationDelay < $now;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::touch()
	 */
	public function touch() {
		$this->lastRequestTime = $_SERVER['REQUEST_TIME'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::issetData()
	 */
	public function issetData($key) {
		return array_key_exists($key, $this->data);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getData()
	 */
	public function getData($key) {
		return $this->data[$key];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::setData()
	 */
	public function setData($key, $value) {
		$this->data[$key] = $value;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getDataArray()
	 */
	public function &getDataArray() {
		return $this->data;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::setDataArray()
	 */
	public function setDataArray(&$array) {
		$this->data = &$array;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getSessionData()
	 */
	public function & getSessionData() {
		return $this->sessionData;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::setSessionData()
	 */
	public function setSessionData(&$array) {
		$this->sessionData = &$array;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::removeData()
	 */
	public function removeData() {
		$this->data = array();
		$this->sessionData = array();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getUserAgent()
	 */
	public function getUserAgent() {
		return $this->agent;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getQoPLevel()
	 */
	public function getQoPLevel() {
		return $this->qop;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getIdentity()
	 */
	public function getIdentity() {
		return $this->identity;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::setIdentity()
	 */
	public function setIdentity(Corp_Auth_Identity $identity) {
		$this->identity = $identity;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::getSalt()
	 */
	public function getSalt() {
		return array_key_exists('salt', $this->sessionData) ? $this->sessionData['salt'] : null;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::setSalt()
	 */
	public function setSalt($salt) {
		$this->sessionData['salt'] = $salt;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::regenerateSalt()
	 */
	public function regenerateSalt() {
		$this->sessionData['salt'] = sha1(
			(function_exists('mt_rand') ? mt_rand() : rand()) .
			($_SERVER['REQUEST_TIME']) .
			(__FILE__)
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::isLogged()
	 */
	public function isLogged($type = Corp_Auth_Identity::TYPE_USER) {
		
		if ($this->identity == null) {
			return false;
		}
		
		return ($this->identity->getIdentityType() === Corp_Auth_Identity::TYPE_USER);
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::removeIdentity()
	 */
	public function removeIdentity() {
		$this->identity = new Corp_Auth_Identity();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::destroy()
	 */
	public function destroy() {
		$this->agent = $this->creationTime = $this->data = $this->expirationDelay = $this->identity =
			$this->lastRequestTime = $this->qop = $this->sid = $this->type = null;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::isDestroyed()
	 */
	public function isDestroyed() {
		return $this->identity === null;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Serializable::__sleep()
	 */
	public function __sleep() {
		if ($this->isDestroyed()) {
			throw new Corp_Exception_Persistence(null, null, 'Unable to sleep a destroyed session');
		}
		return self::$persistentData;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Serializable::__wakeup()
	 */
	public function __wakeup() {
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return get_class($this) . "[SID={$this->sid}, LAST_UPDATE=".date('d/m/Y-h:i:s', $this->lastRequestTime)."]";
	}
	
}

?>