<?php

class Corp_Exception_NotFound extends Corp_Exception {
	public function __construct(Corp_ExecutionContext $context = null, $message = 'Not Found', Exception $previous = null) {
		parent::__construct($context, $message, 404, $previous);
	}
}

?>