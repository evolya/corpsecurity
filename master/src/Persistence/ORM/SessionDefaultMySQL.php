<?php

/**
 * DOCTODO
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
 * @package    evolya.corpsecurity.plugins
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Persistence_ORM_SessionDefaultMySQL implements Corp_Persistence_ORM_Session {
	
	/**
	 * @var PDO
	 */
	protected $pdo;
	
	/**
	 * @var Corp_Persistence_Manager
	 */
	protected $manager = null;
	
	/**
	 * @var string
	 */
	public $tableName = 'corp_sessions';
	
	/**
	 * @var boolean
	 */
	public $debugQueries = false;
	
	protected $statementExists;
	protected $statementInsert;
	protected $statementUpdate;
	protected $statementDelete;
	protected $statementSelect;
	protected $statementSelectIDs;
	protected $statementExpiredSelect;
	protected $statementSelectAll;
	
	/**
	 * Constructor
	 * @param PDO $pdo
	 */
	public function __construct(PDO $pdo) {
		$this->pdo = $pdo;		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::initialize()
	 * IMPROVE On demand ?
	 */
	public function initialize(Corp_Persistence_Manager $manager) {
		
		$this->manager = $manager;
		
		$this->statementExists = $this->pdo->prepare(
			"SELECT COUNT(`id`) FROM `{$this->tableName}`
			WHERE `sid` = :sid
			LIMIT 1");
		
		$this->statementInsert = $this->pdo->prepare(
			"INSERT INTO `{$this->tableName}`
			(`id`, `sid`, `type`, `api`, `agent`, `qop`, `identity`, `creation_time`, `last_request_time`, `expiration_delay`, `user_data`, `session_data`)
			VALUES
			('',?,?,?,?,?,?,?,?,?,?,?)");
		
		$this->statementUpdate = $this->pdo->prepare(
			"UPDATE `{$this->tableName}`
			SET
				`agent`				= ?,
				`qop`				= ?,
				`identity`			= ?,
				`last_request_time`	= ?,
				`expiration_delay`	= ?,
				`user_data`			= ?,
				`session_data`		= ?
			WHERE `sid` = ?
			LIMIT 1");
		
		$this->statementDelete = $this->pdo->prepare(
			"DELETE FROM `{$this->tableName}`
			WHERE `sid` = :sid
			LIMIT 1");
		
		$this->statementSelect = $this->pdo->prepare(
			"SELECT * FROM `{$this->tableName}`
			WHERE `sid` = :sid
			LIMIT 1");
		
		$this->statementSelectIDs = $this->pdo->prepare(
			"SELECT `id`, `sid` FROM `{$this->tableName}`
			WHERE 1");
		
		$this->statementExpiredSelect = $this->pdo->prepare(
			"SELECT * FROM `{$this->tableName}`
			WHERE `last_request_time` + `expiration_delay` < :referenceTime");
		
		$this->statementSelectAll = $this->pdo->prepare(
			"SELECT * FROM `{$this->tableName}`
			WHERE 1");
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::isEntryExists()
	 */
	public function isEntryExists($uid) {
		
		// Execution de la requête
		$this->executeQuery($this->statementExists, array(':sid' => $uid));
		
		// Récupération du résultat
		$result = $this->statementExists->fetch();
		
		// Fermeture du statement
		$this->statementExists->closeCursor();
		
		// Renvoi d'un résultat boolean
		return $result['COUNT(`id`)'] > 0;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::insertEntry()
	 */
	public function insertEntry(array $data) {
		
		// Execution de la requête
		$this->executeQuery($this->statementInsert, array_values($data));
		
		// Fermeture du statement
		$this->statementInsert->closeCursor();
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::updateEntry()
	 */
	public function updateEntry($uid, array $data) {
		
		// Override session unique ID
		$data[8] = $uid;
		
		// Execution de la requête
		$this->executeQuery($this->statementUpdate, array_values($data));
		
		// Fermeture du statement
		$this->statementUpdate->closeCursor();
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::getEntry()
	 */
	public function getEntry($uid) {
		
		// Execution de la requête
		$this->executeQuery($this->statementSelect, array(':sid' => $uid));
		
		// Aucun résultat
		if ($this->statementSelect->rowCount() !== 1) {
			
			// Fermeture du statement
			$this->statementSelect->closeCursor();
			
			// Et on renvoi null pour indiquer que l'entrée n'existe pas
			return null;
			
		}
		
		// Création de la session
		$session = $this->createSessionResultSet($this->statementSelect->fetch());
		
		// Fermeture du statement
		$this->statementSelect->closeCursor();
		
		// On renvoi la session créée
		return $session;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::deleteEntry()
	 */
	public function deleteEntry($uid) {

		// Execution de la requête
		$this->executeQuery($this->statementDelete, array(':sid' => $uid));
		
		// Nombre de lignes supprimées
		$count = $this->statementDelete->rowCount();
		
		// Fermerture du statement
		$this->statementDelete->closeCursor();
		
		// Renvoi sous forme de boolean
		return $count > 0;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::deleteEntries()
	 */
	public function deleteEntries($where = array(), $limit = 0) {
		throw new Exception('Not implemented yet'); // XTODO Not implemented yet
		return 0;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::getEntriesIDs()
	 */
	public function getEntriesIDs() {
		
		// Execution de la requête
		$this->executeQuery($this->statementSelectIDs);
		
		// Aucun résultat
		if ($this->statementSelectIDs->rowCount() < 1) {
			
			// On ferme le statement
			$this->statementSelectIDs->closeCursor();
			
			// Et on renvoi un tableau vide
			return array();
			
		}
		
		// Tableau de sortie
		$r = array();
		
		// Parcours des résultats
		foreach ($this->statementSelectIDs->fetchAll(PDO::FETCH_ASSOC) as $id) {
			
			// Copie de l'ID dans le tableau de sortie
			$r[$data['id']] = $data['sid'];
			
		}
		
		// Fermeture du statement
		$this->statementSelectIDs->closeCursor();
		
		// On renvoi un tableau avec les IDs
		return $r;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::getEntries()
	 */
	public function getEntries($where = array(), $limit = 0) {
		throw new Exception('Not implemented yet'); // XTODO Not implemented yet
		
		
		
		return array();
	}
	
	/**
	 * @return boolean
	 */
	public function supportSQL() {
		return true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_ORM::executeSQL()
	 */
	public function executeSQL($sql) {
		
		// Transfert de la demande au PDO
		return $this->pdo->exec($sql);
		
	}
	
	/**
	 * @return PDO
	 */
	public function getPDO() {
		return $this->pdo;
	}
	
	/**
	 * @return Corp_Persistence_Session[]
	 */
	public function getSessions() {
		
		// Execution de la requête
		$this->executeQuery($this->statementSelectAll);
		
		// Aucun résultat
		if ($this->statementSelectAll->rowCount() < 1) {
			return array();
		}
		
		// Tableau de sortie
		$r = array();
		
		// Parcours des résultats
		foreach ($this->statementSelectAll->fetchAll(PDO::FETCH_ASSOC) as $data) {
			
			// Création de l'objet session, et ajout dans le tableau de sortie
			$r[] = $this->createSessionResultSet($data);
			
		}
		
		// On ferme le statement
		$this->statementSelectAll->closeCursor();
		
		// On renvoi le tableau avec des sessions
		return $r;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_SessionORM::getSession()
	 */
	public function getSession($sid) {
		return $this->getEntry($sid);
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
			$session->getSID(),
			get_class($session),
			$session->getSessionType(),
			$session->getUserAgent(),
			$session->getQoPLevel(),
			$session->getIdentity(),
			$session->getCreationTime(),
			$session->getLastRequestTime(),
			$session->getExpirationDelay(),
			$session->getDataArray(),
			$session->getSessionData()
		));
		// $this->pdo->lastInsertId();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_SessionORM::updateSession()
	 */
	public function updateSession(Corp_Persistence_Session $session) {
		return $this->updateEntry($session->getSID(), array(
			$session->getUserAgent(),
			$session->getQoPLevel(),
			$session->getIdentity(),
			$session->getLastRequestTime(),
			$session->getExpirationDelay(),
			$session->getDataArray(),
			$session->getSessionData()
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
	 * @param PDOStatement $st
	 * @param mixed[] $data
	 * @param int $index
	 * @return void
	 * @throws Corp_Exception_Database
	 */
	protected function executeQuery(PDOStatement $st, array $data = array(), $index = 1) {
	
		foreach ($data as $key => $value) {
	
			// Clé
			if (is_int($key)) {
				$key = $index++;
			}

			// Traitement de la valeur
			$value = $this->prepareValue($value);

			// Bind de la valeur
			if (is_int($value)) {
				$st->bindValue($key, $value, PDO::PARAM_INT);
			}
			else if (is_float($value)) {
				$st->bindValue($key, $value, PDO::PARAM_INT); // TODO C'est quoi le bon type?!
			}
			else if (is_bool($value)) {
				$st->bindValue($key, $value, PDO::PARAM_BOOL);
			}
			else if (is_null($value)) {
				$st->bindValue($key, null, PDO::PARAM_NULL);
			}
			else {
				$st->bindValue($key, $value, PDO::PARAM_STR);
			}
	
		}
	
		// Debug
		if ($this->debugQueries) {
			@$st->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			@$st->debugDumpParams();
		}
	
		if (!$st->execute()) {
			throw new Corp_Exception_Database(
				null,
				$this,
				array_pop($st->errorInfo()),
				$st->errorCode(),
				$st->queryString
			);
		}
	
	}
	
	/**
	 * @param string $value
	 * @param string $type
	 * @return mixed
	 */
	public function prepareValue($value, $type = 'auto') {
		if (is_array($value)) {
			return serialize($value);
		}
		else if (is_object($value)) {
			return serialize($value);
		}
		else if (is_bool($value)) {
			return $value ? true : false;
		}
		else if (is_numeric($value)) {
			return $value;
		}
		else if (is_null($value)) {
			return null;
		}
		return "$value";
	}
	
	/**
	 * @param array $data
	 * @return Corp_Persistence_Session
	 */
	public function createSessionResultSet(array $data) {
		
		// Create session
		$session = $this->manager->sessionFactory(
			$data['sid'],
			$data['type'],
			unserialize($data['agent']),
			unserialize($data['qop']),
			unserialize($data['identity'])
		);

		// Change times
		$session->setTimes($data['creation_time'], $data['last_request_time']);
		
		// Restore user data
		$session->setDataArray(unserialize($data['user_data']));
		
		// Restore session data
		$session->setSessionData(unserialize($data['session_data']));
		
		return $session;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Persistence_SessionORM::getExpiredSessions()
	 */
	public function getExpiredSessions($referenceTime) {

		// Execution de la requête
		$this->executeQuery($this->statementExpiredSelect, array(':referenceTime' => $referenceTime));
		
		// Aucun résultat
		if ($this->statementExpiredSelect->rowCount() < 1) {
			return array();
		}
		
		// Tableau de sortie
		$r = array();
		
		// Parcours des résultats
		foreach ($this->statementExpiredSelect->fetchAll(PDO::FETCH_ASSOC) as $data) {
			
			// Création de l'objet session, et ajout dans le tableau de sortie
			$r[] = $this->createSessionResultSet($data);
			
		}
		
		// On ferme le statement
		$this->statementExpiredSelect->closeCursor();
		
		// On renvoi le tableau avec des sessions
		return $r;
		
	}
	
}

?>