<?php

/**
 * Abstract class for a request
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
 * @package    evolya.corpsecurity.request
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
abstract class Corp_Request {
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @return void
	 * @event beforeUserAgentDetected
	 * @event afterUserAgentDetected
	 */
	public abstract function guessConfiguration(Corp_ExecutionContext $context);
	
	/**
	 * Returns the HTTP request body body
	 *
	 * This method returns a readable stream resource.
	 * If the asString parameter is set to true, a string is sent instead.
	 *
	 * @param bool asString
	 * @return resource|string
	 */
	public abstract function getBody($asString = false);
	
	/**
	 * Sets the contents of the HTTP request body
	 *
	 * This method can either accept a string, or a readable stream resource.
	 *
	 * @param mixed $body
	 * @return void
	 */
	public abstract function setBody($body);
	
	/**
	 * @return void
	 */
	public abstract function closeConnection();
	
	/**
	 * @var string
	 */
	protected $sapiName = 'undefined';
	
	/**
	 * @var string 'HTTP' or 'CLI'
	 */
	protected $type = null;
	
	/**
	 * @var Corp_Request_QoP
	 */
	protected $qop;
	
	/**
	 * @var Corp_Agent
	 */
	protected $agent;
	
	/**
	 * @var mixed[]
	 */
	protected $config = array();
	
	/**
	 * Constructor
	 */
	protected function __construct() {
		$this->qop = new Corp_Request_QoP();
	}
	
	/**
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		return $this->config[$property];
	}
	
	/**
	 * @param string $property
	 * @param mixed $value
	 * @return void
	 */
	public function __set($property, $value) {
		$this->config[$property] = $value;
	}
	
	/**
	 * @param string $property
	 * @return void
	 */
	public function __unset($property) {
		unset($this->config[$property]);
	}
	
	/**
	 * @return string 'HTTP' or 'CLI'
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * @return string
	 */
	public function getSAPIName() {
		return $this->sapiName;
	}
	
	/**
	 * @return Corp_Request_QoP
	 */
	public function getQoP() {
		return $this->qop;
	}
	
	/**
	 * @return Corp_Agent
	 */
	public function getAgent() {
		return $this->agent;
	}
	
	/**
	 * Factory.
	 * 
	 * @param string $cacheDir
	 * @param string $sapiName
	 * @return Corp_Service_Request
	 * @throws Corp_Exception_Security_Forbidden
	 * @event beforeAgentCreated
	 * @event afterAgentCreated
	 */
	public static function create(Corp_ExecutionContext $context, $sapiName = null) {
		
		// Create request according to real SAPI name, NOT given name !
		switch (PHP_SAPI) {
			
			case 'aolserver':
			case 'apache' :
			case 'apache2filter' :
			case 'apache2handler' :
			case 'caudium' :
			case 'cgi' :
			case 'cgi-fcgi':
			case 'isapi' :
			case 'litespeed' :
			case 'nsapi' :
			case 'phttpd' :
			case 'pi3web' :
			case 'roxen' :
			case 'thttpd' :
			case 'tux' :
			case 'webjames' :
				$request = new Corp_Request_HTTP();
				$request->type = 'HTTP';
				break;
				
			case 'cli' :
			case 'embed' :
				$request = new Corp_Request_CLI();
				$request->type = 'CLI';
				break;
				
			default :
				throw new Corp_Exception_Security_Forbidden("Unsupported PHP SAPI: " . PHP_SAPI);
				break;

		}
		
		// Set SAPI name
		$request->sapiName = is_string($sapiName) ? $sapiName : PHP_SAPI;
		
		// Ask the request to huess his configuration
		$request->guessConfiguration($context);
		
		// Event before
		if ($context->getService()->broadcastEvent('beforeAgentCreated', array($context, $request))) {
		
			// Create user agent
			$request->agent = Corp_Agent::create($request);
			
			// Erreur
			if ($request->agent == null) {
				throw new Corp_Exception_UnsupportedOperation($context, 'Unsupported request: ' . get_class($request));
			}
			
			// Event after
			$context->getService()->broadcastEvent('afterAgentCreated', array($request->agent, $context, $request));
			
		}
		
		// Return request
		return $request;
		
	}
	
	/**
	 * @return string
	 */
	public function __toString() {
		return get_class($this) . "[TYPE={$this->type}; URI={$this->REQUEST_URI}; QOP={$this->qop}]";
	}
	
}

?>