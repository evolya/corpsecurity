<?php

// Identités supportant un TeamMember
require_once 'plugin.corp2soho.identity.php';

// ORM entre les sessions CORP et un model Moodel
require_once 'corp.persistence.orm.session.moodel.php';

// Moodel de la table UserSessionCorp
require_once 'model.user_session_corp.php';

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
class Corp_Plugin_Corp2Soho implements Corp_Plugin {
	
	/**
	 * @var MoodelStruct<TeamMember>
	 */
	protected $modelTeamMember;
	
	/**
	 * @var MoodelStruct<UserSession>
	 */
	protected $modelUserSession;
	
	/**
	 * @var Corp_Auth_LoginForm
	 */
	protected $form;
	
	/**
	 * @var Corp_Service
	 */
	protected $service;
	
	/**
	 * @var Moodel<TeamMember>
	 */
	protected $currentUser = null;
	
	/**
	 * @var string|null
	 */
	public $redirectAfterLogin = null;
	
	/**
	 * @var string|null
	 */
	public $redirectAfterLogout = null;
	
	/**
	 * @var boolean
	 */
	public $throwOnLogoutWhenNotLogged = false;
	
	/**
	 * @var boolean
	 */
	public $throwOnLoginWhenLogged = true;
	
	/**
	 * @param MoodelStruct<TeamMember> $modelTeamMember
	 * @param MoodelStruct<UserSession> $modelUserSession
	 * @param Corp_Auth_LoginForm $form
	 */
	public function __construct(MoodelStruct $modelTeamMember, MoodelStruct $modelUserSession, Corp_Auth_LoginForm $form) {
		$this->modelTeamMember = $modelTeamMember;
		$this->modelUserSession = $modelUserSession;
		$this->form = $form;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
		
		// On enregistre le service
		$this->service = $service;
		
		// On s'inscrit à cet event pour executer la vérification d'authentification avant chaque requête
		$service->subscribeEvent('beforeMethod', array($this, 'beforeMethod'));
		
		// On s'inscrit à cet event pour afficher l'option SSL sur le formulaire d'auth
		$service->subscribeEvent('onLoginFormJsGeneration', array($this, 'onLoginFormJsGeneration'));
		
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param string $method
	 * @event beforeAuthProcess
	 * @event afterAuthProcess
	 * @event beforeAuthFailure
	 * @event afterAuthFailure
	 * @event beforeAuthLogin
	 * @event afterAuthLogin
	 */
	public function beforeMethod(Corp_ExecutionContext $context, $method) {

		// On va avoir besoin de la requête
		$req = $context->getRequest();
		
		// On recupère l'identité actuelle de la session
		$identity = $context->getSession()->getIdentity();
		
		// On test si la session est bien loggée
		$logged = $context->getSession()->isLogged();

		// Logout
		if (defined('SOHO__IS_A_LOGOUT_PAGE') && $this->form->isLogoutSubmitted($req)) {
			
			// Event before
			if ($this->service->broadcastEvent('beforeLogoutProcess', array($context, $identity, $req, $this))) {
				
				// Log out the session
				if ($logged) {
				
					// Remove session identity (set a new anonymous identity)
					$context->getSession()->setIdentity(
						$context->getService()->getPluginByClass('Corp_Persistence_Manager')->identityFactory()
					);
					
					// Reset session QoP level with request's
					$context->getSession()->getQoPLevel()->set(
						$context->getRequest()->getQoP()->value()
					);
					
					// Unset logged flag
					$logged = false;

				}
				
				// The session was not logged allready
				else if ($this->throwOnLogoutWhenNotLogged) {
					throw new Corp_SecurityException($context, 'Not logged', 500);
				}
				
				// Event before
				if ($this->redirectAfterLogout !== null && $this->service->broadcastEvent('beforeLocationRedirection', array($this->redirectAfterLogout, $context, $this))) {
			
					// Redirection
					header('Status: 301 Moved Permanently', false, 301);
					header("Location: " . $this->redirectAfterLogout);
				
					// Event after
					$this->service->broadcastEvent('afterLocationRedirection', array($this->redirectAfterLogout, $context, $this));
				
				}
				
				// Event after
				$this->service->broadcastEvent('afterLogoutProcess', array($context, $identity, $req, $this));
				
			}
			
		}
		
		// On regarde si le formulaire d'authentification a été envoyé
		else if (defined('SOHO__IS_A_LOGIN_PAGE') && $this->form->isFormSubmitted($req)) {
			
			// On recupère les données envoyées
			$data = $this->form->getPostedValues($req);
			
			// Event before
			if ($this->service->broadcastEvent('beforeAuthProcess', array($data, $context, $this))) {
				
				// Si on est déjà authentifié, on lêve une erreur
				if ($logged) {
					
					// Affichage d'un message d'erreur
					$this->form->setErrorMessage('Allready logged in');
					
					// Exceptions
					if ($this->throwOnLoginWhenLogged) {
						throw new Corp_SecurityException($context, 'Allready logged', 500);
					}
					
					// On ne continue pas
					return;
					
				}
				
				// On s'assure que le format de la requête soit valide
				if (!preg_match('/^[a-z\+]{1,10}:[a-zA-Z0-9\+\/\=]{32,512}$/', $data['password'])) {
					
					// Affichage d'un message d'erreur
					$this->form->setErrorMessage('Invalid request');
					
					// On ne continue pas
					return;
					
				}
				
				// On sépare les données de QoP
				list($mode, $pwd) = explode(':', $data['password'], 2);
				
				// Salt
				$salt = $context->getSession()->getSalt();
				
				// On prépare un objet de QoP
				$qop = new Corp_Request_QoP();
				
				// Switch QoP modes
				switch ($mode) {
					
					case 's' :
						$qop->add(Corp_Request_QoP::SALT);
						break;
						
					case 's+k' :
						$qop->add(Corp_Request_QoP::SALT);
						$qop->add(Corp_Request_QoP::SECRETE_KEY);
						break;
						
					default :
						// Event before
						if ($this->service->broadcastEvent('beforeAuthUnsupportedQoP', array($mode, $qop, $context, $this))) {
							// Affichage d'un message d'erreur
							$this->form->setErrorMessage('Unsupported QoP');
							// On ne continue pas
							return;
						}
				}
				
				// Si non, on tente une authentification
				$user = $this->auth(
					$data['login'],		// Login name
					$pwd,				// Login password
					$salt,				// Password salt
					$qop				// Authentication QoP
				);
				
				// L'authentification a échouée
				if (!$user) {
					
					// Event before
					if ($this->service->broadcastEvent('beforeAuthFailure', array($context, $data, $qop, $this))) {
					
						// Affichage d'un message d'erreur 
						$this->form->setErrorMessage('Bad login and/or password');
						
						// On force l'annulation de l'user
						$this->currentUser = null;
						
						// Event after
						$this->service->broadcastEvent('afterAuthFailure', array($context, $data, $qop, $this));
						
					}
					
				}
				
				// L'authentification a réussie
				else {
					
					// On fabrique une nouvelle identité
					$identity = new Corp_Auth_SohoIdentity();
					
					// On y enregistre le model de l'user
					$identity->setUserModel($user);
					
					// On enregistre l'identité dans la session
					$context->getSession()->setIdentity($identity);
					
					// Event before
					if ($this->service->broadcastEvent('beforeAuthLogin', array($identity, $qop, $context, $this))) {
						
						// On sauvegarde l'user
						$this->currentUser = $user;
						
						// On met à jour le QoP de la session
						$context->getSession()->getQoPLevel()->add($qop->value());
						
						// Event after
						$this->service->broadcastEvent('afterAuthLogin', array($identity, $qop, $context, $this));
						
					}
					
					// Event before
					if ($this->redirectAfterLogin !== null && $this->service->broadcastEvent('beforeLocationRedirection', array($this->redirectAfterLogin, $context, $this))) {
						
						// Redirection
						header('Status: 301 Moved Permanently', false, 301);
						header("Location: " . $this->redirectAfterLogin);
						
						// Event after
						$this->service->broadcastEvent('afterLocationRedirection', array($this->redirectAfterLogin, $context, $this));
						
					}
					
				}
			
				// Event after
				$this->service->broadcastEvent('afterAuthProcess', array($context, $this));
				
			}
			
		}

		// Restauration de l'utilisateur actuel
		if ($logged) {
			$this->currentUser = $this->modelTeamMember->getByID($identity->getIdentityUID(), 1);
			if (!$this->currentUser) {
				// TODO Et si on n'arrive pas à retrouver l'utilisateur ?
				// S'il a été supprimé entre-temps notamment ?
			}
		}
		
	}
	
	/**
	 * @param string $login
	 * @param string $password
	 * @param string $salt
	 * @param Corp_Request_QoP $qop
	 * @return Moodel<TeamMember>|null
	 */
	public function auth($login, $password, $salt, Corp_Request_QoP $qop) {

		// Fix time response vulnerability
		// Ce petit bout de code permet de contrer les attaques qui se basent sur le temps de réponse
		// du système d'authentification.
		usleep(rand(500, 500000));
		
		// Cette variable contiendra 
		$test = null;
		
		// En fonction du niveau de protection, on va créer le test pour la requête SQL
		if ($qop->is(Corp_Request_QoP::SALT) && $qop->is(Corp_Request_QoP::SECRETE_KEY)) {
			$test = "SHA1(CONCAT_WS(':', '%salt%', `password`, `apikey`))";
		}
		else if ($qop->is(Corp_Request_QoP::SALT)) {
			$test = "SHA1(CONCAT_WS(':', '%salt%', `password`))";
		}
		
		// Si le mode de protection n'est pas supporté, il ne sera jamais possible de s'authentifier
		if (!$test) {
			return null;
		}
		
		// On recupère l'instance de la base de données
		$db = $this->modelTeamMember->_db;
		
		// Requête SQL
		$sql = "
			SELECT *
			FROM %table%
			WHERE
				(`login` = '%login%' OR `email` = '%login%')
				AND
				(`password` != '' AND {$test} = '%password%') 
			LIMIT 1";
		
		// Remplacement des données
		$sql = str_replace(
			array('%table%', '%login%', '%salt%', '%password%'),
			array(
				'`' . $db->getDatabaseName() . '`.`' . $db->getPrefix() . $this->modelTeamMember->name() . '`',
				$db->escapeString($login),
				$db->escapeString($salt),
				$db->escapeString($password)
			),
			$sql
		);
				
		// Execution de la requête
		$r = $db->query($sql);
		
		// Debug
		/*$tmp = $db->query("SELECT password, ".str_replace('%salt%', $salt, $test)." AS password3 FROM ".$db->getPrefix().$this->modelTeamMember->name()." WHERE login = '$login' LIMIT 1");
		$tmp = $tmp->current();
		echo "<hr>Login: $login<br>QoP: $qop<br>Pwd: $password<br>Salt: $salt<br>SQL: $sql<br>Result(s): ".$r->size()."<br>Expected: $tmp<hr />";*/
		
		// Aucun résultat
		if ($r->size() !== 1) {
			return null;
		}
		
		$model = $this->modelTeamMember->createMoodel($r->current());

		return ($model instanceof Moodel ? $model : null);
		
	}
	
	/**
	 * @param string[] &$js
	 * @param Corp_Auth_LoginForm $form
	 */
	public function onLoginFormJsGeneration(array &$js, Corp_Auth_LoginForm $form) {
		
		// TODO
		$sslURL = 'toto'; // if (isActualitySSL) ? .. : ..
		
		$js[] = <<<_JS
Corp.LoginForm.beforeInit.push(function (form) {
	var opt = Corp.LoginForm.addOption('ssl', 'TLS/SSL', 'Cryptographic protocol that provide communication security over the Internet.');
	opt.url = '{$sslURL}';
	opt.checkbox.onchange = function () {
		document.location.href = opt.url;
	};
});
_JS;
	}
	
	/**
	 * @return Moodel<UserSession>
	 */
	public function getCurrentUser() {
		return $this->currentUser;
	}
	
	/**
	 * @return Corp_Auth_LoginForm
	 */
	public function getLoginForm() {
		return $this->form;
	}

	/**
	 * @return Corp_Service
	 */
	public function getService() {
		return $this->service;
	}
	
	/**
	 * @return Corp_ExecutionContext
	 */
	public function getContext() {
		return $this->service->getCurrentContext();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'corp2soho';
	}

}

?>