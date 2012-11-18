<?php

class Corp_Exception_AllreadyExists extends Corp_Exception {
	public function __construct($msg, Exception $previous = null) {
		parent::__construct(null, $msg, 500, $previous);
	}
}

?>