<?php

class Corp_Exception_XmlConfig extends Corp_Exception {
	public function __construct(SimpleXMLElement $rootNode, $message = 'Forbidden', $filename = null, SimpleXMLElement $targetNode = null, Exception $previous = null) {
		parent::__construct(null, $message . ($filename != null ? ', in ' . $filename : ''), 500, $previous);
	}
}

?>