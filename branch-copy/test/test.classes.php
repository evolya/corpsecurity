<?php

error_reporting(E_ALL);

include '../src/CorpSecurity/index.php';

Corp_ClassLoader::uses('AbstractService');
Corp_ClassLoader::uses('Service');
Corp_ClassLoader::uses('Plugin');
Corp_ClassLoader::uses('Serializable');
Corp_ClassLoader::uses('Agent');
Corp_ClassLoader::uses('Request');
Corp_ClassLoader::uses('Request/HTTP');
Corp_ClassLoader::uses('Request/CLI');
Corp_ClassLoader::uses('Request/QoP');
Corp_ClassLoader::uses('ExecutionContext');
Corp_ClassLoader::uses('Auth/LoginForm');
Corp_ClassLoader::uses('Auth/AbstractLoginForm');
Corp_ClassLoader::uses('Auth/BasicLoginForm');
Corp_ClassLoader::uses('Auth/Identity');
Corp_ClassLoader::uses('Auth/Identity/Manager');
Corp_ClassLoader::uses('Auth/Identity/HtdigestFile');
Corp_ClassLoader::uses('Auth/Process/HttpBasic');
Corp_ClassLoader::uses('Auth/Process/HttpDigest');
Corp_ClassLoader::uses('Auth/Permissions/Manager');
Corp_ClassLoader::uses('Auth/Permissions/ACL');
Corp_ClassLoader::uses('Auth/Permissions/Unix');
Corp_ClassLoader::uses('Auth/Permissions/Flags');
Corp_ClassLoader::uses('Persistence/Manager');
Corp_ClassLoader::uses('Persistence/AbstractManager');
Corp_ClassLoader::uses('Persistence/Manager/PHP');
Corp_ClassLoader::uses('Persistence/Manager/Database');
Corp_ClassLoader::uses('Persistence/Manager/Stateless');
Corp_ClassLoader::uses('Persistence/Session');
Corp_ClassLoader::uses('Persistence/ORM');
Corp_ClassLoader::uses('Persistence/ORM/Session');
Corp_ClassLoader::uses('Persistence/ORM/SessionDefaultMySQL');
Corp_ClassLoader::uses('Persistence/SessionBasic');
Corp_ClassLoader::uses('Exception');
Corp_ClassLoader::uses('Exception/Security');
Corp_ClassLoader::uses('Exception/IO');
Corp_ClassLoader::uses('Exception/Internal');
Corp_ClassLoader::uses('Exception/NotFound');
Corp_ClassLoader::uses('Exception/FileNotFound');
Corp_ClassLoader::uses('Exception/NotReadable');
Corp_ClassLoader::uses('Exception/NotWritable');
Corp_ClassLoader::uses('Exception/FileNotWritable');
Corp_ClassLoader::uses('Exception/Persistence');
Corp_ClassLoader::uses('Exception/Database');
Corp_ClassLoader::uses('Exception/FileNotReadable');
Corp_ClassLoader::uses('Exception/InvalidArgument');
Corp_ClassLoader::uses('Exception/AllreadyExists');
Corp_ClassLoader::uses('Exception/UnsupportedOperation');
Corp_ClassLoader::uses('Exception/Security/Unauthorized');
Corp_ClassLoader::uses('Exception/Security/NeedPrivileges');
Corp_ClassLoader::uses('Exception/Security/Forbidden');
Corp_ClassLoader::uses('Exception/XmlConfig');
Corp_ClassLoader::uses('Debugger');
Corp_ClassLoader::uses('Debugger/Error');
Corp_ClassLoader::uses('Debugger/Events');
Corp_ClassLoader::uses('Debugger/Sessions');
Corp_ClassLoader::uses('XmlConfig');



header('Content-type: text/plain');

/**
 * @param string $path
 * @return string[]
 */
function listdir($path, $recursive = true, &$files = array()) {
	if (is_dir($path)) {
		foreach (scandir($path) as $p) {
			if ($p == '.' || $p == '..') continue;
			if (is_file("$path/$p")) {
				$files[] = "$path/$p";
			}
			else if ($recursive) {
				listdir("$path/$p", $recursive, $files);
			}
		}
	}
	else if (is_file($path)) {
		$files[] = $path;
	}
	return $files;
}

$files = listdir('../src/CorpSecurity');

$tokens = array();

foreach ($files as $path) {
	$toks = token_get_all(file_get_contents($path));
	foreach ($toks as $tok) {
		if (!is_array($tok)) continue;
		if ($tok[0] == T_STRING) {
			$name = $tok[1];
			if (substr($name, 0, 5) != 'Corp_') continue;
			if (!isset($tokens[$name])) {
				$tokens[$name] = array();
			}		
			$tokens[$name][$path] = true;
		}
	}
}

foreach ($tokens as $name => $files) {
	if (strpos($name, '_Plugin_')) continue;
	if (!class_exists($name) && !interface_exists($name)) {
		echo "Class not found: $name\nDeclared in:\n - ";
		echo implode("\n - ", array_keys($files));
		echo "\n\n";
	}
}

?>