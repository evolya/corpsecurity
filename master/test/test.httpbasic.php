<?php

header('Content-type: text/plain');

error_reporting(E_ALL);
require_once '../src/index.php';
Corp_ClassLoader::autoload();
error_reporting(E_ALL);

// Création du service cORP
$corp = new Corp_Service();

// Debug
$corp->exposeExceptions = false;
$corp->handleExceptions = 'json';

// Base de données des accès
$db = new Corp_Auth_Identity_HtdigestFile('../../../evolya.workgroop/src/wg/modules/webdav/.htdigest');

// Authentification basique
$auth = new Corp_Auth_Process_HttpBasic('webdav', $db);
$corp->addPlugin($auth);

// On recupère le context d'execution
$context = $corp->getCurrentContext();

// Normalement, seule cette ligne devrait propager des exceptions
$corp->execute();

// C'est bon!
echo "OK!";
echo "\n\nQoP: " . $context->getRequest()->getQoP();


?>