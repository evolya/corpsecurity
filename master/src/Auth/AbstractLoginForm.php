<?php

/**
 * Abstract class for login forms.
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
abstract class Corp_Auth_AbstractLoginForm implements Corp_Auth_LoginForm {
	
	/**
	 * @var string|null 
	 */
	protected $errorMessage = null;
	
	/**
	 * @var mixed[]
	 */
	protected $properties = array();
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_LoginForm::render()
	 */
	public function render(Corp_ExecutionContext $context, $html = true, $css = true, $js = true) {
		
		// Pour améliorer les performances, on récupère le service dans une variable
		$s = $context->getService();
		
		// On prépare une variable de sortie
		$out = '<!-- Corp Login Form (begin) -->';
		
		// Event before
		if (!$s->broadcastEvent('beforeLoginFormRendered', array(&$out, $this, $context))) {
			return $out;
		}
		
		// Append : HTML
		if ($html && $s->broadcastEvent('beforeLoginFormHTMLRendered', array(&$out, $this, $context, $html))) {
			$out .= $this->getHTML($context);
			$s->broadcastEvent('afterLoginFormHTMLRendered', array(&$out, $this, $context, $html));
		}
		
		// Append : CSS
		if ($css && $s->broadcastEvent('beforeLoginFormStylesRendered', array(&$out, $this, $context, $css))) {
			$out .= '<style type="text/css" charset="utf-8">' . $this->getStyles($context) . '</style>';
			$s->broadcastEvent('afterLoginFormStylesRendered', array(&$out, $this, $context, $css));
		}
		
		// Append : JS
		if ($js && $s->broadcastEvent('beforeLoginFormJavascriptsRendered', array(&$out, $this, $context, $js))) {
			$out .= '<script type="text/javascript" charset="utf-8">' . $this->getJavascripts($context) . '</script>';
			$s->broadcastEvent('afterLoginFormJavascriptsRendered', array(&$out, $this, $context, $js));
		}
		
		// Close
		$out .= '<!-- Corp Login Form (end) -->';
		
		// Event after
		$s->broadcastEvent('afterLoginFormRendered', array(&$out, $this, $context));
		
		// On renvoi le code généré
		return $out;
		
	}
	
	/**
	 * Renvoi le code HTML du formulaire.
	 * @param Corp_ExecutionContext $context
	 * @return string
	 */
	public abstract function getHTML(Corp_ExecutionContext $context);
	
	/**
	 * Renvoi le code CSS du formulaire.
	 * @param Corp_ExecutionContext $context
	 * @return string
	 */
	public abstract function getStyles(Corp_ExecutionContext $context);
	
	/**
	 * Renvoi le code JS du formulaire.
	 * @param Corp_ExecutionContext $context
	 * @return string
	 */
	public abstract function getJavascripts(Corp_ExecutionContext $context);
	
	/**
	 * @return string|null
	 */
	public function getErrorMessage() {
		return $this->errorMessage;
	}
	
	/**
	 * @param string|null $str
	 */
	public function setErrorMessage($str = null) {
		$this->errorMessage = $str;
	}
	
	/**
	 * @return boolean
	 */
	public function hasError() {
		return ($this->errorMessage !== null);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_LoginForm::getProperties()
	 */
	public function getProperties() {
		return $this->properties;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_LoginForm::setProperties()
	 */
	public function setProperties(array $array) {
		$this->properties = array_merge($this->properties, $array);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->properties);
	}

	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset) {
		return $this->properties[$offset];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value) {
		$this->properties[$offset] = $value;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset) {
		unset($this->properties[$offset]);
	}
	
}

?>