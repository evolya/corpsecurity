<?php

/**
 * User agent helper object
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
class Corp_Agent implements Corp_Serializable {

	/**
	 * @var string HTTP or CLI
	 */
	public $type					= 'UNKNOWN';
	
	/**
	 * @var string
	 */
	public $agentRaw				= 'unknown';
	
	/**
	 * @var string
	 */
	public $agentName				= 'unknown';
	
	/**
	 * @var string
	 */
	public $agentVersion			= 'unknown';
	
	/**
	 * @var string
	 */
	public $clientIP				= '0.0.0.0';
	
	/**
	 * @var string|false
	 */
	public $clientHost				= false;
	
	/**
	 * @var string[]
	 */
	public $clientLocations		= array();
	
	/**
	 * @var string
	 */
	public $osName					= 'unknown';
	
	/**
	 * @var string
	 */
	public $osVersion				= 'unknown';
	
	/**
	 * @var string[]
	 */
	public $extraDataUnique		= array();
	
	/**
	 * @var string[]
	 */
	public $extraData				= array();
	
	public static function create(Corp_Request $request) {
		
		// Création de l'objet d'agent
		$agent = new Corp_Agent();
		
		switch (get_class($request)) {
			
			case 'Corp_Request_HTTP' :
				$agent->type				= 'HTTP';
				$agent->agentRaw			= $request->SERVER['HTTP_USER_AGENT'];
				//$agent->agentName			= $request->USER_AGENT['Browser'];
				//$agent->agentVersion		= $request->USER_AGENT['Version'];
				$agentInfo = self::parse_user_agent($agent->agentRaw);
				$agent->agentName			= $agentInfo['browser'];
				$agent->agentVersion		= $agentInfo['version'];
				$agent->osName				= $agentInfo['platform'];
				$agent->clientIP			= $request->SERVER['REMOTE_ADDR']; // TODO proxy ?
				$agent->clientHost			= @gethostbyaddr($agent->clientIP);
				$agent->clientLocations		= self::getGeoIPData();
				/*$agent->osName				= $request->USER_AGENT['Platform']; 
				$agent->osVersion			= $request->USER_AGENT['Platform_Version'];*/
				break;
				
			case 'Corp_Request_CLI' :
				$agent->type				= 'CLI';
				$agent->agentRaw			= 'toto';//$request->SERVER['COLORTERM']; // TODO C'est standard ça ?
				//$agent->agentName			= $request->SERVER['COLORTERM'];
				$agent->clientIP			= '127.0.0.1';
				$agent->clientHost			= 'localhost';
				$agent->osName				= php_uname('s');
				$agent->osVersion			= php_uname('r');
				$agent->extraDataUnique		= array_intersect_key($request->SERVER, array(
					'XDG_SESSION_PATH' => 1, 'XDG_SEAT_PATH' => 1, 'SESSION_MANAGER' => 1, 'PATH' => 1,
					'DESKTOP_SESSION' => 1, 'GNOME_KEYRING_PID' => 1, 'GDMSESSION' => 1, 'LOGNAME' => 1,
					'XDG_CURRENT_DESKTOP' => 1, 'XAUTHORITY' => 1, 'PHP_SELF' => 1, 'HOME' => 1
				));
				break;
			
			default :
				return null;
			
		}
		
		return $agent;
	}
	
	public function supportHttpHeaders() {
		return $this->type === 'HTTP';
	}
	
	public function getLocationQualifiedName() {
		return empty($this->clientLocations) ? 'Unkown' : implode('; ', $this->clientLocations);
	}
	
	public function getHashID() {
		
		// On merge les tokens identifiants
		$tokens = array_merge(
			array($this->type, $this->agentRaw),
			$this->extraDataUnique
		);
		
		// On renvoi le hash "unique" de l'agent
		return hash('sha256', implode(':', $tokens));
		
	}
	
	// Move dans le plugin geoip ? avec une event beforeGetProperty ?
	public static function getGeoIPData() {
		
		$map = array(
			'country_code'		=> array('GEOIP_COUNTRY_CODE'),
			'country_name'		=> array('GEOIP_COUNTRY_NAME'),
			'region_name'		=> array('GEOIP_REGION'),
			'state_name'		=> array('GEOIP_STATE'),
			'town_name'			=> array('GEOIP_CITY'),
			'street_name'		=> array('GEOIP_STREET_NAME'),

			'map_region_code'	=> array('GEOIP_REGION'),
			'dma_code'			=> array('GEOIP_DMA_CODE'),
			'area_code'			=> array('GEOIP_AREA_CODE'),
				
			'lat'				=> array('GEOIP_LATITUDE'),
			'lng'				=> array('GEOIP_LONGITUDE')
		);
		
		$r = array();
		foreach ($map as $entry => $keys) {
			foreach ($keys as $key) {
				if (isset($_SERVER[$key])) {
					$r[$entry] = $_SERVER[$key];
				}
			}
		}
		return $r;
	}
	
	/**
	 * Parses a user agent string into its important parts
	 *
	 * @author Jesse G. Donat <donatj@gmail.com>
	 * @link https://github.com/donatj/PhpUserAgent
	 * @link http://donatstudios.com/PHP-Parser-HTTP_USER_AGENT
	 * @param string $u_agent
	 * @return array an array with browser, version and platform keys
	 */
	public static function parse_user_agent( $u_agent = null ) {
		if(is_null($u_agent)) $u_agent = $_SERVER['HTTP_USER_AGENT'];
	
		$data = array(
				'platform' => null,
				'browser'  => null,
				'version'  => null,
		);
	
		if( preg_match('/\((.*?)\)/im', $u_agent, $regs) ) {
	
			# (?<platform>Android|iPhone|iPad|Windows|Linux|Macintosh|Windows Phone OS|Silk|linux-gnu|BlackBerry)(?: i386| i686| x86_64)?(?: NT)?(?:[ /][0-9._]+)*(;|$)
			preg_match_all('%(?P<platform>Android|iPhone|iPad|Windows|Linux|Macintosh|Windows Phone OS|Silk|linux-gnu|BlackBerry)(?: i386| i686| x86_64)?(?: NT)?(?:[ /][0-9._]+)*(;|$)%iim', $regs[1], $result, PREG_PATTERN_ORDER);
			$result['platform'] = array_unique($result['platform']);
			if( count($result['platform']) > 1 ) {
				if( ($key = array_search( 'Android', $result['platform'] )) !== false ) {
					$data['platform']  = $result['platform'][$key];
				}
			}elseif(isset($result['platform'][0])){
				$data['platform'] = $result['platform'][0];
			}
	
		}
	
		# (?<browser>Camino|Kindle|Firefox|Safari|MSIE|AppleWebKit|Chrome|IEMobile|Opera|Silk|Lynx|Version|Wget)(?:[/ ])(?<version>[0-9.]+)
		preg_match_all('%(?P<browser>Camino|Kindle|Firefox|Safari|MSIE|AppleWebKit|Chrome|IEMobile|Opera|Silk|Lynx|Version|Wget|curl)(?:[/ ])(?P<version>[0-9.]+)%im', $u_agent, $result, PREG_PATTERN_ORDER);
	
		if( $data['platform'] == 'linux-gnu' ) {
			$data['platform'] = 'Linux';
		}
	
		if( ($key = array_search( 'Kindle', $result['browser'] )) !== false || ($key = array_search( 'Silk', $result['browser'] )) !== false ) {
			$data['browser']  = $result['browser'][$key];
			$data['platform'] = 'Kindle';
			$data['version']  = $result['version'][$key];
		}elseif( $result['browser'][0] == 'AppleWebKit' ) {
			if( ( $data['platform'] == 'Android' && !($key = 0) ) || $key = array_search( 'Chrome', $result['browser'] ) ) {
				$data['browser'] = 'Chrome';
				if( ($vkey = array_search( 'Version', $result['browser'] )) !== false ) {
					$key = $vkey;
				}
			}elseif( $data['platform'] == 'BlackBerry' ) {
				$data['browser'] = 'BlackBerry Browser';
				if( ($vkey = array_search( 'Version', $result['browser'] )) !== false ) {
					$key = $vkey;
				}
			}elseif( $key = array_search( 'Kindle', $result['browser'] ) ) {
				$data['browser'] = 'Kindle';
			}elseif( $key = array_search( 'Safari', $result['browser'] ) ) {
				$data['browser'] = 'Safari';
				if( ($vkey = array_search( 'Version', $result['browser'] )) !== false ) {
					$key = $vkey;
				}
			}else{
				$key = 0;
			}
	
			$data['version'] = $result['version'][$key];
		}elseif( ($key = array_search( 'Opera', $result['browser'] )) !== false ) {
			$data['browser'] = $result['browser'][$key];
			$data['version'] = $result['version'][$key];
			if( ($key = array_search( 'Version', $result['browser'] )) !== false ) {
				$data['version'] = $result['version'][$key];
			}
		}elseif( $result['browser'][0] == 'MSIE' ){
			if( $key = array_search( 'IEMobile', $result['browser'] ) ) {
				$data['browser'] = 'IEMobile';
			}else{
				$data['browser'] = 'MSIE';
				$key = 0;
			}
			$data['version'] = $result['version'][$key];
		}elseif( $key = array_search( 'Kindle', $result['browser'] ) ) {
			$data['browser'] = 'Kindle';
			$data['platform'] = 'Kindle';
		}else{
			$data['browser'] = $result['browser'][0];
			$data['version'] = $result['version'][0];
		}
	
		return $data;
	
	}
	
	/**
	 * @return string[]
	 */
	public function detectChanges(Corp_Agent $agent) {
		
		$changes = array();
		
		if ($this->type !== $agent->type)				$changes[] = 'type';
		if ($this->agentRaw !== $agent->agentRaw)		$changes[] = 'agent';
		if ($this->clientIP !== $agent->clientIP)		$changes[] = 'clientIP';
		if ($this->clientHost !== $agent->clientHost)	$changes[] = 'clientHost';

		return $changes;
		
	}

	/**
	 * @return string[]
	 */
	public function __sleep() {
		return array('type', 'agentRaw', 'agentName', 'agentVersion', 'clientIP', 'clientHost', 'clientLocations', 'osName', 'osVersion', 'extraDataUnique', 'extraData');
	}
	
	/**
	 * @return void
	 */
	public function __wakeup() {
	
	}
	
	public function __toString() {
		return get_class($this) . "[TYPE={$this->type}; AGENT={$this->agentName}/{$this->agentVersion}; OS={$this->osName}/{$this->osVersion}; HOST={$this->clientHost}; LOCATION=".$this->getLocationQualifiedName()."; HASH={$this->getHashID()}]";
	}

}

?>