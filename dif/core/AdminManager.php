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

require_once('AdminTree.php');

/**
 * Main configuration 
 * @package Common
 */
class AdminManager
{

	//private $config;
	//private $configPath;
	//private $configfile;
	private $urlPrefix;
	private $menu;
	private $director;
	public $tree;

	/**
	 * @var Logger
	 */
	protected $log;

	/**
	 * array with admin objects of the current location
	 * @var array
	 */
	private $objects;

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct($urlPrefix)
	{
		$this->director = Director::getInstance();
		$this->log = Logger::getInstance();

		$this->urlPrefix = $urlPrefix;
		$this->objects = array();
	}

	public function initialize()
	{
		// check if user is logged in
		$authentication = Authentication::getInstance();
		$request = Request::getInstance();

		if($authentication->isLogin() && !$authentication->isRole(SystemUser::ROLE_BACKEND)) 
		{
			$this->log->info("Failed access for ".$authentication->getUserName()." (not enough privileges for admin section) from ".$request->getValue('REMOTE_ADDR', Request::SERVER));
			throw new Exception('Access denied');
		}

		// check if admin section is restricted by ip-addresses
		$ip_allow = $this->director->getConfig()->admin_section_ip_allow;
		if($ip_allow)
		{
			$ips = explode(",",$ip_allow);
			if(!in_array($request->getValue('REMOTE_ADDR', Request::SERVER), $ips)) 
			{
				$this->log->info("Failed access for ".$authentication->getUserName()." (ip not in list for admin access) from ".$request->getValue('REMOTE_ADDR', Request::SERVER));
				throw new Exception('Access denied');
			}
		}


		// create tree object
		$treefile = Director::getConfigPath().$this->director->getConfig()->admin_menu;
		$useLogin = ($this->director->getConfig()->dsn);
		$this->tree = new AdminTree($treefile, $useLogin);
		$this->tree->setPrefix($this->urlPrefix);

		// check if path is set. is not, get the startpage path
		$path = $request->getPath();
		$currentId = $this->tree->isSiteRoot() ? $this->tree->getStartNodeId() : $this->tree->getIdFromPath($path) ;

		// current id does not exist. try to search login pages
		if(!$currentId && $this->tree->pathExists($path)) 
			$this->tree->setCurrentIdExists($this->tree->getIdFromPath($path, Tree::TREE_ORIGINAL));

		$this->tree->setCurrentId($currentId);
	}

	public function getTree()
	{
		return $this->tree;
	}

	/**
	 * Returns plugin
	 *
	 * @param string plugin classname
	 * @return Observer object
	 */
	public function getPlugin($classname) 
	{
		if(!array_key_exists($classname, $this->objects)) 
		{
			// try to load the unloaded theme
			$this->loadPlugin($classname);
		}
		return $this->objects[$classname];
	}

	/**
	 * Returns plugin's main class file name
	 *
	 * @param string plugin classname
	 * @return string
	 */
	private function getPluginFilename($classname) 
	{
		return DIF_WEB_ROOT ."coreplugins/$classname/script/$classname.php";
	}

	/**
	 * Tries to include plugin PHP scripts
	 * @param string plugin name
	 */
	private function includeClassFiles($classname) 
	{
		$includePath = $this->getPluginFilename($classname);
		//$this->log->debug("trying to load class $includePath");

		if (is_readable($includePath))
				include_once($includePath);
	}

	public function loadPlugin($classname)
	{
		if(!$classname) return;

		if(array_key_exists($classname, $this->objects))
			throw new Exception("Plugin $classname already loaded");

		$this->includeClassFiles($classname);

		if(!class_exists($classname))
			throw new Exception("Couldn't load plugin $classname");

		$object = new $classname();
		$this->objects[$object->getClassname()] = $object;
	}

	public function handlePostProcessing($theme)
	{
	}

	public function loadPlugins($dbExists)
	{
		$authentication = Authentication::getInstance();
		if($authentication->isLogin())
		{
			if($dbExists)
			{
				$node = $this->tree->getNode($this->tree->getCurrentId());
				if(!$node) return;
				$classname = $node['class'];
			}
			else
			{
				$classname = "Setup";
			}
		}
		else
		{
			// user is not logged in 
			$classname = $this->director->getConfig()->login_plugin;
		}
		$this->loadPlugin($classname);
	}
}

?>
