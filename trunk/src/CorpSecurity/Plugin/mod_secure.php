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
class Corp_Plugin_ModSecure implements Corp_Plugin {
	
	/**
	 * @var boolean[]
	 */
	protected $config = array(
		'proxy_detect'		=> true,
		'bots_detect'		=> true,
		'bots_deny'			=> true
	);
	
	/**
	 * @var Corp_Service
	 */
	protected $service = null;
	
	/**
	 * The timeout for the remote requests.
	 * @var int
	 */
	public $timeout = 5;
	
	/**
	 * The location from which download the bot list file.
	 * @var string
	 */
	public $botlist_remoteUrl = 'http://www.robotstxt.org/db/all.txt';
	
	/**
	 * The update interval for bot list update, in seconds.
	 * @var int
	 */
	public $botlist_updateInterval = 432000; // 5 days
	
	/**
	 * The path of the local version of the bot list file from which to
	 * update (to be set only if used). This will disable auto update.
	 * @var string
	 */
	public $botlist_localFile = null;
	
	/**
	 * Constructor
	 * @param boolean[] $config
	 */
	public function __construct(array $config = null) {
		if ($config) {
			$this->setConfiguration($config);
		}
	}
	
	/**
	 * @param boolean[] $config
	 * @return void
	 */
	public function setConfiguration(array $config) {
		$this->config = array_merge($this->config, $config);
	}
	
	/**
	 * @return boolean[]
	 */
	public function getConfiguration() {
		return $this->config;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
		
		// Event before
		if (!$service->broadcastEvent('beforeModeSecureInstalled')) {
			return;
		}
		
		// Sauvegarde du service
		$this->service = $service;

		// Après que le context soit initialisé, avec une certaine priorité
		$service->subscribeEvent('afterContextCreated', array($this, 'afterContextCreated'), 20);
		
		// Lors de la détection d'un bot
		$service->subscribeEvent('onBotDetected', array($this, 'onBotDetected'));
		
		// Event after
		$this->service->broadcastEvent('afterModeSecureInstalled');
		
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 */
	public function afterContextCreated(Corp_ExecutionContext $context) {

		// Récupérer un maximum d'informations sur le client par du JS, et voir celle qui sont "identificatrices" et qui permettent
		// de consolier l'emprunte unique
		
		// Vérifier la requête (présence de Accept, détecter une connexion mal formée type file_get_contents('http://...')
		
		// blacklist lookups (avec un système d'ip réputation tiers)
		
		// Voir avec Goolge Safe Browsing API
		
		// Détection des bots, crawler
		
		// Contrôle des uploads, antivirus ?
		
		// Audit de la config php/apache
		
		// Masquage des erreurs
		
		// Controle des réponses du serveur, en détectant des données qui ne doivent pas sortir
		
		/*
		 * In order to provide generic web applications protection, the Core Rules use the following techniques:
		HTTP Protection - detecting violations of the HTTP protocol and a locally defined usage policy.
		Real-time Blacklist Lookups - utilizes 3rd Party IP Reputation
		Web-based Malware Detection - identifies malicious web content by check against the Google Safe Browsing API.
		HTTP Denial of Service Protections - defense against HTTP Flooding and Slow HTTP DoS Attacks.
				Common Web Attacks Protection - detecting common web application security attack.
				Automation Detection - Detecting bots, crawlers, scanners and other surface malicious activity.
				Integration with AV Scanning for File Uploads - detects malicious files uploaded through the web application.
				Tracking Sensitive Data - Tracks Credit Card usage and blocks leakages.
				Trojan Protection - Detecting access to Trojans horses.
				Identification of Application Defects - alerts on application misconfigurations.
				Error Detection and Hiding - Disguising error messages sent by the server.
				*/
		
		// Voir ce qu'il y a à faire avec http://php.net/manual/fr/function.dns-get-record.php
		
		// Uniquement pour les requêtes de type HTTP
		if ($context->getRequest()->getType() !== 'HTTP') {
			return;
		}
		
		// Detection du proxy
		if ($this->config['proxy_detect'] && $this->service->broadcastEvent('beforeProxyDetection')) {
			
			// On détecte le proxy
			list($clientIP, $proxyIP) = self::detectProxy();
			
			// Un proxy a été détecté
			if ($proxyIP != null) {
				
				// Lancement d'un event
				$this->service->broadcastEvent('onProxyDetected');
				
				// On indique que la connexion passe par un relai ouvert
				$context->getRequest()->getQoP()->add(Corp_Request_QoP::VIA_OPEN);
				
				// Si deux IPs sont bien détectées (il est possible de détecter un proxy sans pour autant
				// obtenir l'IP du client
				if ($clientIP != null && $proxyIP != null && $clientIP != $proxyIP) {
					
					// On sauvegarde l'IP du proxy
					$context->getAgent()->extraData['PROXY_IP'] = $proxyIP;
					
					// Et on enregistre la vraie IP du client
					$context->getAgent()->clientIP = $clientIP;
					
					// On propage un event
					$this->service->broadcastEvent('onAgentIPCorrected', array($clientIP, $proxyIP));
					
				}
				
			}
			
			// Event after
			$this->service->broadcastEvent('afterProxyDetection');
			
		}
		
		// Détection des bots
		if ($this->config['bots_detect'] && $this->service->broadcastEvent('beforeBotDetection')) {
			
			// On recupère la liste des bots			
			$list = $this->getBotList($context->getService()->getCacheDirectory());
			
			// Uniquement si la liste est valide
			if (is_array($list)) {
				
				// On recupère l'USER_AGENT du client
				$agent = $context->getAgent()->agentRaw;
	
				// On parcours la liste des infos sur les bots
				foreach ($list as $entry) {
					
					if (!isset($entry['robot-useragent'])) continue;
					
					// Un robot a été détecté !
					if ($agent == $entry['robot-useragent']) {
						
						// On envoi un event
						$this->service->broadcastEvent('onBotDetected', array($entry, $agent));
						
					}
					
				}

			}
			
			// Event after
			$this->service->broadcastEvent('afterBotDetection');
			
		}
		
	}
	
	/**
	 * @param mixed[] $botInfo
	 * @param string $userAgent
	 */
	public function onBotDetected($botInfo, $userAgent) {
		// Si la configuration l'indique, on va couper l'accès aux bots
		if ($this->config['bots_deny'] && $this->service->broadcastEvent('beforeBotDenied', array($botInfo, $userAgent))) {
			throw new Corp_Exception_Security_Unauthorized(
				$this->service->getCurrentContext(),
				'Bot crawling is forbidden'
			);
		}
	}
	
	/**
	 * @param string $cacheDir
	 * @return mixed[]
	 */
	public function getBotList($cacheDir) {

		// Chemin vers le fichier de cache
		$cacheFile = $cacheDir . '/botlist_cache.php';
		
		// Pas d'auto_update, on recherche un fichier local
		if ($this->botlist_localFile != null) {
			 
			// On regarde si un cache a été généré : il est utilisé à chaque fois
			// Si le fichier local a été modifié, le cache ne sera jamais à jour!
			// Il faudra supprime le fichier de cache.
			if (is_file($cacheFile)) {
				$data = self::getLocalCache($cacheFile);
				return $data === null ? array() : $data;
			}
			
			// Sinon, on va lire le fichier local
			$data = self::parseBotListFile($this->botlist_localFile);
			
			// Erreur
			if (!$data) {
				return array();
			}
			
			// Sauvegarde du cache
			file_put_contents($cacheFile, serialize($data));
			
			// Et renvoi
			return $data;
			
		}
		
		// Si un cache existe
		if (is_file($cacheFile)) {
			
			// Si le cache est encore valide
			if (filemtime($cacheFile) + $this->botlist_updateInterval > $_SERVER['REQUEST_TIME']) {
				$data = self::getLocalCache($cacheFile);
				return $data === null ? array() : $data;
			}
			
		}
		
		// Création d'un contexte de flux, pour mettre un timeout
		$ctx = stream_context_create(array('http' => array('timeout' => $this->timeout)));

		// Obtention de la liste
		$data = @file_get_contents($this->botlist_remoteUrl);
		
		// La requête est valide
		if ($data !== false) {
			
			// On parse les données
			$data = self::parseBotListString($data);
			
			// Pas d'erreur, 
			if ($data !== null) {
				
				// On sauvegarde le cache
				file_put_contents($cacheFile, serialize($data));
				
				// Et on renvoi les données
				return $data;
				
			}
			
		}
		
		// Ici on n'a pas réussi à charger l'URL distante, donc la dernière possibilité
		// pour renvoyer des données c'est de lire le cache s'il existe
		if (is_file($cacheFile)) {
			$data = self::getLocalCache($cacheFile);
			return $data === null ? array() : $data;
		}
		
		// Aucune données à renvoyer
		return array();
		
	}
	
	/**
	 * @param string $file
	 * @return mixed[]|null
	 */
	public static function parseBotListFile($file) {
		if (!is_file($file)) {
			return null;
		}
		$data = file_get_contents($file);
		if ($data === false) {
			return null;
		}
		return self::parseBotListString($data);
	}
	
	/**
	 * @param string $data
	 * @return mixed[]|null
	 */
	public static function parseBotListString($data, $keep = null, $remove = null) {
		
		$list = array();
		
		$current = array();
		
		foreach (explode("\n", $data) as $line) {
			
			// Continuity
			if ($line{0} === ' ' || $line{0} === "\t") {
				if (sizeof($current) === 0) {
					continue;
				}
				$current[array_pop(array_keys($current))] .= ' ' . $line;
				continue;
			}
			
			// Trim
			$line = trim($line);
				
			// Empty line = separator
			if (empty($line)) {
				if (sizeof($current) > 0) {
					$list[] = $current;
					$current = array();
				}
				continue;
			}
			
			// Search for key/value delimiter
			$pos = strpos($line, ':');
			
			// Ignored lines
			if (!$pos) {
				$current[] = $line;
				continue;
			}
			
			// Get the key and the value
			$key = trim(substr($line, 0, $pos));
			$value = trim(substr($line, $pos));
			
			// Filters
			if ($keep) {
				if (!in_array($key, $keep)) continue;
			}
			if ($remove) {
				if (in_array($key, $keep)) continue;
			}
			
			// Save this item
			$current[$key] = $value;
			
		}
		
		if (sizeof($current) > 0) {
			$list[] = $current;
		}
		
		return $list;
		
	}
	
	/**
	 * @param string $cacheFile
	 * @return mixed[]|null
	 */
	protected static function getLocalCache($cacheFile) {
		// On lit le fichier de cache
		$data = file_get_contents($cacheFile);
		// Erreur de lecture
		if (!$data) {
			return null;
		}
		// Désérialisation
		$data = unserialize($data);
		// Retour
		return is_array($data) ? $data : null;
	}
	
	/**
	 * Renvoi:
	 * 	array(string $ipClient, null)				Si aucun proxy n'est détecté
	 *  array(null, string $ipProxy)				Si un proxy est détecté sans pouvoir déterminer l'IP du client
	 * 	array(string $ipClient, string $ipProxy)	Si le proxy est détecté et que l'IP du client est déterminée 
	 * 
	 * @return string[]
	 */
	public static function detectProxy() {

		$headers = array(
			// Nom du header            Indique si l'IP est celle du proxy, ou null si ce n'est pas une IP
			'HTTP_VIA'					=> true,
			'HTTP_X_FORWARDED_FOR'		=> false,
			'HTTP_FORWARDED_FOR'		=> false,
			'HTTP_X_FORWARDED'			=> false,
			'HTTP_FORWARDED'			=> false,
			'HTTP_CLIENT_IP'			=> false,
			'HTTP_FORWARDED_FOR_IP'		=> false,
			'VIA'						=> true,
			'X_FORWARDED_FOR'			=> false,
			'FORWARDED_FOR'				=> false,
			'X_FORWARDED'				=> false,
			'FORWARDED'					=> false,
			'CLIENT_IP'					=> false,
			'FORWARDED_FOR_IP'			=> false,
			'HTTP_PROXY_CONNECTION'		=> null
		);
		
		$ipClient = null;
		$ipProxy  = null;

		foreach ($headers as $name => $proxy) {
			
			if (!isset($_SERVER[$name])) continue;
			
			if ($proxy === true) {
				$ipProxy = $_SERVER[$name];
			}
			else if ($proxy === false) {
				$ipClient = $_SERVER[$name];
			}
			else {
				$ipProxy = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : getenv('REMOTE_ADDR');
			}
			
		}
		
		if (!$ipProxy && !$ipClient) {
			$ipClient = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : getenv('REMOTE_ADDR');
		}
		
		return array($ipClient, $ipProxy);

	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'mod_secure';
	}

}

?>