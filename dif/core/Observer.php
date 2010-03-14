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
abstract class Observer extends DbConnector
{

	public function __construct()
	{
		parent::__construct();
		$this->director->attach($this);
	}

	/**
	 * handle notification events from director
	 *
	 * @param notifing observer object
	 * @param id of item whom invoked the notification
	 * @param type of notification
	 */
	public function onEvent($obj, $id, $type)
	{
		return true;
	}

	public function getClassName()
	{
		return get_class($this);
	}
}

?>
