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
abstract class InstallPlugin extends DbConnector
{

	/**
	 * list of templates with location variable as key
	 * @var array
	 */

	private $configPath;
	private $installPath;
	protected $basePath;
	protected $className;
	protected $pluginName;
	protected $version;
	protected $image;
	protected $difVersion;

	/**
	 * List of files that need to be backed up before update
	 * @var array
	 */
	protected $backupFiles = array();

	/**
	 * List of files that were changed and have been backed up
	 * @var array
	 */
	protected $backupLog = array();

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

		// set default dif version
		$this->setDifVersion(DIF_VERSION);
		$this->installPath = realpath(DIF_WEB_ROOT."plugins")."/";
		$this->initialize();
	}

	public function __destruct()
	{
		$cachedir = $this->basePath.$this->className;
		if(is_dir($cachedir)) Utils::removeRecursive($cachedir);
	}

	private function initialize()
	{
		$auth = Authentication::getInstance();
		if(!$auth->isLogin() || !$auth->isRole(SystemUser::ROLE_ADMIN)) throw new Exception('Access denied');
	}

	public function setInstallPath($value)
	{
		$this->installPath = $value;
	}

	private function getPath()
	{
		return $this->basePath;
	}

	public function setClassName($value)
	{
		$this->className = $value;
	}

	public function getClassName()
	{
		return $this->className;
	}

	public function setDifVersion($version)
	{
		$this->difVersion = $version;
	}

	public function getDifVersion()
	{
		return $this->difVersion;
	}

	public function setVersion($version)
	{
		$this->version = $version;
	}

	public function getVersion()
	{
		return $this->version;
	}

	public function getImage($path=false)
	{
		if(!isset($this->image)) return NULL;

		return ($path) ? $this->getPath().$this->image : $this->image;
	}

	public function getLog()
	{
		return $this->backupLog;
	}

	public function install($configMerge=true)
	{
		if(!isset($this->className)) throw new Exception("Class name is not set. Please check installation package.");
		if(!isset($this->installPath)) throw new Exception("Installation path does not exists. Please check core package.");

		if(is_dir($this->installPath.$this->className))
			$this->installUpdate($configMerge);
		else
			$this->installNew();
	}

	protected function installNew()
	{
		// move plugin directory to install location
		$directory = $this->basePath.$this->className;
		if(!is_dir($directory)) throw new Exception("Directory $directory does not exists. Please check installation package.");

		$destDir = $this->installPath.$this->className;
		rename($directory, $destDir);
		chmod($destDir, 0755);

		// install config file
		$configFile = $this->installPath.$this->className.'/script/'.strtolower($this->className).".ini";
		$configFileDist = $configFile.'-dist';
		if(is_file($configFileDist)) 
		{
			copy($configFileDist, $configFile);
			chmod($configFile, 0664);
		}

		$this->insertSql();
	}

	protected function handleBackup($filename)
	{
		$currentFile 	= $this->installPath.$this->className.'/'.$filename;
		$newFile 			= $this->basePath.$this->className.'/'.$filename;

		if(is_dir($currentFile))
		{
			if(!($handle = opendir($currentFile))) throw new Exception("unable to open $currentFile.");
			while(false !== ($file = readdir($handle)))
			{ 
				if($file == '.' || $file == '..' || Utils::getFileExtension($file) == 'orig') continue;
				$tmpfile = $filename.'/'.$file;
				$this->handleBackup($tmpfile);
			}
			closedir($handle);
		}
    elseif(file_exists($currentFile) && file_exists($newFile) && (md5_file($currentFile) != md5_file($newFile)))
		{
			$this->backupLog[] = $currentFile;
			rename($currentFile, $currentFile.".orig");
		}
	}

	protected function installUpdate($configMerge=true)
	{
		// backup files that need to be backed up
		foreach($this->backupFiles as $item)
		{
			$this->handleBackup($item);
		}

		// install config file
		$configFile 				= $this->className.'/script/'.strtolower($this->className).".ini";
		$configFileDist 		= $configFile.'-dist';
		$newConfig 					= $this->basePath.$configFileDist;
		$currentConfig 			= $this->installPath.$configFile;
		$currentConfigDist 	= $this->installPath.$configFileDist;

		if(is_file($newConfig))
		{
			// check if file has differences
			if(file_exists($currentConfig) && (!file_exists($currentConfigDist) || (md5_file($currentConfig) != md5_file($currentConfigDist))))
			{
				// backup existing config file, save new and merge differences
				rename($currentConfig, "$currentConfig.orig");

				if($configMerge)
					Utils::mergeIniFile($newConfig, "$currentConfig.orig", $currentConfig);
				else
				{
					copy($newConfig, $currentConfig);
					chmod($currentConfig, 0664);
				}
			}
			else
			{
				// install new version
				copy($newConfig, $currentConfig);
				chmod($currentConfig, 0664);
			}
		}

		//Utils::copyRecursive($this->basePath.$this->className, $this->installPath);
		Utils::moveRecursive($this->basePath.$this->className, $this->installPath);

		$this->updateSql();
		$this->insertSql();
	}

	private function getTableList()
	{
		if(isset($this->tablelist)) return $this->tablelist;
		$this->tablelist = array();

		$db = $this->getDb();

		$query = "show tables";
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
		while($row = $res->fetchRow())
		{
			$this->tablelist[$row[0]] = $row[0];
		}
		return $this->tablelist;
	}

	protected function TableExists($tableName)
	{
		$table = $this->getTableList();
		return array_key_exists($tableName, $table);
	}

	public function insertSql()
	{
		$sqlfile = $this->installPath.$this->className.'/script/db.sql';
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

	abstract public function updateSql();
}

?>
