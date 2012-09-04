<?php

/**
 * A request using the PHP CLI interface
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
class Corp_Request_CLI extends Corp_Request {
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Service_Request::guessConfiguration()
	 */
	public function guessConfiguration(Corp_ExecutionContext $context) {
		
		// SERVER DATA
		$this->config['SERVER'] = $_SERVER;
		
		// METHOD
		$this->config['METHOD'] = 'GET';
		
		// REQUEST URI
		$this->config['REQUEST_URI'] = '/';
		
		// USER AGENT
		$this->config['USER_AGENT'] = 'TODO';// TODO
		
		// HOST
		$this->config['HOST'] = 'localhost';
		
		// QOP
		$this->qop->add(Corp_Request_QoP::DIRECT_ACCESS);
		$this->qop->add(Corp_Request_QoP::LOCAL_ACCESS);
		
		// DATA : GET/POST/COOKIES (gpc)
		$this->config['DATA_GET']		= $GLOBALS['argv'];
		$this->config['DATA_POST']		= array();
		$this->config['DATA_COOKIE']	= array();
		
		// DATA : FILES
		$this->config['DATA_FILES']	= array();
		
		// SHEME
		$this->config['SHEME'] = 'cli';
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Request::getBody()
	 */
	public function getBody($asString = false) {
		
		if (!defined('STDIN')) {
			define('STDIN', fopen('php://stdin', 'r'));
		}
		
		$stream = STDIN;
		
		if ($asString) {
			$body = stream_get_contents($stream);
			fclose($stream);
			return $body;
		}
		
		return $stream;

	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Request::setBody()
	 */
	public function setBody($body) {
		throw new Exception("Not implemented yet");
	}
	
	
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Request::closeConnection()
	 */
	public function closeConnection() {
		flush();
	}

}

?>