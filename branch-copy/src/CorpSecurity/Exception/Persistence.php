<?php

class Corp_Exception_Persistence extends Corp_Exception {
	public function __construct(Corp_ExecutionContext $context = null, Corp_Persistence_Manager $manager = null, $message = null, Exception $previous = null) {
		parent::__construct($context, $message, 500, $previous);
	}
}

?>