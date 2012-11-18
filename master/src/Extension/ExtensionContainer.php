<?php

class Corp_ExtensionContainer implements Corp_ServicePlugin {

	/**
	 * @param Corp_Service $service
	 * @return void
	 */
	public function initialize(Corp_Service $service) {
		
	}

	public function openExtensionDirectory($dir) {

		if (!is_dir($dir)) {
		}

	}

	/**
	 * @return string
	 */
	public function getPluginName() {
		return 'extensioncontainer';
	}

}

?>