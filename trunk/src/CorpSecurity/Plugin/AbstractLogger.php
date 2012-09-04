<?php

/**
 * TODO Doc
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
abstract class Corp_Plugin_AbstractLogger implements Corp_Plugin {

	/**
	 * Write-only stream to logfile.
	 * @var resource
	 */
	protected $fp;
	
	/**
	 * Path to log file.
	 * @var string
	 */
	protected $file;
	
	/**
	 * Logger name.
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var int
	 */
	protected $level;
	
	/**
	 * @var Corp_Service
	 */
	protected $service = null;
	
	/**
	 * @var mixed[]
	 */
	protected $buffer = array();
	
	/**
	 * @var boolean
	 */
	protected $writed = false;
	
	/**
	 * Constructor.
	 *  
	 * @param string $filename
	 * @param int|null $loglevel
	 */
	public function __construct($filename, $loggerName, $loglevel = null) {
		
		// Open or create the file
		$this->fp = fopen($filename, 'a');
		
		// Check if stream is valid
		if (!is_resource($this->fp)) {
			throw new Corp_Exception_FileNotWritable(null, $filename);
		}
		
		// Check logger name
		if (!is_string($loggerName)) {
			throw new Corp_Exception_InvalidArgument('$loggerName', $loggerName, 'string');
		}
		
		// Save logger name
		$this->name = strtoupper($loggerName);
		
		// Save logfile path
		$this->file = $filename;

		// Get logger level
		$this->level = is_int($loglevel) ? $loglevel : error_reporting();
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
	
		// Save CORP service
		$this->service = $service;
		
		// Subscribe to event for writing process
		$service->subscribeEvent('onShutdown', array($this, 'writeClose'), 200);
		// Note: pas obligatoire, puisque même en cas d'event on a le shutdown
		//$service->subscribeEvent('afterExceptionHandled', array($this, 'writeClose'), 200);
		
		// Initialise super class
		$this->initializeLogger($service);
	
	}
	
	/**
	 * TODO Doc
	 * 
	 * @param Corp_Service $service
	 * @return void
	 */
	protected abstract function initializeLogger(Corp_Service $service);

	/**
	 * @return Corp_Service
	 */
	public function getService() {
		return $this->service;
	}
	
	/**
	 * @return string
	 */
	public function getLogFile() {
		return $this->logfile;
	}
	
	/**
	 * @return resource
	 */
	public function getWriteStream() {
		return $this->fp;
	}
	
	/**
	 * @return resource|false
	 */
	public function getReadStream() {
		return fopen($this->logfile, 'r');
	}
	
	/**
	 * @return int
	 */
	public function getLoggerLevel() {
		return $this->level;
	}
	
	/**
	 * @return string
	 */
	public function getLoggerName() {
		return $this->name;
	}
	
	/**
	 * @param int $level
	 * @throws Corp_ExceptionInvalidArgument
	 */
	public function setLoggerLevel($level) {
		if (!is_int($level)) {
			throw new Corp_Exception_InvalidArgument('$level', $level, 'integer');
		}
		$this->level = $level;
	}
	
	/**
	 * @return void
	 */
	public function close() {
		if (gettype($this->fp) == 'resource') {
			fclose($this->fp);
		}
	}
	
	/**
	 * @throws Corp_Exception_FileNotWritable
	 * @return int|false This method returns the number of butes that where written to the file, or false on failure.
	 */
	public function write() {
		if (gettype($this->fp) != 'resource') {
			throw new Corp_Exception_FileNotWritable(
				$this->service->getCurrentContext(),
				$this->file,
				'stream closed'
			);
		}
		if (empty($this->buffer)) {
			return 0;
		}
		return fwrite(
			$this->fp,
			implode("\n", $this->buffer) . "\n"
		);
	}
	
	/**
	 * @return int
	 */
	public function writeClose() {
		$this->write();
		$this->close();
	}

}

?>