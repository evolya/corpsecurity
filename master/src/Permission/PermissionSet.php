<?php

/**
 *
 */
final class Corp_PermissionSet {

	/**
	 * @var mixed[] Tableau regroupant les permissions, ainsi que les fichiers/lignes des occurences.
	 */
	private $permissions = array();

	/**
	 * Renvoi le nombre de permissions dans le set.
	 *
	 * @return int
	 */
	public function getLength() {
		return sizeof($this->permissions);
	}

	/**
	 * Ajouter une permission dans le set.
	 *
	 * @param Sandbox_Permission $perm La permission à ajouter.
	 * @param string|null $realpath Chemin vers le fichier où se trouve l'occurence.
	 * @param int $line Ligne où se trouve l'occurence.
	 * @return void
	 */
	public function addPermission(Corp_Permission $perm) {
		// On ajoute la permission dans le set.
		// On s'assure que chaque permission soit unique.
		// TODO Note: et si on a plusieurs occurences de la permission ?!
		$this->permissions[$perm->getSubject() . ':' . $perm->getAction()] = $perm;
	}

	/**
	 * Renvoi un objet itérable contenant les permissions.
	 *
	 * @return Iterator
	 */
	public function getIterator() {
		return $this->permissions;
	}

	/**
	 * Afficher les permissions sous forme de string.
	 *
	 * @return string
	 */
	public function __toString() {
		return implode(',', $this->permissions);
	}

}

?>