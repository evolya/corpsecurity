<?php

/**
 * Context helper class
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
final class Corp_ExecutionContext {

	/**
	 * @var Object[] $data
	 */
	private $data = array();

	private $executor = 'system';

	private $parent = null;

	private $sub = array();

	private static $instance = null;

	/**
	 * 
	 */
	private function __construct(Corp_ExecutionContext $parent = null) {
		$this->parent = $parent;
	}

	public function trunk($executor) {
		// On fabrique un nouveau contexte
		$ctx = new Corp_ExecutionContext($this);
		// On lui donne son executor
		$ctx->executor = (string) $executor;
		// On clone les données
		$ctx->data = $executor->data;
		// On le renvoi
		return $ctx;
	}

	public function halt() {
		
	}

	public static function initialize() {

		// On ne peut appeler cette méthode qu'une fois, sinon on a une exception
		if (self::$instance !== null) {
			throw new Exception("ExecutionContext is allready created");
		}

		// Création du context
		$instance = new Corp_ExecutionContext();

		// On recupère les traces de l'execution
		$traces = debug_backtrace();

		// On enlève l'enregistrement concernant cette méthode
		array_shift($traces);

		// On parcours les traces
		foreach ($traces as $t) {
			// Pour trouver une classe
			if (isset($t['class'])) {
				
			}
		}

		var_dump($traces);


		// Enregistrement du contexte en static
		self::$instance = $instance;

		// On fabrique le tableau de retour, avec le contexte
		$r = array('context' => $instance);

		// On donne une méthode pour remettre le system user
		$r['resetExecutor'] = function () use ($instance) {
			$instance->executor = 'sysuser';
		};

		// On renvoi le tableau de sortie
		return $r;

	}

	public function getExecutor() {
		return $this->executor;
	}

	public function setExecutor($executor) {
		if ($executor == 'system') {
			throw new Corp_Exception($this, "SecurityException: can't set system as executor"); // TODO Changer classe d'exception
		}
		$this->executor = (string) $executor;
	}

	public function has($name) {
		return array_key_exists((string) $name, $this->data);
	}

	/**
	 * @param string $methodName
	 * @param mixed[] $arguments
	 */
	public function __call($methodName, $arguments) {
		$methodName = strtolower($methodName);
		if (substr($methodName, 0, 3) == 'get') {
			return $this->data[substr($methodName, 3)];
		}
		if (substr($methodName, 0, 3) == 'set') {
			// TODO Desactiver l'override ?
			$this->data[substr($methodName, 3)] = $arguments[0];
			return;
		}
		trigger_error("Invalid context data: $methodName", E_USER_WARNING);
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
		return get_class($this) . '[' . $this->executor . ']';
	}

}

?>