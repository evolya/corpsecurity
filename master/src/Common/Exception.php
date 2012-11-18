<?php

/**
 * DOCTODO
 */
class Corp_Exception extends Exception {

	public function __construct(Corp_ExecutionContext $context = null, $message = null, $code = 0, Exception $previous = null) {
		// Si le context est null, c'est une erreur interne, sinon une erreur pendant le traitement de la requete
		parent::__construct($message, $code, $previous);
	}

}

?>