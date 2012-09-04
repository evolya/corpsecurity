<?php

/**
 * Corp service. Main class of this library.
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
 * @package    evolya.corpsecurity
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Service extends Corp_AbstractService {
		
	/**
	 * @var Corp_ExecutionContext
	 */
	protected $context = null;
	
	/**
	 * @var Corp_Persistence_Manager
	 */
	protected $persistenceManager = null;
	
	/**
	 * @var Exception|int|string
	 */
	protected $last_error = null;
	
	/**
	 * @var string
	 */
	public $sapiName = null;
	
	/**
	 * @var string|null 'xml' or 'json', or null to disable handling
	 */
	public $handleExceptions = 'xml';
	
	/**
	 * @var boolean
	 */
	public $exposeExceptions = false;
	
	/**
	 * Constructor.
	 */
	public function __construct($cacheDir = null) {
		
		// SAPI Name
		$this->sapiName = PHP_SAPI;
		
		// Cache directory
		$this->cacheDir = is_string($cacheDir) ? $cacheDir : dirname(__FILE__) . '/cache/';

	}
	
	/**
	 * @return Corp_Persistence_Manager
	 */
	public function getPersistenceManager() {
		return $this->persistenceManager;
	}
	
	/**
	 * @param Corp_Persistence_Manager $manager
	 * @return void
	 */
	public function setPersistenceManager(Corp_Persistence_Manager $manager) {
		$this->persistenceManager = $manager;
	}
	
	/**
	 * @return string
	 */
	public function getCacheDirectory() {
		return $this->cacheDir;
	}
	
	/**
	 * Récupérer le contexte d'execution actuel. Si le contexte n'existe pas, il sera crééé.
	 * 
	 * @return Corp_ExecutionContext
	 * @event beforeContextCreated
	 * @event afterContextCreated
	 * @event beforeRequestCreated
	 * @event afterRequestCreated
	 */
	public function getCurrentContext() {
		
		// Le contexte n'existe pas, on va le créer
		if (!$this->context) {
			
			// Event before
			if (!$this->broadcastEvent('beforeContextCreated')) {
				return null;
			}
			
			// Création du contexte
			$this->context = $this->createExecutionContext();
			
			// Event after
			$this->broadcastEvent('afterContextCreated', array($this->context));
			
		}
		
		// On renvoi le contexte
		return $this->context;
		
		
	}
	
	/**
	 * Création du contexte d'execution.
	 * 
	 * @return Corp_ExecutionContext
	 * @event beforeRequestCreated
	 * @event afterRequestCreated
	 */
	protected function createExecutionContext() {
		
		// Création du contexte d'execution
		$context = new Corp_ExecutionContext($this);
		
		// Event before
		if ($this->broadcastEvent('beforeRequestCreated', array($context))) {
		
			// Création de la requête
			$request = Corp_Request::create($context, $this->sapiName);
			
			// On passe la requête au contexte
			$context->setRequest($request);
			
			// Event after
			$this->broadcastEvent('afterRequestCreated', array($request, $context));
			
		}
		
		// On renvoi le contexte
		return $context;
		
	}
	
	/**
	 * Execution du service Corp.
	 * 
	 * @throws Corp_Exception
	 * @event beforePersistenceManagerInitialized
	 * @event afterPersistenceManagerInitialized
	 * @event beforePersistenceManagerGcStarted
	 * @event afterPersistenceManagerGcStarted
	 * @event beforeCurrentSessionCreated
	 * @event afterCurrentSessionCreated
	 * @event beforeExceptionHandled
	 * @event afterExceptionHandled
	 */
	public function executeService() {
		
		// Construction du gestionnaire de persistence par défaut si aucun n'est renseigné
		// avant l'execution
		if (!$this->persistenceManager) {
			$this->persistenceManager = new Corp_Persistence_Manager_Stateless();
		}
		
		// Des exceptions peuvent se produire, on try/catch pour les traiter depuis le service
		try {
			
			// Récupération ou création du contexte
			$context = $this->getCurrentContext();

			// Enregistrement de la méthode de shutdown
			register_shutdown_function(array($this, 'shutdown'));
			
			// Initialisation du manager de persistence des données
			if ($this->broadcastEvent('beforePersistenceManagerInitialized', array($this->context, $this->persistenceManager))) {
				$this->persistenceManager->initialize($this);
				$this->broadcastEvent('afterPersistenceManagerInitialized', array($this->context, $this->persistenceManager));
			}
			
			// Lancement du garbage collector
			if ($this->broadcastEvent('beforePersistenceManagerGcStarted', array($this->context, $this->persistenceManager))) {
				$this->persistenceManager->executeGarbageCollector($this);
				$this->broadcastEvent('afterPersistenceManagerGcStarted', array($this->context, $this->persistenceManager));
			}
			
			// Création ou récupération de la session
			if ($this->broadcastEvent('beforeCurrentSessionCreated', array($this->context, $this->persistenceManager))) {
				$this->persistenceManager->createCurrentSession($this);
				$this->broadcastEvent('afterCurrentSessionCreated', array($this->context, $this->persistenceManager, $this->persistenceManager->getCurrentSession()));
			}
			
			// Traitement de la requête
			$this->invokeMethod($context->getRequest()->METHOD, $context);
		
		}
		catch (Exception $ex) {
			
			// On sauvegarde l'erreur
			$this->last_error = $ex;
			
			// Debug
			//foreach ($this->eventSubscriptions['beforeExceptionHandled'] as $handler) echo "\n".get_class($handler[0]).'::'.$handler[1];
			
			// Si aucun handler n'est spécifié, l'exception est propagée
			if (!$this->handleExceptions) {
				throw $ex;
			}
			
			// Event before
			if (!$this->broadcastEvent('beforeExceptionHandled', array($this->context, $ex))) {
				return;
			}
			
			// Header de réponse
			if (!$this->context->isHeaderResponseSent()) {
				header('HTTP/1.0 ' . $ex->getMessage() . ' ' . get_class($ex), false, $ex->getCode());
			}

			// Traitement de l'erreur par un handler
			switch (strtolower($this->handleExceptions)) {
				
				// XML
				case 'xml' :
					header('Content-type: text/xml');
					echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
					echo '<rsp status="error" code="'.$ex->getCode().'">';
					echo '<exception>'.get_class($ex).'</exception>';
					echo '<message>'.($this->exposeExceptions ? htmlspecialchars($ex->getMessage() . ', in ' . $ex->getFile() . '(' . $ex->getLine() . ')')
							 : 'Error ' . $ex->getCode()).'</message>';
					if ($this->exposeExceptions) {
						echo '<stack>' . htmlspecialchars($ex->getTraceAsString()) . '</stack>';
					}
					echo '</rsp>';
					break;

				// JSON
				case 'json' :
					header('Content-type: application/json');
					$r = array(
						'rsp' => 'error',
						'exception' => get_class($ex),
						'msg' => $this->exposeExceptions ? $ex->getMessage() . ', in ' . $ex->getFile() . '(' . $ex->getLine() . ')' : 'Error ' . $ex->getCode()
					);
					if ($this->exposeExceptions) {
						$r['stack'] = $ex->getTraceAsString();
					}
					echo json_encode($r);
					break;
				
				// Erreur de handler
				default :
					throw new Corp_Exception_InvalidArgument('Corp_Service::$handleExceptions', $this->handleExceptions, 'enum[xml,json]|null', $ex);

			}
			
			// Event after
			$this->broadcastEvent('afterExceptionHandled', array($this->context, $ex));
			
			// On renvoi un code d'erreur
			exit($ex->getCode());
		}
		
	}
	
	/**
	 * DOCTODO
	 * 
	 * @param string $method
	 * @param Corp_ExecutionContext $context
	 * @event beforeMethod
	 * @event afterMethod
	 */
	protected function invokeMethod($method, Corp_ExecutionContext $context) {

		if (!$this->broadcastEvent('beforeMethod', array($context, $method))) {
			return;
		}
		
		$this->broadcastEvent('afterMethod', array($context, $method));
		
	}
		
	/**
	 * Callback de fin d'execution.
	 * 
	 * @return void
	 * @event beforeShutdown
	 * @event afterShutdown
	 */
	public function shutdown() {

		// Event before
		if (!$this->broadcastEvent('beforeShutdown', array($this))) {
			return;
		}

		// On donne un délai illimité aux opérations de fermeture
		set_time_limit(0);
		
		// On laisse le script continuer si l'utilisateur interrompt la requête
		ignore_user_abort(true);
		
		// On commence par fermer la connexion avec le client
		$this->context->getRequest()->closeConnection();

		// Ensuite on donne la posibilité au gestionnaire de persistence d'écrire
		// ses données. Ce n'est pas une obligation, le gestionnaire peut aussi
		// choisir de faire ça avant, mais en tout cas cet appel se fera à chaque fois. 
		$this->persistenceManager->write($this);
		
		// Event after
		$this->broadcastEvent('afterShutdown', array($this));
		
	}
	
	/**
	 * @return boolean
	 */
	public function hasFailed() {
		return $this->last_error !== null;
	}
	
}

?>