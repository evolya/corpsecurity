<?php

/**
 * IOC container to instanciate and configure Corp using XML files
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
class Corp_XmlConfig implements ArrayAccess {
	
	protected $properties = array();
	protected $beans = array();
	
	public $debugFlags = array();
	public $debugMode = false;

	public function loadFile($filename) {
		$xml = simplexml_load_file($filename);
		if (!$xml) {
			return false;
		}
		return $this->load($xml, realpath($filename));
	}
	
	public function loadString($str) {
		$xml = simplexml_load_string($str);
		if (!$xml) {
			return false;
		}
		return $this->load($xml);
	}
	
	public function load(SimpleXMLElement $node, $filename = null) {
		
		// Debug level
		if (isset($node['debug'])) {
			$this->debugFlags = explode(',', str_replace(' ', '', $node['debug']));
			$this->debugMode = in_array('xmlconfig', $this->debugFlags);
		}
		
		// Base directory
		$basedir = '.';
		if ($filename !== null) {
			$basedir = dirname($filename);
		}
		if (isset($node['basedir'])) {
			$basedir = "{$node['basedir']}";
			if ($basedir == '.') {
				$basedir = isset($filename) ? dirname($filename) : dirname($_SERVER['PHP_SELF']);
			}
			else if (substr($basedir, 0, 1) == '.') {
				$basedir = (isset($filename) ? dirname($filename) : dirname($_SERVER['PHP_SELF'])) . '/' . substr($basedir, 1);
			}
		}
		$basedir = realpath($basedir) . '/';
		
		// Fetch sub-children
		foreach ($node->children() as $item) {
			
			// Nom du tag
			$tagname = $item->getName();
			
			// Properties
			if ($tagname == 'property') {
				$this->handleProperty($node, $filename, $item, $basedir);
			}
			
			// Beans
			else if ($tagname == 'bean') {
				$this->handleBean($node, $filename, $item, $basedir);
			}
			
			// Include
			else if ($tagname == 'include-path') {
				$this->handleIncludePath($node, $filename, $item, $basedir);
			}
			
			// Include
			else if ($tagname == 'include') {
				$this->handleInclude($node, $filename, $item, $basedir);
			}
			
			// Erreur
			else {
				throw new Corp_Exception_XmlConfig($node, "Invalid tag name: $tagname", $filename, $item);
			}
			
		}
		
		return true;
		
	}
	
	/**
	 * DOCTODO
	 *
	 * @param SimpleXMLElement $node
	 * @param string $filename
	 * @param SimpleXMLElement $item
	 * @param string $basedir
	 * @return string
	 * @throws Corp_Exception_XmlConfig
	 */
	protected function handleInclude($node, $filename, $item, $basedir) {

		// Vérification qu'un chemin de fichier a été indiqué
		if (!isset($item['filename'])) {
			throw new Corp_Exception_XmlConfig($node, 'Tag "include" must have a "filename" attribute', $filename, $item);
		}
		
		// Détermination du chemin réel
		$path = $this->applyPropertiesReplace("{$item['filename']}", $basedir, $filename, $node, $item);
		
		// Debug
		if ($this->debugMode) {
			echo "[XmlConfig] include($path)" . PHP_EOL;
		}
		
		// Include
		include_once $path;
		
	}
	
	/**
	 * DOCTODO
	 *
	 * @param SimpleXMLElement $node
	 * @param string $filename
	 * @param SimpleXMLElement $item
	 * @param string $basedir
	 * @return string
	 * @throws Corp_Exception_XmlConfig
	 */
	protected function handleIncludePath($node, $filename, $item, $basedir) {
		set_include_path($this->applyPropertiesReplace("$item", $basedir, $filename, $node, $node));
	}
	
	/**
	 * DOCTODO
	 * 
	 * @param SimpleXMLElement $node
	 * @param string $filename
	 * @param SimpleXMLElement $item
	 * @param string $basedir
	 * @return string
	 * @throws Corp_Exception_XmlConfig
	 */
	protected function handleProperty($node, $filename, $item, $basedir) {
		if (!isset($item['name'])) {
			throw new Corp_Exception_XmlConfig($node, 'Tag "property" must have a "name" attribute', $filename, $item);
		}
		$value = isset($item['value']) ? $item['value'] : "$item";
		$this->properties["{$item['name']}"] = $this->applyPropertiesReplace($value, $basedir, $filename, $node, $node);
		return $value;
	}
	
	/**
	 * DOCTODO
	 * 
	 * @param SimpleXMLElement $node
	 * @param string $filename
	 * @param SimpleXMLElement $item
	 * @param string $basedir
	 * @return Object
	 * @throws Corp_Exception_XmlConfig
	 */
	protected function handleBean($node, $filename, $item, $basedir, $parent = null) {
		
		// On vérifie le nom du tag
		if ($item->getName() != 'bean') {
			throw new Corp_Exception_XmlConfig($node, 'Invalid tag to handle as a bean: ' . $item->getName(), $filename, $item);
		}
		
		// L'objet construit on rappelé
		$bean = null;
		
		// Nom du bean
		$name = isset($item['name']) ? "{$item['name']}" : null;
		
		// Le bean existe déjà : restauration
		if ($name != null && array_key_exists($name, $this->beans)) {
			$bean = $this->beans[$name];
			$class = get_class($bean);
		}
		
		// Création du bean
		else {
			
			// Vérification de la présence du nom de classe du bean
			if (!isset($item['class'])) {		
				throw new Corp_Exception_XmlConfig($node, 'Tag "bean" must have a "class" attribute' . ($name != null ? ' for bean "'.$name.'"' : ''), $filename, $item);
			}
			
			// Nom de classe du bean
			$class = "{$item['class']}";
			
			// Vérification que la classe existe
			if (!class_exists($class)) {			
				throw new Corp_Exception_XmlConfig($node, 'Class not found' . ($name != null ? ' for bean "'.$name.'" ' : '') . ": $class", $filename, $item);
			}
			
			// Debug
			if ($this->debugMode) {
				echo "[XmlConfig] new $class()".($name != null ? " as \"$name\"" : '').PHP_EOL;
			}
			
			// Arguments à passer au constructeur
			$args = array();
			
			// On parcours les enfants
			foreach ($item->children() as $prop) {

				if ($prop->getName() !== 'constructor-arg') continue;
				
				$sub = $prop->children();
				
				if (sizeof($sub) > 1) {
					throw new Corp_Exception_XmlConfig($node, 'Invalid declaration for constructor-arg'. ($name != null ? ' in bean "'.$name.'"' : '') . ' : must have exactly one children only', $filename, $prop);
				}
				else if (sizeof($sub) === 1) {
					$args[] = $this->valueOf($node, $filename, $basedir, $name, $prop, "constructor-arg");
				}
				else {
					$args[] = $this->applyPropertiesReplace("{$prop}", $basedir, $filename, $node, $prop);
				}
								
				unset($sub);
				
			}
			
			// Création du bean
			if (sizeof($args) > 0) {
				$reflexion = new ReflectionClass($class);
				$bean = $reflexion->newInstanceArgs($args);
			}
			else {
				$bean = new $class();
			}
			
			// Enregistrement du bean
			if ($name != null) {
				$this->beans[$name] = $bean;
			}
			
		}
		
		// On parcours les tags qui définissent le bean
		foreach ($item->children() as $prop) {
			
			// Nom du tag
			$tagname = $prop->getName();
			
			// Déjà traités
			if ($tagname == 'constructor-arg') {
				continue;
			}
			
			// Modification d'un attribut du bean
			if ($tagname == 'property') {

				// Vérification de la présence du nom de l'attribut
				if (!isset($prop['name'])) {		
					throw new Corp_Exception_XmlConfig($node, 'Tag "attr" must have a "name" attribute' . ($name != null ? ' in bean "'.$name.'"' : ''), $filename, $prop);
				}
				$attr = "{$prop['name']}";
				
				// Valeur
				$value = $this->valueOf($node, $filename, $basedir, $name, $prop, $attr);
				
				// Debug
				if ($this->debugMode) {
					echo "[XmlConfig] " . get_class($bean) . '::set' . ucfirst($attr) . '('.(is_object($value) ? get_class($value) : gettype($value)).')'.PHP_EOL;
				}
				
				// Première méthode : un setter existe
				if (method_exists($bean, 'set' . ucfirst($attr))) {
					call_user_func_array(array($bean, 'set' . ucfirst($attr)), array($value));
				}
				// Deuxième méthode : l'attribut est publique
				else if (property_exists($bean, $attr)) {
					$bean->$attr = $value;
				}
				// Troisième méthode : l'objet implémente l'interface ArrayAccess
				else if ($bean instanceof ArrayAccess) {
					$bean[$attr] = $value;
				}
				// Quatrième méthode : l'objet propose une méthode __set 
				else if (method_exists($bean, '__set')) {
					call_user_func_array(array($bean, '__set'), array($attr, $value));
				}
				// Erreur : impossible de modifier cette valeur
				else {
					throw new Corp_Exception_XmlConfig($node, 'Object ' . get_class($bean) . ' does not provides a method to set attribute "'.$attr.'"' . ($name != null ? ' for bean "'.$name.'"' : ''), $filename, $prop);
				}

			}
			
			// Listes
			else if ($tagname == 'list') {
				// TODO Supporter les listes
			}
			
			// Include
			else if ($tagname == 'include') {
				$this->handleInclude($node, $filename, $prop, $basedir);
			}
			
			// Include
			else if ($tagname == 'include-path') {
				$this->handleIncludePath($node, $filename, $prop, $basedir);
			}
			
			// Call
			else if ($tagname == 'call') {
				
				// Vérification de la présence du nom de la méthode
				if (!isset($prop['name'])) {
					throw new Corp_Exception_XmlConfig($node, 'Tag "attr" must have a "name" attribute' . ($name != null ? ' in bean "'.$name.'"' : ''), $filename, $prop);
				}
				$method = "{$prop['name']}";
				
				// Vérification que la méthode existe
				if (!method_exists($bean, $method)) {
					throw new Corp_Exception_XmlConfig($node, 'Method "'.$method.'" not found for "call" ' . ($name != null ? ' in bean "'.$name.'"' : ''), $filename, $prop);
				}
				
				// Arguments
				$args = array();
				
				// Traitement des arguments
				foreach ($prop->children() as $arg) {
					
					// Vérification du type de tag
					if ($arg->getName() != 'arg') {
						throw new Corp_Exception_XmlConfig($node, 'Invalid tag "'.$arg->getName().'" in "call" statement' . ($name != null ? ' in bean "'.$name.'"' : ''), $filename, $arg);
					}
					
					// On rajoute l'argument
					$args[] = $this->valueOf($node, $filename, $basedir, $name, $arg, 'arg');
					
				}
				
				// Debug
				if ($this->debugMode) {
					echo "[XmlConfig] " . get_class($bean) . "::$method(";
					$i = 0;
					foreach ($args as $name => $value) {
						echo ($i++ > 0 ? ', ' : '') . (is_object($value) ? get_class($value) : gettype($value));
					}
					echo ")" . PHP_EOL;
				}
				
				call_user_func_array(array($bean, $method), $args);
				
			}
			
			// Erreur
			else {
				throw new Corp_Exception_XmlConfig($node, 'Invalid tag name: "'.$tagname.'" in "bean" statement' . ($name != null ? ' in bean "'.$name.'"' : ''), $filename, $prop);
			}
			
		}
		
		// On renvoi le bean crééé
		return $bean;
	}
	
	protected function valueOf($node, $filename, $basedir, $name, $prop, $attr) {
		
		// Si la valeur est définie dans un attribut
		if (isset($prop['value'])) {
			$value = "{$prop['value']}";
			$value = $this->applyPropertiesReplace($value, $basedir, $filename, $node, $prop);
		}
		
		// Si la valeur est un bean
		else if ($prop->count() > 0) {
				
			// On recupère les enfants
			$child = $prop->children();
		
			// On vérifie qu'un unique attribut est spécifié
			if (sizeof($child) !== 1) {
				throw new Corp_Exception_XmlConfig($node, 'Invalid declaration for "'.$attr.'"' . ($name != null ? ' in bean "'.$name.'"' : '') . ' : too many children', $filename, $prop);
			}
				
			// On recupère l'enfant
			$child = $child[0];

			// On vérifie qu'il s'agisse bien d'un bean
			if ($child->getName() != 'bean') {
				throw new Corp_Exception_XmlConfig($node, 'Invalid declaration for "'.$attr.'"' . ($name != null ? ' in bean "'.$name.'"' : '') . ' : children must be a bean', $filename, $prop);
			}
				
			// Soit une classe est spécifiée
			if (isset($child['class'])) {
		
				try {
					$value = $this->handleBean($node, $filename, $child, $basedir, $prop);
				}
				catch (Corp_Exception $ex) {
					throw new Corp_Exception_XmlConfig($node, 'Invalid declaration for "'.$attr.'"' . ($name != null ? ' in bean "'.$name.'"' : '') . ' : unable to create bean ('.$ex->getMessage().')', $filename, $prop, $ex);
				}
		
			}
			
			// Soit un nom est spécifié
			else if (isset($child['name'])) {
			
				// On recupère le nom du bean
				$beanname = "{$child['name']}";
			
				// On s'assure que ce bean existe bien
				if (!array_key_exists($beanname, $this->beans)) {
					throw new Corp_Exception_XmlConfig($node, 'Invalid declaration for "'.$attr.'"' . ($name != null ? ' in bean "'.$name.'"' : '') . ' : bean "'.$beanname.'" not found', $filename, $prop);
				}
			
				// Si le bean existe bien, il devient la valeur
				$value = $this->beans[$beanname];
			
			}
				
			else {
				throw new Corp_Exception_XmlConfig($node, 'Invalid declaration for "'.$attr.'"' . ($name != null ? ' in bean "'.$name.'"' : '') . ' : the linked bean must have a "name" or a "class" attribute', $filename, $prop);
			}
		
		}
		
		// Si la valeur est contenue entre les balises
		else {
			$value = "{$prop}";
			$value = $this->applyPropertiesReplace($value, $basedir, $filename, $node, $prop);
		}
		
		// Si l'objet est de type POJO, on regarde s'il faut lui forcer un type
		if (!is_object($prop) && !is_resource($prop) && isset($prop['type'])) {
			// TODO Caster la valeur en fonction du type
		}
		
		return $value;
		
	}
	
	public function applyPropertiesReplace($str, $basedir, $filename, $node, $xml) {
		if (!is_string($str)) {
			return $str;
		}
		if ($str === 'FALSE') {
			return false;
		}
		if ($str === 'TRUE') {
			return true;
		}
		if ($str === 'NULL') {
			return null;
		}
		if (is_numeric($str)) {
			return $str + 0;
		}
		$matches = array();
		preg_match_all('/\$\{(.*?)\}/', $str, $matches);
		foreach ($matches[1] as $k => $property) {
			// Magic : base directory
			if ($property == 'basedir') {
				$str = str_replace($matches[0][$k], $basedir, $str);
			}
			// Existing properties
			else if (array_key_exists($property, $this->properties)) {
				$str = str_replace($matches[0][$k], $this->properties[$property], $str);
			}
			// Magic : debug properties
			else if (substr($property, 0, 6) == 'debug.') {
				$str = in_array(substr($property, 6), $this->debugFlags);
			}
			// Magic : include path
			else if ($property == 'includepath') {
				$str = str_replace($matches[0][$k], get_include_path(), $str);
			}
			// Magic : include path separator
			else if ($property == 'includepathseparator') {
				$str = str_replace($matches[0][$k], PATH_SEPARATOR, $str);
			}
			// Magic : now
			else if ($property == 'now') {
				$str = str_replace($matches[0][$k], $_SERVER['REQUEST_TIME'], $str);
			}
			// Magic : base url
			else if ($property == 'baseurl') {
				if (PHP_SAPI === 'cli') {
					$str = str_replace('${baseurl}', 'cli://', $str);
				}
				else {
					$url = 'http';
					if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') $url .= 's';
					$url .= '://' . $_SERVER['SERVER_NAME'];
					if ($_SERVER['SERVER_PORT'] != 80) {
						$url .= ':' . $_SERVER['SERVER_PORT'];
					}
					$url .= dirname(substr($_SERVER['REQUEST_URI'], 0, (strpos($_SERVER['REQUEST_URI'], '?') === false ? strlen($_SERVER['REQUEST_URI']) : strpos($_SERVER['REQUEST_URI'], '?'))));
					if (substr($url, -1) != '/') $url .= '/';
					$str = str_replace('${baseurl}', $url, $str);
				}
			}
			else {
				throw new Corp_Exception_XmlConfig($node, 'Invalid reference "${'.$property.'}" inside "' . $xml->getName() . '" tag', $filename, $xml);
			}
		}
		return $str;
	}
	
	public function getProperties() {
		return $this->properties;
	}
	
	public function getBeans() {
		return $this->beans;
	}
	
	public function getBeanByName($name) {
		return $this->beans[$name];
	}

	public function isBeanExists($name) {
		return isset($this->beans[$name]);
	}
	
	public function setBean($name, $bean) {
		if (is_string($name) && is_object($bean)) {
			$this->beans[$name] = $bean;
		}
		
	}
	
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->properties) || isset($this->beans[$offset]);
	}
	
	public function offsetGet($offset) {
		if (array_key_exists($offset, $this->properties)) {
			return $this->properties[$offset];
		}
		return $this->beans[$offset];
	}
	
	public function offsetSet($offset, $value) {
		$this->properties[$offset] = $value;
	}
	
	public function offsetUnset($offset) {
		if (array_key_exists($offset, $this->properties)) {
			unset($this->properties[$offset]);
			return true;
		}
		if (isset($this->beans[$offset])) {
			unset($this->beans[$offset]);
			return true;
		}
		return false;
	}
	
}

?>