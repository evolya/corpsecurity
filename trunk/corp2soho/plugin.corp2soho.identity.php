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
class Corp_Auth_SohoIdentity extends Corp_Auth_Identity {
	
	/**
	 * @param Moodel<TeamMember> $model
	 */
	protected $model;
	
	/**
	 * Constructor
	 * @param Moodel<TeamMember> $model
	 */
	public function __construct($realm = null, $uid = null, $type = Corp_Auth_Identity::TYPE_ANONYMOUS, $name = 'Anonymous') {
		if ($realm instanceof Moodel || $realm === null) {
			$this->setUserModel($realm);
		}
		else {
			parent::__construct($realm, $uid, $type, $name);
		}
	}
	
	/**
	 * @return Moodel<TeamMember> $model
	 */
	public function getUserModel() {
		return $this->model;
	}
	
	/**
	 * @param Moodel<TeamMember> $model
	 * @return void
	 */
	public function setUserModel(Moodel $model = null) {
		$this->model = $model;
		if ($model != null) {
			$this->realm = 'Soho';
			$this->uid = $model->id();
			$this->type = Corp_Auth_Identity::TYPE_USER;
			$this->name = $model->get('login');
		}
		else {
			$this->realm = '';
			$this->uid = null;
			$this->type = Corp_Auth_Identity::TYPE_ANONYMOUS;
			$this->name = 'Anonymous';
		}
	}
	
	/**
	 * @return void
	 */
	public function __wakeup() {
		if ($this->uid !== null && $this->type === Corp_Auth_Identity::TYPE_USER) {
			$this->model = ModelManager::get('TeamMember')->getByID($this->uid);
			if (!$this->model) {
				throw new Corp_Persistence_Exception(null, null, 'Unable to restore TeamMember model');
			}
		}
	}

}

?>