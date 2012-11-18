<?php

class Corp_Exception_Security_Unauthorized extends Corp_Exception_Security {
	public function __construct(Corp_ExecutionContext $context = null, $message = 'Unauthorized', Exception $previous = null) {
		parent::__construct($context, $message, 401, $previous);
	}
}

?>