<?php

class Corp_Exception_Security_NeedPrivileges extends Corp_Exception_Security {
	public function __construct($uri, Corp_Auth_Identity $identity, array $failed, Corp_ExecutionContext $context = null, Exception $previous = null) {
		parent::__construct($context, 'Required privileges: ' . implode(', ', $failed), 501, $previous);
	}
}

?>