<?php

// Load Moodel before WG
// Moodel est une lib sans conflit, donc si elle est chargée ici c'est pour ne pas
// utiliser la version packée qui est incluse dans WG.
include '../../../evolya.moodel/src/include.php';

// Load CORP
error_reporting(E_ALL | E_STRICT);
require_once '../src/CorpSecurity/index.php';
error_reporting(E_ALL | E_STRICT);
Corp_ClassLoader::autoload();

// Load Soho (WG)
include '../../../evolya.workgroop/src/wg/starter.php';
error_reporting(E_ALL | E_STRICT);

// On indique que cette page peut faire l'authentification et le logout
define('SOHO__IS_A_LOGIN_PAGE', true);
define('SOHO__IS_A_LOGOUT_PAGE', true);

// Création d'un configurateur XML
$launcher = new Corp_XmlConfig();

// On passe les models de WG à corp
$launcher->setBean('model.user', ModelManager::get('TeamMember'));

// Chargement du fichier de config
$launcher->loadFile(dirname(__FILE__) . '/../corp2soho/config.xml');

// Récupération du service
$service = $launcher->getBeanByName('service');

// On lance le service
$service->execute();

// On recupère le plugin Soho
$corp2soho = $service->getPluginByName('corp2soho');

// Current user
$user = $corp2soho->getCurrentUser();

// On affiche le formulaire d'authentification
if (!$user) {

	echo '<body id="wg"><div id="container"><div id="main"><div id="view-login" class="view">';
	
	$req = $service->getCurrentContext()->getRequest();
	
	$form = $corp2soho->getLoginForm();
	
	echo $form->render($service->getCurrentContext());
	
	echo '</div></div></div></body>';
	
}

else {
	
	$identity = $service->getCurrentContext()->getSession()->getIdentity();
	
	echo '<p>You are <b>' . $identity->getIdentityName() . '</b></p>';
	
	echo '<p>User profile is: <em>' . $identity->getUserModel() . '</em></p>'; 
	
	echo '<p><a href="?logout=1&ts='.time().'">Logout</a></p>';
	
}

?>