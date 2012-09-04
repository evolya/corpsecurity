<?php

/**
 * Set up "Bans" hostmasks for service access deny.
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
 * @package    evolya.corpsecurity.plugins
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Plugin_Gline implements Corp_Plugin {
	
	/**
	 * @var string[]
	 */
	protected $rules = array();
	
	/**
	 * @var Corp_Service
	 */
	protected $service;
	
	/**
	 * @var string
	 */
	protected $filename;
	
	/**
	 * @var boolean
	 */
	protected $rulesHasChanged = false;
	
	/**
	 * @var boolean
	 */
	public $deleteExpiredRules = true;
	
	/**
	 * @var boolean
	 */
	public $displayPenalityRemaining = true;
	
	/**
	 * Constructor.
	 * 
	 * @param string $filename Path to file used to record glines
	 */
	public function __construct($filename) {
		
		// Save file path
		$this->filename = $filename;
		
		// If the file doesn't exists, it not a mess
		if (!is_file($filename)) {
			return;
		}
		
		// Open the file (read only)
		$fp = fopen($filename, 'r');
		
		// Handle failure
		if (!$fp) {
			throw new Corp_Exception_FileNotReadable(
				null,
				$filename,
				'Invalid gline file'
			);
		}
		
		// Fetch rules
		$rules = explode("\n", stream_get_contents($fp));
		$keys = array('hostmask', 'expiration', 'reason');
		foreach ($rules as $rule) {
			if (empty($rule) || substr($rule, 0, 1) == '#') {
				continue;
			}
			$this->rules[] = array_combine($keys, explode(' ', $rule, 3));
		}
		
		// Close file
		fclose($fp);
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
		
		// Save service
		$this->service = $service;
		
		// Register a callback to stop request if agent hostmask is catched (with maximum proprity)
		$service->subscribeEvent('afterCurrentContextCreated', array($this, 'afterCurrentContextCreated'), 1);
		
		// Subscribe a callback to run write
		$this->service->subscribeEvent('onShutdown', array($this, 'write'));
		
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param Corp_Service $service
	 */
	public function afterCurrentContextCreated(Corp_ExecutionContext $context, Corp_Service $service) {
		$this->applyRules($context->getAgent(), true);
	}
	
	/**
	 * @param Corp_Agent $agent
	 * @param boolean $exit
	 * @return int|false
	 */
	protected function applyRules(Corp_Agent $agent = null, $exit = true) {
		
		// Get current agent
		if (!$agent) {
			$agent = $this->service->getCurrentContext()->getAgent();
		}
		
		// Fetch rules
		foreach ($this->rules as $key => &$rule) {
		
			// Check if rule is expired
			if ($rule['expiration'] < $_SERVER['REQUEST_TIME']) {
		
				// Delete rule
				if ($this->deleteExpiredRules) {
		
					// Unset entry
					unset($this->rules[$key]);
		
					// Remember rules has changed
					$this->rulesHasChanged = true;
		
				}
		
				// Next
				continue;
		
			}
			
			// Apply this rule
			if ($this->applyRule($rule, $exit, $agent)) {
				// Return applied rule key
				return $key;
			}
			
		}
		
		// Nothing happened
		return false;
		
	}

	/**
	 * @param mixed[] $rule
	 * @param boolean $exit
	 * @param Corp_Agent $agent
	 * @return boolean
	 * @event beforeGlineBlock
	 * @event afterGlineBlock
	 */
	protected function applyRule(array $rule, $exit = true, Corp_Agent $agent = null) {
		
		// Get current agent
		if (!$agent) {
			$agent = $this->service->getCurrentContext()->getAgent();
		}
		
		// Agent IP
		$ip = $agent->clientIP;
		
		// Agent Host
		$host = $agent->clientHost;
		
		// Deny flag
		$deny = false;
				
		// IP Matching
		if (fnmatch($rule['hostmask'], $ip)) {
			$deny = true;
		}

		// Host matching
		else if (is_string($host)) {
			if (fnmatch($rule['hostmask'], $host)) {
				$deny = true;
			}
		}

		// Connexion is allowed
		if (!$deny) {
			return false;
		}

		// Event before
		if (!$this->service->broadcastEvent('beforeGlineBlock', array($rule, $agent, $this))) {
			// Prevent block
			return false;
		}
		
		// Connexion is refused
		header('HTTP/1.0 406 Blocked', 406, true);
		header('Content-type: text/plain');
		
		// Display the reason
		echo $rule['reason'];
		
		// Remaning penality delay
		if ($this->displayPenalityRemaining) {
			echo ' (' . ($rule['expiration'] - $_SERVER['REQUEST_TIME']) . ' seconds penality)';
		}
		
		// End of line
		echo PHP_EOL;
		
		// Event after
		$this->service->broadcastEvent('afterGlineBlock', array($rule, $agent, $this));

		// Stop script execution not gracefully
		if ($exit) {
			
			$this->service->shutdown();
			
			exit();
		}
		
		// Returns true
		return true;
		
	}

	/**
	 * @param Corp_Agent $agent User agent to banish
	 * @param int $expiration Banishment expiration datetime
	 * @param string $reason Reason of banishment
	 * @param boolean $applyNow Run rules after add
	 * @return boolean
	 */
	public function addAgent(Corp_Agent $agent, $expiration, $reason, $applyNow = true) {
		return $this->addHostmask($agent->clientIP, $expiration, $reason, $applyNow);
	}

	/**
	 * Pattern:
	 *  <host|ip>
	 * Example:
	 *  *.aol.com
	 *  158.201.???.174
	 * 
	 * @param string $hostmask Hostmask to match
	 * @param int $expiration Banishment expiration datetime
	 * @param string $reason Reason of banishment
	 * @param boolean $applyNow Run rules after add
	 * @return int Rule ID
	 */
	public function addHostmask($hostmask, $expiration, $reason, $applyNow = true) {

		// Search if the rule allready exists
		$exists = false;
		foreach ($this->rules as $key => &$rule) {
			
			// Rule allready exists
			if ($rule['hostmask'] === $hostmask && $rule['reason'] === $reason) {
				
				// Expiration time hasn't changed, don't update
				if ($rule['expiration'] === $expiration) {
					return $key;
				}

				// Just update expiration
				$rule['expiration'] = $expiration;

				// Remember that rule allready exists
				$exists = true;
				
				// Stop looping
				break;

			}

		}

		// Create a new rule
		if (!$exists) {
			
			// Add entry
			$this->rules[] = array(
				'hostmask'		=> $hostmask,
				'expiration'	=> $expiration,
				'reason'		=> $reason
			);
			
			// Get key
			$key = array_keys($this->rules);
			$key = array_pop($key);
			
		}

		// Remember that callback has been allready registered
		$this->rulesHasChanged = true;
		
		// Execute now
		if ($applyNow) {
			
			// Rule matches
			if ($this->applyRule($this->rules[$key], false)) {
				
				// Shutdown service
				// TODO C'est pas super stable ce truc, dans l'idée ça permet au manager de persistence
				// de bien writer la session et donc d'enregistrer les trucs stoqués par les plugins
				// pour mémoriser ce qui a provoqué la gline.
				// Dans les faits, ça marche ici mais pas plus haut dans applyRule(), car l'arrêt
				// peut intervenir avant l'initialisation des composants...
				// Donc pour le moment, il est conseillé de ne pas utiliser $applyNow et d'arrêter le
				// script autrement.
				$this->service->shutdown();
				
				// Then stop script execution
				exit();
				
			}
			
		}

		// Return the rule key
		return $key;

	}
	
	/**
	 * @return boolean
	 */
	public function write() {

		// No changes to write
		if (!$this->rulesHasChanged) {
			return;
		}
		
		// Event before
		if (!$this->service->broadcastEvent('beforeGlineWrite', array(&$this->rules, $this->filename, $this->service->getCurrentContext(), $this))) {
			// Prevent writing
			return;
		}
		
		// Copy rules
		$rules = $this->rules;
		
		// Convert data to strings
		foreach ($rules as &$rule) {
			$rule = implode(' ', $rule);
		}
		
		// Write
		file_put_contents($this->filename, implode("\n", $rules));
		
		// Event after
		$this->service->broadcastEvent('afterGlineWrite', array(&$this->rules, $this->filename, $this->service->getCurrentContext(), $this));
		
	}

	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'gline';
	}

}

?>