<?php

/**
 * Abstract service helper class
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
 * @link       http://blog.evolya.fr/labo/fr/corpsecurity/
 */
abstract class Corp_AbstractService {

	/**
	 * @var Corp_Plugin[] 
	 */
	protected $plugins = array();
	
	/**
	 * @var mixed[]
	 */
	protected $eventSubscriptions = array();
	
	/**
	 * @param mixed Callable
	 */
	protected $catchAll = null;

	/**
	 * @param Corp_Plugin $plugin
	 * @return void
	 * @event beforePluginAdded
	 * @event beforePluginInitialized
	 * @event afterPluginInitialized
	 * @event afterPluginAdded
	 */
	public function addPlugin(Corp_Plugin $plugin) {
		
		// Event before installation
		if (!$this->broadcastEvent('beforePluginAdded', array($plugin))) {
			return;
		}
		
		// Get plugin name
		$name = $plugin->getPluginName();
		
		// Check if a plugin with this name allready exists
		if (isset($this->plugins[$name])) {
			throw new Corp_Exception_Internal("Plugin '$name' allready exists");
		}
		
		// Add plugin
		$this->plugins[$name] = array(
			$plugin,	// Plugin instance
			false		// Not initialized
		);
		
		// Event after installation
		$this->broadcastEvent('afterPluginAdded', array($plugin));
		
	}

	/**
	 * @param string $pluginName
	 * @return Corp_Plugin|null
	 */
	public function getPluginByName($pluginName) {
		return $this->plugins[$pluginName][0];
	}
	
	/**
	 * @param string $class
	 * @return Corp_Plugin|null
	 */
	public function getPluginByClass($class) {
		foreach ($this->plugins as $plugin) {
			if ($plugin[0] instanceof $class) {
				return $plugin[0];
			}
		}
		return null;
	}
	
	/**
	 * @param string $pluginName
	 * @return boolean
	 */
	public function isPluginInstalled($pluginName) {
		return isset($this->plugins[$pluginName]);
	}
	
	/**
	 * @param string $pluginName
	 * @return boolean
	 */
	public function removePlugin($pluginName) {
		if (isset($this->plugins[$pluginName])) {
			unset($this->plugins[$pluginName]);
		}
		return false;
	}

	/**
	 * @throws Exception
	 * @return void
	 * @event beforeServiceInitialized
	 * @event afterServiceInitialized
	 * @event beforeServiceExecuted
	 * @event afterServiceExecuted
	 */
	public function execute() {
		
		// Event before initialization
		if (!$this->broadcastEvent('beforeServiceInitialized', array($this))) {
			return;
		}
		
		// Initialisation
		$this->initialize();
		
		// Event after initialization
		$this->broadcastEvent('afterServiceInitialized', array($this));
		
		// Event before service execution
		if (!$this->broadcastEvent('beforeServiceExecuted', array($this))) {
			return;
		}
		
		// Execution du service
		$this->executeService();
		
		// Event after service execution
		$this->broadcastEvent('afterServiceExecuted', array($this));

	}
	
	/**
	 * @return void
	 * @event beforePluginInitialized
	 * @event afterPluginInitialized
	 */
	protected function initialize() {
		
		// Fetch plugins
		foreach ($this->plugins as $name => $plugin) {
		
			// List plugins data
			list($plugin, $initialized) = $plugin;
			
			// Allready initialized
			if ($initialized) {
				continue;
			}
			
			// Event before plugin initialization
			if (!$this->broadcastEvent('beforePluginInitialized', array($plugin))) {
				continue;
			}
			
			// Initialize plugin
			$plugin->initialize($this);
		
			// Mark plugin as loaded
			$this->plugins[$name][1] = true;
			
			// Event after plugin initialization
			$this->broadcastEvent('afterPluginInitialized', array($plugin));
			
		}

		
	}
	
	/**
	 * @throws Exception
	 * @return void
	 */
	protected abstract function executeService();

	/**
	 * Souscrire à tous les events. Il ne peut y avoir qu'un catch-all.
	 * Cette fonctionnalité sert essentiellement au debugger, elle ne doit
	 * pas être utilisée pour du code utilisateur à priori.
	 * 
	 * @param mixed $callback
	 */
	public function catchAllEvents($callback) {
		$this->catchAll = $callback;
	}
	
	/**
	 * Souscrire une callback à un event.
	 *
	 * Quand un événement est déclanché, il sera propragé à tous les listeners.
	 * Il est possible de contrôler l'ordre de propagation en utilisant
	 * la priorité.
	 *
	 * @param string $eventName Nom de l'event.
	 * @param callback $callback Callback.
	 * @param int $priority Ordre de priorité, à 100 par défaut.
	 * @return void
	 */
	public function subscribeEvent($eventName, $callback, $priority = 100) {

		// On fabrique un sous-tableau pour cet event s'il n'existe pas déjà
		if (!isset($this->eventSubscriptions[$eventName])) {
			$this->eventSubscriptions[$eventName] = array();
		}

		// Classement par priorité
		while (isset($this->eventSubscriptions[$eventName][$priority])) {
			$priority++;
		}

		// On inscrit la callback
		$this->eventSubscriptions[$eventName][$priority] = $callback;

		// On classe le tableau en fonction de la priorité
		ksort($this->eventSubscriptions[$eventName]);

	}

	/**
	 * Propager un événement.
	 *
	 * Cette méthode va diffuser l'event à tous les listeners. Si un listener renvoi FALSE,
	 * le processus est interrompu.
	 *
	 * Le paramètre $arguments sera envoyé à tous les listeners.
	 *
	 * @param string $eventName
	 * @param array $arguments
	 * @return boolean
	 */
	public function broadcastEvent($eventName, $arguments = array()) {
		
		// Catch-call sur les events
		if ($this->catchAll !== null) {
			// On ajoute le nom de l'event dans les arguments
			array_unshift($arguments, $eventName);
			// Et on appel la callback
			call_user_func_array($this->catchAll, $arguments);
			// On vire le nom de l'event
			array_shift($arguments);
		}

		// Résultat en sortie
		$r = true;
		
		// On vérifie qu'il existe des listeners sur cet event
		if (isset($this->eventSubscriptions[$eventName])) {

			// On parcours les listeners, qui seront récupérés dans l'ordre de priorité
			foreach($this->eventSubscriptions[$eventName] as $subscriber) {

				// Debug
				//echo "[$eventName>>".get_class($subscriber[0])."::".$subscriber[1]."]";
				
				// On lance l'appel à la callback
				$result = call_user_func_array($subscriber, $arguments);

				// Si la callback stope le processus
				if ($result === false) {
					$r = false;
					break;
				}

			}

		}

		// Renvoi du status sous forme de boolean
		return $r;

	}
	
	/**
	 * @throws Exception
	 */
	public function __sleep() {
		throw new Exception('Serialization of ' . get_class($this) . ' is forbidden');
	}
	
	/**
	 * @throws Exception
	 */
	public function __wakeup() {
		throw new Exception('Serialization of ' . get_class($this) . ' is forbidden');
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return get_class($this);
	}

}

?>