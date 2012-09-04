<?php

/**
 * DOCTODO class Corp_Persistence_SessionORM_Moodel implements Corp_Persistence_SessionORM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    evolya.corpsecurity.config
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Persistence_SessionORM_Moodel implements Corp_Persistence_ORM_Session {

	/**
	 * @var MoodelStruct <CorpSession> $model
	 */
	protected $model;
	
	/**
	 * @param Corp_Persistence_Manager $manager
	 */
	protected $manager;
	
	/**
	 * @param MoodelStruct <UserSessionCorp> $model
	 */
	public function __construct(MoodelStruct $model) {
		$this->model = $model;
	}
	
	/**
	 * @return void
	 */
	public function initialize(Corp_Persistence_Manager $manager) {
		$this->manager = $manager;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_SessionORM::getSessions()
	 */
	public function getSessions() {
		$r = array();
		foreach ($this->model->all() as $model) {
			$r[] = $this->moodel2Session($model);
		}
		return $r;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_SessionORM::getSession()
	 */
	public function getSession($sid) {
		$model = $this->model->getBySID($sid, 1);
		return $model ? $this->moodel2Session($model) : null;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_SessionORM::isSessionExists()
	 */
	public function isSessionExists(Corp_Persistence_Session $session) {
		return $this->isEntryExists($session->getSID());
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_SessionORM::insertSession()
	 */
	public function insertSession(Corp_Persistence_Session $session) {
		return $this->insertEntry(array(
			'sid'				=> $session->getSID(),
			'type'				=> get_class($session),
			'api'				=> $session->getSessionType(),
			'agent'				=> $session->getUserAgent(),
			'qop'				=> $session->getQoPLevel(),
			'identity'			=> $session->getIdentity(),
			'creation_time'		=> $session->getCreationTime(),
			'last_request_time'	=> $session->getLastRequestTime(),
			'expiration_delay'	=> $session->getExpirationDelay(),
			'user_data'			=> $session->getDataArray(),
			'session_data'		=> $session->getSessionData()
		));
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_SessionORM::updateSession()
	 */
	public function updateSession(Corp_Persistence_Session $session) {
		return $this->updateEntry($session->getSID(), array(
			'agent'				=> $session->getUserAgent(),
			'qop'				=> $session->getQoPLevel(),
			'identity'			=> $session->getIdentity(),
			'last_request_time'	=> $session->getLastRequestTime(),
			'expiration_delay'	=> $session->getExpirationDelay(),
			'user_data'			=> $session->getDataArray(),
			'session_data'		=> $session->getSessionData()
		));
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_SessionORM::deleteSession()
	 */
	public function deleteSession(Corp_Persistence_Session $session) {
		return $this->deleteEntry($session->getSID());
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_SessionORM::getExpiredSessions()
	 */
	public function getExpiredSessions($referenceTime) {
		
		// Get the database binded to the model
		$db = $this->model->_db;
		
		// Parse as integer
		$referenceTime = intval($referenceTime);
		
		try {
			
			// Request
			$q = $db->query("SELECT * FROM `{$db->getPrefix()}{$this->model->name()}` WHERE `last_request_time` + `expiration_delay` < {$referenceTime}");
			
			// Returned array
			$r = array();
			
			// Fetch results
			foreach ($q as $rs) {
				$r[] = $this->resultset2Session($rs);
			}
			
			// Return sessions
			return $r;
			
		}
		catch (Exception $ex) {
			throw new Corp_Persistence_Exception(
				null,
				$this->manager,
				'Unable to perform: getExpiredSessions',
				$ex
			);
		}
		
	}
	
	/**
	 * @param string $uid
	 * @return boolean
	 */
	public function isEntryExists($uid) {
		
		// Get the database binded to the model
		$db = $this->model->_db;
		
		// Escape UID
		$uid = $db->escapeString($uid);
		
		try {
		
			// Request count
			$r = $db->query("SELECT COUNT(`sid`) FROM `{$db->getPrefix()}{$this->model->name()}` WHERE `sid` = '{$uid}' LIMIT 1");
			
		}
		catch (Exception $ex) {
			throw new Corp_Persistence_Exception(
				null,
				$this->manager,
				'Unable to perform: isEntryExists',
				$ex
			);
		}
		
		// Get result, and parse as an integer
		$count = $r->current()->get('COUNT(`sid`)') + 0;
		
		// Return as boolean
		return $count > 0;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::insertEntry()
	 */
	public function insertEntry(array $data) {
		
		try {
		
			// Create a new instance, set data then save it 
			$this->model
				->new
				->values($data)
				->save();
				
		}
		catch (Exception $ex) {
			throw new Corp_Persistence_Exception(
				null,
				$this->manager,
				'Unable to perform: insertEntry',
				$ex
			);
		}
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::updateEntry()
	 */
	public function updateEntry($uid, array $data) {

		try {
		
			// Get model instance
			$instance = $this->model->getBySID($uid, 1);
			
			// Model doesn't exists
			if (!$instance) {
				throw new Corp_Persistence_Exception(
					null,
					$this->manager,
					"Unable to update inexistent session (SID={$uid})"
				);
			}
			
			// Set data
			$instance->values($data);
			
			// Save 
			$instance->save();
			
		}
		catch (Exception $ex) {
			throw new Corp_Persistence_Exception(
				null,
				$this->manager,
				'Unable to perform: updateEntry',
				$ex
			);
		}
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::getEntry()
	 */
	public function getEntry($uid) {
		$session = $this->getSession($uid);
		return $session ? $session->values() : array();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::deleteEntry()
	 */
	public function deleteEntry($uid) {
		
		// Get the database binded to the model
		$db = $this->model->_db;
		
		// Escape UID
		$uid = $db->escapeString($uid);
		
		try {
		
			// Request count
			$r = $db->query("DELETE FROM `{$db->getPrefix()}{$this->model->name()}` WHERE `sid` = '{$uid}' LIMIT 1");

		}
		catch (Exception $ex) {
			throw new Corp_Persistence_Exception(
					null,
					$this->manager,
					'Unable to perform: deleteEntry',
					$ex
			);
		}
		
		// Return as boolean
		return $r > 0;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::deleteEntries()
	 */
	public function deleteEntries($where = array(), $limit = 0) {
		// Not implemented yet
		throw new Corp_Exception_UnsupportedOperation();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::getEntriesIDs()
	 */
	public function getEntriesIDs() {
		
		// Get the database binded to the model
		$db = $this->model->_db;
		
		// Escape UID
		$uid = $db->escapeString($uid);
		
		try {
		
			// Request count
			$q = $db->query("SELECT `id`, `sid` FROM `{$db->getPrefix()}{$this->model->name()}` WHERE 1");

		}
		catch (Exception $ex) {
			throw new Corp_Persistence_Exception(
					null,
					$this->manager,
					'Unable to perform: getEntriesIDs',
					$ex
			);
		}
		
		// Returned array
		$r = array();
		
		// Fetch results
		foreach ($q as $rs) {
			$r[$rs->get('id')] = $rs->get('sid');
		}
		
		// Return IDs
		return $r;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::getEntries()
	 */
	public function getEntries($where = array(), $limit = 0) {
		// Not implemented yet
		throw new Corp_Exception_UnsupportedOperation();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::supportSQL()
	 */
	public function supportSQL() {
		return true;
	}
	
	/**
	 * @param Moodel<UserSessionCorp> $model
	 * @param boolean $restoreData
	 * @return Corp_Persistence_Session
	 */
	public function moodel2Session(Moodel $model, $restoreData = true) {
		
		// Create session
		$session = $this->manager->sessionFactory(
			$model->get('sid'),
			$model->get('type'),
			$model->get('agent'),
			$model->get('qop'),
			$model->get('identity')
		);
		
		// Update times
		$session->setTimes(
			$model->get('creation_time'),
			$model->get('last_request_time')
		);
		
		// Restore data
		if ($restoreData) {
			$userData = $model->get('user_data');
			$session->setDataArray($userData);
			$sessionData = $model->get('session_data');
			$session->setSessionData($sessionData);
		}

		// Return the session
		return $session;
		
	}
	
	/**
	 * @param DBResultRow $row
	 * @param boolean $restoreData
	 * @return Corp_Persistence_Session
	 */
	public function resultset2session(DBResultRow $row, $restoreData = true) {
		
		// Create session
		$session = $this->manager->sessionFactory(
			$row->get('sid'),
			$row->get('type'),
			unserialize($row->get('agent')),
			unserialize($row->get('qop')),
			unserialize($row->get('identity'))
		);
		
		// Update times
		$session->setTimes(
			$row->get('creation_time'),
			$row->get('last_request_time')
		);
		
		// Restore data
		if ($restoreData) {
			$userData = unserialize($row->get('user_data'));
			$session->setDataArray($userData);
			$sessionData = unserialize($row->get('session_data'));
			$session->setSessionData($sessionData);
		}
		
		// Return the session
		return $session;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::executeSQL()
	 */
	public function executeSQL($sql) {
		// TODO Rendre le type de retour compatible
		return $this->model->query($sql);
	}
	
}

?>