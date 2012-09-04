<?php

class Corp_Test_XmlConfig extends UnitTestCase {
	
	/**
	 @var Corp_XmlConfig
	 */
	protected $xmlconfig;
	
	function __construct() {
		parent::__construct('Corp - XmlConfig');
		$this->setup();
	}
	
	function setup() {
		$this->xmlconfig = new Corp_XmlConfig();
	}

	function test_initialState() {
		$this->assertFalse($this->xmlconfig->debugMode);
		$this->assertEqual(sizeof($this->xmlconfig->debugFlags), 0);
		$this->assertEqual(sizeof($this->xmlconfig->getProperties()), 0);
		$this->assertEqual(sizeof($this->xmlconfig->getBeans()), 0);
		$this->assertFalse($this->xmlconfig->isBeanExists('service'));
	}
	
	function test_load_failure() {
		@$this->assertFalse($this->xmlconfig->loadFile('toto'));
		@$this->assertFalse($this->xmlconfig->loadString('toto'));
		@$this->assertFalse($this->xmlconfig->loadString('<?xml version="1.0" encoding="UTF-8"?><confs></confs>'));
	}
	
	function test_load_string() {
		@$this->assertTrue($this->xmlconfig->loadString('<?xml version="1.0" encoding="UTF-8"?><conf basedir="."></conf>'));
	}
		
	function test_debug() {
		$this->assertFalse($this->xmlconfig->debugMode);
		$this->assertEqual(sizeof($this->xmlconfig->debugFlags), 0);
		@$this->assertTrue($this->xmlconfig->loadString('<?xml version="1.0" encoding="UTF-8"?><conf debug="xmlconfig,autrechose"></conf>'));
		$this->assertTrue($this->xmlconfig->debugMode);
		$this->assertEqual(sizeof($this->xmlconfig->debugFlags), 2);
	}
	
	function test_properties() {
		@$this->assertTrue($this->xmlconfig->loadString('<?xml version="1.0" encoding="UTF-8"?><conf>
			<property name="Boolean">TRUE</property>
			<property name="Null">NULL</property>
			<property name="Integer">512</property>
			<property name="Float">3.14</property>
			<property name="Empty"></property>
		</conf>'));
		$this->assertEqual(sizeof($this->xmlconfig->getProperties()), 5);
		$this->assertTrue(isset($this->xmlconfig['Boolean']));
		$this->assertTrue(isset($this->xmlconfig['Null']));
		$this->assertTrue(isset($this->xmlconfig['Integer']));
		$this->assertTrue(isset($this->xmlconfig['Float']));
		$this->assertTrue(isset($this->xmlconfig['Empty']));
		$this->assertIdentical($this->xmlconfig['Boolean'], true);
		$this->assertIdentical($this->xmlconfig['Null'], null);
		$this->assertIdentical($this->xmlconfig['Integer'], 512);
		$this->assertIdentical($this->xmlconfig['Float'], 3.14);
		$this->assertIdentical($this->xmlconfig['Empty'], '');
	}
	
	function test_properties_replace() {
		// TODO test_properties_replace
	}
	
	function test_properties_baseDir() {
		// TODO TODO test_properties_baseDir
	}

	function test_finalStates() {
		$this->assertFalse($this->xmlconfig->debugMode);
		$this->assertEqual(sizeof($this->xmlconfig->debugFlags), 0);
		$this->assertEqual(sizeof($this->xmlconfig->getProperties()), 0);
		$this->assertEqual(sizeof($this->xmlconfig->getBeans()), 0);
		//$this->assertTrue($this->xmlconfig->isBeanExists('service'));
	}
	
}

?>