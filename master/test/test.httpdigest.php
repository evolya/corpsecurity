<?php

header('Content-type: text/plain');

error_reporting(E_ALL);
require_once '../src/CorpSecurity/index.php';
Corp_ClassLoader::autoload();
error_reporting(E_ALL);

require 'test-require.php';

// Création du service cORP
$corp = new Corp_Service();

// On met en place un véritable manager de sessions
$corp->addPlugin(new Corp_Plugin_PHPSessionPersistenceManager());

// Authentification digest
$auth = new Corp_Auth_Process_HttpDigest('default', new MyIdentityManager());
$corp->addPlugin($auth);

// On recupère le context d'execution
$context = $corp->getCurrentContext();

// Normalement, seule cette ligne devrait propager des exceptions
$corp->execute();

// C'est bon!
echo "OK!";
echo "\n\nQoP: " . $context->getRequest()->getQoP();


?>