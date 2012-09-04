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
 * @license    htt4p://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
class Corp_Debugger_Sessions implements Corp_Plugin {
	
	/**
	 * @var Corp_Persistence_Manager
	 */
	protected $persistence;
	
	/**
	 * @var boolean
	 */
	public $printOnShutdown = false;
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::initialize()
	 */
	public function initialize(Corp_Service $service) {
	
		// Get persistence manager
		$this->persistence = $service->getPluginByClass('Corp_Persistence_Manager');
		
		// Print des logs à la fin de l'execution de la page
		if ($this->printOnShutdown) {
			$service->subscribeEvent('beforeShutdown', array($this, 'printSessionsList'));
		}
	
	}
	
	/**
	 * @return void
	 * @stdout text/html
	 */
	public function printSessionsList() {
		
		// No persistence manager found
		if (!$this->persistence) {
			return;
		}
		
		// Header
		echo '<table id="sessionDebugger" border="1" style="font:13px arial,sans;margin:1em" cellspacing="0" cellpadding="2"><thead>';
		echo '<tr><th colspan="6" style="background:green;color:white">Sessions</th></tr>';
		echo '<tr><th>Type</th><th>SID</th><th>Identity</th><th>QoP</th><th>Agent</th><th>LastUpdate</th></tr></thead><tbody>';
		
		// Body
		foreach ($this->persistence->getSessions() as $sess) {
			echo '<tr><td>' . $sess->getSessionType();
			echo '</td><td>' . $sess->getSID();
			echo '</td><td>' . $sess->getIdentity();
			echo '</td><td>' . $sess->getQoPLevel();
			echo '</td><td>' . $sess->getUserAgent();
			echo '</td><td>' . date('Y/m/d H:i:s', $sess->getLastRequestTime()) . ' (' . self::rdatetime_en($sess->getLastRequestTime()) . ')';
			echo '</td><tr>';			
		}

		// Footer
		echo '</tbody></table>';
	
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'sessionsdebugger';
	}
	
	/**
	 * http://blog.evolya.fr/?q=relative+time
	 * 10/03/2010
	 */
	public static function rdatetime_en($timestamp, $ref = 0) {
	
		if (!$timestamp) return 'Never';
	
		if ($ref < 1) $ref = time();
	
		$ts = $ref - $timestamp;
		$past = $ts > 0;
		$ts = abs($ts);
	
		if ($past) {
			$left = '';
			$right = ' ago';
		}
		else {
			$left = 'In ';
			$right = '';
		}
	
		if ($ts === 0) return 'Now';
	
		if ($ts === 1) return $left.'1 second'.$right;
	
		// Less than 1 minute
		if ($ts < 60) return $left.$ts.' seconds'.$right;
	
		$tm = floor($ts / 60);
		$ts = $ts - $tm * 60;
	
		// Less than 3 hours
		if ($tm < 3 && $ts > 0) {
			return $left.$tm.' minute'.($tm > 1 ? 's' : '').' and '.$ts.' second'.($ts > 1 ? 's' : '').$right;
		}
	
		// Less than 1 hour
		if ($tm < 60) {
			if ($ts > 0) {
				//$left = 'About ';
			}
			return $left.$tm.' minutes'.$right;
		}
	
		$th = floor($tm / 60);
		$tm = $tm - $th * 60;
	
		// Less than 3 hours
		if ($th < 3) {
			if ($tm > 0) {
				return $left.$th.' hour'.($th > 1 ? 's' : '').' and '.$tm.' minute'.($tm > 1 ? 's' : '').$right;
			}
			else {
				return $left.$th.' hour'.($th > 1 ? 's' : '').$right;
			}
		}
	
		$td = floor($th / 24);
		$th = $th - $td * 24;
	
		$refday = strtotime(date('Y-m-d', $ref));
		$refyday = strtotime(date('Y-m-d', $ref - 86400));
	
		// Same day, or yesterday
		if ($td <= 1 && $timestamp >= $refyday) {
			if ($timestamp < $refday) {
				$left = 'Yesterday';
				$right = '';
			}
			else {
				$left = 'Today';
				$right = '';
			}
			return $left.' at '.date('H:i a', $timestamp).($right != '' ? ' '.$right : '');
		}
	
		// Less than 3 days
		if ($td < 3) {
			$left = 'Last ';
			$right = '';
			return $left.strtolower(date('l', $timestamp)).' at '.date('H:i', $timestamp).$right;
		}
	
		// Less than 5 days
		if ($td < 5) {
			return $left.$td.' days'.$right;
		}
	
		$sameyear = date('Y', $timestamp) == date('Y', $ref);
		$refday = strtotime(date('Y-m-01', $ref));
	
		$right = '';
	
		// Same month
		if ($sameyear && $timestamp >= $refyday) {
			$left = 'The ';
			return $left.date('j M \a\t H:i', $timestamp).$right;
		}
	
		return date('F j, Y', $timestamp);
	
	}
	
}

?>