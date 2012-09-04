<?php

/**
 * DOCTODO
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
 * @package    evolya.corpsecurity.config
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Debugger_Events implements Corp_Plugin {
	
	/**
	 * @var mixed[][]
	 */
	protected $events = array();
	
	/**
	 * @var boolean
	 */
	public $printOnShutdown = false;
	
	/**
	 * @var boolean
	 */
	public $verboseMode = false;
	
	/**
	 * @var boolean
	 */
	public $detectErrors = false;
	
	/**
	 * @var string[]
	 */
	public $ignoreEvents = array('beforePluginAdded', 'afterPluginAdded', 'beforePluginInitialized', 'afterPluginInitialized');
	
	/**
	 * Renvoi la liste des événements qui ont été diffusés et réceptionnés par le debugger.
	 * 
	 * @return mixed[][]
	 */
	public function getLoggedEvents() {
		return $this->events;
	}
	
	/**
	 * @return void
	 * @stdout text/html
	 */
	public function printLoggerEvents() {
		
		// Header
		echo '<table id="eventsDebugger" border="1" style="font:13px arial,sans;margin:1em" cellspacing="0" cellpadding="2"><thead>';
		echo '<tr><th colspan="4" style="background:orange;color:white">Event Traces</th></tr>';
		echo '<tr><th>Lv</th><th>Event</th><th>Launcher</th><th>Arguments</th></tr></thead><tbody>';
		
		$color = 0;
		$colors = array('black', 'gray', 'black');
		
		// Body
		foreach ($this->events as $event) {
			if ($event['name'] == 'beforeMethod' || $event['name'] == 'beforeShutdown') $color = min($color + 1, 2);
			echo '<tr style="color:'.$colors[$color].'"><td>'  . ($event['depth'] + 1);
			echo '</td><th>' . htmlspecialchars($event['name']);
			echo '</th><td>' . htmlspecialchars(self::debugtrace2string($event['sender']));
			echo '</td><td>' . htmlspecialchars(implode(', ', self::array2string($event['args'], $this->verboseMode, false)));
			echo '</td></tr>';			
		}
		
		// Détection des errors
		if ($this->detectErrors) {
			
			// On récupère les erreurs
			$errors = $this->getErrors();
			
			// S'il y en a, on les affiche dans le tableau
			if (sizeof($errors) > 0) {
				echo '<tr><th colspan="4">Anomalies</th></tr><tr><td colspan="4">' . implode('</td><tr><tr><td colspan="4">', $errors) . '</td></tr>';
			}
			
		}
		
		// Footer
		echo '</tbody></table>';
		
	}
	
	/**
	 * Renvoi un tableau avec les erreurs rencontrées dans les events.
	 * 
	 * @return string[]
	 */
	public function getErrors() {

		$errors = array();
		
		foreach ($this->events as $event) {
			
			$name = $event['name'];
			
			// Mismatch error
			if (substr($name, 0, 6) == 'before' && $name != 'beforeShutdown') {
				if (self::countEvents('after' . substr($name, 6), $this->events) < 1) {
					$errors[] = "Event $name has no 'after' event, sended by " . self::debugtrace2string($event['sender']);
				}
			}
			else if (substr($event['name'], 0, 5) == 'after') {
				if (self::countEvents('before' . substr($name, 5), $this->events) < 1) {
					$errors[] = "Event $name has no 'before' event, sended by " . self::debugtrace2string($event['sender']);
				}
			}
			
			// Multiple
			$n = in_array($name, $this->ignoreEvents) ? 0 : self::countEvents($name, $this->events);
			if ($n > 1 && !in_array("Event $name is triggered $n times", $errors)) {
				$errors[] = "Event $name is triggered $n times"; 
			}
			
			// Not documented
			$doc = self::getDeclaredEvents($event['sender']['class'], $event['sender']['function']);
			if (!in_array($name, $doc)) {
				$errors[] = "Event $name is not declared by " . self::debugtrace2string($event['sender']);
			}

		}
		
		return $errors;

	}
	
	/**
	 * 
	 * @param unknown_type $class
	 * @param unknown_type $method
	 * @param unknown_type $followParent
	 * @return string[]
	 */
	protected static function getDeclaredEvents($class, $method, $followParent = true) {
		if (class_exists($class) || interface_exists($class)) {
			$_class = new ReflectionClass($class);
		}
		else {
			throw new ReflectionException("Class $class does not exist", 696);
		}
		$_method = $_class->getMethod($method);
		$doc = $_method->getDocComment();
		if (empty($doc)) {
			return array();
		}
		$r = array();
		// Parse doc comment
		$matches = array();
		preg_match_all('/@event (.*+)/', $doc, $matches);
		foreach ($matches[1] as $name) {
			$r[] = trim($name);
		}
		// Follow parent
		if (strpos($doc, '(non-PHPdoc)')) {
			$match = null;
			if ($followParent && preg_match('/@see (.*::.*)\\(/', $doc, $match)) {
				list($c, $m) = explode('::', $match[1], 2);
				try {
					$r = array_merge($r, self::getDeclaredEvents($c, $m, $followParent));
				}
				catch (ReflectionException $ex) {
					if ($ex->getCode() === 696) {
						throw new ReflectionException("Invalid 'see' target $c::$m(), declared in $class::$method()", 6);
					}
					throw $ex;
				}
			}
		}
		return $r;
	}
	
	/**
	 * Compte le nombre de fois qu'un event apparait dans la liste.
	 * 
	 * @param string $name
	 * @param mixed[] $list
	 * @return int
	 */
	protected static function countEvents($name, &$list) {
		$c = 0;
		foreach ($list as $event) {
			if ($event['name'] === $name) $c++;
		}
		return $c;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
		
		// Inscription au système de catch-all des events
		$service->catchAllEvents(array($this, 'catchEvent'));
		
		// Print des logs à la fin de l'execution de la page
		if ($this->printOnShutdown) {
			$service->subscribeEvent('afterShutdown', array($this, 'printLoggerEvents'));
		}
		
	}
	
	/**
	 * Réception de tous les events produits par le service.
	 */
	public function catchEvent($eventName) {
		
		// Ignore events
		if (in_array($eventName, $this->ignoreEvents)) {
			return;
		}
		
		// On recupère les arguments
		$args = func_get_args();
		array_shift($args);
		
		// On prépare une entrée de log
		$event = array('name' => $eventName);
		
		// On enregistre les traces d'execution
		$backtrace = debug_backtrace();
		
		// On retire l'appel à cette méthode
		array_shift($backtrace); // Corp_Debugger_Events::catchEvent (ici)
		array_shift($backtrace); // call_user_func_array dans Corp_AbstractService::broadcastEvent
		array_shift($backtrace); // Corp_AbstractService::broadcastEvent
		
		// On calcule la profondeur d'execution
		$event['depth'] = $this->getDepth($backtrace, true);
		
		// Le code qui a lancé l'event
		$event['sender'] = array_shift($backtrace);
		
		// Les arguments passés à l'event
		$event['args'] = $args;
		
		// On ajoute l'event dans les logs
		$this->events[] = $event;
		
	}
	
	/**
	 * Calcule de la profondeur d'un event dans la procédure globale.
	 * 
	 * @param array $backtrace
	 * @param boolean $eventsOnly
	 * @return int
	 */
	protected function getDepth(array $backtrace, $eventsOnly = false) {
		
		// Calcule de la profondeur
		// Si on cherche a avoir uniquement les événements, on va parcourir les
		// traces pour compter le nombre de fois où la méthode de broadcast des
		// events a été appelé.
		if ($eventsOnly) {
			$depth = 0;
			foreach ($backtrace as $trace) {
				if (isset($trace['class']) && isset($trace['function']) && $trace['class'] == 'Corp_AbstractService' && $trace['function'] == 'broadcastEvent') {
					$depth++;
				}
			}
			return $depth;
		}
		
		// Sinon, on cherche uniquement la profondeur globale de la pile 
		else {
			return sizeof($backtrace);
		}
		
	}
	
	/**
	 * Transformer une trace obtenue avec debug_backtrace() en format affichable
	 * @param mixed[] $trace
	 */
	public static function debugtrace2string(array $trace) {
		if (isset($trace['class'])) {
			return $trace['class'] . '::' . $trace['function'] . '()';
		}
		return $trace['function'] . '()';
	}
	
	/**
	 * Transformer les traces obtenues avec debug_backtrace() en format affichable
	 * @param mixed[] $traces
	 */
	public static function debugtraces2string(array $traces) {
		$r = array();
		foreach ($traces as $k => $trace) {
			$r[] = "#$k " . self::debugtrace2string($trace);
		}
		return implode("\n", $r);
	}
	
	/**
	 * Transformer un tableau en strings affichables.
	 * Le dernier argument n'est pas à spécifier.
	 * 
	 * @param mixed[] $array
	 * @param boolean $verbose
	 * @param boolean $useTostring
	 */
	public static function array2string(array $array, $verbose = false, $useTostring = true, array & $recursive = array()) {
		$r = array();
		foreach ($array as $arg) {
			if (!$verbose) {
				$r[] = is_object($arg) ? get_class($arg) : gettype($arg);
				continue;
			}
			if (is_object($arg)) {
				if ($useTostring && method_exists($arg, '__toString')) {
					$r[] = get_class($arg) . '{' . $arg->__toString() . '}';
				}
				else {
					$r[] = get_class($arg);
				}
			}
			else if (is_string($arg)) {
				if (strlen($arg) > 20) {
					$r[] = '"' . addslashes(substr($arg, 0, 17)) . '..."';
				}
				else {
					$r[] = '"' . addslashes($arg) . '"';
				}
			}
			else if ($arg === 0) {
				$r[] = '0';
			}
			else if ($arg === NULL) {
				$r[] = 'NULL';
			}
			else if (is_int($arg) || is_float($arg) || is_resource($arg)) {
				$r[] = "$arg";
			}
			else if (is_bool($arg)) {
				$r[] = ($arg ? 'TRUE' : 'FALSE');
			}
			else if (is_array($arg)) {
				if (in_array($arg, $recursive)) {
					$r[] = "#RECURSIVE#";
				}
				else {
					$recursive[] = $arg;
					$r[] = '[' . implode(', ', self::array2string($arg, $verbose, $useTostring, $recursive)) . ']';
				}
			}
			else {
				$r[] = gettype($arg);
			}
		}
		return $r;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'eventsdebugger';
	}
	
}

?>