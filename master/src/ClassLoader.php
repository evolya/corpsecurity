<?php

class Corp_ClassLoader {

	/**
	 * @var string Chemin utilisé pour inclure les fichiers.
	 * @see Corp_ClassLoader::init()
	 */
	protected static $PATH;

	/**
	 * @var string[] Chemin vers les classes connues.
	 */
	protected static $CLASSES = array(
		# Service
		'Service'						=> 'Service/Service.php',
		'AbstractService'				=> 'Service/AbstractService.php',
		'ServicePlugin'					=> 'Service/ServicePlugin.php',
		'ExecutionContext'				=> 'Service/ExecutionContext.php',
		'SystemUser'					=> 'Service/SystemUser.php',
		# Common
		'Serializable'					=> 'Common/Serializable.php',
		'Exception'						=> 'Common/Exception.php',
		'XmlConfig'						=> 'Common/XmlConfig.php',
		# Requests
		'Request'						=> 'Request/Request.php',
		'HTTP'							=> 'Request/HTTP.php',
		'CLI'							=> 'Request/CLI.php',
		'QoP'							=> 'Request/QoP.php',
		# UserInterface
		'Agent'							=> 'UserInterface/Agent.php',
		# Extensions
		'ExtensionContainer'			=> 'Extension/ExtensionContainer.php'
	);

	/**
	 * Cette méthode permet d'initialiser le ClassLoader.
	 * Elle va déterminer la variable self::$PATH qui permet de pointer
	 * vers les fichiers PHP de la librairie que l'on utilise le fichier PHAR
	 * ou bien directement les sources.
	 *
	 * @return void
	 */
	public static function init() {

		// Si la librairie est utilisée sous la forme d'un unique fichier PHAR
		if (substr(__FILE__, 0, 5) === 'phar:') {
			// Le chemin pointe directement vers le PHAR
			self::$PATH = 'phar://corpsecurity.phar';
		}

		// Si la librairie est utilisée directement avec les sources PHP
		else {
			// On ajoute le chemin vers le répertoire actuel dans l'include_path
			set_include_path(
				  get_include_path()
				. PATH_SEPARATOR
				. realpath(dirname(__FILE__) . '/..') . '/'
			);
			// Le chemin pointe vers ce répertoire
			self::$PATH = dirname(__FILE__);
		}

	}

	/**
	 * Activer le chargement automatique des classes.
	 *
	 * Cette fonction n'est pas obligatoire, il est même recommandé de ne pas l'utiliser
	 * et de charger les fichiers manuellement pour plus de performances.
	 * Néanmoins, pour simplifier l'utilisation de la librairie, cette fonction permet
	 * d'activer le chargement automatique des classes, dès qu'elles sont réclamées.
	 *
	 * Cette méthode est compatible avec la méthode SPL de PHP 5.2, ou bien la méthode
	 * classique avec __autoload.
	 *
	 * Renvoi TRUE si le système d'autoload a bien été mis en place, FALSE si un
	 * système d'autoload existait déjà.
	 *
	 * @return boolean
	 */
	public static function autoload() {

		// SPL autloader
		// http://www.php.net/manual/en/function.spl-autoload-register.php
		if (function_exists('spl_autoload_register')) {
			spl_autoload_register('Corp_ClassLoader::load');
		}

		// Basic autoloading classes
		// http://www.php.net/manual/en/language.oop5.autoload.php
		else if (!function_exists('__autoload')) {
			function __autoload($className) {
				Corp_ClassLoader::load($className);
			}
		}

		// Erreur : la fonction d'autoload existe déjà
		else {
			return false;
		}

		// Renvoi TRUE pour indiquer que l'autload est bien mis en place
		return true;

	}

	/**
	 * Charger une classe.
	 *
	 * Cette méthode sert à demander le chargement d'une classe, en spécifiant
	 * son nom.
	 *
	 * @param string $className
	 * @return boolean
	 */
	public static function load($className) {

		// Not a CORP class
		if (strtolower(substr($className, 0, 5)) != 'corp_') {
			return false;
		}

		// Explode classname in namespaces
		$path = explode('_', $className);

		// Remove Corp_ prefix
		array_shift($path);

		// Extract class name
		$className = array_pop($path);

		// Known classe
		if (array_key_exists($className, self::$CLASSES)) {
			include self::$PATH . DIRECTORY_SEPARATOR . self::$CLASSES[$className];
			return true;
		}

		// Path to file (Old rule DEPRECATED)
		if (sizeof($path) > 0) {
			$file = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR . $className . '.php';
		}
		else {
			$file = DIRECTORY_SEPARATOR . $className . '.php';
		}

		// File exists
		if (is_file(self::$PATH . $file)) {
			include(self::$PATH . $file);
			return true;
		}

		// Error reporting is currently turned ON and not suppressed with @
		if (error_reporting() !== 0) {
			debug_print_backtrace();
			trigger_error("[CORP] Unable to load: ".self::$PATH.$file, E_USER_WARNING);
		}

		// Return false
		return false;

	}

	/**
	 * DOCTODO Deprecated ?
	 */
	public static function uses($filename) {
		include_once(self::$PATH . "/$filename.php");
	}

}

Corp_ClassLoader::init();

?>