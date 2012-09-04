<?php

/**
 * Identity database using a .htdigest file
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
 * @package    evolya.corpsecurity.auth
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Auth_Identity_HtdigestFile implements Corp_Auth_Identity_Manager {

	/**
	 * @var string[]
	 */
	protected $entries = array();
	
	/**
	 * Constructor
	 * @param string $filename
	 * @throws Corp_Exception_FileNotFound
	 * @throws Corp_Exception_FileNotReadable
	 */
	public function __construct($filename) {
		
		if (!is_file($filename)) {
			throw new Corp_Exception_FileNotFound(null, $filename);
		}
		
		$contents = @file_get_contents($filename);
		
		if ($contents === false) {
			throw new Corp_Exception_FileNotReadable(null, $filename);
		}
		
		foreach (explode("\n", $contents) as $line) {
			$line = trim($line);
			if (empty($line) || substr($line, 0, 1) == '#') continue;
			list($uid, $realm, $hash) = explode(':', $line, 3);
			$this->entries["$realm:$uid"] = $line;
		}
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_Identity_Database::exists()
	 */
	public function exists($realm, $uid, $passwordHash) {
		return in_array("$uid:$realm:$passwordHash", $this->entries) ? $uid : null;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_IdentityManager::getHash()
	 */
	public function getHash($realm, $uid) {
		if (!array_key_exists("$realm:$uid", $this->entries)) {
			return false;
		}
		list($uid, $realm, $hash) = explode(':', $this->entries["$realm:$uid"], 3);
		return $hash;
	}
	
	/**
	 * @param string $uid
	 * @return Corp_Auth_Identity|null
	 */
	public function getIdentityByUID($realm, $uid) {
		if (!array_key_exists("$realm:$uid", $this->entries)) {
			throw new Corp_Exception_NotFound(null, 'UID not found');
		}
		return new Corp_Auth_Identity(
			$realm,							// Realm
			$uid,							// User unique ID
			Corp_Auth_Identity::TYPE_USER,	// Identity Type
			$uid							// User name
		);
	}
	
	/**
	 * @return string
	 */
	public function __toString() {
		return get_class($this) . '[]';
	}
	
}

?>