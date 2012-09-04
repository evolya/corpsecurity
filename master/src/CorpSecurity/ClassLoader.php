<?php

class Corp_ClassLoader {

	/**
	 * @var string
	 */
	protected static $PATH = 'phar://corpsecurity.phar';
	
	/**
	 * @param string $className
	 * @return boolean
	 */
	public static function load($className) {
		
		// Not a CORP class
		if (substr($className, 0, 5) != 'Corp_') {
			return false;
		}
		
		// Explode classname in namespaces
		$path = explode('_', $className);
		
		// Remove Corp_ prefix
		array_shift($path);
		
		// Extract class name
		$className = array_pop($path);
		
		// Plugin are not auto-loaded
		/*if (in_array('Plugin', $path)) {
			return false;
		}*/
		
		// Path to file
		if (sizeof($path) > 0) {
			$file = '/' . implode('/', $path) . '/' . $className . '.php';
		}
		else {
			$file = '/' . $className . '.php';
		}
		
		// File exists
		if (is_file(self::$PATH . $file)) {
			include self::$PATH . $file;
			return true;
		}
		
		// Error reporting is currently turned on and not suppressed with @
		if (error_reporting() !== 0) {
			debug_print_backtrace();
			trigger_error("[CORP] Unable to load: ".self::$PATH.$file, E_USER_WARNING);
		}
		
		// Return false
		return false;
		
	}

	/**
	 * Enable class auto-load loader.
	 */
	public static function autoload() {

		// Not in the PHAR archive
		if (substr(__FILE__, 0, 5) !== 'phar:') {
			// Extends include path
			set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/..') . '/');
			// Set local path to files
			self::$PATH = dirname(__FILE__);
		}
		else {
			self::$PATH = 'phar://corpsecurity.phar';
		}
		
		// Fix ?
		//self::$PATH = dirname(__FILE__);
		
		// Using SPL autloader
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

	}
	
	/**
	 * DOCTODO
	 */
	public static function uses($filename) {
		include_once dirname(__FILE__) . "/$filename.php";
	}

}

?>