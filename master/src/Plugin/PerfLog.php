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
class Corp_Plugin_PerfLog implements Corp_ServicePlugin {
	
	/**
	 * @var string
	 */
	protected $logDir;
	
	/**
	 * @var Corp_Service $service
	 */
	protected $service = null;
	
	/**
	 * @var float[]
	 */
	protected $ticks = array();
	
	/**
	 * @var string[]
	 */
	public $glob_include = array('${basedir}/*.php');
	
	/**
	 * @var string[]
	 */
	public $glob_exclude = array();
	
	/**
	 * @var string
	 */
	public $version_name = 'untitled';
	
	/**
	 * Constructor
	 * @param string $logDir
	 */
	public function __construct($logDir = '${basedir}') {
		$this->logDir = str_replace('${basedir}', dirname(__FILE__), $logDir);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
		
		$this->service = $service;
		
		$service->subscribeEvent('beforeServiceExecuted', array($this, 'beforeServiceExecuted'));
		
		$service->subscribeEvent('beforeContextCreated', array($this, 'beforeContextCreated'));
		
		$service->subscribeEvent('beforePersistenceManagerInitialized', array($this, 'beforePersistenceManagerInitialized'));
		
		$service->subscribeEvent('beforeSessionNameChanged', array($this, 'beforeSessionNameChanged'));
		
		$service->subscribeEvent('beforePHPSessionConfigured', array($this, 'beforePHPSessionConfigured'));
		
		$service->subscribeEvent('beforePHPSessionStarted', array($this, 'beforePHPSessionStarted'));
		
		$service->subscribeEvent('beforePersistenceManagerGcStarted', array($this, 'beforePersistenceManagerGcStarted'));
		
		$service->subscribeEvent('beforeCurrentSessionCreated', array($this, 'beforeCurrentSessionCreated'));
		
		$service->subscribeEvent('beforeMethod', array($this, 'beforeMethod'));
		
		$service->subscribeEvent('afterServiceExecuted', array($this, 'afterServiceExecuted'));
		
		$service->subscribeEvent('beforeShutdown', array($this, 'beforeShutdown'));
		
		$service->subscribeEvent('afterShutdown', array($this, 'afterShutdown'));
		
	}
	
	/**
	 * @return void
	 */
	public function start() {
		$this->tick('start');
	}
	
	/**
	 * @return void
	 */
	public function stop() {
		$this->tick('stop');
	}
	
	/**
	 * @param string $name
	 * @return void
	 */
	public function tick($name) {
		$this->ticks[$name] = microtime(true);
	}
	
	/**
	 * @return float[]
	 */
	public function getTicks() {
		return $this->ticks;
	}
	
	/**
	 * @return boolean
	 */
	public function isStarted() {
		return array_key_exists('start', $this->ticks);
	}
	
	/**
	 * @return boolean
	 */
	public function isStopped() {
		return array_key_exists('stop', $this->ticks);
	}
	
	/**
	 * @return void
	 */
	public function beforeServiceExecuted() {
		if ($this->isStarted()) {
			$this->tick('execute');	
		}
		else {
			$this->start();
		}
	}
	
	/**
	 * @return void
	 */
	public function beforeContextCreated() {
		if ($this->isStarted()) {
			$this->tick('context.create');			
		}
		else {
			$this->start();
		}
	}
	
	/**
	 * @return void
	 */
	public function beforePersistenceManagerInitialized() {
		$this->tick('persistence.init');
	}
	
	/**
	 * @return void
	 */
	public function beforeSessionNameChanged() {
		$this->tick('persistence.name');
	}
	
	/**
	 * @return void
	 */
	public function beforePHPSessionConfigured() {
		$this->tick('persistence.config');
	}
	
	/**
	 * @return void
	 */
	public function beforePHPSessionStarted() {
		$this->tick('persistence.start');
	}
	
	/**
	 * @return void
	 */
	public function beforePersistenceManagerGcStarted() {
		$this->tick('persistence.gc');
	}
	
	/**
	 * @return void
	 */
	public function beforeCurrentSessionCreated() {
		$this->tick('persistence.createsession');
	}
	
	/**
	 * @return void
	 */
	public function beforeMethod() {
		$this->tick('method');
	}
	
	/**
	 * @return void
	 */
	public function afterServiceExecuted() {
		$this->tick('usercode');
	}
	
	/**
	 * @return void
	 */
	public function beforeShutdown() {
		$this->tick('shutdown');
	}
	
	/**
	 * @return void
	 * @event beforePerfLogWrite
	 * @event afterPerfLogWrite
	 */
	public function afterShutdown() {
		
		// Add stop tick
		$this->stop();
		
		// If the service failed, the perflog will not be saved
		if ($this->service->hasFailed()) {
			return;
		}
		
		// Get log resume
		$data = $this->getLogData();
		
		// Path to log directory
		$logfile = $this->logDir . '/' . $data['id'] . '.perflog';
		
		// Output logs
		$logs = array();
		
		// Get last logs
		if (is_file($logfile)) {
			$logs = unserialize(file_get_contents($logfile));
		}
		
		// Add the new one
		$logs[] = $data;
		
		// Write
		if ($this->service->broadcastEvent('beforePerfLogWrite', array($logs, $this, $logfile))) {
			file_put_contents(
				$logfile,
				serialize($logs)
			);
			$this->service->broadcastEvent('afterPerfLogWrite', array($logs, $this, $logfile));
		}

	}
	
	public function getLogData() {
		return array(
			'id'		=> md5($_SERVER['REQUEST_URI'] . ':' . $this->version_name . ':' . $this->service->sapiName),
			'api'		=> $this->service->sapiName,
			'uri'		=> $_SERVER['REQUEST_URI'],
			'version'	=> $this->getCurrentVersion(),
			'ticks'		=> $this->ticks
		);
	}
	
	/**
	 * @return mixed[]
	 */
	public function getCurrentVersion() {
		
		// Replacements
		$a = array('${basedir}', '${basefile}');
		$b = array(dirname(__FILE__) . '/', basename(__FILE__));
		
		// Excludes
		$exclude = array();
		foreach ($this->glob_exclude as $pattern) {
			$exclude[] = str_replace($a, $b, $pattern);
		}
		
		// Output vars
		$id = array();
		$mtime = 0;
		
		// Fetch includes pattern
		foreach ($this->glob_include as $pattern) {
			
			// Replace
			$pattern = str_replace($a, $b, $pattern);
			
			// Fetch matched files
			foreach (glob($pattern) as $file) {
				
				// Files only
				if (!is_file($file)) {
					continue;
				}
				
				// Ignore
				foreach ($exclude as $rule) {
					if (fnmatch($rule, $file)) {
						continue;
					}
				}
				
				// Add entry
				$id[] = md5_file($file);
				$mtime = max($mtime, filemtime($file));
			
			}
		}
		return array(
			'name' => $this->version_name,
			'id' => md5($mtime . ':' . implode(':', $id)),
			'mtime' => $mtime
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'perflog';
	}

}

?>