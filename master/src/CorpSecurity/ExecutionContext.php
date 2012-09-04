<?php

/**
 * Context helper class
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
 * @package    evolya.corpsecurity
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_ExecutionContext {

	/**
	 * @var Object[] $data
	 */
	protected $data = array();
	
	/**
	 * @var boolean
	 */
	protected $headersSend = false;
	
	/**
	 * DOCTODO
	 * @return void
	 */
	public function setHeaderResponseSent() {
		$this->headersSent = true;
	}
	
	/**
	 * DOCTODO
	 * @return boolean
	 */
	public function isHeaderResponseSent() {
		return $this->headersSent || headers_sent();
	}
	
	/**
	 * @param string $methodName
	 * @param mixed[] $arguments
	 */
	public function __call($methodName, $arguments) {
		$methodName = strtolower($methodName);
		if (substr($methodName, 0, 3) == 'get') {
			return $this->data[substr($methodName, 3)];
		}
		if (substr($methodName, 0, 3) == 'set') {
			$this->data[substr($methodName, 3)] = $arguments[0];
			return;
		}
		trigger_error("Invalid context data: $methodName", E_USER_WARNING);
	}
	
	/**
	 * @throws Exception
	 */
	public function __sleep() {
		throw new Exception('Serialization of ' . get_class($this) . ' is forbidden');
	}
	
	/**
	 * @throws Exception
	 */
	public function __wakeup() {
		throw new Exception('Serialization of ' . get_class($this) . ' is forbidden');
	}
	
	/**
	 * @return string
	 */
	public function __toString() {
		return get_class($this); // . "[REQUEST={$this->request}; AGENT=".$this->getAgent()."; SESSION=".$this->getSession(). "\; IDENTITY=".$this->getIdentity().']';
	}

} 

?>