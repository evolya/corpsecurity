<?php

/**
 * State-less persistence storage
 * (used for test only, it doesn't store the session effectively)
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
class Corp_Persistence_Manager_Stateless extends Corp_Persistence_AbstractManager {
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::supportSessionInvokation()
	 */
	public function supportSessionInvokation() {
		return false;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::supportMultipleSession()
	 */
	public function supportMultipleSession() {
		return false;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessionExpirationDelay()
	 */
	public function getSessionExpirationDelay() {
		return 0;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::initialize()
	 */
	public function initialize(Corp_Service $service) {
		$this->service = $service;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::createCurrentSession()
	 */
	public function createCurrentSession(Corp_Service $service) {
		$this->currentSession = $this->sessionFactory(
			'0',
			$this->factoryClassSession,
			new Corp_Agent(),
			new Corp_Request_QoP(),
			Corp_Auth_Identity::createAnonymous()
		);
		return $this->currentSession;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getCurrentSession()
	 */
	public function getCurrentSession() {
		$this->currentSession;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessionByUID()
	 */
	public function getSessionBySID($sid) {
		return null;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessionsByIdentity()
	 */
	public function getSessionsByIdentity(Corp_Auth_Identity $identity) {
		return array();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessions()
	 */
	public function getSessions() {
		return array();
	}

	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::executeGarbageCollector()
	 */
	public function executeGarbageCollector(Corp_Service $service) { }
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::write()
	 */
	public function write(Corp_Service $service) { }
	
	/**
	 * @param Corp_Persistence_Session $session
	 * @return void
	 */
	public function writeSession(Corp_Persistence_Session $session) { }
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::logoutSession()
	 */
	public function logoutSession(Corp_Persistence_Session $session) {
		if ($this->service->broadcastEvent('beforeSessionLogout', array($this, $session))) {
			$session->removeIdentity();
			$this->service->broadcastEvent('afterSessionLogout', array($this, $session));
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::destroySession()
	 */
	public function destroySession(Corp_Persistence_Session $session) {
		if ($this->service->broadcastEvent('beforeSessionDestroy', array($this, $session))) {
			$session->destroy();
			$this->service->broadcastEvent('afterSessionDestroy', array($this, $session));
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_AbstractManager::initSession()
	 */
	public function initSession(Corp_Service $service) {
		$this->currentSession = $this->sessionFactoryContext($service->getCurrentContext());
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_AbstractManager::createSID()
	 */
	public function createSID(Corp_Agent $agent) {
		return '' . rand(0, 9999999999);
	}
	
}

?>