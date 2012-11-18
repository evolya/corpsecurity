<?php

class Corp_Exception_FileNotFound extends Corp_Exception_NotFound {
	public function __construct(Corp_ExecutionContext $context = null, $file, Exception $previous = null) {
		parent::__construct($context, "File: $file", $previous);
	}
}

?>