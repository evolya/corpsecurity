<?php

/**
 * Quality of Protection levels
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
class Corp_Request_QoP implements Corp_Serializable {

	/**
	 * Le mot de passe a été transmis avec un SALT
	 */
	const SALT = 2;

	/**
	 * Authentification avec en plus une clé secrète déjà connue par le client avant la connexion
	 * et qui n'est pas échangée. Par exemple: une API key transmise par email, et jamais échangée.
	 */
	const SECRETE_KEY = 4;

	/**
	 * Secured transport layer : la couche de transport est sécurisée (SSL/TLS ou TOR)
	 */
	const SECURED_TRANSPORT = 8;

	/**
	 * Le contenu est crypté en plus par le client et le serveur
	 * Par exemple, jCryption entre JS et PHP
	 */
	const CRYPTED_DIALOG = 16;

	/**
	 * La connexion est faite à partir d'un emplacement connu et validé
	 */
	const KNOWN_PLACE = 32;

	/**
	 * La connexion a passée un test supplémentaire en raison d'une politique supplémentaire
	 * Par exemple : keyring sur soho quand l'utilisateur est en dehors du pays du serveur
	 */
	const EXTRA_CHALLENGE = 64;
	
	/**
	 * La connextion est directe, sans passer par une requête réseau
	 * Par exemple: utilisation en CLI
	 */
	const DIRECT_ACCESS = 128;
	
	/**
	 * La connexion se fait depuis le localhost 
	 */
	const LOCAL_ACCESS = 256;
	
	/**
	 * La connexion passe par un tiers ouvert (un proxy par exemple)
	 */
	const VIA_OPEN = 512;
	
	/**
	 * La connexion passe par un tiers n'ayant pas la possibilité de lire la transmission,
	 * un relai TOR par exemple.
	 */
	const VIA_SECURE = 1024;
	
	/**
	 * @var int (byte field)
	 */
	protected $qop;
	
	/**
	 * Constructor
	 * @param int $qop (byte field)
	 */
	public function __construct($qop = 0) {
		$this->qop = intval($qop);
	}
	
	/**
	 * @return int (byte field)
	 */
	public function value() {
		return $this->qop;
	}
	
	/**
	 * @param int $level (byte field)
	 * @return boolean
	 */
	public function is($level) {
		return $this->qop & $level;
	}
	
	/**
	 * @param int $flag
	 * @return void
	 */
	public function add($flag) {
		$this->qop |= intval($flag);
	}
	
	/**
	 * @param int $flag
	 * @return void
	 */
	public function remove($flag) {
		$this->qop ^= intval($flag);
	}
	
	/**
	 * @return void
	 */
	public function reset() {
		$this->qop = 0;
	}
	
	/**
	 * @param int $value
	 * @return void
	 */
	public function set($value) {
		$this->qop = max(0, $value + 0);
	}
	
	/**
	 * @return string[]
	 */
	public function __sleep() {
		return array('qop');
	}
	
	/**
	 * @return void
	 */
	public function __wakeup() {
		
	}
	
	/**
	 * @return string
	 */
	public function __toString() {
		return self::toString($this->qop);
	}
	
	/**
	 * @param int $level (byte field)
	 * @return string
	 */
	public static function toString($level) {
		$r = array();
		foreach (self::values() as $name => $value) {
			if ($level & $value) {
				$r[] = $name;
			}
		}
		return implode('|', $r);
	}
	
	/**
	 * @return int[]
	 */
	public static function values() {
		$reflect = new ReflectionClass('Corp_Request_QoP');
		return $reflect->getConstants();
	}
	
	/**
	 * @return int (byte field)
	 */
	public static function all() {
		$r = 0;
		foreach (self::values() as $name => $const) {
			$r |= $const;
		}
		return $r;
	}

}

?>