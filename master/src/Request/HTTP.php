<?php

/**
 * A HTTP request 
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
 * @package    evolya.corpsecurity.request
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Request_HTTP extends Corp_Request {
	
	/**
	 * Flag to disable the automatic interval based update.
	 * Browsecap setting.
	 * @var boolean
	 */
	public $browscap_doAutoUpdate = true;
	
	/**
	 * The path of the local version of the browscap.ini file from which to
	 * update (to be set only if used).
	 * Browsecap setting.
	 * @var string
	 */
	public $browscap_localFile = null;
	
	/**
	 * The location to use to check out if a new version of the browscap.ini file is available.
	 * Browsecap setting.
	 * @var boolean
	 */
    public $browscap_remoteIniUrl = 'http://browsers.garykeith.com/stream.asp?BrowsCapINI';
    
    /**
     * The location from which download the ini file. The placeholder for the file should be represented by a %s.
     * Browsecap setting.
     * @var boolean
     */
    public $browscap_remoteVerUrl = 'http://browsers.garykeith.com/versions/version-date.asp';
    
    /**
     * The timeout for the requests.
     * Browsecap setting.
     * @var boolean
     */
    public $browscap_timeout = 5;
    
    /**
     * The update interval in seconds.
     * Browsecap setting.
     * @var boolean
     */
    public $browscap_updateInterval = 432000;  // 5 days
    
    /**
     * The next update interval in seconds in case of an error.
     * Browsecap setting.
     * @var boolean
     */
    public $browscap_errorInterval = 7200;  // 2 hours
    
    /**
     * The method to use to update the file, has to be a value of an UPDATE_* constant, null or false.
     * Available values are:
     * - UPDATE_FOPEN: Uses the fopen url wrapper (use file_get_contents).
     * - UPDATE_FSOCKOPEN: Uses the socket functions (fsockopen).
     * - UPDATE_CURL: Uses the cURL extension.
     * - UPDATE_LOCAL: Updates from a local file (file_get_contents).
     * Browsecap setting.
     * @var boolean
     */
    public $browscap_updateMethod = null;
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Request::guessConfiguration()
	 */
	public function guessConfiguration(Corp_ExecutionContext $context) {
		
		// SERVER DATA
		$this->config['SERVER'] = $_SERVER;

		// METHOD
		$this->config['METHOD'] = strtoupper($_SERVER['REQUEST_METHOD']);
		
		// REQUEST URI
		$this->config['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
		
		// USER AGENT
		if ($context->getService()->broadcastEvent('beforeUserAgentDetected', array($this))) {
			
			$this->config['USER_AGENT'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
			
			/*
			TODO Mettre dans un plugin
			require_once 'Browscap.php';
			$browscap = self::createBrowscap($context->getService()->getCacheDirectory());
			$this->config['USER_AGENT'] = isset($_SERVER['HTTP_USER_AGENT']) ? $browscap->getBrowser($_SERVER['HTTP_USER_AGENT'], true) : 'unknown';
			*/
			
			$context->getService()->broadcastEvent('afterUserAgentDetected', array($this));
			
		}

		// HOST
		$this->config['HOST'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
		
		// QOP : HTTPS (TLS)
		if ((isset($_ENV['HTTPS']) && $_ENV['HTTPS'] === 'on') || $_SERVER['SERVER_PORT'] === 443) {
			$this->qop->add(Corp_Request_QoP::SECURED_TRANSPORT);
		}
		
		// QOP : LOCAL HOST
		if ($this->config['HOST'] === 'localhost') {
			$this->qop->add(Corp_Request_QoP::LOCAL_ACCESS);
		}
		
		// DATA : GET/POST/COOKIES (gpc)
		if (get_magic_quotes_gpc()) {
			function _stripslashes($value) {
				return is_array($value) ? array_map('_stripslashes', $value) : (is_string($value) ? stripslashes($value) : $value);
			}
			$this->config['DATA_GET']		= _stripslashes($_GET);
			$this->config['DATA_POST']		= _stripslashes($_POST);
			$this->config['DATA_COOKIE'] 	= _stripslashes($_COOKIE);
		}
		else {
			$this->config['DATA_GET']		= $_GET;
			$this->config['DATA_POST']		= $_POST;
			$this->config['DATA_COOKIE']	= $_COOKIE;
		}
		
		// DATA : FILES
		$this->config['DATA_FILES']	= isset($_FILES) ? $_FILES : array();
		
		// SHEME
		$this->config['SHEME'] = ($this->qop->is(Corp_Request_QoP::SECURED_TRANSPORT) ? 'https' : 'http');
		
		// REFERER
		$this->config['REFERER'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

		// ACCEPT
		$this->config['ACCEPT'] = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '*/*;q=0.1';
		
	}
	
	public function createBrowscap($cacheDir) {
		
		// Création de l'objet
		$browscap = new Browscap($cacheDir);
		
		// Configuration
		$browscap->doAutoUpdate		= $this->browscap_doAutoUpdate;
		$browscap->localFile		= $this->browscap_localFile;
		$browscap->remoteIniUrl		= $this->browscap_remoteIniUrl;
		$browscap->remoteVerUrl		= $this->browscap_remoteVerUrl;
		$browscap->timeout			= $this->browscap_timeout;
		$browscap->updateInterval	= $this->browscap_updateInterval;
		$browscap->errorInterval	= $this->browscap_errorInterval;
		$browscap->updateMethod		= $this->browscap_updateMethod;

		// Retour de l'objet
		return $browscap;
		
	}
	
	/**
	 * Parser une URL.
	 *
	 * @param string $url
	 * @return string[]|null
	 */
	public static function parse_url($url) {
	
		// On parse l'URL avec la fonction de PHP
		$temp = @parse_url($url);
	
		// S'il s'agit d'une URL valide, on va compléter le résultat
		if (!empty($temp)) {
	
			// Scheme
			if (!isset($temp['scheme'])) {
				$temp['scheme'] = 'http';
			}
				
			// Complete
			$temp['complete'] = $url;
				
			// Extension
			$temp['port_ext'] = '';
	
			// Base
			$temp['base'] = $temp['scheme'] . '://' . $temp['host'];
	
			// Port
			if (isset($temp['port'])) {
				$temp['base'] .= $temp['port_ext'] = ':' . $temp['port'];
			}
			else {
				$temp['port'] = $temp['scheme'] === 'https' ? 443 : 80;
			}
	
			// Path (décomposé)
			$temp['path'] = isset($temp['path']) ? explode('/', $temp['path']) : array();
	
			// Parcours des éléments du chemin
			$path = array();
			foreach ($temp['path'] as $dir) {
				if ($dir === '..') 	{
					array_pop($path);
				}
				else if ($dir !== '.') {
					$new_dir = '';
					for (
							$dir = rawurldecode($dir), $new_dir = '', $i = 0, $count_i = strlen($dir);
					$i < $count_i;
					$new_dir .= strspn($dir{$i}, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$-_.+!*\'(),?:@&;=') ? $dir{$i} : rawurlencode($dir{$i}), ++$i
					);
					$path[] = $new_dir;
				}
			}
	
			// Path (recomposé)
			$temp['path'] = str_replace('/%7E', '/~', '/' . ltrim(implode('/', $path), '/'));
	
			// Nom du fichier
			$temp['file'] = substr($temp['path'], strrpos($temp['path'], '/') + 1);
	
			// Répertoire
			$temp['dir'] = substr($temp['path'], 0, strrpos($temp['path'], '/'));
	
			// Base
			$temp['base'] .= $temp['dir'];
	
			// Répertoire précédent
			$temp['prev_dir'] = substr_count($temp['path'], '/') > 1 ? substr($temp['base'], 0, strrpos($temp['base'], '/') + 1) : $temp['base'] . '/';
	
			// On renvoi true
			return $temp;
		}
	
		// On renvoi false
		return null;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Request::getBody()
	 */
	public function getBody($asString = false) {

		$stream = fopen('php://input', 'r');
		
		if ($asString) {
            $body = stream_get_contents($stream);
            fclose($stream);
            return $body;
        }
        
        return $stream;
        
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Request::setBody()
	 */
	public function setBody($body) {

		$stream = fopen('php://temp', 'r+');
		
		if (is_resource($body)) {
			stream_copy_to_stream($body, $stream);
		}
		else {
			fputs($stream, $body);
			rewind($stream);
		}

	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Request::closeConnection()
	 */
	public function closeConnection() {
		
		// Si la connexion est déjà fermée, on n'a pas besoin de faire ce travail
		if (connection_aborted()) {
			return;
		}
		
		// On indique que la connexion va se fermer
		if (!headers_sent()) {
			if (ob_get_level() > 0) {
				header('Content-Length: ' . ob_get_length());
			}
			header('Connection: close');
		}

		// On ferme tous les buffers d'output
		while (ob_get_level() > 0) {
			ob_end_flush();
		}		
		
		// On désactive toutes futures bufferisation
		ob_implicit_flush(false);

		// On force l'envoi des données des buffers
		// Voir http://php.net/manual/fr/function.flush.php pour plus de détails sur les limitations
		flush();

		// FastCGI propose en plus une méthode pour terminer la requête principale et continuer
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
		
	}
	
}

?>