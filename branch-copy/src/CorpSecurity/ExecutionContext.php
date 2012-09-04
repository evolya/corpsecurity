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
	 * @var Corp_Service
	 */
	protected $service;
	
	/**
	 * @var Corp_Request
	 */
	protected $request;
	
	/**
	 * @var boolean
	 */
	protected $headerResponseSent = false;
	
	/**
	 * Constructor
	 * @param Corp_Request $request
	 */
	public function __construct(Corp_Service $service) {
		$this->service = $service;
	}
	
	/**
	 * @return Corp_Service
	 */
	public function getService() {
		return $this->service;
	}
	
	/**
	 * @return Corp_Request
	 */
	public function getRequest() {
		return $this->request;
	}
	
	/**
	 * @param Corp_Request $request
	 * @return void
	 */
	public function setRequest($request) {
		$this->request = $request;
	}
	
	/**
	 * @return Corp_Agent
	 */
	public function getAgent() {
		return $this->request != null ? $this->request->getAgent() : null;
	}
	
	/**
	 * @return Corp_Persistence_Manager
	 */
	public function getPersistenceManager() {
		return $this->service->getPersistenceManager();
	}
	
	/**
	 * @return Corp_Persistence_Session
	 */
	public function getSession() {
		return $this->service->getPersistenceManager()->getCurrentSession();
	}
	
	/**
	 * @return Corp_Auth_Identity|null
	 */
	public function getIdentity() {
		$session = $this->service->getPersistenceManager()->getCurrentSession();
		if (!$session) return null;
		return $session->getIdentity();
	}
	
	/**
	 * @return boolean
	 */
	public function isHeaderResponseSent() {
		return $this->headerResponseSent;
	}
	
	/**
	 * @return void
	 */
	public function setHeaderResponseSent() {
		$this->headerResponseSent = true;
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
		return get_class($this) . "[REQUEST={$this->request}; AGENT=".$this->getAgent()."; SESSION=".$this->getSession(). "\; IDENTITY=".$this->getIdentity().']';
	}

} 

?>