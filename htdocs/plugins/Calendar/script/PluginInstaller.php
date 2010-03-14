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
class CalendarInstaller extends InstallPlugin 
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
		if(!$this->columnExists('cal_count', 'calendar'))
		{
			$db = $this->getDb();
			$query = "alter table calendar add cal_count integer default 0 after cal_img_height";
			$res = $db->query($query);
		}

		if(!$this->columnExists('cal_date_format', 'calendar_settings'))
		{
			$db = $this->getDb();

			$query = array();
			// calendar settings
			$query[] = "alter table calendar_settings add cal_date_format varchar(25) default '%A %d %B %Y' after cal_rows";
			$query[] = "alter table calendar_settings add cal_cap_detail varchar(100) default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_cap_back varchar(100) default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_cap_submit varchar(100) default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_cap_desc varchar(100) default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_cap_email varchar(50) default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_cap_name varchar(100) default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_comment_width integer default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_comment_display integer default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_comment_title varchar(255) default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_comment_notify tinyint default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_comment tinyint default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_comment_order_asc tinyint default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_detail_img tinyint default NULL after cal_date_format";
			$query[] = "alter table calendar_settings add cal_template text default NULL after cal_date_format";

			// calendar overview settings
			$query[] = "alter table calendar_overview_settings add set_cap_submit varchar(50) default null after set_display";
			$query[] = "alter table calendar_overview_settings add set_cap_desc varchar(50) default null after set_display";
			$query[] = "alter table calendar_overview_settings add set_cap_email varchar(50) default null after set_display";
			$query[] = "alter table calendar_overview_settings add set_cap_name varchar(50) default null after set_display";
			$query[] = "alter table calendar_overview_settings add set_comment_width integer default 50 after set_display";
			$query[] = "alter table calendar_overview_settings add set_comment_display integer default 1 after set_display";
			$query[] = "alter table calendar_overview_settings add set_comment_title varchar(255) default NULL after set_display";
			$query[] = "alter table calendar_overview_settings add set_comment_notify tinyint(1) default 1 after set_display";
			$query[] = "alter table calendar_overview_settings add set_comment tinyint(1) default 1 after set_display";
			$query[] = "alter table calendar_overview_settings add set_comment_order_asc tinyint(1) default 1 after set_display";
			$query[] = "alter table calendar_overview_settings add set_detail_img tinyint default 0 after set_display";
			$query[] = "alter table calendar_overview_settings add set_template text default null after set_display";
			$query[] = "alter table calendar_overview_settings add set_date_format varchar(25) default '%A %d %B %Y' after set_display";
			$query[] = "alter table calendar_overview_settings add set_rows int default 0 after set_display";
			$query[] = "alter table calendar_overview_settings add set_image_max_width int default 0 after set_display";
			$query[] = "alter table calendar_overview_settings add set_image_height int default 0 after set_display";
			$query[] = "alter table calendar_overview_settings add set_image_width int default 0 after set_display";

			// calendar headlines
			$query[] = "alter table calendar_headlines add cal_cap_detail varchar(50) default NULL after cal_rows";
			$query[] = "alter table calendar_headlines add cal_group tinyint(1) default 0 after cal_rows";
			$query[] = "alter table calendar_headlines add cal_display tinyint(1) default 3 after cal_rows";
			$query[] = "alter table calendar_headlines add cal_date_format varchar(25) default '%d %B %Y' after cal_rows";

			// calendar archive
			$query[] = "alter table calendar_archive add cal_cap_detail varchar(50) default NULL after cal_stop";
			$query[] = "alter table calendar_archive add cal_group tinyint(1) default 0 after cal_stop";
			$query[] = "alter table calendar_archive add cal_display tinyint(1) default 3 after cal_stop";
			$query[] = "alter table calendar_archive add cal_date_format varchar(25) default '%d %B %Y' after cal_stop";
			$query[] = "alter table calendar_archive add cal_rows tinyint(1) default 20 after cal_stop";

			// settings
			$query[] = "alter table calendar_settings drop cal_group_arch";
			$query[] = "alter table calendar_settings drop cal_display_hdl";
			$query[] = "alter table calendar_settings drop cal_display_arch";

			// calendar

			foreach($query as $item)
			{
				$res = $db->query($item);
			}
		}

		if(!$this->columnExists('set_group', 'calendar_overview_settings'))
		{
			$db = $this->getDb();
			$query = "alter table calendar_overview_settings add set_group tinyint(1) default 0 after set_display";
			$res = $db->query($query);
		}

		if(!$this->columnExists('set_history', 'calendar_overview_settings'))
		{
			$db = $this->getDb();
			$query = "alter table calendar_overview_settings add set_history_comment varchar(100) default null after set_display";
			$res = $db->query($query);

			$query = "alter table calendar_overview_settings add set_history date default null after set_display";
			$res = $db->query($query);

			$query = "alter table calendar_settings add cal_history_comment varchar(100) default null after cal_display";
			$res = $db->query($query);

			$query = "alter table calendar_settings add cal_history date default null after cal_display";
			$res = $db->query($query);
		}
	}
}

?>
