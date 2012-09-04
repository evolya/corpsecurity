<?php

interface Corp_Serializable {
	
	/**
	 * @return string[]
	 */
	public function __sleep();

	/**
	 * @return void
	 */
	public function __wakeup();
	
}

?>