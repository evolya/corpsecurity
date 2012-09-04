<?php

/**
 * Persistence manager using PHP sessions
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
abstract class Corp_Persistence_AbstractManager implements Corp_Persistence_Manager {
	
	/**
	 * @var Corp_Service
	 */
	protected $service;
	
	/**
	 * @var string
	 */
	protected $sessionName = 'PHPSESSID';
	
	/**
	 * @var string
	 */
	protected $factoryClassSession = 'Corp_Persistence_SessionBasic';
	
	/**
	 * @var string
	 */
	protected $factoryClassIdentity = 'Corp_Auth_Identity';
	
	/**
	 * @var Corp_Persistence_Session
	 */
	protected $currentSession = null;
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessionName()
	 */
	public function getSessionName() {
		return $this->sessionName;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::setSessionName()
	 */
	public function setSessionName($name) {
		$this->sessionName = $name;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::createCurrentSession()
	 */
	public function createCurrentSession(Corp_Service $service) {

		// Event before
		$restored = false;
		if ($service->broadcastEvent('beforeSessionInitialized', array($this))) {
			// Initialisation de la session
			$restored = $this->initSession($service);
		}
		
		// On vérifie que la session ai bien été initialisée
		if (!($this->getCurrentSession() instanceof Corp_Persistence_Session)) {
			// Il s'agit d'une erreur interne
			// XTODO Trouver une meilleur exception, genre RuntimeException ?
			throw new Corp_Exception_NotFound($service->getCurrentContext(), 'Current session should be created');
		}
		
		// Event before
		if ($service->broadcastEvent('beforeSessionAgentChangesDetected', array($this, $this->currentSession))) {
		
			// On lui demande de signaler les changements sur l'agent (comme le changement d'IP ou de passerelle)
			$changes = $this->currentSession->getUserAgent()->detectChanges(
				$service->getCurrentContext()->getAgent()
			);
			
			// S'il y a des changements
			if (sizeof($changes) > 0) {
				if (!$service->broadcastEvent('onSessionAgentChanged', array($this, $this->currentSession, $changes))) {
					// On supprime la session actuelle
					$this->currentSession = null;
				}
			}

			// Event after
			$service->broadcastEvent('afterSessionAgentChangesDetected', array($this, $this->currentSession, $changes));
			
		}
		
		// Régénération du salt si besoin
		if (!$this->currentSession->getSalt()) {
			if ($service->broadcastEvent('beforeSessionSaltRegenerated', array($this, $this->currentSession))) {
				$this->currentSession->regenerateSalt();
				$service->broadcastEvent('afterSessionSaltRegenerated', array($this, $this->currentSession));
			}
		}
		
		// On met à jour la session
		if ($service->broadcastEvent('beforeSessionTouched', array($this, $this->currentSession))) {
			$this->currentSession->touch();
			$service->broadcastEvent('afterSessionTouched', array($this, $this->currentSession));
		}
		
		// Event after
		$service->broadcastEvent('afterSessionInitialized', array($this, $this->currentSession, $restored));
		
		// Return
		return $this->currentSession;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::sessionFactoryContext()
	 */
	public function sessionFactoryContext(Corp_ExecutionContext $context) {
		
		// L'agent
		$agent = $context->getAgent();
		
		// Et la requête
		$request = $context->getRequest();
		
		// On détermine l'identifiant unique de la session
		$sid = $this->createSID($agent);

		// On fabrique la session
		return new $this->factoryClassSession(
			$sid,								// session unique id
			$request->getSAPIName(),			// nom d'api
			$agent,								// agent
			$qop = $request->getQoP(),			// QoP flags
			new $this->factoryClassIdentity(),	// identity TODO Est-ce que c'est normal que ce soit dans la persistence qu'on fabrique une identité ?
			$this->getSessionExpirationDelay()	// Expiration delay (seconds)
		);
		
	}
	
	/**
	 * @param string $sid
	 * @param string $type
	 * @param Corp_Agent $agent
	 * @param Corp_Request_QoP $qop
	 * @param Corp_Auth_Identity $identity
	 * @return Corp_Persistence_Session
	 */
	public function sessionFactory($sid, $type, Corp_Agent $agent, Corp_Request_QoP $qop, Corp_Auth_Identity $identity) {
		return new $this->factoryClassSession(
			$sid,								// session unique id
			$type,								// nom d'api
			$agent,								// agent
			$qop,								// QoP flags
			$identity,							// identity
			$this->getSessionExpirationDelay()	// Expiration delay (seconds) IMPROVE Faire en sorte de ne pas écraser le TTL de la session au restore
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getCurrentSession()
	 */
	public function getCurrentSession() {
		return $this->currentSession;
	}
	
	/**
	 * @return string
	 */
	public function getSessionFactoryClass() {
		return $this->factoryClassSession;
	}
	
	/**
	 * @param string $class
	 */
	public function setSessionFactoryClass($class) {
		$this->factoryClassSession = $class;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getIdentityFactoryClass()
	 */
	public function getIdentityFactoryClass() {
		return $this->factoryClassIdentity;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::setIdentityFactoryClass()
	 */
	public function setIdentityFactoryClass($class) {
		$this->factoryClassIdentity = $class;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessionByUID()
	 */
	public function getSessionBySID($sid) {
		
		$sessions = $this->getSessions();
		
		return array_key_exists($sid, $sessions) ? $sessions[$sid] : null;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessionsByIdentity()
	 */
	public function getSessionsByIdentity(Corp_Auth_Identity $identity) {
		
		$sessions = $this->getSessions();
		
		$r = array();
		
		foreach ($sessions as $sid => $sess) {
			
			if ($sess->getIdentity()->getIdentityUID() === $identity->getIdentityUID()) {
				
				$r[$sid] = $sess;
				
			}
			
		}
		
		return $r;
		
	}

	/**
	 * Créer ou restaurer la session, et l'enregistrer dans $this->currentSession
	 *
	 * @param Corp_Service $service
	 * @return boolean TRUE is session was restored
	 * @throws Corp_InternalException
	 * @event beforeSessionRestored
	 * @event afterSessionRestored
	 * @event beforeSessionAgentChanged
	 * @event afterSessionAgentChanged
	 * @event beforeSessionCreated
	 * @event afterSessionCreated
	 */
	protected abstract function initSession(Corp_Service $service);
	
	/**
	 * @param Corp_Agent $agent
	 * @return string
	 */
	public abstract function createSID(Corp_Agent $agent);
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::identityFactory()
	 */
	public function identityFactory($realm = '', $uid = null, $type = Corp_Auth_Identity::TYPE_ANONYMOUS, $name = 'Anonymous') {
		return new $this->factoryClassIdentity($realm, $uid, $type, $name);
	}
	
}

?>