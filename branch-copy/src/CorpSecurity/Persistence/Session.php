<?php

/**
 * Interface for persistents sessions
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
interface Corp_Persistence_Session extends Corp_Serializable {
	
	/**
	 * Constructor
	 *
	 * @param string $sid
	 * @param string $type
	 * @param Corp_Agent $agent
	 * @param Corp_Request_QoP $qop
	 * @param Corp_Auth_Identity $identity
	 * @param int $expirationDelay int seconds
	 */
	public function __construct($sid, $type, Corp_Agent $agent, Corp_Request_QoP $qop, Corp_Auth_Identity $identity, $expirationDelay);
	
	/**
	 * @return string
	 */
	public function getSID();
	
	/**
	 * @return string
	 */
	public function getSessionType();

	/**
	 * @return int timestamp seconds
	 */
	public function getCreationTime();
	
	/**
	 * @return int timestamp seconds
	 */
	public function getLastRequestTime();
	
	/**
	 * @return int seconds
	 */
	public function getExpirationDelay();
	
	/**
	 * @param boolean $fromNow Si true, par rapport à maintenant. Sinon par rapport au last upadate de la session.
	 * @return int seconds
	 */
	public function getExpirationTime($fromNow = true);
	
	/**
	 * @param int $creationTime
	 * @param int $lastRequestTime
	 */
	public function setTimes($creationTime, $lastRequestTime);
	
	/**
	 * @return boolean
	 */
	public function isExpired();
	
	/**
	 * @return void
	 */
	public function touch();
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function issetData($key);
	
	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getData($key); // TODO Renommer en get ou en getUserData ?
	
	/**
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function setData($key, $value);
	
	/**
	 * @return &mixed[]
	 */
	public function &getDataArray();
	
	/**
	 * @param mixed[] $array
	 * @return void
	 */
	public function setDataArray(&$array);
	
	/**
	 * @return &mixed[]
	 */
	public function & getSessionData();
	
	/**
	 * @param mixed[] &$array
	 * @return void
	 */
	public function setSessionData(&$array);
	
	/**
	 * @return void
	 */
	public function removeData();
	
	/**
	 * @return Corp_Agent
	 */
	public function getUserAgent();
	
	/**
	 * @return Corp_Request_QoP
	 */
	public function getQoPLevel();
	
	/**
	 * @return Corp_Auth_Identity
	 */
	public function getIdentity();
	
	/**
	 * @param Corp_Auth_Identity $identity
	 */
	public function setIdentity(Corp_Auth_Identity $identity);
	
	/**
	 * @param string $salt
	 * @return string
	 */
	public function setSalt($salt);
	
	/**
	 * @return string
	 */
	public function getSalt();
	
	/**
	 * @return void
	 */
	public function regenerateSalt();
	
	/**
	 * @param string $type Corp_Auth_Identity::TYPE_*
	 * @return boolean
	 */
	public function isLogged($type = Corp_Auth_Identity::TYPE_USER);
	
	/**
	 * @return void
	 */
	public function removeIdentity();
	
	/**
	 * @return void
	 */
	public function destroy();
	
	/**
	 * @return boolean
	 */
	public function isDestroyed();
	
}

?>