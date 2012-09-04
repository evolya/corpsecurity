<?php

class Corp_Plugin_jCryption implements Corp_Plugin {
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
		$service->subscribeEvent('onLoginFormJsGeneration', array($this, 'onLoginFormJsGeneration'));
	}
	
	/**
	 * @param string[] &$js
	 * @param Corp_Auth_LoginForm $form
	 */
	public function onLoginFormJsGeneration(array &$js, Corp_Auth_LoginForm $form) {
		$js[] = <<<_JS
Corp.LoginForm.beforeInit.push(function (form) {
	var opt = Corp.LoginForm.addOption('aes', 'RSA+AES', 'Encypt all messages with AES for securized communications.');
});
_JS;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'jcryption';
	}
	
}

?>