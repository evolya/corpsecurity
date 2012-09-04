<?php

/**
 * GeoIP Plugin 
 * 
 * Requests a geo-IP-server to check, returns where an IP is located (state, country, town).
 * Using www.netip.de web service.
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
class Corp_Plugin_GeoIP implements Corp_Plugin {
	
	/**
	 * URL to webservice
	 * @var string
	 */
	public static $url = 'http://www.netip.de/search?query=%s';

	/**
	 * @var Corp_Service
	 */
	protected $service;
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
		
		// Save service
		$this->service = $service;
		
		// Register an event handler
		$service->subscribeEvent('afterCurrentSessionCreated', array($this, 'afterCurrentSessionCreated'), 20);
		
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param Corp_Persistence_Manager $persistence
	 * @param Corp_Persistence_Session $session
	 */
	public function afterCurrentSessionCreated(Corp_ExecutionContext $context, Corp_Persistence_Manager $persistence, Corp_Persistence_Session $session) {
		
		// Get session data
		$sessionData =& $session->getSessionData();
		
		// GeoIP data search has been allready done
		if (isset($sessionData['geoip_search_done'])) {
			return;
		}
		
		// Get request user agent
		$agent = $session->getUserAgent();
		
		// Local addresses can't be geolocated
		if ($agent->clientIP === '127.0.0.1' || fnmatch('192.168.*', $agent->clientIP)) {
			return;
		}
		
		// Event before
		if (!$this->service->broadcastEvent('beforeGeodataServiceRequested', array($session, $context, $this))) {
			return;
		}
		
		// Ask for geodata (will open a remote connection)
		$geodata = self::getGeoData($agent->clientIP);
		
		// Event after 
		if (!$this->service->broadcastEvent('afterGeodataServiceRequested', array(&$geodata, $session, $context, $this))) {
			return;
		}
		
		// Remember that geodata has been allready asked
		$sessionData['geoip_search_done'] = true;
		
		// Not geodata found
		if (!$geodata) {
			return;
		}
		
		// Update agent location
		foreach ($geodata as $entry => $value) {
			$agent->clientLocations[$entry] = $value;
		}

	}
	
	/**
	 * Retrieve GeoIP data from remote webservice.
	 * 
	 * @param string $ip
	 * @return string[]|false
	 */
	public static function getGeoData($ip) {
		
		// Check ip validity
		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			return false;
		}
		
		// Ask webservice
		$response = @file_get_contents(str_replace('%s', urlencode($ip), self::$url));
		
		// Check error
		if (empty($response)) {
			return false;
		}
		
		// Entries to find in result
		$patterns = array(
			'country'		=> '#Country: (.*?)&nbsp;#i',
			'state_name'	=> '#State/Region: (.*?)<br#i',
			'town_name'		=> '#City: (.*?)<br#i'
		);
		
		// Returned array
		$geodata = array();
		
		// Fetch entries
		foreach ($patterns as $entry => $pattern) {

			// Pattern matching
			$result = preg_match($pattern, $response, $value) && !empty($value[1]) ? trim($value[1]) : null;
			
			// Pattern not found
			if (!$result) {
				continue;
			}

			// Special treatment
			if ($entry === 'country') {
				
				// Empty result
				if ($result == '-') {
					continue;
				}
				
				// Split result
				list($code, $country) = explode('-', $result, 2);
				
				// Save data
				$geodata['country_code'] = trim($code);
				$geodata['country_name'] = trim($country);
			}
			
			// Generic treatment
			else {
				$geodata[$entry] = $result;
			}
		}
		
		// Return geodata
		return $geodata;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'geoip';
	}

}

?>