<?php

/**
 * HTTP Digest Authentication handler
 *
 * Use this class for easy http digest authentication.
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
 * @package    evolya.corpsecurity.auth
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Auth_Process_HttpDigest implements Corp_Plugin {
	
	const QOP_AUTH = 1;
	const QOP_AUTHINT = 2;
	
	/**
     * The realm is often displayed in authentication dialog boxes
     * Commonly an application name displayed here
	 * @var string
	 */
	protected $realm;
	
	/**
	 * @var Corp_Auth_IdentityManager
	 */
	protected $mgr;
	
	/**
	 * @var string
	 */
	protected $nonce;
	
	/**
	 * @var string
	 */
	protected $opaque;
	
	/**
	 * @var Corp_Service
	 */
	protected $service = null;
	
	/**
	 * @var int
	 */
	protected $qop = self::QOP_AUTH;
	
	/**
	 * Consructor.
	 * 
	 * @param string $realm
	 * @param Corp_Auth_IdentityManager $mgr
	 */
	public function __construct($realm, Corp_Auth_Identity_Manager $mgr) {

		$this->realm	= $realm;
		$this->mgr		= $mgr;
		
		// On détermine un ID unique
		$this->nonce = uniqid();
		
		// On sauvegarde le realm hashé
		$this->opaque = md5($this->realm);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
		
		// On conserve une copie du service d'authentification
		$this->service = $service;
		
		// On corrige le bug PHP-CGI/HttpBasicAuthentication
		// Fonctionne avec le fix dans le htaccess
		$service->subscribeEvent('beforeContextCreated', array($this, 'beforeContextCreated'));
		
		// On s'inscrit à ce event pour executer la vérification d'authentification avant chaque requête
		$service->subscribeEvent('beforeMethod', array($this, 'beforeMethod'));
		
		// On s'inscrit à cet event pour lever un message d'alert, car ce type de process ne sait pas faire de logout
		$service->subscribeEvent('beforeSessionLoggedOut', array($this, 'beforeSessionLoggedOut'));
		
	}
	
	/**
	 * @return void
	 */
	public function beforeContextCreated() {
		self::fixHttpDigestAuthentication();
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param string $method
	 */
	public function beforeMethod(Corp_ExecutionContext $context, $method) {
		
		// On recupère la requête
		$request = $context->getRequest();

		$digest = null;
		
		// Récupération du digest
		if (isset($request->SERVER['PHP_AUTH_DIGEST'])) {
			$digest = $request->SERVER['PHP_AUTH_DIGEST']; // patch
		}
		else if (isset($request->SERVER['PHP_AUTH_USER'])) {
			$digest = $request->SERVER['PHP_AUTH_USER']; // mod_php
		}
		else if (isset($request->SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
			$digest = $request->SERVER['REDIRECT_HTTP_AUTHORIZATION']; // mod_rewrite
		}
		else if (function_exists('apache_request_headers')) {
			// TODO Passer par $request !
			$headers = apache_request_headers();
			if (isset($headers['Authorization'])) {
				$digest = $headers['Authorization'];
			}
		}
		
		if ($digest && strpos(strtolower($digest), 'digest') === 0) {
			$digest = substr($digest, 7);
		}

		// Uniquement si les informations d'authentifications ont été envoyés
		if ($digest && ($parts = self::parseDigest($digest))) {
			
			// Event before
			if ($this->service->broadcastEvent('beforeHttpDigestAuthentication', array($context, &$parts))) {

				// On recherche le hash correspondant à cet utilisateur
				$A1 = $this->mgr->getHash($this->realm, $parts['username']);

				// Si la méthode renvoi false, c'est que l'utilisateur n'existe pas
				if (!$A1) {
					// Display login form
					$this->requireLogin($context);
				}
				
				// On prépare une variable de comparaison avec les éléments reçus
				$A2 = $context->getRequest()->METHOD . ':' . $parts['uri'];
				
				// Ce niveau de protection est spécifique
				if ($parts['qop'] == 'auth-int') {
					// On s'assure que ce niveau est supporté 
					if (!($this->qop & self::QOP_AUTHINT)) {
						$this->requireLogin($context, false);
						throw new Corp_Exception_UnsupportedOperation($context, 'Quality of protection "auth-int" is not supported');
					}
					$body = $request->getBody(true);
					$request->setBody($body);
					$A2 .= ':' . md5($body);
					unset($body);
				}
				
				// Pour les autres niveau, on ne teste que le support
				else {
					if (!($this->qop & self::QOP_AUTH)) {
						$this->requireLogin($context, false);
						throw new Corp_Exception_UnsupportedOperation($context, 'Quality of protection "auth" is not supported');
					}
				}
				
				// On hash A2 en md5
				$A2 = md5($A2);
				
				// La réponse valide, à vérifier donc
				$A3 = md5("{$A1}:{$parts['nonce']}:{$parts['nc']}:{$parts['cnonce']}:{$parts['qop']}:{$A2}");
				
				/*echo "A1=$A1\n";
				echo "A2=$A2\n";
				echo "A3=$A3\n";
				echo "Response={$parts['response']}\n";*/
				
				// Vérification validée
				if ($parts['response'] === $A3) {
					
					// Event before
					if (!$this->service->broadcastEvent('beforeAuthenticationValided', array($context, &$parts))) {
						return;
					}
					
					// Get identity
					$identity = $this->mgr->getIdentityByUID($this->realm, $parts['username']);
					
					// Check identity
					if (!$identity) {
						throw new Corp_Exception_NotFound($context, "Unable to create identity: $uid");
					}
					
					// Before login
					if ($this->service->broadcastEvent('beforeIdentityLogin', array($context, $identity))) {
						$context->getSession()->setIdentity($identity);
						$this->service->broadcastEvent('afterIdentityLogin', array($context, $identity));
					}
					
					// Update QoP
					$qop = $request->getQoP();
					$qop->add(Corp_Request_QoP::SALT);
					if ($parts['qop'] == 'auth-int') {
						$qop->add(Corp_Request_QoP::EXTRA_CHALLENGE);
					}

					// Event after
					$this->service->broadcastEvent('afterAuthenticationValided', array($context, $identity));
					
					return;
					
				}
			
			}
			
		}
		
		// Display login form
		$this->requireLogin($context);

	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param boolean $throwException
	 * @throws Corp_Exception_Unauthorized
	 * @return void
	 */
	protected function requireLogin(Corp_ExecutionContext $context, $throwException = true) {
		
		// QoP
		$qop = '';
		switch ($this->qop) {
			case self::QOP_AUTH    					: $qop = 'auth'; break;
			case self::QOP_AUTHINT						: $qop = 'auth-int'; break;
			case self::QOP_AUTH | self::QOP_AUTHINT	: $qop = 'auth,auth-int'; break;
		}
		
		// Return authentication headers
		header('WWW-Authenticate: Digest realm="' . $this->realm . '",qop="' . $qop .'",nonce="' . $this->nonce . '",opaque="' . $this->opaque . '"');
		header('HTTP/1.0 401 Unauthorized', true, 401);
		
		// On indique que le code de réponse a été tranmis, pour qu'il ne soit pas modifié par la suite
		$context->setHeaderResponseSent();
		
		// Return page's body
		if ($throwException) {
			throw new Corp_Exception_Security_Unauthorized($context);
		}
		
	}
	
	/**
	 * @param Corp_Persistence_Manager_PHP $manager
	 * @param Corp_Persistence_Session $session
	 * @return void
	 */
	public function beforeSessionLoggedOut(Corp_Persistence_Manager_PHP $manager, Corp_Persistence_Session $session) {
		trigger_error('Unable to logout with Http Digest Authentication', E_USER_NOTICE);
	}
	
	/**
	 * Fix a bug with PHP/CGI and Http Authentications processes
	 * 
	 * Following .htaccess instructions are required to use this patch:
	 * 
	 * <IfModule mod_rewrite.c>
	 *   RewriteEngine on
	 *   RewriteCond %{HTTP:Authorization} ^(.*)
	 *   RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization},L]
	 * </IfModule>
	 * 
	 * @return void
	 */
	public static function fixHttpDigestAuthentication() {
		$matches = array();
		if (isset($_SERVER['REMOTE_USER']) && preg_match('/Digest\s+(.*)$/i', $_SERVER['REMOTE_USER'], $matches) > 0) {
			$_SERVER['PHP_AUTH_DIGEST'] = $matches[0];
		}
	}
	
	/**
     * Parses the different pieces of the digest string into an array.
     *
     * This method returns false if an incomplete digest was supplied
     *
     * @param string $digest
     * @return mixed
     */
    public static function parseDigest($digest) {

        // protect against missing data
        $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
        $data = array();

        preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $digest, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[2] ? $m[2] : $m[3];
            unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data;
    }
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'httpdigestauth';
	}
	
}

?>