<?php

error_reporting(E_ALL);
require_once '../src/index.php';
Corp_ClassLoader::autoload();
error_reporting(E_ALL);

echo '<pre style="border-left: 4px solid orange;padding-left:5px">';

// Création d'un configurateur XML
$launcher = new Corp_XmlConfig();

// Chargement du fichier de config
$launcher->loadFile(dirname(__FILE__) . '/test.dbsession.xml');

// Récupération du service
$service = $launcher->getBeanByName('service');

// On lance le service
$service->execute();

// On recupère les objets dont on a besoin
$persistence = $service->getPluginByClass('Corp_Persistence_Manager');
$context = $service->getCurrentContext();
$session = $context->getSession();
$request = $context->getRequest();

// Container
echo '</pre><pre style="border-left: 4px solid green;padding-left:5px">';

$session = $persistence->getCurrentSession();

echo "Current session is: $session\n\n";

echo "All sessions:\n";

$sessions = $persistence->getSessions();

foreach ($sessions as $s) {
	echo " - $s\n";
}

echo "Total: " . sizeof($sessions);

// Close container
echo '</pre><pre style="border-left:4px solid orange;padding-left:5px">';



?>