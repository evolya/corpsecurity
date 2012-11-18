<?php

class Corp_Exception_InvalidArgument extends Corp_Exception {
	public function __construct($name, $value, $expected, Exception $previous = null) {
		parent::__construct(null, "Invalid argument '$name' with type ".gettype($value).", $expected expected", 500, $previous);
	}
}

?>