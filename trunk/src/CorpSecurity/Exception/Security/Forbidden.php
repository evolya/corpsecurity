<?php

class Corp_Exception_Security_Forbidden extends Corp_Exception_Security {
	public function __construct(Corp_ExecutionContext $context = null, $message = 'Forbidden', Exception $previous = null) {
		parent::__construct($context, $message, 403, $previous);
	}
}

?>