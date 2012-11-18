<?php

class Corp_PermissionScanner {

	public $handlers = array();

	public static function analyze($filename) {

		// On d�termine le chemin r�el vers le fichier.
		$realpath = realpath($filename);

		// Si l'emplacement ne d�signe pas un fichier valide, on l�ve une exception.
		if (!$realpath || !is_file($realpath)) {
			throw new Corp_Exception("Invalid file: $filename ($realpath)");
		}

		// Lecture du contenu du fichier.
		$tokens = file_get_contents($realpath);

		// Si la lecture a �chou�e, on l�ve une exception.
		if ($tokens === false) {
			throw new Corp_Exception("Unable to read file: $filename");
		}

	}

}

?>