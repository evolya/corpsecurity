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
	 * @var string
	 */
	const VERSION = '3.2';

	/**
	 * @var Corp_ExecutionContext
	 */
	protected $context = null;

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

		// TODO $cacheDir n'est plus utilisé ?

		// SAPI Name
		$this->sapiName = PHP_SAPI;

	}

	/**
	 * Récupérer le contexte d'execution actuel. Si le contexte n'existe pas, il sera crééé.
	 * 
	 * @return Corp_ExecutionContext
	 */
	public function getCurrentContext() {

		// Le contexte n'existe pas, on va en créer un
		if (!$this->context) {
			return $this->createExecutionContext(false);
		}

		// On renvoi le contexte
		return $this->context;

	}
	
	/**
	 * Création du contexte d'execution.
	 * 
	 * @param boolean $broadcast
	 * @return Corp_ExecutionContext
	 * @event beforeRequestCreated
	 * @event afterRequestCreated
	 */
	protected function createExecutionContext($broadcast = false) {

		// Création du contexte d'execution
		$context = new Corp_ExecutionContext($this);

		// Set service
		$context->setService($this);

		// Event before
		if ($broadcast && !$this->broadcastEvent('beforeRequestCreated', array($context))) {
			return $context;
		}

		// Création de la requête
		$request = Corp_Request::create(
			$context,		// Current context
			$this->sapiName	// API name
		);

		// On passe la requête au contexte
		$context->setRequest($request);

		// Et l'agent
		$context->setAgent($request->getAgent());

		// Event after
		if ($broadcast) {
			$this->broadcastEvent('afterRequestCreated', array($request, $context));
		}

		// On renvoi le contexte
		return $context;

	}

	/**
	 * Execution du service Corp.
	 * 
	 * @throws Corp_Exception
	 * @event beforeCurrentContextCreated
	 * @event afterCurrentContextCreated
	 * @event beforeExceptionHandled
	 * @event afterExceptionHandled
	 */
	public function executeService() {
		
		// Des exceptions peuvent se produire, on try/catch pour les traiter depuis le service
		try {
			
			// Création du contexte
			if ($this->broadcastEvent('beforeCurrentContextCreated', array($this))) {
				
				// Create context object
				$this->context = $this->createExecutionContext(true);

				// Event after
				$this->broadcastEvent('afterCurrentContextCreated', array($this->context, $this));
			}

			// Enregistrement de la méthode de shutdown
			register_shutdown_function(array($this, 'shutdown'));

			// Traitement de la requête
			$this->invokeMethod(
				$this->context->getRequest()->METHOD,
				$this->context
			);
		
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
	 * @event onShutdown
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
		
		// On ferme la connexion avec le client
		$this->context->getRequest()->closeConnection();
		
		// Event during
		$this->broadcastEvent('onShutdown', array($this));
		
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