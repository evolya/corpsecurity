<?php

class Corp_Exception_FileNotReadable extends Corp_Exception_NotReadable {
	public function __construct(Corp_ExecutionContext $context = null, $file, $reason = '', Exception $previous = null) {
		parent::__construct($context, "File '$file' not readable: $reason", $previous);
	}
}

?>