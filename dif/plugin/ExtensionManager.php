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

require_once('Extension.php');
require_once(DIF_ROOT.'/utils/Gzip.php');

/**
 * Main configuration 
 * @package Common
 */
class ExtensionManager extends Observer
{
	private $extensions;
	private $list;

	const ACTION_UPDATE = 1;

	public function __construct()
	{
		parent::__construct();

		$this->extensions = array();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('extension', 'a');
		$this->sqlParser->addField(new SqlField('a', 'ext_id', 'id', 'Identifier', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'ext_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'ext_description', 'description', 'Description', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'ext_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'ext_classname', 'classname', 'Naam klasse', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'ext_version', 'version', 'Versie', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'ext_dif_version', 'dif_version', 'DIF Versie', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'ext_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'ext_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'ext_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->orderStatement = array('order by a.ext_name asc');
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

	public function getExtensionName($classname)
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
				case 'no_id' : $sqlParser->addCriteria(new SqlCriteria('a.ext_id', $value, '<>')); break;
				case 'compatible' : $sqlParser->addCriteria(new SqlCriteria('a.ext_dif_version', $value, '>=')); break;
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
		$sqlParser->addCriteria(new SqlCriteria('ext_classname', $values['classname']));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception("Extension {$values['name']} already exists.");
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
		$sqlParser->addCriteria(new SqlCriteria('ext_classname', $values['classname']));
		$sqlParser->addCriteria(new SqlCriteria('ext_id', $id['id'], '<>'));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception("Extension {$values['name']} already exists.");
	}

	/**
	 * handle pre delete checks and additions 
	 * this function removed the extension files
   *
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePreDelete
	 */
	protected function handlePreDelete($id, $values)
	{
	}

	/**
	 * handle post delete functions
	 * this function removes the extension files
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

		$extensionPath = $this->getExtensionPath($className);
		Utils::removeRecursive($extensionPath);
	}

	/**
	 * Returns extension
	 *
	 * @param string extension classname
	 * @return Extension object
	 */
	public function getExtension($classname) 
	{
		if(array_key_exists($classname, $this->extensions)) 
			return $this->extensions[$classname];
	}

	/**
	 * Returns extension
	 *
	 * @param array extension id 
	 * @return Extension object
	 */
	public function getExtensionFromId($id) 
	{
		$id['active'] = 1;
		$id['compatible'] = $this->director->getRequiredDifVersion();
		$detail = $this->getDetail($id);
		if(!$detail) throw new Exception("Extension at $id does not exist");

		return $this->getExtension($detail['classname']);
	}

	/**
	 * Returns extension's main class file name
	 *
	 * @param string extension classname
	 * @return string
	 */
	private function getExtensionFilename($classname) 
	{
		return $this->getExtensionPath($classname)."script/$classname.php";
	}

	public function getExtensionPath($classname='') 
	{
		$retval = DIF_WEB_ROOT."extensions/";
		if($classname) $retval .= $classname."/";
		return $retval;
	}

	/**
	 * Tries to include extension PHP scripts
	 * @param string extension name
	 */
	private function includeClassFiles($classname) 
	{
		$includePath = $this->getExtensionFilename($classname);
		//$this->log->debug("trying to load class $includePath");

		if (is_readable($includePath))
				include_once($includePath);
	}

	private function loadExtension($classname)
	{
		if(!$classname) throw new Exception("Try to load a extension with no classname");

		if(array_key_exists($classname, $this->extensions))
			throw new Exception("Extension $classname already loaded");

		// check if plugin is enabled
		$search = array('classname' => $classname, 'active' => 1, 'compatible' => $this->director->getRequiredDifVersion());
		if(!$this->exists($search)) throw new Exception("$classname is not active or disabled due to version incompatibility.");

		$this->includeClassFiles($classname);

		if(!class_exists($classname))
			throw new Exception("Couldn't load extension $classname");

		$extension = new $classname();
		$extension->setPluginName($this->getExtensionName($classname));
		$extension->setDescription($this->getDescription($classname));

		$this->extensions[$extension->getClassname()] = $extension;
	}

	public function loadExtensions()
	{
		if(!$this->director->dbExists) return;

		$search = array('active' => 1, 'compatible' => $this->director->getRequiredDifVersion());
		$list = $this->getList($search);
		$extensionlist = $list['data'];

		foreach($extensionlist as $item)
		{
			$this->loadExtension($item['classname']);
		}
	}

	/**
	 * installs a new extension
	 *
	 * @param array with posted values
	 * @return void
	 */
	protected function install($values, $id=NULL)
	{
		// check if extension file is added. if not, only update extensionmanager settings
		if(!array_key_exists('extensionfile', $values) || 
			 !array_key_exists('tmp_name', $values['extensionfile']) ||
			 !$values['extensionfile']['tmp_name']
			 ) 
		{
			if(!isset($id)) throw new Exception('Upload file is not set. Please supply extension installation file.');

			// no upload file so update active state
			$extensionVals = $this->getDetail($id);
			$extensionVals['active'] = (array_key_exists('active', $values) && $values['active']) ? 1 : 0;
			$this->update($id, $extensionVals);
			return;
		}

		// extension file is provided. install / update new extension

		$file = $values['extensionfile'];

		$tempPath = realpath($this->director->getTempPath());
		$uploadFile = $tempPath.'/'.$file['name'];
		if(!move_uploaded_file($file['tmp_name'], $uploadFile)) throw new Exception("Error moving {$file['tmp_name']} to $uploadFile.");

		$this->extractExtension($tempPath, $uploadFile);
		$this->installExtension($tempPath, $values, $id);
	}

	public function extractExtension($tempPath, $extensionFile)
	{
		try
		{
			$gzip = new Gzip($extensionFile);
			$gzip->extract($tempPath);
			unlink($extensionFile);
		}
		catch(Exception $e)
		{
			if(isset($extensionFile)) unlink($extensionFile);
			throw $e;
		}
	}

	public function isExtension($extensionPath)
	{
		return (file_exists($extensionPath.'/ExtensionInstaller.php') && file_exists($extensionPath.'/extensionInstaller.ini'));
	}

	public function installExtension($tempPath, $settings, $id=NULL, $action=NULL)
	{
		$retval = '';

		try
		{
			$installFile = $tempPath.'/ExtensionInstaller.php';
			if(!file_exists($installFile)) throw new Exception("Wrong extension file. Use freecms extension files.");

			$iniFile = $tempPath.'/extensionInstaller.ini';
			if(!file_exists($iniFile)) throw new Exception("Wrong extension file. Use freecms extension files.");
			$classinfo = parse_ini_file($iniFile);

			// get installation script
			require($installFile);
			$install = new $classinfo['installer_class']();
			$install->setInstallPath($this->getExtensionPath());
			$install->setVersion($classinfo['version']);
			$install->setClassName($classinfo['class']);
			if(array_key_exists('dif_version', $classinfo) && $classinfo['dif_version']) $install->setDifVersion($classinfo['dif_version']);

			// check if installed dif version is sufficient
			if(DIF_VERSION < $install->getDifVersion()) throw new Exception("Extension requires DIF version {$install->getDifVersion()} or greater.");
			
			// insert/update extensionmanager record
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

			$ext_exists = isset($id);

			if($ext_exists)
				$this->update($id, $settings);
			else
				$id = $this->insert($settings);

			if($action == self::ACTION_UPDATE)
			{
				// only update if plugin exists
				if($ext_exists) $install->updateSql();
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
