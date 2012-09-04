<?php

class Corp_Exception_UnsupportedOperation extends Corp_Exception {
	public function __construct(Corp_ExecutionContext $context = null, $message = 'Unsupported Operation', Exception $previous = null) {
		parent::__construct($context, $message, 501, $previous);
	}
}

?>