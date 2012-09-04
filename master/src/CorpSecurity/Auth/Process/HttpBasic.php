<?php

/**
 * HTTP Basic Authentication handler
 * 
 * Use this class for easy http basic authentication.
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
class Corp_Auth_Process_HttpBasic implements Corp_Plugin {
	
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
	 * @var Corp_Service
	 */
	protected $service = null;
	
	/**
	 * Consructor.
	 * 
	 * @param string $realm
	 * @param Corp_Auth_IdentityManager $mgr
	 */
	public function __construct($realm, Corp_Auth_Identity_Manager $mgr) {
		$this->realm	= $realm;
		$this->mgr		= $mgr;
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
		self::fixHttpBasicAuthentication();
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param string $method
	 */
	public function beforeMethod(Corp_ExecutionContext $context, $method) {

		// On recupère la requête
		$request = $context->getRequest();
		
		// Uniquement si les informations d'authentifications ont été envoyés
		if (isset($request->SERVER['PHP_AUTH_USER']) && isset($request->SERVER['PHP_AUTH_PW'])) {
			
			// Event before
			if ($this->service->broadcastEvent('beforeHttpBasicAuthentication', array($context, $request->SERVER['PHP_AUTH_USER'], $this->realm, $request->SERVER['PHP_AUTH_PW']))) {

				// Hash
				$hash = md5($request->SERVER['PHP_AUTH_USER'] . ':' . $this->realm . ':' . $request->SERVER['PHP_AUTH_PW']);
				
				// Vérification par le manager
				$uid = $this->mgr->exists($this->realm, $request->SERVER['PHP_AUTH_USER'], $hash);
				
				// L'entrée existe
				if ($uid) {
					
					// Event before
					if (!$this->service->broadcastEvent('beforeAuthenticationValided', array($context, $request->SERVER['PHP_AUTH_USER'], $this->realm, $uid))) {
						return;
					}
					
					// Get identity
					$identity = $this->mgr->getIdentityByUID($this->realm, $uid);
					
					// Check identity
					if (!$identity) {
						throw new Corp_Exception_NotFound($context, "Unable to create identity: $uid");
					}
					
					// Before login
					if ($this->service->broadcastEvent('beforeIdentityLogin', array($context, $identity))) {
						$context->getSession()->setIdentity($identity);
						$this->service->broadcastEvent('afterIdentityLogin', array($context, $identity));
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

		// Return authentication headers
		header('WWW-Authenticate: Basic realm="' . $this->realm . '"');
		header('HTTP/1.0 401 Unauthorized', true, 401);
		
		// On indique que le code de réponse a été tranmis, pour qu'il ne soit pas modifié par la suite
		$context->setHeaderResponseSent();
		
		// Return page's body
		if ($throwException) {
			throw new Corp_Exception_Security_Unauthorized($context);
		}
	}
	
	/**
	 * @param Corp_Persistence_Manager $manager
	 * @param Corp_Persistence_Session $session
	 * @return void
	 */
	public function beforeSessionLoggedOut(Corp_Persistence_Manager $manager, Corp_Persistence_Session $session) {
		trigger_error('Unable to logout with Http Basic Authentication', E_USER_NOTICE);
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
	public static function fixHttpBasicAuthentication() {
		$matches = array();
		if (isset($_SERVER['REMOTE_USER']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REMOTE_USER'], $matches) > 0) {
			list($name, $pass) = explode(':', base64_decode($matches[1]));
			$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
			$_SERVER['PHP_AUTH_PW'] = strip_tags($pass);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'httpbasicauth';
	}
	
}

?>