<?php

/**
 * Corp persistence manager interface
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
interface Corp_Persistence_Manager {

	/**
	 * @return string
	 */
	public function getSessionName();
	
	/**
	 * @param string $name
	 * @return void
	 * @throws Corp_Persistence_Exception
	 */
	public function setSessionName($name);
	
	/**
	 * @return boolean
	 */
	public function supportSessionInvokation();
	
	/**
	 * @return boolean
	 */
	public function supportMultipleSession();
	
	/**
	 * @param Corp_Service $service
	 * @return void
	 * @throws Corp_Persistence_Exception
	 */
	public function initialize(Corp_Service $service);
	
	/**
	 * @return Corp_Persistence_Session
	 * @event beforeSessionAgentChangesDetected
	 * @event afterSessionAgentChangesDetected
	 * @event onSessionAgentChanged
	 * @event beforeSessionInitialized
	 * @event afterSessionInitialized
	 * @event beforeSessionTouched
	 * @event afterSessionTouched
	 * @event beforeSessionSaltRegenerated
	 * @event afterSessionSaltRegenerated
	 */
	public function createCurrentSession(Corp_Service $service);
	
	/**
	 * @return Corp_Persistence_Session|null
	 */
	public function getCurrentSession();
	
	/**
	 * @return Corp_Persistence_Session|null
	 */
	public function getSessionBySID($sid);
	
	/**
	 * @return Corp_Persistence_Session[]
	 */
	public function getSessionsByIdentity(Corp_Auth_Identity $identity);
	
	/**
	 * @return Corp_Persistence_Session[]
	 */
	public function getSessions();
	
	/**
	 * @return void
	 * @throws Corp_Persistence_Exception
	 * @event beforeSessionExpired
	 * @event afterSessionExpired
	 */
	public function executeGarbageCollector(Corp_Service $service);
	
	/**
	 * @return void
	 * @throws Corp_Persistence_Exception
	 * @event beforePersistenceManagerWrite
	 * @event afterPersistenceManagerWrite
	 * @event beforeSessionWrited
	 * @event afterSessionWrited
	 */
	public function write(Corp_Service $service);
	
	/**
	 * @param Corp_Persistence_Session $session
	 * @return void
	 */
	public function writeSession(Corp_Persistence_Session $session);

	/**
	 * @param Corp_Persistence_Session $session
	 * @return void
	 */
	public function logoutSession(Corp_Persistence_Session $session);
	
	/**
	 * @param Corp_Persistence_Session $session
	 * @return void
	 */
	public function destroySession(Corp_Persistence_Session $session);
	
	/**
	 * @return string
	 */
	public function getSessionFactoryClass();
	
	/**
	 * @param string $class
	 */
	public function setSessionFactoryClass($class);
	
	/**
	 * @return string
	 */
	public function getIdentityFactoryClass();
	
	/**
	 * @param string $class
	 */
	public function setIdentityFactoryClass($class);
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @return Corp_Persistence_Session
	 */
	public function sessionFactoryContext(Corp_ExecutionContext $context);
	
	/**
	 * @param string $sid
	 * @param string $type
	 * @param Corp_Agent $agent
	 * @param Corp_Request_QoP $qop
	 * @param Corp_Auth_Identity $identity
	 * @return Corp_Persistence_Session
	 */
	public function sessionFactory($sid, $type, Corp_Agent $agent, Corp_Request_QoP $qop, Corp_Auth_Identity $identity);
	
	/**
	 * By default, return an anonymous identity.
	 * @param string $realm
	 * @param string|int $uid
	 * @param string $type Corp_Auth_Identity::TYPE_*
	 * @param string $name
	 * @return Corp_Auth_Identity
	 */
	public function identityFactory($realm = '', $uid = null, $type = Corp_Auth_Identity::TYPE_ANONYMOUS, $name = 'Anonymous');
	
	/**
	 * @return int In seconds
	 */
	public function getSessionExpirationDelay();
	
}

?>