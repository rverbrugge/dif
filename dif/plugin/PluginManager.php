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

require_once('Plugin.php');
require_once(DIF_ROOT.'/utils/Gzip.php');

/**
 * Main configuration 
 * @package Common
 */
class PluginManager extends Observer
{
	private $plugins;
	private $list;

	const ACTION_UPDATE = 1;

	public function __construct()
	{
		parent::__construct();

		$this->plugins = array();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('plugin', 'a');
		$this->sqlParser->addField(new SqlField('a', 'plug_id', 'id', 'Identifier', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'plug_name', 'name', 'Name', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'plug_description', 'description', 'Description', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'plug_active', 'active', 'Active state', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'plug_classname', 'classname', 'Class name', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'plug_version', 'version', 'Version', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'plug_dif_version', 'dif_version', 'DIF Version', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'plug_usr_id', 'usr_id', 'User', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'plug_create', 'createdate', 'Create date', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'plug_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->orderStatement = array('order by a.plug_name asc');
	}

	private function getClassList()
	{
		if($this->list) return $this->list;
	
		$tmplist = $this->getList();
		foreach($tmplist['data'] as $item)
		{
			$this->list[$item['classname']] = $item;
		}
		return $this->list;
	}

	public function getPluginName($classname)
	{
		$list = $this->getClassList();
		if(array_key_exists($classname, $list)) return $list[$classname]['name'];
	}

	public function getDescription($classname)
	{
		$list = $this->getClassList();
		if(array_key_exists($classname, $list)) return $list[$classname]['description'];
	}

	protected function parseCriteria($sqlParser, $searchcriteria)
	{
		if(!$searchcriteria || !is_array($searchcriteria)) return;

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'no_id' : $sqlParser->addCriteria(new SqlCriteria('a.plug_id', $value, '<>')); break;
				case 'compatible' : $sqlParser->addCriteria(new SqlCriteria('a.plug_dif_version', $value, '>=')); break;
			}
		}
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
		$fields['active'] = (array_key_exists('active', $fields) && $fields['active']);

		if((!array_key_exists('name', $fields) || !$fields['name']) && array_key_exists('classname', $fields))
			$fields['name'] = $fields['classname'];

		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$usr_id = $userId['id'];
		$this->sqlParser->setFieldValue('usr_id', $usr_id);

		return $fields;
	}

	public function getDefaultValue($fieldname)
	{
		switch($fieldname)
		{
			case 'active' : return 1; break;
		}
	}

	protected function handlePostGetList($values)
	{
		// check if plugin is active
		$values['activated'] = ($values['active'] && $values['dif_version'] >= $this->director->getRequiredDifVersion()) ;
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$values['activated'] = ($values['active'] && $values['dif_version'] >= $this->director->getRequiredDifVersion()) ;
		return $values;
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
		$sqlParser->addCriteria(new SqlCriteria('plug_classname', $values['classname']));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception("Plugin {$values['name']} already exists.");
	}

	/**
	 * handle pre update checks and additions 
	 * eg. check for uniqueness of set default values
   *
	 * @param array id of element
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePreUpdate
	 */
	protected function handlePreUpdate($id, $values)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('plug_classname', $values['classname']));
		$sqlParser->addCriteria(new SqlCriteria('plug_id', $id['id'], '<>'));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception("Plugin {$values['name']} already exists.");
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
		$sitePlugin = $this->director->siteManager->systemSite->getSitePlugin();
		$searchcriteria = array('plugin_id' => $values['id']);
		if($sitePlugin->exists($searchcriteria)) throw new Exception("Plugin {$values['name']} is beeing used by a page. Remove it first.");
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

		$pluginPath = $this->getPluginPath($className);
		Utils::removeRecursive($pluginPath);
	}

	/**
	 * Returns plugin
	 *
	 * @param string plugin classname
	 * @return Plugin object
	 */
	public function getPlugin($classname, $id=NULL) 
	{
		if(!array_key_exists($classname, $this->plugins)) 
		{
			// try to load the unloaded theme
			$this->loadPlugin($classname, $id);
		}
		return $this->plugins[$classname];
	}

	/**
	 * Returns plugin
	 *
	 * @param array plugin id 
	 * @return Plugin object
	 */
	public function getPluginFromId($id) 
	{
		$id['active'] = 1;
		$id['compatible'] = $this->director->getRequiredDifVersion();
		$detail = $this->getDetail($id);
		if(!$detail) throw new Exception("Plugin at ${id['id']} does not exist");

		return $this->getPlugin($detail['classname'], $id['id']);
	}

	/**
	 * Returns plugin's main class file name
	 *
	 * @param string plugin classname
	 * @return string
	 */
	private function getPluginFilename($classname) 
	{
		return $this->getPluginPath($classname)."script/$classname.php";
	}

	public function getPluginPath($classname='') 
	{
		$retval = DIF_WEB_ROOT."plugins/";
		if($classname) $retval .= $classname."/";
		return $retval;
	}

	/**
	 * Tries to include plugin PHP scripts
	 * @param string plugin name
	 */
	private function includeClassFiles($classname) 
	{
		$includePath = $this->getPluginFilename($classname);

		if (is_readable($includePath))
				include_once($includePath);
	}

	public function loadPlugin($classname, $id=NULL)
	{
		if(!$classname) throw new Exception("Try to load a plugin with no classname");

		if(array_key_exists($classname, $this->plugins)) return;
			//throw new Exception("Plugin $classname already loaded");

		// check if plugin is enabled
		$search = array('classname' => $classname, 'active' => 1, 'compatible' => $this->director->getRequiredDifVersion());
		if(!$this->exists($search)) throw new Exception("$classname version is incompatible with your DIF version");

		$this->includeClassFiles($classname);

		if(!class_exists($classname)) throw new Exception("Unable to load plugin $classname");

		$plugin = new $classname();
		$plugin->setId($id);
		$plugin->setPluginName($this->getPluginName($classname));
		$plugin->setDescription($this->getDescription($classname));

		$this->plugins[$plugin->getClassname()] = $plugin;
	}

	public function loadPlugins()
	{
		$search = array('active' => 1, 'compatible' => $this->director->getRequiredDifVersion());
		$list = $this->getList($search);
		$pluginlist = $list['data'];

		foreach($pluginlist as $item)
		{
			$this->loadPlugin($item['classname'], $item['id']);
		}
	}

	/**
	 * installs a new plugin
	 *
	 * @param array with posted values
	 * @return void
	 */
	protected function install($values, $id=NULL)
	{
		// check if plugin file is added. if not, only update pluginmanager settings
		if(!array_key_exists('pluginfile', $values) || 
			 !array_key_exists('tmp_name', $values['pluginfile']) ||
			 !$values['pluginfile']['tmp_name']
			 ) 
		{
			if(!isset($id)) throw new Exception('Upload file is not set. Please supply plugin installation file.');

			// no upload file so update active state
			$pluginVals = $this->getDetail($id);
			$pluginVals['active'] = (array_key_exists('active', $values) && $values['active']) ? 1 : 0;
			$this->update($id, $pluginVals);
			return;
		}

		// plugin file is provided. install / update new plugin

		$file = $values['pluginfile'];

		$tempPath = realpath($this->director->getTempPath());
		$uploadFile = $tempPath.'/'.$file['name'];
		if(!move_uploaded_file($file['tmp_name'], $uploadFile)) throw new Exception("Error moving {$file['tmp_name']} to $uploadFile.");

		$this->extractPlugin($tempPath, $uploadFile);
		$this->installPlugin($tempPath, $values, $id);
	}

	public function extractPlugin($tempPath, $pluginFile)
	{
		try
		{
			$gzip = new Gzip($pluginFile);
			$gzip->extract($tempPath);
			unlink($pluginFile);
		}
		catch(Exception $e)
		{
			if(isset($pluginFile)) unlink($pluginFile);
			throw $e;
		}
	}

	public function isPlugin($pluginPath)
	{
		return (file_exists($pluginPath.'/PluginInstaller.php') && file_exists($pluginPath.'/pluginInstaller.ini'));
	}

	public function installPlugin($tempPath, $settings, $id=NULL, $action=NULL)
	{
		$retval = '';

		try
		{
			$installFile = $tempPath.'/PluginInstaller.php';
			if(!file_exists($installFile)) throw new Exception("Wrong plugin file. Use DIF plugin files.");

			$iniFile = $tempPath.'/pluginInstaller.ini';
			if(!file_exists($iniFile)) throw new Exception("Wrong plugin file. Use DIF plugin files.");
			$classinfo = parse_ini_file($iniFile);

			// get installation script
			require($installFile);
			$install = new $classinfo['installer_class']();
			$install->setInstallPath($this->getPluginPath());
			$install->setVersion($classinfo['version']);
			$install->setClassName($classinfo['class']);
			if(array_key_exists('dif_version', $classinfo) && $classinfo['dif_version']) $install->setDifVersion($classinfo['dif_version']);

			// check if installed dif version is sufficient
			if(DIF_VERSION < $install->getDifVersion()) throw new Exception("Plugin requires DIF version {$install->getDifVersion()} or greater.");
			
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
			$plug_exists = isset($id);

			if($plug_exists)
				$this->update($id, $settings);
			else
				$id = $this->insert($settings);

			if($action == self::ACTION_UPDATE)
			{
				// only update if plugin exists
				if($plug_exists) $install->updateSql();
				$install->insertSql();
			}
			else
			{
				$install->install();
				unlink($installFile);
				unlink($iniFile);
			}

			$retval = $install->getLog();
			$install->__destruct();
			return $retval;
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
