<?php

/**
 * TODO Doc
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
class Corp_Plugin_GlobalLogger extends Corp_Plugin_AbstractLogger {

	/**
	 * @var Corp_Request
	 */
	protected $request = null;
	
	/**
	 * @var Corp_Persistence_Session
	 */
	protected $session = null;
	
	/**
	 * Constructor.
	 * 
	 * @param string $filename
	 */
	public function __construct($filename) {
		parent::__construct($filename, 'global', E_ALL);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin_AbstractLogger::initializeLogger()
	 */
	protected function initializeLogger(Corp_Service $service) {
		
		// Inscription aux events loggés
		$service->subscribeEvent('afterRequestCreated', array($this, 'afterRequestCreated'));
		$service->subscribeEvent('afterSessionInitialized', array($this, 'afterSessionInitialized'));
		$service->subscribeEvent('afterExceptionHandled', array($this, 'afterExceptionHandled'));
		$service->subscribeEvent('afterAuthFailure', array($this, 'afterAuthFailure'));
		$service->subscribeEvent('afterAuthLogin', array($this, 'afterAuthLogin'));
		$service->subscribeEvent('beforeLogoutProcess', array($this, 'beforeLogoutProcess'));
		$service->subscribeEvent('afterSessionExpired', array($this, 'afterSessionExpired'));
		$service->subscribeEvent('afterSessionUnSudo', array($this, 'afterSessionUnSudo'));
		$service->subscribeEvent('afterLocationRedirection', array($this, 'afterLocationRedirection'));
		
	}
	
	/**
	 * @param Corp_Request $request
	 * @param Corp_ExecutionContext $context
	 */
	public function afterRequestCreated(Corp_Request $request, Corp_ExecutionContext $context) {
		// Save request
		$this->request = $request;
	}
	
	/**
	 * @param Corp_Persistence_Manager $persistence
	 * @param Corp_Persistence_Session $session
	 * @param boolean $restored
	 */
	public function afterSessionInitialized(Corp_Persistence_Manager $persistence, Corp_Persistence_Session $session, $restored) {

		// Save session
		$this->session = $session;
		
		
		
		// Log request
		$this->sessionLog("REQUEST {$this->request->METHOD} {$this->request->SHEME}://{$this->request->HOST}{$this->request->REQUEST_URI}");
		
		// No log if the session is restored 
		if ($restored) {
			return;
		}
		
		// Log
		$this->sessionLog("NEW_SESSION Agent={$this->request->getAgent()}", $session);
		
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param Exception $ex
	 */
	public function afterExceptionHandled(Corp_ExecutionContext $context, Exception $ex) {
		$this->sessionLog("CATCH Exception=".get_class($ex)." Code={$ex->getCode()} Message={$ex->getMessage()}", $context->getSession());
		$this->sessionLog($ex->getTraceAsString(), $context->getSession());
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param string[] $post
	 * @param Corp_Request_QoP $qop
	 * @param Object $sender
	 */
	public function afterAuthFailure(Corp_ExecutionContext $context, array $post, Corp_Request_QoP $qop, $sender) {
		$this->sessionLog("AUTH_FAILURE Login={$post['login']} QoP={$qop}");
	}
	
	/**
	 * @param Corp_Auth_Identity $identity
	 * @param Corp_Request_QoP $qop
	 * @param Corp_ExecutionContext $context
	 * @param Object $sender
	 */
	public function afterAuthLogin(Corp_Auth_Identity $identity, Corp_Request_QoP $qop, Corp_ExecutionContext $context, $sender) {
		$this->sessionLog("LOGIN Identity=" . $identity);
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param Corp_Auth_Identity $identity
	 * @param Corp_Request $req
	 * @param object $sender
	 */
	public function beforeLogoutProcess(Corp_ExecutionContext $context, Corp_Auth_Identity $identity, Corp_Request $req, $sender) {
		$this->sessionLog("LOGOUT Identity={$identity}");
	}
	
	/**
	 * @param Corp_Persistence_Session $session
	 * @param Corp_Persistence_Manager $persitence
	 */
	public function afterSessionExpired(Corp_Persistence_Session $session, Corp_Persistence_Manager $persitence) {
		$this->sessionLog("EXPIRED");
	}

	/**
	 * @param Corp_Persistence_SessionSudo $session
	 */
	public function afterSessionSudo(Corp_Persistence_SessionSudo $session) {
		$this->sessionLog("SUDO SudoIdentity={$session->getSudoIdentity()}");
	}

	/**
	 * @param Corp_Persistence_SessionSudo $session
	 */
	public function afterSessionUnSudo(Corp_Persistence_SessionSudo $session) {
		$this->sessionLog("UNSUDO SudoIdentity={$session->getSudoIdentity()}");
	}
	
	/**
	 * @param string $url
	 * @param Corp_ExecutionContext $context
	 * @param Object $sender
	 */
	public function afterLocationRedirection($url, Corp_ExecutionContext $context, $sender) {
		$this->sessionLog("REDIRECT URL={$url}");
	}
	
	/**
	 * @param string $str
	 * @param Corp_Persistence_Session $session
	 * @param int $level
	 */
	public function sessionLog($str, Corp_Persistence_Session $session = null, $level = E_USER_NOTICE) {
		$date = date('Y/m/d H:i:s');
		if (!$session) {
			$session = $this->session;
		}
		$session = is_null($session) ? '?' : $session->getSID();
		$this->buffer[] = "{$date} {$this->name} {$this->request->getType()} {$this->request->getAgent()->clientIP} {$session} {$str}";
	}
	
	/**
	 * Parse a line of userlog.
	 * 
	 * @param string $str
	 * @return mixed[]
	 */
	public static function parseUserLog($str) {
		
		$r = array_combine(
			array('datetime', 'logger', 'apitype', 'ip', 'sid', 'log', 'action'),
			explode(' ', $str, 7)
		);
		
		$r['datetime'] = strtotime($r['datetime']); // Convert as a timestamp
		
		return $r;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'globallogger';
	}

}

?>