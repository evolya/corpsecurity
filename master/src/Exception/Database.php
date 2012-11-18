<?php

class Corp_Exception_Database extends Corp_Exception_Persistence {
	public function __construct(Corp_ExecutionContext $context = null, Corp_Persistence_ORM $orm = null, $errmsg = null, $errcode = 0, $sqlQuery = '', Exception $previous = null) {
		parent::__construct($context, null, "$errmsg ($errcode) in request '".addslashes($sqlQuery)."'", $previous);
	}
}

?>