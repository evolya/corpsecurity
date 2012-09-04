<?php

/**
 * Persistence manager using DBO storage
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
class Corp_Persistence_Manager_Database extends Corp_Persistence_Manager_PHP {

	/**
	 * @var Corp_Persistence_SessionORM
	 */
	protected $orm;
	
	/**
	 * @var Corp_Persistence_Session[] $sessionsToWrite
	 */
	protected $sessionsToWrite = array();
	
	/**
	 * @var boolean
	 */
	public $synchDataWithSessionGlobal = true;
	
	/**
	 * Constructor
	 * @param Corp_Persistence_ORM $orm
	 */
	public function __construct(Corp_Persistence_ORM_Session $orm) {
		$this->orm = $orm;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::supportSessionInvokation()
	 */
	public function supportSessionInvokation() {
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager_PHP::initialize()
	 */
	public function initialize(Corp_Service $service) {
		
		// Initialisation du parent
		parent::initialize($service);
		
		// Initialisation du gestionnaire ORM
		$this->orm->initialize($this);
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager_PHP::initSession()
	 */
	protected function initSession(Corp_Service $service) {
		
		// Get context
		$context = $service->getCurrentContext();
		
		// Get session unique ID
		$sid = $this->createSID($context->getAgent());
		
		// The session existe, juste restore it
		if ($this->orm->isEntryExists($sid)) {

			// Event before
			if (!$service->broadcastEvent('beforeSessionRestored', array($this, $sid))) {
				return true;
			}
			
			// Restore the entry
			$entry = $this->orm->getSession($sid);
			
			// Failure
			if (!$entry) {
				throw new Corp_Exception_Persistence($service->getCurrentContext(), $this, "Unable to select session: {$sid}");
			}
			
			// Create session with data
			$this->currentSession = $entry;
			
			// Synchronisation avec la variable globale $_SESSION
			if ($this->synchDataWithSessionGlobal) {
				
				// Restauration des données
				$_SESSION = $this->currentSession->getDataArray();
				
				// Bind dynamique (par référence)
				$this->currentSession->setDataArray($_SESSION);
				
			}
			
			// Event after
			$service->broadcastEvent('afterSessionRestored', array($this, $this->currentSession));
			
			// Indicate the session was restored
			return true;
			
		}
		
		// The session doesn't exists, create it
		else {
			
			if ($service->broadcastEvent('beforeSessionCreated', array($this, $sid))) {

				// On fabrique une nouvelle session
				$this->currentSession = $this->sessionFactoryContext($context);
				
				// Synchronisation avec la variable globale $_SESSION
				if ($this->synchDataWithSessionGlobal) {
					
					// Il s'agit d'une nouvelle session, par sécurité on va vider la variable globale
					$_SESSION = array();
					
					// Bind dynamique (par référence)
					$this->currentSession->setDataArray($_SESSION);
					
				}

				// Event
				$service->broadcastEvent('afterSessionCreated', array($this, $this->currentSession));
					
			}
			
			// indicate the session was created
			return false;
			
		}
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getCurrentSession()
	 */
	public function getCurrentSession() {
		return $this->currentSession;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessionByUID()
	 */
	public function getSessionBySID($sid) {
		return $this->orm−>getSession($sid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessionsByIdentity()
	 */
	public function getSessionsByIdentity(Corp_Auth_Identity $identity) {
		throw new Exception("Not implemented yet"); // XTODO Not implemented yet
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessions()
	 */
	public function getSessions() {
		return $this->orm->getSessions();
	}

	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::executeGarbageCollector()
	 */
	public function executeGarbageCollector(Corp_Service $service) {

		// On parcours les sessions expirées
		foreach ($this->orm->getExpiredSessions($_SERVER['REQUEST_TIME']) as $session) {
			
			// Event before
			if ($this->service->broadcastEvent('beforeSessionExpired', array($session, $this))) {
				
				// Delete
				if (!$this->orm->deleteSession($session)) {
					// TODO Traitement des erreurs
				}
				
				// Event after
				$this->service->broadcastEvent('afterSessionExpired', array($session, $this));
				
			}
			
		}
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::write()
	 */
	public function write(Corp_Service $service) {
		
		// Event before
		if (!$this->service->broadcastEvent('beforePersistenceManagerWrite', array($this))) {
			return;
		}
			
		// Si une session a été créée, on l'enregistrer en base de données
		if ($this->currentSession != null) {
			
			// Write this session
			$this->writeSessionNow($this->currentSession, true);

			// Et on ferme la session PHP
			// TODO Pourquoi c'est ici ça ?
			session_write_close();
			
		}
		
		// On parcours les sessions à écrire
		foreach ($this->sessionsToWrite as $session) {
			
			// La session active est déjà à jour
			if ($this->currentSession != null && $session->getSID() == $this->currentSession->getSID()) {
				continue;
			}
			
			// On enregistre les changements
			$this->writeSessionNow($session, false);
		}
		
		// Reset des sessions à écrire
		$this->sessionsToWrite = array();
		
		// Event after
		$this->service->broadcastEvent('afterPersistenceManagerWrite', array($this));
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::writeSession()
	 */
	public function writeSession(Corp_Persistence_Session $session) {

		// Debug
		/*$t = debug_backtrace();
		 unset($t[1]['object']);
		unset($t[1]['args']);
		print_r($t[1]);
		echo $session->getSID();*/
		
		// L'écriture des sessions se fait à la fin de l'execution du service. Donc quand on demande
		// l'enregistrement d'une session, elle est simplement enregistrée dans une pile et sera
		// réellement écrite en différé.
		$this->sessionsToWrite[$session->getSID()] = $session;
		
	}
	
	/**
	 * @param Corp_Persistence_Session $session
	 * @event beforeSessionWrited
	 * @event afterSessionWrited
	 * @event beforeSessionORMUpdate
	 * @event afterSessionORMUpdate
	 * @event beforeSessionORMInsert
	 * @event afterSessionORMInsert
	 */
	protected function writeSessionNow(Corp_Persistence_Session $session, $isCurrent = false) {

		// Debug
		/*$t = debug_backtrace();
		unset($t[1]['object']);
		unset($t[1]['args']);
		print_r($t[1]);
		echo $session->getSID();*/
		
		// Event before
		if (!$this->service->broadcastEvent('beforeSessionWrited', array($session, $this))) {
			return false;
		}
		
		// Synchronisation avec la variable globale $_SESSION
		if ($isCurrent && $this->synchDataWithSessionGlobal) {
			$session->setDataArray($_SESSION);
		}
		
		// Get the session unique ID
		$sid = $session->getSID();
		
		// Test if the entry exists
		if ($this->orm->isEntryExists($sid)) {

			// Mise à jour de l'entrée
			if ($this->service->broadcastEvent('beforeSessionORMUpdate', array($session, $this))) {
				$this->orm->updateSession($session);
				$this->service->broadcastEvent('afterSessionORMUpdate', array($session, $this));
			}
			
		}
		
		// If it doesn't, create it
		else if ($this->service->broadcastEvent('beforeSessionORMInsert', array($session, $this))) {
			$this->orm->insertSession($session);
			$this->service->broadcastEvent('afterSessionORMInsert', array($session, $this));
		}
		
		// Event after
		$this->service->broadcastEvent('afterSessionWrited', array($session, $this));
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::destroySession()
	 */
	public function destroySession(Corp_Persistence_Session $session) {
		
		// On s'assure que la session soit bien supprimée de la liste des sessions à modifier
		unset($this->sessionsToWrite[$session->getSID()]);
		
		// Et on supprime la session
		parent::destroySession($session);
		
	}
	
}

?>