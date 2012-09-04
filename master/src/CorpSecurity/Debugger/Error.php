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
 * @package    evolya.corpsecurity.config
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Debugger_Error implements Corp_Plugin {

	/**
	 * @var Corp_Service $service
	 */
	protected $service;

	/**
	 * @var boolean
	 */
	public $printOnShutdown = false;
	
	/**
	 * @var mixed[]
	 */
	protected $list = array();
	
	/**
	 * @var int
	 */
	public $level;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->level = E_ALL | E_STRICT;
	}

	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {

		// Save service instance
		$this->service = $service;
		
		// Set error handler
		set_error_handler(array($this, 'handleError'), $this->level);
		
		// Catch exceptions
		$service->subscribeEvent('beforeExceptionHandled', array($this, 'beforeExceptionHandled'), 1);

		// Print des logs à la fin de l'execution de la page
		if ($this->printOnShutdown) {
			$service->subscribeEvent('afterShutdown', array($this, 'printErrorList'));
		}

	}

	/**
	 * 
	 */
	public function printErrorList() {
		
		// Header
		echo '<table id="errorDebugger" border="1" style="font:13px arial,sans;margin:1em" cellspacing="0" cellpadding="2"><thead>';
		echo '<tr><th colspan="6" style="background:red;color:white">Errors</th></tr>';
		echo '<tr><th>Level</th><th>Type</th><th>Message</th><th>Code</th><th>Traces</th><tbody>';
		
		// Body
		foreach ($this->list as $item) {
				
			// Color
			$color = '#d8f0fd';
			if ($item['level'] == E_USER_ERROR || $item['level'] == E_ERROR) {
				$color = '#f3b4b4';
			}
			else if ($item['level'] == E_USER_WARNING || $item['level'] == E_WARNING) {
				$color = '#f3e5c0';
			}
			
			// Row
			echo '<tr style="background:'.$color.'"><td>' . $item['level'];
			echo '</td><td><b>' . htmlspecialchars($item['type']).'</b>';
			echo '</td><td>' . htmlspecialchars($item['message']);
			echo '</td><td>' . htmlspecialchars($item['code']);
			echo '</td><td><pre>' . htmlspecialchars($item['traces']).'</pre>';
	
			echo '</td><tr>';
				
		}
		
		// Footer
		echo '</tbody></table>';
	}
	
	/**
	 * @param Corp_ExecutionContext $context
	 * @param Exception $ex
	 */
	public function beforeExceptionHandled(Corp_ExecutionContext $context, Exception $ex) {
		
		// Get debug traces
		$traces = $ex->getTraceAsString();
		
		// Fetch previous exceptions 
		while ($previous = $ex->getPrevious()) {
			echo get_class($previous);
		}
		
		// Record an error
		$this->list[] = array(
			'level'		=> E_USER_ERROR,
			'type'		=> get_class($ex),
			'message'	=> $ex->getMessage(),
			'code'		=> $ex->getCode(),
			'traces'	=> $traces
		);

	}
	
	/**
	 * 
	 * @param int $errno
	 * @param stirng $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @param mixed[] $errcontext
	 */
	public function handleError($errno, $errstr, $errfile, $errline, array $errcontext) {
		
		// Get debug traces
		$traces = debug_backtrace();
		
		// Remove trace for this method
		array_shift($traces);
		
		// Traces refactory
		foreach ($traces as $k => &$v) {
			if (!isset($v['file'])) {
				$v['file'] = '?';
				$v['line'] = '0';
			}
			$v = str_replace(')(', '',
				"#$k {$v['file']}({$v['line']}): "
				. Corp_Debugger_Events::debugtrace2string($v)
				. '('
				. implode(', ', Corp_Debugger_Events::array2string($v['args'], true, false))
				. ')'
			);
		}

		// Record an error
		$this->list[] = array(
			'level'		=> $errno,
			'type'		=> 'Error',
			'message'	=> $errstr . ", in $errfile($errline)",
			'code'		=> $errno,
			'traces'	=> implode("\n", $traces)
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'errordebugger';
	}

}

?>