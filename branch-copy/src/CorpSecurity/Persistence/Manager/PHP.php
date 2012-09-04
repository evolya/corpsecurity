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
class Corp_Persistence_Manager_PHP extends Corp_Persistence_AbstractManager {
	
	/**
	 * @var string 
	 * @see http://php.net/manual/en/function.session-cache-limiter.php
	 */
	public $sessionCacheLimiter = 'private_no_expire';
	
	/**
	 * @var int (minutes)
	 * @var http://php.net/manual/en/function.session-cache-expire.php
	 */
	public $sessionCacheExpire = 30;
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessionExpirationDelay()
	 */
	public function getSessionExpirationDelay() {
		return $this->sessionCacheExpire * 60;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::setSessionName()
	 */
	public function setSessionName($name) {
		parent::setSessionName($name);
		if (headers_sent()) {
			// TODO Implémenter le changement du nom de la session
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::supportSessionInvokation()
	 */
	public function supportSessionInvokation() {
		return is_readable(session_save_path());
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
	 * @see Corp_Persistence_Manager::initialize()
	 * @event beforePHPSessionConfigured
	 * @event afterPHPSessionConfigured
	 * @event beforePHPSessionStarted
	 * @event afterPHPSessionStarted
	 */
	public function initialize(Corp_Service $service) {
		
		// Vérification de la bonne configuration du module de session
		if (!function_exists('session_start') || (function_exists('session_status') && session_status() === PHP_SESSION_DISABLED)) {
			throw new Corp_Exception_Internal($service->getCurrentContext(), 'PHP sessions are not supported');
		}
		
		// On enregistre le service
		$this->service = $service;

		// On vérifie que la session PHP ne soit pas déjà ouverte.
		// Pour ne pas rendre le script trop chiant, on ne lance une exception que si le nom de la session n'est pas
		// le même que celui qui a été configuré dans ce manager.
		if (session_id() !== '' && session_name() !== $this->sessionName) {
			throw new Corp_Exception_Persistence($service->getCurrentContext(), $this, 'A PHP session was allready started');
		}
		
		// On modifie le nom de la session
		if (session_name() !== $this->sessionName) {
			if ($service->broadcastEvent('beforeSessionNameChanged', array($this))) {
				session_name($this->sessionName);
				$service->broadcastEvent('afterSessionNameChanged', array($this));
			}
		}
		
		// On modifie la configuration
		if ($service->broadcastEvent('beforePHPSessionConfigured', array($this))) {
		
			// Désactive la réécriture des liens
			@ini_set('url_rewriter.tags', '');
			@ini_set('session.use_trans_sid', 0);
			
			// Désactive le cache des en-têtes pour les proxy
			@session_cache_limiter($this->sessionCacheLimiter);
			
			// Modifie le temps d'expiration de la session
			@session_cache_expire($this->sessionCacheExpire);
			
			// Event after
			$service->broadcastEvent('afterPHPSessionConfigured', array($this));
		
		}
		
		// On initialise la session si besoin
		if (session_id() === '') {
			if ($service->broadcastEvent('beforePHPSessionStarted', array($this))) {
				if (@!session_start()) {
					throw new Corp_Exception_Persistence($service->getCurrentContext(), $this, 'Unable to start the session');
				}
				$service->broadcastEvent('afterPHPSessionStarted', array($this));
			}
		}

	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_AbstractManager::initSession()
	 */
	protected function initSession(Corp_Service $service) {
		
		// Si des données corp sont déclarées dans la session
		if (array_key_exists('Corp_Session_Data', $_SESSION)) {
			
			// On vérifie que le type de donnée soit bon
			if (!($_SESSION['Corp_Session_Data'] instanceof Corp_Persistence_Session)) {
				throw new Corp_Exception_Internal($service->getCurrentContext(), 'Unkown corp session data type: '
						. (is_object($_SESSION['Corp_Session_Data']) ? get_class($_SESSION['Corp_Session_Data']) : gettype($_SESSION['Corp_Session_Data'])));
			}
			
			// Restoration de la session 
			if ($service->broadcastEvent('beforeSessionRestored', array($this, $_SESSION['Corp_Session_Data']))) {
				
				// On restore la session
				$this->currentSession = $_SESSION['Corp_Session_Data'];
				
				// On retire l'instance de la session
				unset($_SESSION['Corp_Session_Data']);
				
				// On bind la variable globale avec la session
				$this->currentSession->setDataArray($_SESSION);
				
				// Event after
				$service->broadcastEvent('afterSessionRestored', array($this, $this->currentSession));
				
			}
			
		}
		
		// Création d'une nouvelle session
		else {
			
			if ($service->broadcastEvent('beforeSessionCreated', array($this))) {
		
				// On fabrique une nouvelle session
				$this->currentSession = $this->sessionFactoryContext($service->getCurrentContext());
				
				// On bind la session avec la variable globale
				$this->currentSession->setDataArray($_SESSION);
				
				// Event
				$service->broadcastEvent('afterSessionCreated', array($this, $this->currentSession));
			
			}
		
		}

	}
	
	/**
	 * @param Corp_Agent $agent
	 * @return string
	 */
	public function createSID(Corp_Agent $agent) {
		// XTODO Y'a un problème ici : l'identifiant de session est construit automatiquement
		// à partir de l'identifiant de sesssion PHP. 
		return hash('sha1', session_name() . ':' . session_id() . ':' . $agent->getHashID());
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::getSessions()
	 */
	public function getSessions() {
		
		// Chemin vers le répertoire où sont enregistrés les fichiers de session
		$path = session_save_path();
		
		// Le fichier n'est pas lisibile, c'est une atteinte à la sécurité
		if (!is_readable($path)) {
			throw new Corp_Exception_Security_Forbidden(
				$this->service->getCurrentContext(),
				'Unable to read session save directory'
			);
		}
		
		// Tableau de sortie
		$r = array();
		
		// On parcours les fichiers de session
		foreach (glob($path . '/sess_*') as $file) {

			// Lecture du fichier
			$fg = @file_get_contents($file);
			
			// Erreur de lecture
			if ($fg === false) {
				trigger_error("Unable to read session file: $file", E_USER_WARNING);
				continue;
			}

			// Un fichier vide ne signie pas forcément une erreur, mais simplement une
			// session vide ou qui n'a pas encore été writée
			if (empty($fg)) {
				continue;
			}
			
			// Désérialisation
			$fg = self::unserialize_session($fg);
			
			// Ce n'est pas une session Corp valide
			if (!isset($fg['Corp_Session_Data'])) {
				continue;
			}
			if (!($fg['Corp_Session_Data'] instanceof Corp_Persistence_Session)) {
				continue;
			}
			
			// On ajoute la session dans le tableau de sortie
			$r[substr(basename($file), 5)] = $fg['Corp_Session_Data'];
			
		}
		
		// Returns
		return $r;
		
	}

	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::executeGarbageCollector()
	 */
	public function executeGarbageCollector(Corp_Service $service) {
		// Ici on ne fait rien, car PHP fait automatiquement le nettoyage
		// En revanche, il n'est pas possible de détecter les fermetures de session
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::write()
	 */
	public function write(Corp_Service $service) {
		
		// Event before
		if ($this->service->broadcastEvent('beforePersistenceManagerWrite', array($this))) {
			
			// Si la session actuelle existe, on y enregistre l'objet de session
			if ($this->currentSession !== null && $this->service->broadcastEvent('beforeSessionWrited', array($this->currentSession, $this))) {
				$_SESSION['Corp_Session_Data'] = $this->currentSession;
				$this->service->broadcastEvent('afterSessionWrited', array($this->currentSession, $this));
			}
			
			// On ferme la session PHP
			session_write_close();
			
			// Event after
			$this->service->broadcastEvent('afterPersistenceManagerWrite', array($this));
		}
	}
	
	/**
	 * @param Corp_Persistence_Session $session
	 * @return void
	 */
	public function writeSession(Corp_Persistence_Session $session) {
		// TODO Write de session PHP
		throw new Corp_Exception_UnsupportedOperation(null, 'Not implemented yet');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::logoutSession()
	 */
	public function logoutSession(Corp_Persistence_Session $session) {
		if ($this->service->broadcastEvent('beforeSessionLoggedOut', array($this, $session))) {
			$session->removeIdentity();
			$this->service->broadcastEvent('afterSessionLoggedOut', array($this, $session));
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_Manager::destroySession()
	 */
	public function destroySession(Corp_Persistence_Session $session) {
		if ($this->service->broadcastEvent('beforeSessionDestroyed', array($this, $session))) {
			
			// On supprime l'objet session
			$session->destroy();
			
			// Si la session est active, on va forcer la destruction de la session
			if ($session == $this->currentSession) {
				
				// Suppression des cookies
				if (ini_get('session.use_cookies')) {
					$params = session_get_cookie_params();
					@setcookie(
						session_name(),
						'',
						time() - 42000,
						$params['path'],
						$params['domain'],
						$params['secure'],
						$params['httponly']
					);
				}

				// Forcer la régénération d'un nouvel ID
				@session_regenerate_id();
				
				// Destruction de la session
				@session_destroy();
				
				// Reset de la variable globale
				$_SESSION = array();
				
			}
			
			$this->service->broadcastEvent('afterSessionDestroyed', array($this, $session));
		}
	}
	
	/**
	 * @param string $session_data
	 * @return mixed
	 * @throws Corp_Exception_Persistence
	 * @see http://www.php.net/manual/fr/function.session-decode.php
	 */
	public static function unserialize_session($session_data, $handler = null) {
		
		// On recupère le nom du handler
		$method = is_string($handler) ? $handler : ini_get('session.serialize_handler');
		
		switch ($method) {
			
			// Format PHP sérialisé
			case "php":
				return self::unserialize_php($session_data);
				break;
				
			// Format binaire
			case "php_binary":
				return self::unserialize_phpbinary($session_data);
				break;
				
			// Erreur
			default:
				throw new Corp_Exception_Persistence(null, null, "Unsupported session.serialize_handler: $method. Supported: php, php_binary");
				
		}
	}
	
	/**
	 * @param string $session_data
	 * @throws Corp_Exception_Persistence
	 * @return mixed
	 */
	private static function unserialize_php($session_data) {
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($session_data)) {
			if (!strstr(substr($session_data, $offset), '|')) {
				throw new Corp_Exception_Persistence(null, null, 'Invalid data, remaining: ' . substr($session_data, $offset));
			}
			$pos = strpos($session_data, '|', $offset);
			$num = $pos - $offset;
			$varname = substr($session_data, $offset, $num);
			$offset += $num + 1;
			$data = unserialize(substr($session_data, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}
	
	/**
	 * @param string $session_data
	 * @throws Corp_Exception_Persistence
	 * @return mixed
	 */
	private static function unserialize_phpbinary($session_data) {
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($session_data)) {
			$num = ord($session_data[$offset]);
			$offset += 1;
			$varname = substr($session_data, $offset, $num);
			$offset += $num;
			$data = unserialize(substr($session_data, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}
	
}

?>