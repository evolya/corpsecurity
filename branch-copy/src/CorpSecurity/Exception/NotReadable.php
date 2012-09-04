<?php

class Corp_Exception_NotReadable extends Corp_Exception_IO {
	public function __construct(Corp_ExecutionContext $context = null, $message = 'Not Readable', Exception $previous = null) {
		parent::__construct($context, $message, 500, $previous);
	}
}

?>