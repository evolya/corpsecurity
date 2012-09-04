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
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
		
		$service->subscribeEvent('afterContextCreated', array($this, 'afterContextCreated'), 20);
		
	}
	
	/**
	 * Retrieve GeoIP data from remote webservice.
	 * 
	 * @param Corp_ExecutionContext $context
	 */
	public function afterContextCreated(Corp_ExecutionContext $context) {
		
		$place = $context->getAgent();
		
		$ip = $place->clientIP;
		
		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			return;
		}

		$response = @file_get_contents('http://www.netip.de/search?query=' . $ip);
		
		if (empty($response)) {
			return;
		}
		
		$patterns = array(
			'country'		=> '#Country: (.*?)&nbsp;#i',
			'state_name'	=> '#State/Region: (.*?)<br#i',
			'town_name'		=> '#City: (.*?)<br#i'
		);
			
		foreach ($patterns as $entry => $pattern) {
			
			$result = preg_match($pattern, $response, $value) && !empty($value[1]) ? trim($value[1]) : null;
			
			if (!$result) continue;
			
			if ($entry == 'country') {
				if ($result == '-') continue;
				list($code, $country) = explode('-', $result, 2);
				$place->clientLocations['country_code'] = trim($code);
				$place->clientLocations['country_name'] = trim($country);
			}
			else {
				$place->clientLocations[$entry] = $result;
			}
		}
		
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