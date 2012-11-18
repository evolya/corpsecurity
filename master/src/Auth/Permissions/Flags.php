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
 * @package    evolya.corpsecurity.auth
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
abstract class Corp_Auth_Permissions_Flags extends Corp_Auth_Permissions_Manager implements Corp_Serializable {
	
	/**
	 * @var mixed[]
	 */
	protected $flags = array();
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_Permissions_Manager::getPermissions()
	 */
	public function getPermissions($uri, Corp_Auth_Identity $identity) {
		// Ensure the identity is bound to an user
		if ($identity->getIdentityType() === Corp_Auth_Identity::TYPE_USER) {
			return self::explodeCharacteres($this->getFlags($identity));
		}
		return array();
	}
	
	/**
	 * @param Corp_Auth_Identity $identity
	 * @return string
	 */
	public abstract function getFlags(Corp_Auth_Identity $identity);
	
	/**
	 * @param char $flag
	 * @param int $level
	 * @param string $description
	 * @return void
	 * @throws Corp_Exception_InvalidArgument
	 * @throws Corp_Exception_AllreadyExists
	 */
	public function addFlag($flag, $level = 100, $description = '', $category = 'corp') {
		
		// Check $flag argument
		if (!is_string($flag) || strlen($flag) != 1) {
			throw new Corp_Exception_InvalidArgument('$flag', $flag, 'char');
		}
		
		// Check $level argument
		if (!is_int($level) || $level < 0) {
			throw new Corp_Exception_InvalidArgument('$level', $level, 'integer over 0');
		}
		
		// Flag allready exists
		if (isset($this->flags[$flag])) {
			throw new Corp_Exception_AllreadyExists("Flag: $flag");
		}
		
		// Add flag
		$this->flags[$flag] = array(
			'flag'			=> $flag,
			'category'		=> $category,
			'level'			=> $level,
			'description'	=> $description
		);
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Serializable::__sleep()
	 */
	public function __sleep() {
		return array('flags');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Serializable::__wakeup()
	 */
	public function __wakeup() {
		
	}
	
	/**
	 * @param string $str
	 */
	public static function explodeCharacteres($str) {
		$r = array();
		for ($l = strlen($str), $i = 0; $i < $l; $i++) {
			$r[] = $str{$i};
		}
		return $r;
	}
	
}

?>