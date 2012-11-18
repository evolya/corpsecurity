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
 * @package    evolya.corpsecurity.plugins
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Plugin_NoFlood implements Corp_ServicePlugin {
	
	/**
	 * Minimum delay between each request.
	 * 
	 * @var float seconds
	 */
	public $minQueryDelay = 0.1000;
	
	/**
	 * Flood interval.
	 * 
	 * @var float seconds
	 */
	public $floodIntervalDelay = 5;
	
	/**
	 * Max allowed queries during flood interval.
	 *  
	 * @var int number of connexion
	 */
	public $floodIntervalMaxQuery = 50;
	
	/**
	 * Duration of short banishment.
	 * 
	 * @var int seconds
	 */
	public $shortBanDuration = 10;
	
	/**
	 * Reason of short banishment.
	 * 
	 * @var string
	 */
	public $shortBanReason = 'Flood detected';
	
	/**
	 * Maxmimum ban tolerated before long penality
	 * 
	 * @var int number of ban
	 */
	public $maxShortBan = 2;
	
	/**
	 * Duration of long banishment.
	 *
	 * @var int seconds
	 */
	public $longBanDuration = 3600;
	
	/**
	 * Reason of long banishment.
	 *
	 * @var string
	 */
	public $longBanReason = 'Too many flood detected';
	
	/**
	 * @var Corp_Service
	 */
	protected $service;
	
	/**
	 * @var Corp_Plugin_Gline
	 */
	protected $gline;
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {

		// Save service
		$this->service = $service;
		
		// Get K-Line plugin
		$this->gline = $service->getPluginByName('gline');
		
		// Plugin not found
		if (!$this->gline) {
			throw new Corp_Exception_Internal(
				$service->getCurrentContext(),
				'Gline plugin is required in order to use NoFlood plugin',
				500
			);
		}
		
		// Register an event handler, with maximum proprity
		$service->subscribeEvent('afterCurrentSessionCreated', array($this, 'afterCurrentSessionCreated'), 1);
		
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param Corp_Persistence_Manager $persistence
	 * @param Corp_Persistence_Session $session
	 * @event beforeFloodDetected
	 * @event afterFloodDetected
	 */
	public function afterCurrentSessionCreated(Corp_ExecutionContext $context, Corp_Persistence_Manager $persistence, Corp_Persistence_Session $session) {
		
		// Get session data
		$sessionData =& $session->getSessionData();
		
		// Detect flood
		$flood = $this->isSessionFloodingData($sessionData);
		
		// Save current timestamp
		$sessionData['flood_last'] = microtime(true);
		
		// Add a new connexion timestamp
		if (!isset($sessionData['flood_all'])) {
			$sessionData['flood_all'] = array();
		}
		$sessionData['flood_all'][] = $sessionData['flood_last'];
		
		// Flood detected
		if ($flood > 0) {
			
			// Event before
			if (!$this->service->broadcastEvent('beforeFloodDetected', array($context, $this))) {
				// Prevent ban
				return;
			}
			
			// Increment total flood counter
			$sessionData['flood_total'] = isset($sessionData['flood_total']) ? ($sessionData['flood_total'] + 1) : 1;
			
			// Too many ban : long penality
			if ($sessionData['flood_total'] > $this->maxShortBan) {
				// Add the new ban line
				$this->gline->addAgent(
					$session->getUserAgent(),							// User agent
					$_SERVER['REQUEST_TIME'] + $this->longBanDuration,	// Expiration datetime
					$this->longBanReason,								// Reason
					false												// Apply now
				);
			}
			// Short ban
			else {
				// Add the new ban line
				$this->gline->addAgent(
					$session->getUserAgent(),							// User agent
					$_SERVER['REQUEST_TIME'] + $this->shortBanDuration,	// Expiration datetime
					$this->shortBanReason,								// Reason
					false												// Apply now
				);
			}
			
			// Event after
			$this->service->broadcastEvent('afterFloodDetected', array($context, $this));
			
		}
		
	}
	
	/**
	 * @param Corp_Persistence_Session $session
	 * @return int
	 * 	0 : no flood
	 *  1 : flood flag allready set in session data
	 *  2 : yes, flood by min delay rule
	 *  3 : yes, flood by interval rule
	 */
	public function isSessionFlooding(Corp_Persistence_Session $session) {
		return $this->isSessionFloodingData($session->getSessionData());
	}
	
	/**
	 * @param mixed[] &$sessionData
	 * @return int
	 * 	0 : no flood
	 *  1 : yes, flood by min delay rule
	 *  2 : yes, flood by interval rule
	 */
	public function isSessionFloodingData(array &$sessionData) {

		// Current timestamp
		$now = microtime(true);
		
		// Min delay rule
		if (isset($sessionData['flood_last'])) {
			// Flood detected
			if ($sessionData['flood_last'] + $this->minQueryDelay > $now) {
				return 1;
			}
		}
		
		// Interval rule
		if (isset($sessionData['flood_all'])) {
			// Fetch data to remove past times
			foreach ($sessionData['flood_all'] as $k => $time) {
				if ($time + $this->floodIntervalDelay < $_SERVER['REQUEST_TIME']) {
					unset($sessionData['flood_all'][$k]);
				}
			}
			// Flood detected
			if (sizeof($sessionData['flood_all']) > $this->floodIntervalMaxQuery) {
				return 2;
			}
		}
		
		// No flood detected
		return 0;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'noflood';
	}

}

?>