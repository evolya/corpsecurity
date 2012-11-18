<?php

/**
 * 
 */
abstract class Corp_Permission {

	/**
	 * @var string $subject
	 * @var string $action
	 */
	private $subject, $action;

	/**
	 * Constructure de la classe.
	 *
	 * Les permissions correspondent au droit d'éxecuter des ACTIONS.
	 * Ces actions ont toujours une CIBLE (sur quoi on déclanche l'action)
	 * et un NOM d'action.
	 *
	 * Par exemple :
	 *		voiture:demarrer
	 * ou	mail:envoyer
	 * ou	user:supprimer
	 *
	 * @param string $subject		Nom de la cible
	 * @param string $action		Nom de l'action
	 */
	public function __construct($subject, $action) {
		$this->subject = (string) $subject;
		$this->action = (string) $action;
	}

	/**
	 * Renvoi le nom du sujet de la permission.
	 * Pour plus de détails, voir le constructeur.
	 *
	 * @return string
	 */
	public final function getSubject() {
		return $this->subject;
	}

	/**
	 * Renvoi le nom d'action de la permission.
	 * Pour plus de détails, voir le constructeur.
	 *
	 * @return string
	 */
	public final function getAction() {
		return $this->action;
	}

	/**
	 * Affichage de la permission sous forme de string.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->subject . ':' . $this->action;
	}

}

?>