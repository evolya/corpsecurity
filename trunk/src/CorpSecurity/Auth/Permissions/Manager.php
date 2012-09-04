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
 * @package    evolya.corpsecurity.auth
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2012 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/?q=corp
 */
abstract class Corp_Auth_Permissions_Manager {
	
	/**
	 * @param string $uri
	 * @param Corp_Auth_Identity $identity
	 * @return string[]
	 */
	public abstract function getPermissions($uri, Corp_Auth_Identity $identity);
	
	/**
	 * @param string $uri
	 * @param string|string[] $requiredPrivileges
	 * @param Corp_Auth_Identity $identity
	 * @return boolean
	 * @throws Corp_Exception_Security_NeedPrivileges
	 */
	public function checkPermission($uri, $requiredPrivileges, Corp_Auth_Identity $identity, $throwExceptions = true) {
		
		// On recupère les priviléges de l'utilisateur sur cet URI
		$userPrivileges = $this->getPermissions($uri, $identity);
		
		// On s'assure que les priviléges soient en tableau
		if (!is_array($requiredPrivileges)) {
			$requiredPrivileges = array($requiredPrivileges);
		}

		// Ce tableau contiendra les priviléges qui ne sont pas respectés
		$failed = array();
		
		// On parcours les priviléges réquis
		foreach ($requiredPrivileges as $priv) {
				
			// Si l'utilisateur ne posséde pas de privilége, on l'ajoute dans le tableau de ratés
			if (!in_array($priv, $userPrivileges)) {
				$failed[] = $priv;
			}

		}
		
		// Si des priviléges ont été 'ratés'
		if (sizeof($failed) > 0) {
			if ($throwExceptions) {
				throw new Corp_Exception_Security_NeedPrivileges($uri, $identity, $failed);
			}
			return false;
		}
		
		// Si tout est accepté, on renvoi true
		return true;
		
	}
	
}

?>