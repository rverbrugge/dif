<?php
/**
 * This file is part of the DIF Web Framework
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2007 Ramses Verbrugge
 * @package Common
 */

/**
 * Main configuration 
 * @package Common
 */
interface AuthenticationUser
{

	/**
	 * returns default value of a field
	 * @return mixed
	 */
	public function getUserName($id);
	public function getUserId($searchcriteria);
	public function checkPassword($id, $password);
	public function getPassword($id);
	public function disable($id);
	public function isEnabled($id);
	public function isAdmin($id);
	public function isType($id);
	public function onLogin($id);
	public function onLogout($id);
}

?>
