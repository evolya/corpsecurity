<?php

class Corp_Test_Service extends UnitTestCase {
	
	/**
	 @var Corp_Service
	 */
	protected $service;
	
	function __construct() {
		parent::__construct('Corp - Service');
		$this->setup();
	}
	
	function setup() {
		$this->service = new Corp_Service();
	}
	
	function test_getPersistenceManager() {
		$this->assertNull($this->service->getPersistenceManager());
	}
	
	function test_setPersistenceManager() {
		$this->service->setPersistenceManager(new Corp_Persistence_Stateless());
		$this->assertIsA($this->service->getPersistenceManager(), 'Corp_Persistence_Manager');
		$this->assertIsA($this->service->getPersistenceManager(), 'Corp_Persistence_Stateless');
	}

	
}

?>