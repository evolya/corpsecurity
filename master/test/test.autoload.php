<?php

error_reporting(E_ALL | E_STRICT);

if (@$_GET['phar'] == 'yes') {
	include dirname(__FILE__) . '/../../dist/corpsecurity.phar';
	echo '<p><a href="?phar=no">Without PHAR</a></p>';
}
else {
	include dirname(__FILE__) . '/../src/index.php';
	echo '<p><a href="?phar=yes">With PHAR</a></p>';
}

// Enable auto-loading for classes
Corp_ClassLoader::autoload();

echo '<p>Class Corp_Service: ' . (class_exists('Corp_Service') ? 'OK' : 'Error').'</p>';

$service = new Corp_Service();

$service->exposeExceptions = true;
$service->handleExceptions = 'json';

$service->execute();

echo '<p>#eof</p>';

?>