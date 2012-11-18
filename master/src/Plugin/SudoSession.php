<?php

/**
 * Session with sudo support
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
class Corp_Persistence_SessionSudo extends Corp_Persistence_SessionBasic {

	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Session::__construct()
	 */
	public function __construct($sid, $type, Corp_Agent $agent, Corp_Request_QoP $qop, Corp_Auth_Identity $identity, $expirationDelay) {
		parent::__construct($sid, $type, $agent, $qop, $identity, $expirationDelay);
		$this->sessionData['identity_real'] = $identity;
	}

	/**
	 * @return Corp_Auth_Identity
	 */
	public function getRealIdentity() {
		return $this->sessionData['identity_real'];
	}

	/**
	 * @return Corp_Auth_Identity|null
	 */
	public function getSudoIdentity() {
		return $this->isSudoUsed() ? $this->sessionData['identity_real'] : null;
	}

	/**
	 * @param Corp_Auth_Identity $identity
	 * @return void
	 */
	public function setSudoIdentity(Corp_Auth_Identity $identity = null) {
		if (!$identity) {
			$this->identity = $this->sessionData['identity_real'];
		}
		else {
			$this->identity = $identity;
		}
	}

	/**
	 * @return boolean
	 */
	public function isSudoUsed() {
		return $this->identity->getIdentityType() != Corp_Auth_Identity::TYPE_ANONYMOUS && $this->identity->getIdentityUID() === $this->sessionData['identity_real']->getIdentityUID();
	}


	/**
	 * @return void
	 */
	public function unSudo() {
		$this->setSudoIdentity(null);
		// TODO Il faudrait pouvoir propager à $persistence->writeSession($this) pour que le changement soit effectif
	}

}


/**
 * Bring sudo support to corp system
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
class Corp_Plugin_SudoSession implements Corp_Plugin {
	
	/**
	 * @var Corp_Persistence_SessionORM
	 */
	protected $orm;
	
	/**
	 * @var Corp_Service
	 */
	protected $service;
	
	/**
	 * @var int Expiration delay in seconds (10 minutes by default)
	 */
	public $sudoExpirationDelay = 600;
	
	/**
	 * Constructor
	 * @param Corp_Persistence_SessionORM $orm
	 */
	public function __construct(Corp_Persistence_ORM_Session $orm) {
		$this->orm = $orm;
	}
	
	/**
	 * @param Corp_Service $service
	 * @return void
	 */
	public function initialize(Corp_Service $service) {
				
		// On enregistre le service
		$this->service = $service;
		
		// On change la classe de factory par défaut pour les sessions
		$service->getPluginByClass('Corp_Persistence_Manager')->setSessionFactoryClass('Corp_Persistence_SessionSudo');
		
		// Et on enregistre un listener pour intervenir à la fin du lancement du garbage collector
		$service->subscribeEvent('beforePersistenceManagerGcStarted', array($this, 'beforePersistenceManagerGcStarted'));
		
	}
	
	/**
	 * Cette callback permet de unsudo les sessions inactives depuis un certain temps.
	 * 
	 * @return void
	 * @event beforeSessionUnSudo
	 * @event afterSessionUnSudo
	 */
	public function beforePersistenceManagerGcStarted(Corp_ExecutionContext $context, Corp_Persistence_Manager $persistence) {
		
		// On parcours les sessions dont le sudo a expiré
		foreach ($this->orm->getExpiredSessions($_SERVER['REQUEST_TIME'] + $persistence->getSessionExpirationDelay() - $this->sudoExpirationDelay) as $session) {
			
			// On ne fait cette opération que sur les sessions qui sont en sudo
			if (!$session->isSudoUsed()) {
				continue;
			}
			
			// Event before
			if (!$this->service->broadcastEvent('beforeSessionUnSudo', array($session))) {
				continue;
			}

			// On supprime l'identité du sudo
			$session->unSudo();
			
			// On enregistre les modifications
			$persistence->writeSession($session);
				
			// Event after
			$this->service->broadcastEvent('afterSessionUnSudo', array($session));
			
		}
		
	}
	
	/**
	 * @return string
	 */
	public function getPluginName() {
		return 'sudosession';
	}
	
}

?>