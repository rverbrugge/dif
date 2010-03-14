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
class NewsLetterInstaller extends InstallPlugin 
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
		if(!$this->columnExists('news_count', 'news'))
		{
			$db = $this->getDb();
			$query = "alter table news add news_count integer default 0 after news_img_height";
			$res = $db->query($query);
		}

		if(!$this->columnExists('news_display', 'news_headlines'))
		{
			$db = $this->getDb();
			$query = "alter table news_headlines add news_display tinyint(1) default 3 after news_rows";
			$res = $db->query($query);

			$query = "alter table news_settings drop news_display_hdl";
			$res = $db->query($query);

			$query = "alter table news_settings add news_comment tinyint(1) default 0 after news_rows";
			$res = $db->query($query);
		}

		if(!$this->columnExists('news_display', 'news_settings'))
		{
			$db = $this->getDb();
			$query = "alter table news_settings drop news_display";
			$res = $db->query($query);

			$query = "alter table news_settings drop news_comment";
			$res = $db->query($query);
		}

		if(!$this->columnExists('news_cap_detail', 'news_headlines'))
		{
			$db = $this->getDb();
			$query = "alter table news_headlines add news_cap_detail varchar(50) default NULL after news_display";
			$res = $db->query($query);
		}

		if(!$this->columnExists('set_comment_order_asc', 'news_overview_settings'))
		{
			$db = $this->getDb();

			$query = "alter table news_overview_settings add set_comment_order_asc tinyint(1) default 1 after set_display";
			$res = $db->query($query);

			$query = "alter table news_overview_settings add set_comment_display integer default 1 after set_comment_title";
			$res = $db->query($query);

			$query = "alter table news_comment add com_date datetime default null after com_ip";
			$res = $db->query($query);

			$query = "update news_comment set com_date = com_create";
			$res = $db->query($query);
		}

		if(!$this->columnExists('com_email', 'news_comment'))
		{
			$db = $this->getDb();

			$query = "alter table news_comment add com_email varchar(50) default null after com_name";
			$res = $db->query($query);

			$query = "alter table news_overview_settings add set_comment_notify tinyint(1) default 1 after set_comment";
			$res = $db->query($query);

			$query = "alter table news_overview_settings add set_cap_email varchar(50) default null after set_cap_name";
			$res = $db->query($query);

			$query = "alter table news_overview_settings add set_comment_width integer default 50 after set_comment_display";
			$res = $db->query($query);
		}

	}
}

?>
