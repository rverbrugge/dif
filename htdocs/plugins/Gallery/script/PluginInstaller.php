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
class GalleryInstaller extends InstallPlugin 
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
		if(!$this->columnExists('display', 'gallery_headlines'))
		{
			$db = $this->getDb();

			$query = array();

			// gallery settings
			$query[] = "alter table gallery_settings add gal_date_format varchar(25) default '%A %d %B %Y' after gal_rows";
			$query[] = "alter table gallery_settings add gal_cap_detail varchar(100) default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_cap_back varchar(100) default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_cap_next varchar(100) default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_cap_previous varchar(100) default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_cap_submit varchar(100) default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_cap_desc varchar(100) default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_cap_email varchar(50) default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_cap_name varchar(100) default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_comment_width integer default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_comment_display integer default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_comment_title varchar(255) default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_comment_notify tinyint default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_comment tinyint default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_comment_order_asc tinyint default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_detail_img tinyint default NULL after gal_date_format";
			$query[] = "alter table gallery_settings add gal_template text default NULL after gal_date_format";

			// gallery headlines
			$query[] = "alter table gallery_headlines add gal_display tinyint(1) default 1 after gal_name";

			// settings
			$query[] = "alter table gallery_settings drop gal_display_hdl";
			
			foreach($query as $item)
			{
				$res = $db->query($item);
			}
		}

		if(!$this->columnExists('display_overview', 'gallery_settings'))
		{
			$db = $this->getDb();

			$query = array();
			$query[] = "alter table gallery_settings add gal_display_overview tinyint default 4 after gal_display";
			$query[] = "alter table gallery_overview_settings add set_display_overview tinyint default 4 after set_display";

			foreach($query as $item)
			{
				$res = $db->query($item);
			}
		}

	}
}

?>
