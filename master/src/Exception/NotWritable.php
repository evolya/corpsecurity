<?php

class Corp_Exception_NotWritable extends Corp_Exception_IO {
	public function __construct(Corp_ExecutionContext $context = null, $message = 'Not Writable', Exception $previous = null) {
		parent::__construct($context, $message, 500, $previous);
	}
}

?>