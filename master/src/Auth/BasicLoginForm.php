<?php

/**
 * Basic login form, with salt.
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
class Corp_Auth_BasicLoginForm extends Corp_Auth_AbstractLoginForm {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->setProperties(array(
			'formID'					=> 'CorpLoginForm',
			'formAction'				=> '',
			'realmName'					=> 'Restricted Area',
			'allowLoginAutocomplete'	=> true,
			'fieldNameLogin'			=> 'CorpLogin',
			'fieldNamePassword'			=> 'CorpPassword',
			'fieldNameSubmit'			=> 'CorpSubmit',
			'fieldPlaceholderLogin'		=> 'Login',
			'fieldPlaceholderPassword'	=> 'Password',
			'fieldLabelLogin'			=> 'Login:',
			'fieldLabelPassword'		=> 'Password:',
			'logoutVarName'				=> 'logout',
			'logoutVarValue'			=> '1',
			'enableVirtualKeyboard'		=> true,
			'randomVirtualKeyboard'		=> true
		));
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_LoginForm::isFormSubmitted()
	 */
	public function isFormSubmitted(Corp_Request $req) {
		if ($req->getType() !== 'HTTP') {
			return false;
		}
		if ($req->METHOD !== 'POST') {
			return false;
		}
		if (!array_key_exists($this['fieldNameSubmit'], $req->DATA_POST)) {
			return false;
		}
		if (!array_key_exists($this['fieldNameLogin'], $req->DATA_POST)) {
			return false;
		}
		if (!array_key_exists($this['fieldNamePassword'], $req->DATA_POST)) {
			return false;
		}
		return true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_LoginForm::isLogoutSubmitted()
	 */
	public function isLogoutSubmitted(Corp_Request $req) {
		if ($req->METHOD !== 'GET') {
			return false;
		}
		if (!array_key_exists($this['logoutVarName'], $req->DATA_GET)) {
			return false;
		}
		return $req->DATA_GET[$this['logoutVarName']] === $this['logoutVarValue'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Corp_Auth_LoginForm::getPostedValues()
	 */
	public function getPostedValues(Corp_Request $req) {
		$r = array();
		if (isset($req->DATA_POST[$this['fieldNameLogin']])) {
			$r['login'] = $req->DATA_POST[$this['fieldNameLogin']];
		}
		if (isset($req->DATA_POST[$this['fieldNamePassword']])) {
			$r['password'] = $req->DATA_POST[$this['fieldNamePassword']];
		}
		return $r;
	}
	
	/**
	 * DOCTODO
	 * 
	 * form.corp-form[id][salt]
	 *   div.corp-container
	 *   	div.corp-realm
	 *   	div.corp-error
	 *   	div.corp-login label + input[type="password"][id][name]
	 *   	div.corp-pwd label + input[type="password"][id][name]
	 *   		div.corp-vkb
	 *   	div.corp-opt input[type="checkbox"] input + label
	 *   	a.corp-btcmd
	 *   	input.corp-submit[type="submit"]
	 * 
	 * @see Corp_Auth_LoginForm::getHTML()
	 */
	public function getHTML(Corp_ExecutionContext $context) {
		
		$html = array();
		
		// Form (begin)
		$html[] = '<form class="corp-form" id="';
		$html[] = htmlspecialchars($this['formID']);
		$html[] = '" action="';
		$html[] = htmlspecialchars($this['formAction']);
		$html[] = '" method="POST" salt="';
		$html[] = htmlspecialchars($context->getSession()->getSalt());
		$html[] = '" onsubmit="return Corp.LoginForm.handleSubmit(this);" expires="';
		$html[] = round($context->getSession()->getExpirationTime(true));
		$html[] = '">';
		
		// Container (begin)
		$html[] = '<div class="corp-container">';
		
		// Realm
		$html[] = '<div class="corp-realm">';
		$html[] = htmlspecialchars($this['realmName']);
		$html[] = '</div>';
		
		// Error
		if ($this->hasError()) {
			$html[] = '<div class="corp-error">';
			$html[] = htmlspecialchars($this->getErrorMessage());
			$html[] = '</div>';
		}
		
		// Login input
		$html[] = '<div class="corp-login">';
		$html[] = '<label for="';
		$html[] = htmlspecialchars($this['fieldNameLogin']);
		$html[] = '">';
		$html[] = htmlspecialchars($this['fieldLabelLogin']);
		$html[] = '</label> ';
		$html[] = '<input id="';
		$html[] = htmlspecialchars($this['fieldNameLogin']);
		$html[] = '" type="password" name="';
		$html[] = htmlspecialchars($this['fieldNameLogin']);
		$html[] = '" autocomplete="';
		$html[] = ($this['allowLoginAutocomplete'] ? 'on' : 'off');
		$html[] = '" placeholder="';
		$html[] = htmlspecialchars($this['fieldPlaceholderLogin']);
		$html[] = '"';
		if (!$this['allowLoginAutocomplete']) {
			$html[] = ' value=""';
		}
		$html[] = ' autofocus="autofocus" /></div>';
			
		// Password input
		$html[] = '<div class="corp-pwd">';
		$html[] = '<label for="';
		$html[] = htmlspecialchars($this['fieldNamePassword']);
		$html[] = '">';
		$html[] = htmlspecialchars($this['fieldLabelPassword']);
		$html[] = '</label> ';
		$html[] = '<input id="';
		$html[] = htmlspecialchars($this['fieldNamePassword']);
		$html[] = '" type="password" name="';
		$html[] = htmlspecialchars($this['fieldNamePassword']);
		$html[] = '" autocomplete="off" placeholder="';
		$html[] = htmlspecialchars($this['fieldPlaceholderPassword']);
		$html[] = '" value="" />';
		
		// Virtual keyboard
		if ($this['enableVirtualKeyboard']) {
			$html[] = $this->getVirtualKeyboardHTML();
		}
		$html[] = '</div>';
		// Noscript
		$html[] = '<noscript>This form needs JavaScripts</noscript>';
		
		// Container (end)
		$html[] = '</div>';
		
		// Bottom action
		//$html[] = '<a class="corp-btcmd">test</a>';
		
		// Form end
		$html[] = '</form>';
		
		return implode('', $html);
		
	}
	
	/**
	 * Renvoi le code CSS du formulaire.
	 * @param Corp_ExecutionContext $context
	 * @return string
	 */
	public function getStyles(Corp_ExecutionContext $context) {
		$css = array();

		$css[] = <<<_CSS
/* Corp - Form */
form.corp-form {
	font: 12px Verdana, Arial, sans;
	width: 200px;
	border: 1px solid #ccc;
	cursor: default;
	padding: 5px 0 10px 0;
	-webkit-border-radius: 2px;
	-moz-border-radius: 2px;
	border-radius: 2px;
	box-shadow: 0 2px 10px 5px rgba(0,0,0,0.1);	
	background: -webkit-linear-gradient(bottom, #eee, #E0E0E0);
	background:    -moz-linear-gradient(left, #eee, #E0E0E0);
	background:     -ms-linear-gradient(left, #eee, #E0E0E0);
	background:      -o-linear-gradient(left, #eee, #E0E0E0);
	-webkit-touch-callout: none;
	-webkit-user-select: none;
	-khtml-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}
/* Realm */
form.corp-form div.corp-realm {
	margin: 0;
	font-weight: bolder;
	text-align: center;
	font-size: 16px;
	text-shadow: 0 1px 0 #ccc;
	text-align: center;
	padding: 12px 0;
	border-bottom: 2px solid #eee;
	position: relative;
	color: #555;
}
form.corp-form div.corp-realm:after {
	content: "";
	display: block;
	position: absolute;
	background: #ccc;
	width: 100%;
	height: 1px;
	bottom: -2px;
}
/* Login & Password fields */
form.corp-form div.corp-login label,
form.corp-form div.corp-pwd label {
	display: none;
}
form.corp-form div.corp-login > input,
form.corp-form div.corp-pwd > input {
	display: block;
	font-size: 13px;
	padding: 4px 7px;
	cursor: text;
	border: 1px solid #ccc;
	background: #fff;
	margin: 15px;
	width: 170px;
}
/* Options */
form.corp-form .corp-opt {
	margin: 0 15px 10px 15px;
}
form.corp-form .corp-opt input {
	position: absolute;
	top: -9999px;
	left: -9999px;
}
form.corp-form .corp-opt input + label {
	display: inline-block;
	cursor: pointer;
}
form.corp-form .corp-opt input + label:before {
	display: inline-block;
	position: relative;
	width: 28px;
	height: 13px;
	border: 1px solid #ababab;
	border-radius: 6px;
	background: red;
	margin-right: 5px;
	content: "OFF";
	font-size: .8em;
	font-weight: bolder;
	text-align: center;
	color: #fff;
}
form.corp-form .corp-opt input:checked + label:before {
	background: green;
	content: "ON";
}
/* Submit button */
/* TODO Faire une classe générique pour les boutons */
form.corp-form div.corp-submit {
	clear: both;
	height: 28px;
}
form.corp-form div.corp-submit > input {
	float: right;
	display: inline-block;
	margin-right: 15px;
}
form.corp-form input.corp-submit:hover {
	color: #333 !important;
}
/* Error message */
form.corp-form .corp-error {
	text-align: center;
	padding: 10px 10px 0 10px;
	font-size: 0.9em;
	color: red;
	margin: 0;
}
/* Small command */
form.corp-form a.corp-btcmd {
	display: inline-block;
	float: left;
	margin: 5px 0 -18px 15px;
	font-size: 0.9em;
	text-decoration: underline;
	color: #888;
}
form.corp-form a.corp-btcmd:hover {
	text-decoration: none;
}
_CSS;
		
		if ($this['enableVirtualKeyboard']) {
			$css[] = <<<_CSS
/* Virtual Keyboard */
form.corp-form div.corp-pwd {
	position: relative;
}
form.corp-form div.corp-vkb {
	position: absolute;
	top: 42px;
	left: 50%;
	margin-left: -280px;
	display: none;
	border: 1px solid #ccc;
	width: 520px;
	padding: 10px;
	-webkit-border-radius: 10px;
	-moz-border-radius: 10px;
	border-radius: 10px;
	line-height: 25px;
	z-index: 51;
	background: #E0E0E0;
	background: -webkit-linear-gradient(bottom, #eee, #E0E0E0);
	background:    -moz-linear-gradient(left, #eee, #E0E0E0);
	background:     -ms-linear-gradient(left, #eee, #E0E0E0);
	background:      -o-linear-gradient(left, #eee, #E0E0E0);
}
form.corp-form div.corp-vkb:before {
	content: " ";
	z-index: 49;
	display: block;
	position: absolute;
	width: 16px;
	height: 16px;
	top: -8px;
	left: 260px;
	background: #E0E0E0;
	border: 1px solid #ccc;
	-moz-transform:rotate(45deg);
	-webkit-transform:rotate(45deg);
	-o-transform:rotate(45deg);
	-ms-transform:rotate(45deg);
}
form.corp-form div.corp-vkb:after {
	content: " ";
	z-index: 50;
	display: block;
	position: absolute;
	width: 36px;
	height: 14px;
	top: 0px;
	left: 250px;
	background: #E0E0E0;
}
form.corp-form div.corp-vkb > div {
	margin: 0;
	padding: 0;
	clear: both;
	text-align: center;
}
form.corp-form div.corp-vkb input {
	display: inline-block;
	color: #888;
	font: bold 9pt arial;
	text-decoration: none;
	text-align: center;
	width: 28px;
	height: 22px;
	margin: 5px 0;
	background: #eff0f2;
	-moz-border-radius: 4px;
	border-radius: 4px;
	border: 1px solid #ccc;
	border-top: 1px solid #f5f5f5;
	cursor: pointer;
	-webkit-box-shadow: 
		inset 0 0 25px #e8e8e8,
		0 1px 0 #c3c3c3,
		0 2px 0 #c9c9c9,
		0 2px 3px #333;
	-moz-box-shadow: 
		inset 0 0 25px #e8e8e8,
		0 1px 0 #c3c3c3,
		0 2px 0 #c9c9c9,
		0 2px 3px #333;
	box-shadow: 
		inset 0 0 25px #e8e8e8,
		0 1px 0 #c3c3c3,
		0 2px 0 #c9c9c9,
		0 2px 3px #333;
	text-shadow: 0px 1px 0px #f5f5f5;
}
form.corp-form div.corp-vkb input.active,
form.corp-form div.corp-vkb input:active {
	color: #888;
	background: #ebeced;
	-webkit-box-shadow: inset 0 0 25px #ddd, 0 0 3px #333;
	-moz-box-shadow: inset 0 0 25px #ddd, 0 0 3px #333; box-shadow: inset 0 0 25px #ddd, 0 0 3px #333;
	border-top: 1px solid #eee;
}
form.corp-form div.corp-vkb input + input { margin-left: 5px; }
form.corp-form div.corp-vkb > div.shift { display: none; }
form.corp-form div.corp-vkb input[name="backspace"] { width: 64px; }
form.corp-form div.corp-vkb input[name="shift"] { width: 64px; text-align: left; }
form.corp-form div.corp-vkb > div.shift input[name="shift"] { background: #ccc; }
form.corp-form div.corp-vkb input[name="spacebar"] { width: 300px; }
_CSS;
		}
		
		return str_replace(array("\n", "\t", ': ', ' {', '{ ', ', ', ' }'), array('', '', ':', '{', '{', ',', '}'), implode('', $css));
		
	}
	
	/**
	 * Renvoi le code Javascript du formulaire.
	 * @param Corp_ExecutionContext $context
	 * @return string
	 * @event onLoginFormJsGeneration
	 */
	public function getJavascripts(Corp_ExecutionContext $context) {

		$js = array();
		
		$js[] = <<<_JS
function sha1 (str) {

	var rotate_left = function (n, s) {
		var t4 = (n << s) | (n >>> (32 - s));
		return t4;
	};

	var cvt_hex = function (val) {
		var str = "";
		var i;
		var v;
		for (i = 7; i >= 0; i--) {
			v = (val >>> (i * 4)) & 0x0f;
			str += v.toString(16);
		}
		return str;
	};

	var blockstart;
	var i, j;
	var W = new Array(80);
	var H0 = 0x67452301;
	var H1 = 0xEFCDAB89;
	var H2 = 0x98BADCFE;
	var H3 = 0x10325476;
	var H4 = 0xC3D2E1F0;
	var A, B, C, D, E;
	var temp;

	/*str = this.utf8_encode(str);*/
	var str_len = str.length;

	var word_array = [];
	for (i = 0; i < str_len - 3; i += 4) {
		j = str.charCodeAt(i) << 24 | str.charCodeAt(i + 1) << 16 | str.charCodeAt(i + 2) << 8 | str.charCodeAt(i + 3);
		word_array.push(j);
	}

	switch (str_len % 4) {
		case 0:
			i = 0x080000000;
			break;
		case 1:
			i = str.charCodeAt(str_len - 1) << 24 | 0x0800000;
			break;
		case 2:
			i = str.charCodeAt(str_len - 2) << 24 | str.charCodeAt(str_len - 1) << 16 | 0x08000;
			break;
		case 3:
			i = str.charCodeAt(str_len - 3) << 24 | str.charCodeAt(str_len - 2) << 16 | str.charCodeAt(str_len - 1) << 8 | 0x80;
			break;
	}

	word_array.push(i);

	while ((word_array.length % 16) != 14) {
		word_array.push(0);
	}

	word_array.push(str_len >>> 29);
	word_array.push((str_len << 3) & 0x0ffffffff);

	for (blockstart = 0; blockstart < word_array.length; blockstart += 16) {
	
		for (i = 0; i < 16; i++) {
			W[i] = word_array[blockstart + i];
		}
		for (i = 16; i <= 79; i++) {
			W[i] = rotate_left(W[i - 3] ^ W[i - 8] ^ W[i - 14] ^ W[i - 16], 1);
		}


		A = H0;
		B = H1;
		C = H2;
		D = H3;
		E = H4;
		
		for (i = 0; i <= 19; i++) {
			temp = (rotate_left(A, 5) + ((B & C) | (~B & D)) + E + W[i] + 0x5A827999) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B, 30);
			B = A;
			A = temp;
		}

		for (i = 20; i <= 39; i++) {
			temp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0x6ED9EBA1) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B, 30);
			B = A;
			A = temp;
		}
		
		for (i = 40; i <= 59; i++) {
			temp = (rotate_left(A, 5) + ((B & C) | (B & D) | (C & D)) + E + W[i] + 0x8F1BBCDC) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B, 30);
			B = A;
			A = temp;
		}
	
		for (i = 60; i <= 79; i++) {
			temp = (rotate_left(A, 5) + (B ^ C ^ D) + E + W[i] + 0xCA62C1D6) & 0x0ffffffff;
			E = D;
			D = C;
			C = rotate_left(B, 30);
			B = A;
			A = temp;
		}

		H0 = (H0 + A) & 0x0ffffffff;
		H1 = (H1 + B) & 0x0ffffffff;
		H2 = (H2 + C) & 0x0ffffffff;
		H3 = (H3 + D) & 0x0ffffffff;
		H4 = (H4 + E) & 0x0ffffffff;
	}
	
	temp = cvt_hex(H0) + cvt_hex(H1) + cvt_hex(H2) + cvt_hex(H3) + cvt_hex(H4);
	return temp.toLowerCase();
};
_JS;
		
		// Global statement
		$js[] = "window['Corp'] = window['Corp'] || {};";

		// Begin
		$js[] = <<<_JS
Corp.LoginForm = {

	/* Events */
	beforeInit: [],
	afterInit: [],
	beforeSubmit: [],
	afterSubmit: [],
	
	/* UI */
	ui: {},
	
	addOption: function (name, text, title) {
		var div = document.createElement('div');
		div.className = 'corp-opt corp-opt-' + name;
		var input = document.createElement('input');
		input.type = 'checkbox';
		input.value = 'on';
		input.name = 'corp-' + name;
		input.id = input.name;
		var label = document.createElement('label');
		label.setAttribute('for', input.name);
		label.title = title;
		label.innerHTML = text;
		div.appendChild(input);
		div.appendChild(label);
		this.ui.form.appendChild(div);
		return {
			"container": div,
			"checkbox": input,
			"label": label
		};
	},

	init: function (doc) {
	
		this.ui.form = doc.getElementById('{$this['formID']}');
		this.ui.fieldLogin  = doc.getElementById('{$this['fieldNameLogin']}');
		this.ui.fieldPwd = doc.getElementById('{$this['fieldNamePassword']}');
		this.ui.fieldSubmit = null;
		
		if (!this.broadcastEvent('beforeInit', this)) {
			return;
		}

		var div = doc.createElement('div');
		div.className = 'corp-submit';
		this.ui.fieldSubmit = doc.createElement('input');
		this.ui.fieldSubmit.type = 'submit';
		this.ui.fieldSubmit.id   = '{$this['fieldNameSubmit']}';
		this.ui.fieldSubmit.name = '{$this['fieldNameSubmit']}';	
		div.appendChild(this.ui.fieldSubmit);
		this.ui.form.appendChild(div);
		
		this.broadcastEvent('afterInit', this);
	
	},

	handleSubmit: function (form) {
	
		if (!this.broadcastEvent('beforeSubmit', this)) {
			return true;
		}
	
		/* Expired */
		var expires = parseInt(form.getAttribute('expires')) * 1000;
		if (expires <= new Date().getTime()) {
			alert("TODO");
			return false;
		}
		
		if (this.ui.fieldLogin.value.length == 0) {
			this.ui.fieldLogin.focus();
			return false;
		}
		if (this.ui.fieldPwd.value.length == 0) {
			this.ui.fieldPwd.focus();
			return false;
		}
		
		this.ui.fieldSubmit.focus();
		
		/*this.ui.fieldLogin.disabled = true;
		this.ui.fieldPwd.disabled = true;
		this.ui.fieldSubmit.disabled = true;*/
		
		var salt = this.ui.form.getAttribute('salt'),
			password = sha1(this.ui.fieldLogin.value + ':' + this.ui.fieldPwd.value);
		
		/*console.log("LOGIN   = " + this.ui.fieldLogin.value);
		console.log("PASSW 1 = " + this.ui.fieldPwd.value);
		console.log("PASSW 2 = " + password);*/
			
		if (this.ui.form.hasAttribute('apikey')) {
			password = 's+k:' + sha1(salt + ':' + password + ':' + this.ui.form.getAttribute('apikey'));
			console.log("API KEY = " + this.ui.form.getAttribute('apikey'));
		}
		else {
			password = 's:' + sha1(salt + ':' + password);
		}

		/*console.log("SALT    = " + salt);
		console.log("PASSW 3 = " + password);*/
		
		this.ui.fieldPwd.value = password;
		
		this.broadcastEvent('afterSubmit', this);

		return true;
		
	},
	
	broadcastEvent: function (event, data) {
		if (event in this) {
			for (i in this[event]) {
				if (this[event][i](data) === false) {
					return false;
				}
			}
		}
		return true;
	}

};
_JS;

		// Virtual keyboard
		if ($this['enableVirtualKeyboard']) {
			$js[] = <<<_JS
Corp.LoginForm.afterInit.push(function (form) {

	form.vkb = {
		key: null,
		timer: null,
		shifted: false,
		shift: function () {
			for (var child = form.ui.vkb.childNodes, i = 0, l = child.length; i < l; i++) {
				if (!(child[i] instanceof HTMLDivElement)) continue;
				if (child[i].className == 'unshift') continue;
				child[i].style.display = (child[i].className == 'shift') ? (this.shifted ? 'none' : 'block') : (this.shifted ? 'block' : 'none');
			}
			this.shifted = !this.shifted;
		}
	};

	form.ui.vkb = document.getElementById('vkb');
	
	form.ui.fieldPwd.onfocus = function () {
		form.ui.vkb.style.display = 'block';
	};
	
	form.ui.fieldPwd.onblur = function () {
		form.ui.vkb.style.display = 'none';
	};

	var onMouseOver = function () {
		
		var input = this;
	
		form.vkb.key = this.getAttribute('name');
		
		form.vkb.value = this.getAttribute('value');
		
		form.vkb.timer = setTimeout(function () {
		
			input.className = 'active';
			
			switch (form.vkb.key) {
			
				case 'backspace' :
					if (form.ui.fieldPwd.value.length > 0) {
						form.ui.fieldPwd.value = form.ui.fieldPwd.value.substr(0, form.ui.fieldPwd.value.length - 1);
					}
					break;
					
				case 'shift' :
					form.vkb.shift();
					break;
					
				case '&lt;' :
					form.ui.fieldPwd.value += '<';
					break;
					
				case '&gt;' :
					form.ui.fieldPwd.value += '>';
					break;
					
				default :
					form.ui.fieldPwd.value += form.vkb.value;
					break;
					
			}
			
			form.vkb.timer = form.vkb.key = null;
			
			setTimeout(function () {
				input.className = '';
			}, 200);
			
		}, 600);
		
	};
	
	var onMouseOut = function () {
		clearTimeout(form.vkb.timer);
	};
	
	for (var keys = form.ui.vkb.getElementsByTagName('input'), i = 0, j = keys.length; i < j; i++) {
		keys[i].onmouseover = onMouseOver;
		keys[i].onmouseout = onMouseOut;
	}

});
_JS;
		}
		
		// Event before
		$context->getService()->broadcastEvent('onLoginFormJsGeneration', array(&$js, $this));
	
		// Init statement
		$js[] = 'Corp.LoginForm.init(document);';
		
		// Return
		return str_replace(array("\n", "\t", ' = ', ' += ', ', ', ': ', ' + ', ' {', 'if '), array('', '', '=', '+=', ',', ':', '+', '{', 'if'), implode('', $js));

	}
	
	/**
	 * @return string
	 */
	public function getVirtualKeyboardHTML() {
		
		// Keyboard structure
		$struct = array(
			array(
				'accent' => '`',
				array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
					'7' => '7',
					'8' => '8',
					'9' => '9',
					'0' => '0',
				),
				'backspace' => '&larr;',
			),
			array(
				array(
					'tilde' => '~',
					'exc' => '!',
					'at' => '@',
					'hash' => '#',
					'dollar' => '$',
					'percent' => '%',
					'caret' => '^',
					'ampersand' => '&amp;',
					'asterik' => '*',
					'openbracket' => '(',
					'closebracket' => ')'
				),
				'backspace' => '&larr;'
			),
			array(
				array(
					'q' => 'q',
					'w' => 'w',
					'e' => 'e',
					'r' => 'r',
					't' => 't',
					'y' => 'y',
					'u' => 'u',
					'i' => 'i',
					'o' => 'o',
					'p' => 'p'
				),
				array(
					'[' => '[',
					']' => ']',
					'\\' => '\\'
				)
			),
			array(
				array(
					'Q' => 'Q',
					'W' => 'W',
					'E' => 'E',
					'R' => 'R',
					'T' => 'T',
					'Y' => 'Y',
					'U' => 'U',
					'I' => 'I',
					'O' => 'O',
					'P' => 'P'
				),
				array(
					'{' => '{',
					'}' => '}',
					'|' => '|'
				)
			),
			array(
				array(
					'a' => 'a',
					's' => 's',
					'd' => 'd',
					'f' => 'f',
					'g' => 'g',
					'h' => 'h',
					'j' => 'j',
					'k' => 'k',
					'l' => 'l'
				),
				array(
					';' => ';',
					"'" => "'",
					'=' => '='
				)
			),
			array(
				array(
					'a' => 'A',
					's' => 'S',
					'd' => 'D',
					'f' => 'F',
					'g' => 'G',
					'h' => 'H',
					'j' => 'J',
					'k' => 'K',
					'l' => 'L'
				),
				array(
					';' => ':',
					'"' => '&quot;',
					'plus' => '+'
				)
			),
			array(
				'shift' => 'Shift',
				array(
					'z' => 'z',
					'x' => 'x',
					'c' => 'c',
					'v' => 'v',
					'b' => 'b',
					'n' => 'n',
					'm' => 'm'
				),
				array(
					',' => ',',
					'.' => '.',
					'/' => '/',
					' - ' => '-'
				)
			),
			array(
				'shift' => 'Shift',
				array(
					'Z' => 'Z',
					'X' => 'X',
					'C' => 'C',
					'V' => 'V',
					'B' => 'B',
					'N' => 'N',
					'M' => 'M'
				),
				array(
					'lt' => '&lt;',
					'gt' => '&gt;',
					'?' => '?',
					'underscore' => '_'
				)
			)
		);
		
		// Random keyboard
		if ($this['randomVirtualKeyboard']) {
			foreach ($struct as &$row) {
				foreach ($row as $key => $value) {
					if (is_array($value)) {
						$copy = $value;
						$row[$key] = array();
						$keys = array_keys($copy);
						shuffle($keys);
						foreach ($keys as $k) {
							$row[$key][$k] = $copy[$k];
						}
					}
				}
			}
		}
		
		// Render as HTML
		$html[] = '<div id="vkb" class="corp-vkb">';
		foreach ($struct as $i => $row) {
			$html[] = ($i % 2 !== 0 ? '<div class="shift">' : '<div>');
			foreach ($row as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $k => $v) {
						$html[] = '<input type="button" name="';
						$html[] = htmlspecialchars($k);
						$html[] = '" value="';
						$html[] = $v;
						$html[] = '" />';
					}
					continue;
				}
				$html[] = '<input type="button" name="';
				$html[] = htmlspecialchars($key);
				$html[] = '" value="';
				$html[] = $value;
				$html[] = '" />';
			}
			$html[] = '</div>';
		}
		$html[] = '<div class="unshift"><input type="button" name="spacebar" value=" " /></div>';
		$html[] = '</div>';
		
		// Returns a string
		return implode('', $html);
		
	}
}

?>