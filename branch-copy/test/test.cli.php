<?php

// Load Moodel before WG
// Moodel est une lib sans conflit, donc si elle est chargée ici c'est pour ne pas
// utiliser la version packée qui est incluse dans WG.
include '../../../evolya.moodel/src/include.php';

// Load CORP
error_reporting(E_ALL);
require_once '../src/CorpSecurity/index.php';
error_reporting(E_ALL);
Corp_ClassLoader::autoload();

// Load Soho (WG)
include '../../../evolya.workgroop/src/wg/starter.php';
error_reporting(E_ALL);

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

// Suppression des plugins de debuggage
$service->removePlugin('sessionsdebugger');
$service->removePlugin('eventsdebugger');
$service->removePlugin('errordebugger');

// On lance le service
$service->execute();

// On recupère le plugin Soho
$corp2soho = $service->getPlugin('corp2soho');

echo "\n\nok";

?>