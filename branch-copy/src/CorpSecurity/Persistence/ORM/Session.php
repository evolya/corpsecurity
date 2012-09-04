<?php

interface Corp_Persistence_ORM_Session extends Corp_Persistence_ORM {

	/**
	 * @return Corp_Persistence_Session[]
	 */
	public function getSessions();
	
	/**
	 * @param int|string $sid
	 * @return Corp_Persistence_Session|null
	 */
	public function getSession($sid);

	/**
	 * @param Corp_Persistence_Session $session
	 * @return boolean
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function isSessionExists(Corp_Persistence_Session $session);
	
	/**
	 * @param Corp_Persistence_Session $session
	 * @return void
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function insertSession(Corp_Persistence_Session $session);
	
	/**
	 * @param Corp_Persistence_Session $session
	 * @return void
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function updateSession(Corp_Persistence_Session $session);
	
	/**
	 * @param Corp_Persistence_Session $session
	 * @return boolean
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function deleteSession(Corp_Persistence_Session $session);
	
	/**
	 * @param int $referenceTime
	 * @return Corp_Persistence_Session[]
	 * @throws Corp_Persistence_Exception
	 * @throws Corp_Exception
	 */
	public function getExpiredSessions($referenceTime);

}

?>