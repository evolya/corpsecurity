<?php

class Corp_Exception_FileNotWritable extends Corp_Exception_NotWritable {
	public function __construct(Corp_ExecutionContext $context = null, $file, $reason = '', Exception $previous = null) {
		parent::__construct($context, "File '$file' not writable: $reason", $previous);
	}
}

?>