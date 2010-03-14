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
class LoginMailerInstaller extends InstallPlugin 
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
		if(!$this->columnExists('login_fin_tree_id', 'login_mail'))
		{
			$db = $this->getDb();
			$query = "alter table login_mail add login_fin_tree_id integer default 0 after login_ref_tree_id";
			$res = $db->query($query);

			$query = "alter table login_mail add login_intro text default '' after login_fin_tree_id";
			$res = $db->query($query);

			$query = "alter table login_mail add login_cap_submit varchar(50) default '' after login_footer";
			$res = $db->query($query);

			$query = "alter table login_mail add login_cap_fin_submit varchar(50) default '' after login_cap_submit";
			$res = $db->query($query);
		}

		if($this->columnExists('login_from', 'login_mail'))
		{
			$db = $this->getDb();
			$query = "alter table login_mail drop login_from";
			$res = $db->query($query);
		}

		if($this->columnExists('login_header', 'login_mail'))
		{
			$db = $this->getDb();
			$query = "alter table login_mail change login_header login_content text default null";
			$res = $db->query($query);

			$linktext = addslashes('\n<?=$password_url;?> \n');
			$query = "update login_mail set login_content = concat(login_content, '$linktext', login_footer)";
			$res = $db->query($query);

			$query = "alter table login_mail drop login_footer";
			$res = $db->query($query);
		}

	}
}

?>
