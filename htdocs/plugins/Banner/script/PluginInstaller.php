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
class BannerInstaller extends InstallPlugin 
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
		$db = $this->getDb();

		//use this to modify tables (add / change columns)
		if($this->columnExists('bnr_proportion', 'banner_settings'))
		{
			$query = "alter table banner_settings drop bnr_proportion";
			$res = $db->query($query);
		}

		if(!$this->columnExists('bnr_img_max_width', 'banner_settings'))
		{
			$query = "alter table banner_settings add bnr_img_max_width int default 800 after bnr_img_height";
			$res = $db->query($query);
		}

		if(!$this->columnExists('bnr_url', 'banner_settings'))
		{
			$query = "alter table banner_settings add bnr_url tinyint(1) default 0 after bnr_display_order";
			$res = $db->query($query);
		}

		if(!$this->columnExists('bnr_image_temp', 'banner'))
		{
			$query = "alter table banner add bnr_image_temp varchar(255) default null after bnr_image";
			$res = $db->query($query);

			$query = "alter table banner add bnr_img_x integer default 0 after bnr_image_temp";
			$res = $db->query($query);

			$query = "alter table banner add bnr_img_y integer default 0 after bnr_img_x";
			$res = $db->query($query);

			$query = "alter table banner add bnr_img_width integer default 0 after bnr_img_y";
			$res = $db->query($query);

			$query = "alter table banner add bnr_img_height integer default 0 after bnr_img_width";
			$res = $db->query($query);
		}

		if(!$this->columnExists('bnr_image', 'banner_settings'))
		{
			$query = "alter table banner_settings add bnr_image varchar(255) default null after bnr_img_max_width";
			$res = $db->query($query);
		}

	}
}

?>
