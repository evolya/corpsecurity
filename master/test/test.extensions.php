<?php

// Error level
error_reporting(E_ALL);

// Load CORP library
include '../src/index.php';
Corp_ClassLoader::autoload();

// Create a service
$service = new Corp_Service();

// Add a debugger
$debugger = new Corp_Debugger_Events();
$debugger->printOnShutdown = true;
$debugger->verboseMode = true;
$debugger->detectErrors = true;
$service->addPlugin($debugger);

// Add an extension container
$extCont = new Corp_ExtensionContainer();
$service->addPlugin($extCont);

$service->initialize();

echo "<br>Current executor is : " . $service->getCurrentContext()->getExecutor();


//$extCont->openExtensionDirectory('./modules/mailbox');

?>