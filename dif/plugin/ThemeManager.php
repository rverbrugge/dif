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

require_once('Theme.php');
require_once(DIF_ROOT.'utils/Image.php');

/**
 * Main configuration 
 * @package Common
 */
class ThemeManager extends Observer
{
	private $themes;
	private $config;
	private $configFile;

	const ACTION_UPDATE = 1;

	public function __construct()
	{
		parent::__construct();

		$this->configFile = strtolower(__CLASS__.".ini");
		$this->themes = array();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('theme', 'a');
		$this->sqlParser->addField(new SqlField('a', 'theme_id', 'id', 'Identifier', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'theme_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'theme_description', 'description', 'Description', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'theme_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'theme_selected', 'selected', 'Standaard status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'theme_classname', 'classname', 'Naam klasse', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'theme_version', 'version', 'Versie', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'theme_dif_version', 'dif_version', 'DIF Versie', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'theme_image', 'image', 'Afbeelding', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'theme_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'theme_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->orderStatement = array('order by a.theme_name asc');
	}

	public function getConfig()
	{
		if($this->config) return $this->config;

		$this->config = new Config(Director::$configPath, $this->configFile);
		return $this->config;
	}

	protected function parseCriteria($sqlParser, $searchcriteria)
	{
		if(!$searchcriteria || !is_array($searchcriteria)) return;

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'no_id' : $sqlParser->addCriteria(new SqlCriteria('a.theme_id', $value, '<>')); break;
				case 'compatible' : $sqlParser->addCriteria(new SqlCriteria('a.theme_dif_version', $value, '>=')); break;
			}
		}
	}

	/**
	 * returns default value of a field
	 * @return mixed
	 * @see DbConnector::getDefaultValue
	 */
	public function getDefaultValue($fieldname)
	{
		switch($fieldname)
		{
			case 'active' : return 1; break;
			case 'selected' : return 0; break;
		}
	}

	/**
	 * handle post getlist additions 
	 * eg. add image 
   *
	 * @param array row array
	 * @return array
	 */
	protected function handlePostGetList($values)
	{
		// check if plugin is active
		$values['activated'] = ($values['active'] && $values['dif_version'] >= $this->director->getRequiredDifVersion()) ;
		$values['themePath'] = $this->getThemePath($values['classname']);
		return $values;
	}

	/**
	 * handle post getDetail additions 
	 * eg. add image 
   *
	 * @param array row array
	 * @return array
	 */
	protected function handlePostGetDetail($values)
	{
		$values['themePath'] = $this->getThemePath($values['classname']);
		$values['activated'] = ($values['active'] && $values['dif_version'] >= $this->director->getRequiredDifVersion()) ;
		return $values;
	}


	/**
	 * filters field values like checkbox conversion and date conversion
	 *
	 * @param array unfiltered values
	 * @return array filtered values
	 * @see DbConnector::filterFields
	 */
	public function filterFields($fields)
	{
		// convert posted checkbox value to boolean
		$fields['active'] = (array_key_exists('active', $fields) && $fields['active']);
		$fields['selected'] = (array_key_exists('selected', $fields) && $fields['selected']);
		return $fields;
	}

	private function deselect()
	{
		$query = "update theme set theme_selected = 0";
		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}


	/**
	 * handle pre insert checks and additions 
	 * eg. check for uniqueness of set default values
   *
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePreInsert
	 */
	protected function handlePreInsert($values)
	{
		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));
		
		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('theme_classname', $values['classname']));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('Thema bestaat reeds.');
		if($values['selected']) $this->deselect();
	}

	/**
	 * handle pre update checks and additions 
	 * eg. check for uniqueness of set default values
   *
	 * @param array filtered values for insertion
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePreInsert
	 */
	protected function handlePreUpdate($id, $values)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('theme_classname', $values['classname']));
		$sqlParser->addCriteria(new SqlCriteria('theme_id', $id['id'], '<>'));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('Thema bestaat reeds.');

		// only 1 default selected theme can exist
		if(array_key_exists('selected', $values) && $values['selected']) $this->deselect();
	}

	/**
	 * handle pre delete checks and additions 
	 * this function removed the plugin files
   *
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePreDelete
	 */
	protected function handlePreDelete($id, $values)
	{
		$siteTheme = new SystemSiteTheme();
		$searchcriteria = array('theme_id' => $values['id']);
		if($siteTheme->exists($searchcriteria)) throw new Exception("Theme {$values['name']} is beeing used by a page. Remove it first.");

		$detail = $this->getDetail($id);
		if($detail['selected']) throw new Exception("Cannot delete a default theme. First use another theme as default.");
		if($detail['classname'] == $this->director->getConfig()->admin_theme) throw new Exception("Cannot delete a administration theme. First specify another theme in adminmanager config file.");
	}

	/**
	 * handle post delete functions
	 * this function removes the plugin files
   *
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePostDelete
	 */
	protected function handlePostDelete($id, $values)
	{
		$className =  $values['classname'];

		$configFile = Director::getConfigPath().strtolower($className).".ini";
		if(file_exists($configFile)) unlink($configFile);

		$themePath = $this->getThemePath($className);
		Utils::removeRecursive($themePath);
	}

	/**
	 * Returns theme's main class file name
	 *
	 * @param string theme classname
	 * @return string
	 */
	private function getThemeFilename($classname) 
	{
		return $this->getThemePath($classname)."script/$classname.php";
	}

	public function getThemePath($classname='') 
	{
		$retval = DIF_WEB_ROOT."themes/";
		if($classname) $retval .= $classname."/";
		return $retval;
	}


	/**
	 * Returns theme
	 *
	 * @param string theme classname
	 * @return Theme object
	 */
	public function getTheme($classname, $force=false) 
	{
		if(!array_key_exists($classname, $this->themes)) 
		{
			// try to load the unloaded theme
			$this->loadTheme($classname, $force);
			//throw new Exception("Theme $classname is not loaded.");
		}
		return $this->themes[$classname];
	}

	/**
	 * Returns theme
	 *
	 * @param array theme identifier
	 * @return Theme object
	 */
	public function getThemeFromId($id) 
	{
		$id['active'] = 1;
		$id['compatible'] = $this->director->getRequiredDifVersion();
		$detail = $this->getDetail($id);
		if(!$detail) throw new Exception("Theme at $id does not exist");

		return $this->getTheme($detail['classname']);
	}

	/**
	 * Returns theme
	 *
	 * @param string theme classname
	 * @return string
	 */
	public function getThemeList()
	{
		return $this->themes;
	}

	/**
	 * Tries to include theme PHP scripts
	 * @param string theme name
	 */
	private function includeClassFiles($classname) 
	{
		$includePath = $this->getThemeFilename($classname);
		//$this->log->debug("trying to load class $includePath");

		if (is_readable($includePath))
				include_once($includePath);
	}

	/**
	 * Try to instantiate theme class. by loading class files and creating the theme object
	 * @param string theme class name
	 * @param boolean force to load class name by not checking theme database
	 */
	private function loadTheme($classname, $force=false)
	{
		if(array_key_exists($classname, $this->themes))
			throw new Exception("Theme $classname already loaded");

		// check if plugin is enabled
		if(!$force)
		{
			$search = array('classname' => $classname, 'active' => 1, 'compatible' => $this->director->getRequiredDifVersion());
			if(!$this->exists($search)) throw new Exception("$classname is not active or disabled due to version incompatibility.");
		}

		$this->includeClassFiles($classname);

		if(!class_exists($classname))
			throw new Exception("Couldn't load theme $classname");

		$plugin = new $classname();

		$this->themes[$plugin->getClassName()] = $plugin;
	}

	public function loadThemes()
	{
		$search = array('active' => 1, 'compatible' => $this->director->getRequiredDifVersion());
		$list = $this->getList($search);
		$pluginlist = $list['data'];

		foreach($pluginlist as $item)
		{
			$this->loadTheme($item['classname']);
		}
	}

	/**
	 * installs a new theme
	 *
	 * @param array with posted values
	 * @return void
	 */
	protected function install($values, $id=NULL, $content_path)
	{
		// check if theme file is added. if not, only update thememanager settings
		if(!array_key_exists('themefile', $values) || 
			 !array_key_exists('tmp_name', $values['themefile']) ||
			 !$values['themefile']['tmp_name']
			 ) 
		{
			if(!isset($id)) throw new Exception('Upload file is not set. Please supply theme installation file.');

			// no upload file so update active state
			$themeVals = $this->getDetail($id);
			$themeVals['active'] = (array_key_exists('active', $values) && $values['active']) ? 1 : 0;
			$themeVals['selected'] = (array_key_exists('selected', $values) && $values['selected']) ? 1 : 0;
			$this->update($id, $themeVals);
			return;
		}

		// theme file is provided. install / update new theme

		$file = $values['themefile'];

		$tempPath = realpath($this->director->getTempPath());
		$uploadFile = $tempPath.'/'.$file['name'];
		if(!move_uploaded_file($file['tmp_name'], $uploadFile)) throw new Exception("Error moving {$file['tmp_name']} to $uploadFile.");

		$this->extractTheme($tempPath, $uploadFile);
		$this->installTheme($tempPath, $values, $id);
	}

	public function extractTheme($tempPath, $themeFile)
	{
		try
		{
			$gzip = new Gzip($themeFile);
			$gzip->extract($tempPath);
			unlink($themeFile);
		}
		catch(Exception $e)
		{
			if(isset($themeFile)) unlink($themeFile);
			throw $e;
		}
	}

	public function isTheme($themePath)
	{
		return (file_exists($themePath.'/ThemeInstaller.php') && file_exists($themePath.'/themeInstaller.ini'));
	}


	public function installTheme($tempPath, $settings, $id=NULL, $action=NULL)
	{
		$retval = '';

		try
		{
			if(!array_key_exists('content_path', $settings)) throw new Exception('content_path setting is missing.');

			$installFile = $tempPath.'/ThemeInstaller.php';
			if(!file_exists($installFile)) throw new Exception("Wrong theme file. Use DIF theme files.");

			$iniFile = $tempPath.'/themeInstaller.ini';
			if(!file_exists($iniFile)) throw new Exception("Wrong theme file. Use DIF theme files.");
			$classinfo = parse_ini_file($iniFile);

			// get installation script
			require($installFile);
			$install = new $classinfo['installer_class']();
			$install->setInstallPath($this->getThemePath());
			$install->setVersion($classinfo['version']);
			$install->setClassName($classinfo['class']);
			if(array_key_exists('dif_version', $classinfo) && $classinfo['dif_version']) $install->setDifVersion($classinfo['dif_version']);
			
			// check if installed dif version is sufficient
			if(DIF_VERSION < $install->getDifVersion()) throw new Exception("Theme requires DIF version {$install->getDifVersion()} or greater.");
			
			// insert/update pluginmanager record
			$settings['classname'] 		= $classinfo['class'];
			$settings['name'] 				= $classinfo['name'];
			$settings['description'] 	= $classinfo['description'];
			$settings['version'] 			= $classinfo['version'];
			$settings['dif_version'] 	= array_key_exists('dif_version', $classinfo) ? $classinfo['dif_version'] : '';
			$key = array('classname' => $settings['classname']);

			if(!isset($id) && $this->exists($key))
			{
				/*$tmp = $this->getDetail($key);
				$tmp['name'] = $settings['classname'];
				$settings = $tmp;
				$id = $this->getKey($settings);*/
				$id = $this->getKey($this->getDetail($key));
			}

			$theme_exists = isset($id);

			// create image if supplied
			$image = $install->getImage();
			if($image)
			{
				$imgfile = $install->getClassName().".png";

				// resize image and save it to the htdocs directory
				$img = new Image($tempPath.'/'.$image);
				$img->resize($this->getConfig()->thumb_width);
				$img->save($settings['content_path'].$imgfile);

				// add filename to database
				$settings['image'] = $imgfile;

				// delete temp image
				if($action != self::ACTION_UPDATE) unlink($tempPath.'/'.$image);
			}
			
			if($theme_exists)
				$this->update($id, $settings);
			else
				$id = $this->insert($settings);

			if($action == self::ACTION_UPDATE)
			{
				if($theme_exists) $install->updateSql();
				$install->insertSql();
			}
			else
			{
				$install->install(false);
				unlink($installFile);
				unlink($iniFile);
			}
			return $install->getLog();

		}
		catch(Exception $e)
		{
			if($action != self::ACTION_UPDATE)
			{
				if(isset($installFile)) unlink($installFile);
				if(isset($iniFile)) unlink($iniFile);
			}
			if(isset($install)) $install->__destruct();

			throw $e;
		}
	}

}

?>
