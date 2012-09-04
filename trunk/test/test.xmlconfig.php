<?php

error_reporting(E_ALL);
require_once '../src/CorpSecurity/index.php';
Corp_ClassLoader::autoload();
require_once 'test-require.php';
error_reporting(E_ALL);

try {

	echo '<pre style="border-left: 4px solid orange;padding-left:5px">';
	
	// Création d'un configurateur XML
	$launcher = new Corp_XmlConfig();
	
	// Chargement du fichier de config
	$launcher->loadFile(dirname(__FILE__) . '/test.xmlconfig.xml');
	
	// Récupération du service
	$service = $launcher->getBeanByName('service');
	
	// On recupère les objets dont on a besoin
	$persistence = $service->getPersistenceManager();
	$context = $service->getCurrentContext();
	$session = $context->getSession();
	$request = $context->getRequest();
	
	// Container
	echo '</pre><pre style="border-left: 4px solid green;padding-left:5px">';
	
	// Logout action
	if (@$_GET['logout'] == '1') {
		$persistence->logoutSession($session);
	}
	
	// Tests
	echo "\nCurrent SAPI: " . $request->getSAPIName().PHP_EOL;
	echo "Current session: " . $session->getSID().PHP_EOL;
	echo "Request QoP: " . $request->getQoP().PHP_EOL;
	echo "Identity: " . $session->getIdentity().PHP_EOL;
	
	echo "\nCreation time   : " . date('r', $session->getCreationTime()).PHP_EOL;
	echo "Last update time: " . date('r', $session->getLastRequestTime()).PHP_EOL;
	echo "Expires time    : " . date('r', $session->getLastRequestTime() + $session->getExpirationDelay()).' ('.round($session->getExpirationDelay() / 60, 2).' min)'.PHP_EOL;
	
	// Logout link
	if ($session->isLogged()) {
		echo "<a href='?logout=1'>Logout</a>".PHP_EOL;
	}
	
	// Close container
	echo '</pre><pre style="border-left:4px solid orange;padding-left:5px">';

}
catch (Exception $ex) {
	echo get_class($ex) . ': ' . $ex->getMessage() . "\n";
	echo $ex->getTraceAsString();
}

?>