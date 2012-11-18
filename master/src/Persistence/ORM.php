<?php

interface Corp_Persistence_ORM {
	
	/**
	 * @return void
	 */
	public function initialize(Corp_Persistence_Manager $manager);
	
	/**
	 * @param string $uid
	 * @return boolean
	 */
	public function isEntryExists($uid);
	
	/**
	 * @param mixed[] $data
	 * @return void
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function insertEntry(array $data);
	
	/**
	 * @param string $uid
	 * @param mixed[] $data
	 * @return void
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function updateEntry($uid, array $data);
	
	/**
	 * @param string $uid
	 * @return mixed[]|null
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function getEntry($uid);
	
	/**
	 * @param string $uid
	 * @return boolean
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function deleteEntry($uid);
	
	/**
	 * @param mixed[] $where
	 * @param int $limit
	 * @return int
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function deleteEntries($where = array(), $limit = 0);
	
	/**
	 * @return int[]|string[]
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function getEntriesIDs();
	
	/**
	 * @param mixed[] $where
	 * @param int $limit
	 * @return mixed[][]
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function getEntries($where = array(), $limit = 0);
	
	/**
	 * @return boolean
	 */
	public function supportSQL();
	
	/**
	 * @param string $sql
	 * @return mixed
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function executeSQL($sql);
	
}

?>