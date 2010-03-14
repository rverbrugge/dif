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
class DIFInstaller extends DbConnector
{

	/**
	 * list of templates with location variable as key
	 * @var array
	 */

	private $basePath;
	private $webRoot;
	private $root;
	private $indexRoot;

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

		$this->basePath = realpath(dirname(__FILE__))."/";
	}

	public function __destruct()
	{
		$difdir 		= "{$this->basePath}dif";
		$htdocsdir 	= "{$this->basePath}htdocs";
		$datadir 		= "{$this->basePath}data";

		$copying 		= "{$this->basePath}COPYING";
		$readme 		= "{$this->basePath}README";

		if(is_dir($difdir)) 		Utils::removeRecursive($difdir);
		if(is_dir($htdocsdir)) 	Utils::removeRecursive($htdocsdir);
		if(is_dir($datadir)) 		Utils::removeRecursive($datadir);

		if(is_file($copying)) unlink($copying);
		if(is_file($readme)) unlink($readme);

	}

	public function getWebRoot()
	{
		if(defined('DIF_WEB_ROOT')) return DIF_WEB_ROOT;
		if($this->webRoot) return $this->webRoot;
	}

	public function getRoot()
	{
		if(defined('DIF_ROOT')) return DIF_ROOT;
		if($this->root) return $this->root;
	}

	public function setWebRoot($value)
	{
		$this->webRoot = $value;
	}

	public function setRoot($value)
	{
		$this->root = $value;
	}

	private function mergeIniFiles()
	{
		$updatelist = array();

		// get update ini files
		$updatePath = $this->basePath."data/conf/";
		$dh = dir($updatePath);
		while (false !== ($line = $dh->read()))
		{
			// only add files
			if(!is_file($updatePath.'/'.$line)) continue;
			// only add ini files
			$ext = Utils::getFileExtension($line);
			if($ext == "ini" || $ext  == "ini-dist" || $ext == "xml")
				$updatelist[] = $line;
		}
		$dh->close();

		// get path to source ini files
		$currentPath = $this->director->getConfigPath();
		
		// merge files
		foreach($updatelist as $config)
		{
			$conf_ext = Utils::getFileExtension($config);
			if($conf_ext == "ini-dist") 
			{
				$configFile = substr($config, 0, -5);
				$configFileDist = $config;

				$updateConfigDist 	= $updatePath.$configFileDist;
				$currentConfig 	= $currentPath.$configFile;
				$currentConfigDist 	= $currentPath.$configFileDist;
				
				if(file_exists($currentConfig) && (!file_exists($currentConfigDist) || (md5_file($currentConfig) != md5_file($currentConfigDist))))
				{
					// backup existing config file and merge differences
					rename($currentConfig, "$currentConfig.orig");
					//todo point to Utils after release
					self::mergeIniFile($updateConfigDist, "$currentConfig.orig", $currentConfig);
				}
				else
				{
					// install update version
					copy($updateConfigDist, $currentConfig);
					chmod($currentConfig, 0664);
				}
				// move distribution file
				rename($updateConfigDist, $currentConfigDist);
				chmod($currentConfigDist, 0664);
			}
			else
			{
				$currentConfig = $currentPath.$config;
				$updateConfig = $updatePath.$config;
				// check if config file exists and backup if so
				if(file_exists($currentConfig))
				{
					rename($currentConfig, $currentConfig.".orig");
					//todo point to Utils after release
					if($conf_ext == "ini")
						self::mergeIniFile($updateConfig, "$currentConfig.orig", $currentConfig);
					else
					{
						rename($updateConfig, $currentConfig);
						chmod($currentConfig, 0664);
					}
				}
				else
				{
					// set new config file
					rename($updateConfig, $currentConfig);
					chmod($currentConfig, 0664);
				}
			}

		}
	}

	// TODO delete after release 1.0
	static public function mergeIniFile($inifile1, $inifile2, $destination)
	{
		// retrieve current settings
		$settings = parse_ini_file($inifile2, true);
		// the force array in the new distrubution file specifies which entries are not allowed to be overwritten (merged) by the existing settings (like version number etc)
		$forceSettings = parse_ini_file($inifile1, true);
		if(array_key_exists('difsettings', $forceSettings) && array_key_exists('force', $forceSettings['difsettings']))
		{
			foreach($forceSettings['difsettings']['force'] as $item)
			{
				// force variables in sections are specified as <section>:<variable>
				$info = explode(":", $item);
				if(sizeof($info) > 1)
					unset($settings[$info[0]][$info[1]]);
				else
					unset($settings[$info[0]]);
			}
		}
		self::storeIniValues($inifile1, $destination, $settings);
	}

	// TODO delete after release 1.0
	static public function storeIniValues($source, $dest, $settings)
	{
		$newfile = '';
		$section = '';

		// read new file and change new settings to current settings
		if(!($fh = fopen($source, 'r'))) throw new Exception("Error opening file $source for reading.");
		while(!feof($fh))
		{
			$line = fgets($fh, 1024);
			$is_array = false;

			// check if we are in a section
			if(preg_match("/^\s*\[(\w+)\]\s*$/", $line, $matches))
				$section = $matches[1];

			// check if we are dealing with a variable
			if(!($pos = strrpos($line, '=')))
			{
				$newfile .= $line;
				continue;
			}

			// check if variable is array
			if(preg_match("/^\s*(\S+)\[\]\s*=/", $line, $matches))
			{
				$key = $matches[1];
				$is_array = true;
			}
			else
				$key = trim(substr($line, 0, $pos-1));

			// if section definition was previously found, use the settings in this section instead
			// arrays are define with the [name] definition in the config file
			$settingsList = ($section) ? $settings[$section] : $settings;

			if(is_array($settingsList) && array_key_exists($key, $settingsList))
			{
				$value = ($is_array) ? ($section ? array_shift($settings[$section][$key]) : array_shift($settings[$key])) : $settingsList[$key];
				if(is_numeric($value))
					$value = $value;
				elseif(is_bool($value)) 
					$value = $value ? 'true' : 'false';
				else
					$value = '"'.$value.'"';

				$newfile .= substr_replace($line, " ".$value, $pos+1)."\n";
				//$newfile .= substr_replace($line, " ".self::iniToString($value, $key), $pos+1)."\n";
				
				// remove settings so we can append new settings at the end of the file
				if($is_array)
				{
					if($section && sizeof($settings[$section][$key]) == 0)
						unset($settings[$section][$key]);
					elseif(sizeof($settings[$key]) == 0)
						unset($settings[$key]);
				}
				else
				{
					if($section)
						unset($settings[$section][$key]);
					else
						unset($settings[$key]);
				}
			}
			else
			{
				$newfile .= $line;
			}

		}
		fclose($fh);

		// add new settings
		$newfile .= self::iniToString($settings);

		// save new file
		if(!($fh = fopen($dest, 'w'))) throw new Exception("Error opening file $dest for writing.");
		fputs($fh, $newfile);
		fclose($fh);
	}

	// TODO delete after release 1.0
	public static function iniToString($values, $id=NULL)
	{
		$prefix = isset($id) ? "$id = " : "";
		$retval = "";

		if(is_array($values))
		{
			foreach($values as $key=>$value)
			{
				if(is_array($value) && sizeof($value) > 0)
				{
					if(is_numeric(key($value)))
						$retval .= self::iniToString($value, $key.'[]');
					else
						$retval .= "[$key]\n".self::iniToString($value);
				}
				else
					$retval .= self::iniToString($value, is_numeric($key) ? $id : $key);
			}
		}
		elseif(is_numeric($values))
			$retval = "$prefix$values\n";
		elseif(is_bool($values)) 
			$retval =  $prefix.($values ? 'true' : 'false')."\n";
		else
			$retval =  $prefix.'"'.$values.'"'."\n";

		return $retval;
	}


	public function install($updateDif=true)
	{
		if(!$this->getWebRoot()) throw new Exception("No webroot set.");
		if(!$this->getRoot()) throw new Exception("No root set.");

		$this->mergeIniFiles();

		if($updateDif) Utils::moveRecursive("{$this->basePath}dif/", $this->getRoot());
		Utils::moveRecursive("{$this->basePath}htdocs/coreplugins/", $this->getWebRoot());
		Utils::moveRecursive("{$this->basePath}htdocs/css/", $this->getWebRoot());
		Utils::moveRecursive("{$this->basePath}htdocs/images/", $this->getWebRoot());
		Utils::moveRecursive("{$this->basePath}htdocs/js/", $this->getWebRoot());
		Utils::moveRecursive("{$this->basePath}htdocs/themes/", $this->getWebRoot());
		if(!is_dir($this->getWebRoot()."plugins/")) Utils::moveRecursive("{$this->basePath}htdocs/plugins/", $this->getWebRoot());
		if(!is_dir($this->getWebRoot()."extensions/")) Utils::moveRecursive("{$this->basePath}htdocs/extensions/", $this->getWebRoot());
		if(!is_dir($this->getWebRoot()."dbcache/")) Utils::moveRecursive("{$this->basePath}htdocs/dbcache/", $this->getWebRoot());
		if(!is_dir($this->getWebRoot()."dbfiles/")) Utils::moveRecursive("{$this->basePath}htdocs/dbfiles/", $this->getWebRoot());
		rename("{$this->basePath}COPYING", "{$this->getWebRoot()}../COPYING");
		rename("{$this->basePath}README", "{$this->getWebRoot()}../README");
		//rename("{$this->basePath}index.php", "{$this->getWebRoot()}../index.php");

		$this->insertSql();
		$this->updateSql();

		$cache = Cache::getInstance();
		$cache->clear();
	}

	private function insertSql()
	{
		$sqlfile = DIF_ROOT.'sql/dif.sql';
		if(!file_exists($sqlfile)) return;

		$db = $this->getDb();
		$query = file_get_contents($sqlfile);
		$queries = explode(';', $query);
		foreach($queries as $table)
		{
			$table = trim($table);
			if(!$table) continue;

			$res = $db->query($table);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());
		}
	}

	private function updateSql()
	{
		// change passwords to md5
		if($this->columnExists('usr_password', 'users'))
		{
			if($this->getColumn('usr_password','users', 1) == 'varchar(25)')
			{
				$db = $this->getDb();
				$query = "alter table users modify usr_password varchar(50) default ''";
				$res = $db->query($query);
				if($db->isError($res)) throw new Exception($res->getDebugInfo());

				$query = "update users set usr_password = encrypt(usr_password)";
				$res = $db->query($query);
				if($db->isError($res)) throw new Exception($res->getDebugInfo());
			}
		}

		// add acl and role based security
		if(!$this->columnExists('usr_role', 'users'))
		{
			$db = $this->getDb();
			$query = "alter table users add usr_role tinyint default 0 after usr_active";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			$query = "alter table groups drop grp_type";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			$query = "alter table sitetree drop tree_grp_id";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			$query = "alter table sitetree drop tree_grp_edit_id";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());
		}

		// add notification option
		if(!$this->columnExists('usr_notify', 'users'))
		{
			$db = $this->getDb();
			$query = "alter table users add usr_notify tinyint default 0 after usr_active";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());
		}

		// add view type on site plugin (to hide plugins in a certain view)
		if(!$this->columnExists('plug_view', 'siteplugin'))
		{
			$db = $this->getDb();
			$query = "alter table siteplugin add plug_view mediumint default 0 after plug_type";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());
		}

		// add view type on site plugin (to hide plugins in a certain view)
		if(!$this->columnExists('plug_description', 'plugin'))
		{
			$db = $this->getDb();
			$query = "alter table plugin add plug_description text default NULL after plug_name";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());
		}

		if(!$this->columnExists('ext_description', 'extension'))
		{
			$db = $this->getDb();

			$query = "alter table extension add ext_description text default NULL after ext_name";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());
		}

		// add site title and keywordsitegroup 
		if(!$this->columnExists('grp_title', 'sitegroup'))
		{
			$db = $this->getDb();

			$query = "alter table sitegroup add grp_keywords varchar(255) default NULL after grp_name";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			$query = "alter table sitegroup add grp_description varchar(255) default NULL after grp_name";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			$query = "alter table sitegroup add grp_title varchar(50) default NULL after grp_name";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			$query = "update sitegroup set grp_title = grp_name";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());
		}

		// add recursive site plugin
		if(!$this->columnExists('plug_recursive', 'siteplugin'))
		{
			$db = $this->getDb();
			$query = "alter table siteplugin add plug_recursive tinyint(1) default 0 after plug_view";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());
		}

		// add recursive site plugin
		if(!$this->columnExists('remove_container', 'sitetag'))
		{
			$db = $this->getDb();
			$query = "alter table sitetag add remove_container tinyint(1) default 0 after tags";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());
		}

		// add DIF version attributes
		if(!$this->columnExists('plug_dif_version', 'plugin'))
		{
			$db = $this->getDb();
			$query = array();
			$query[] = "alter table plugin add plug_dif_version varchar(15) default NULL after plug_version";
			$query[] = "alter table extension add ext_dif_version varchar(15) default NULL after ext_version";
			$query[] = "alter table theme add theme_dif_version varchar(15) default NULL after theme_version";
			$query[] = "alter table theme add theme_description varchar(255) default NULL after theme_name";

			foreach($query as $item)
			{
				$res = $db->query($item);
				if($db->isError($res)) throw new Exception($res->getDebugInfo());
			}
		}
	}


	/**
	 * deprecated functions for backward compatablilty
	 * functions are moved to DbConnector
	 */
	protected function columnExists($columnName, $tableName)
	{
		$db = $this->getDb();
		//$query = sprintf("show columns from %s where Field = '%s'", $tableName, $columnName);
		$query = sprintf("show columns from %s", $tableName);
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		while($row = $res->fetchRow())
		{
			if($row[0] == $columnName) return true;
		}

		return false;
	}

	/**
	 * deprecated functions for backward compatablilty
	 * functions are moved to DbConnector
	 */
	protected function getColumn($columnName, $tableName, $item)
	{
		$retval = '';

		$db = $this->getDb();
		//$query = sprintf("show columns from %s where Field = '%s'", $tableName, $columnName);
		$query = sprintf("show columns from %s", $tableName);
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		while($row = $res->fetchRow())
		{
			if($row[0] == $columnName) $retval = $row[$item];
		}

		return $retval;
	}


}

?>
