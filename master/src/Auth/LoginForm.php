<?php

/**
 * Interface for login forms.
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
interface Corp_Auth_LoginForm extends ArrayAccess {
	
	/**
	 * @param Corp_Request $req
	 * @return boolean
	 */
	public function isFormSubmitted(Corp_Request $req);
	
	/**
	 * @param Corp_Request $req
	 * @return boolean
	 */
	public function isLogoutSubmitted(Corp_Request $req);
	
	/**
	 * @param Corp_Request $req
	 * @return string[]
	 */
	public function getPostedValues(Corp_Request $req);
	
	/**
	 * Récupére la concaténation des codes HTML, CSS et Javascripts.
	 * @param Corp_ExecutionContext $context
	 * @param boolean $html
	 * @param boolean $css
	 * @param boolean $js
	 * @return string
	 * @event beforeLoginFormRendered
	 * @event afterLoginFormRendered
	 * @event beforeLoginFormHTMLRendered
	 * @event afterLoginFormHTMLRendered
	 * @event beforeLoginFormStylesRendered
	 * @event afterLoginFormStylesRendered
	 * @event beforeLoginFormJavascriptsRendered
	 * @event afterLoginFormJavascriptsRendered
	 * @event onLoginFormJsGeneration
	 */
	public function render(Corp_ExecutionContext $context, $html = true, $css = true, $js = true);
	
	/**
	 * @return string|null
	 */
	public function getErrorMessage();
	
	/**
	 * @param string|null $str
	 */
	public function setErrorMessage($str = null);
	
	/**
	 * @return boolean
	 */
	public function hasError();
	
	/**
	 * @return mixed[]
	 */
	public function getProperties();
	
	/**
	 * @param mixed[] $array
	 * @return void
	 */
	public function setProperties(array $array);
	
}

?>