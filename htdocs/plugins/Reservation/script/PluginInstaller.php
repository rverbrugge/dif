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

require_once(DIF_ROOT.'plugin/InstallPlugin.php');

/**
 * Main configuration 
 * @package Common
 */
class ReservationInstaller extends InstallPlugin 
{
	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct()
	{
		parent::__construct();
		$this->backupFiles 	= array("templates", "htdocs/css");

		$this->basePath = realpath(dirname(__FILE__))."/";
	}

	public function updateSql()
	{
		//use this to modify tables (add / change columns)
		if($this->columnExists('set_cap_submit', 'reservation_overview_settings'))
		{
			$db = $this->getDb();
			$query = "alter table reservation_overview_settings change set_cap_submit set_cap_subscribe varchar(50) default null";
			$res = $db->query($query);
			$query = "alter table reservation_overview_settings change set_cap_back set_cap_unsubscribe varchar(50) default null";
			$res = $db->query($query);
			$query = "alter table reservation_overview_settings add set_vip_slots tinyint default 0 after set_slots";
			$res = $db->query($query);
			$query = "alter table reservation_overview_settings add set_vip_grp_id integer default 0 after set_cap_unsubscribe";
			$res = $db->query($query);
			$query = "alter table reservation add res_vip tinyint default 0 after res_time";
			$res = $db->query($query);
		}

	}
}

?>
