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

require_once(DIF_ROOT.'core/Config.php');
require_once(DIF_ROOT.'core/Authentication.php');
require_once(DIF_ROOT.'utils/Request.php');
require_once(DIF_ROOT.'database/DbConnector.php');
require_once(DIF_ROOT.'core/Observer.php');
require_once(DIF_ROOT.'core/Acl.php');
require_once(DIF_ROOT.'core/SystemSiteGroup.php');
require_once(DIF_ROOT.'core/SystemUser.php');
require_once(DIF_ROOT.'utils/Logger.php');
require_once(DIF_ROOT.'utils/Utils.php');
require_once(DIF_ROOT.'utils/Cache.php');
require_once(DIF_ROOT.'plugin/Providers.php');
require_once(DIF_ROOT.'plugin/ExtensionManager.php');

/**
 * Current software version
 */
define('DIF_VERSION', "2.0.0");

/**
 * Main configuration 
 * @package Common
 */
class Director 
{

	/**
	 * type of notification
	 */
	const INSERT = 1; 
	const UPDATE = 2;
	const DELETE = 3;

	/**
	 * Array of string which contains contents of .ini configuration file
	 * @var array
	 */
	protected $observer;
	private $db;

	private $config;
	protected $configfile;
	static public $configPath;

	/**
	 * list of system messages
	 * @var array
	 */
	private $messages = array();

	/**
	 * Static variable that specifies if full domain name has to be appended to an url
	 * @var bool
	 */
	static public $append_domain;

	/**
	 * Static singleton reference
	 * @var Director
	 */
	static private $instance;

	public $pluginManager;
	public $extensionManager;
	public $themeManager;
	public $systemUser;
	public $adminManager;
	private $rpcServer;
	private $cliServer;
	public $siteManager;
	public $tree;
	public $siteTree;
	public $siteGroup;

	/**
	 * theme object with templates
	 * @var Theme object
	 */
	public $theme;
	
	/**
	 * specifies admin environment
	 * @var bool
	 */

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	private function __construct()
	{
		self::$configPath = DIF_SYSTEM_ROOT."conf/";
		$this->configfile = "system.ini";
		self::$append_domain = false;
	}
	
	static public function getInstance()
	{
		if(self::$instance == NULL)
			self::$instance = new Director();

		return self::$instance;
	}

	private function initializeObjects()
	{

		require_once(DIF_ROOT.'plugin/ThemeManager.php');
		require_once(DIF_ROOT.'plugin/PluginManager.php');
		require_once(DIF_ROOT.'core/SystemSite.php');
		require_once(DIF_ROOT.'core/AdminManager.php');
		require_once(DIF_ROOT.'core/SiteManager.php');

		// set current sitegroup
		$request = Request::getInstance();

		if($request->exists(SystemSiteGroup::CURRENT_ID_KEY, Request::GET)) 
		{
			$this->siteGroup->setCurrentId($request->getValue(SystemSiteGroup::CURRENT_ID_KEY));
		}

		// set the current view type and not the cached one. (when this is a post action, view type is stored in session)
		$view = ViewManager::getInstance();

		$this->pluginManager = new PluginManager();
		$this->themeManager = new ThemeManager();
		//$this->themeManager->loadThemes();

		$this->adminManager = new AdminManager($this->getConfig()->admin_path);

		if($this->isAdminSection())
		{
			$this->adminManager->initialize();
			$this->tree = $this->adminManager->getTree();

			$this->theme = $this->themeManager->getTheme($this->getConfig()->admin_theme, true);
			$this->theme->setTree($this->tree);

			// initialize site tree
			if($this->dbExists)
			{
				$this->siteManager = new Sitemanager();
				$this->siteManager->initialize();
				$this->siteTree = $this->siteManager->getTree();
			}

			$this->adminManager->loadPlugins($this->dbExists);
		}
		elseif($this->isRpc())
		{
			require_once('RpcServer.php');
			$this->rpcServer = new RpcServer();
			$this->rpcServer->registerObjects();

			$this->siteManager = new Sitemanager();
			$this->siteManager->initialize();
			$this->tree = $this->siteManager->getTree();
			$this->siteTree = $this->tree;
		}
		elseif($this->isCli())
		{
			require_once('CliServer.php');
			$this->cliServer = new CliServer();
			$this->cliServer->registerObjects();

			$this->siteManager = new Sitemanager();
			$this->siteManager->initialize();
			$this->tree = $this->siteManager->getTree();
			$this->siteTree = $this->tree;
		}
		else
		{
			$this->siteManager = new Sitemanager();
			$this->siteManager->initialize();
			$this->tree = $this->siteManager->getTree();
			$this->siteTree = $this->tree;

			$themeClass = $this->siteManager->getThemeClassName();
			$this->theme = $this->themeManager->getTheme($themeClass);
			$this->theme->setTree($this->tree);

			// only load plugins that are connected to the tree item
			$this->siteManager->loadPlugins();
		}
	}

	/**
	 * main function
	 * This is the starting point of the request. Everything is being loaded from here.
	 * The database is initiated
	 * Type of request is being defined (admin section, GET, POST, CLI, XML RPC)
	 * Plugins are loaded and called
	 * Exceptions are cached and raised
	 * Theme is loaded and used to generate result
	 * Cache is checked and updated
	 * result string from Theme (or Cache) with page content is send back to client
	 *
	 * @return string
	 */
	public function main()
	{
		$this->dbExists = $this->hasDb();

		try 
		{
			// initialize system users
			$this->systemUser = new SystemUser();
			Authentication::registerUser($this->systemUser);

			$this->siteGroup = new SystemSiteGroup();

			$view = ViewManager::getInstance();
			$request = Request::getInstance();

			// always load extensions
			$this->extensionManager = new ExtensionManager();
			$this->extensionManager->loadExtensions();

			// initialize authentication
			Authentication::getInstance()->initialize();

			if($this->dbExists)
			{
				$cache = Cache::getInstance();
				if($cache->isCached()) 
				{
					// save view type
					$view->setType($view->getType());
					echo $cache->getCache();
					//echo "page cached";
					return;
				}
			}


			$this->initializeObjects();

			// always handle extensions
			$this->callObjectsImplementing('ExtensionProvider', 'handleRequest');

			if(isset($this->rpcServer))
			{
				//request is rpc request so dont do any html stuff
				$this->callObjectsImplementing('RpcProvider', 'registerRpcMethods', array($this->rpcServer));
				$this->rpcServer->handleRequest();
			}
			elseif(isset($this->cliServer))
			{
				//request is cli request so dont do any html stuff
				$this->callObjectsImplementing('CliProvider', 'handleCliRequest', array($this->cliServer));
				$this->cliServer->handleRequest();
			}
			else
			{
				//request is html request 
				$this->theme->handlePreProcessing($this);

				switch($request->getRequestType())
				{
					case Request::GET : $this->callObjectsImplementing('GuiProvider', 'handleHttpGetRequest'); break;
					case Request::POST : $this->callObjectsImplementing('GuiProvider', 'handleHttpPostRequest'); break;
				}

				$this->theme->handlePostProcessing();

				$this->callObjectsImplementing('GuiProvider', 'renderForm', array($this->theme));
				($this->isAdminSection()) ? $this->adminManager->handlePostProcessing($this->theme) : $this->siteManager->handlePostProcessing($this->theme);

				// before rendering the content, check if page exists. (the catch can fill the content with a nice error message)
				if($this->tree && !$this->tree->getCurrentId() && !$this->tree->currentIdExists() && !$this->tree->isSiteRoot()) throw new HttpException("404");

				// retrieve content
				$content =  $this->theme->fetchTheme();
				
				// save view type
				$view->setType($view->getType());
				
				// save page to cache
				//if($this->getConfig()->dsn && isset($cache))
				if($this->dbExists && isset($cache) && $cache->isCacheable()) $cache->save($content);
						

				// show content
				echo $content;
				//echo "page is generated.";
			}
		}
		catch(HttpException $e)
		{
			switch($e->getMessage())
			{
				default : Utils::handleHttp404($this->theme);
			}
			exit;
		}
		catch(DbException $e)
		{
			$this->theme = $this->themeManager->getTheme($this->getConfig()->admin_theme, true);
			$this->theme->handlePreProcessing($this);
			$this->theme->handlePostProcessing();
			Utils::handleDbError($this->theme);
			echo  $this->theme->fetchTheme();
		}
		catch(Exception $e)
		{
			die($e->getMessage());
		}
	}

	public function resetConfig()
	{
		$this->config = NULL;
	}

	public function setConfig(Config $config)
	{
		$this->config = $config;
	}

	public function getConfig()
	{
		if($this->config) return $this->config;

		// check if config file is available. If not try to copy the distribution file
		if(!file_exists(self::$configPath.$this->configfile) && file_exists(self::$configPath.$this->configfile."-dist"))
		{
			copy(self::$configPath.$this->configfile."-dist", self::$configPath.$this->configfile);
			chmod(self::$configPath.$this->configfile, 0600);
		}

		$this->config = new Config(self::$configPath, $this->configfile);
		return $this->config;
	}

	static public function getConfigPath()
	{
		return self::$configPath;
	}

	public function getSystemPath()
	{
		return DIF_SYSTEM_ROOT;
	}

	static public function getLogPath()
	{
		return DIF_SYSTEM_ROOT."log/";
	}

	/**
	 * Returns minimun version of DIF system that is required for a plugin, extension or theme to run 
	 * @return string
	 */
	public function getRequiredDifVersion()
	{
		return $this->getConfig()->required_dif_version;
	}

	/**
	 * Returns location of temporary directory
	 * This location is used by all kinds of plugins to store temporary generated files
	 * @return string
	 */
	public function getTempPath()
	{
		return DIF_SYSTEM_ROOT.$this->getConfig()->temp_path;
	}

	/**
	 * Returns location of import directory
	 * This direcotry is being used by all kinds of plugins
	 * @return string
	 */
	public function getImportPath()
	{
		return DIF_SYSTEM_ROOT.$this->getConfig()->import_path;
	}

	/**
	 * Returns location of import directory
	 * This direcotry is being used by all kinds of plugins
	 * @return string
	 */
	public function getContentPath($absolute=false)
	{
		$path = $this->getConfig()->content_path.'/'; 
		$retval = $absolute ? DIF_WEB_ROOT.$path : self::getWebRoot().$path;
		return $retval;
	}

	/**
	 * Returns location of import directory
	 * This direcotry is being used by all kinds of plugins
	 * @return string
	 */
	public function getCachePath($absolute=false)
	{
		$path = $this->getConfig()->cache_path.'/'; 
		$retval = $absolute ? DIF_WEB_ROOT.$path : self::getWebRoot().$path;
		return $retval;
	}

	/**
	 * Add observer object to notification list (not being used so far)
	 * @param Observer object
	 * @return void
	 */
	public function getObservers()
	{
		return $this->observer;
	}

	/**
	 * Add observer object to notification list (not being used so far)
	 * @param Observer object
	 * @return void
	 */
	public function attach($obj) 
	{
		$this->observer[$obj->getClassname()] = $obj;
	}

	/**
	 * Notify observers (not being used so far)
	 * @param Observer $obj
	 * @param integer $id
	 * @param integer $type type of event
	 * @return void
	 */
	public function notify($obj, $id, $type) 
	{
		foreach($this->observer as $ref)
		{
			$ref->onEvent($obj, $id, $type);
		}
	}


	/**
	 * Returns protected var {@link $observer} (not being used so far)
	 * @param string $key name of observed object
	 * @return array
	 */
	public function getObserver($key) 
	{
			if(array_key_exists($key, $this->observer)) return $this->observer[$key];
	}

	/**
	 * Calls plugins implementing an interface
	 *
	 * Interfaces are declared in {@link ClientPlugin}.
	 * @param string $interface type of interface the plugin has to implement
	 * @param string $functionName name of the function being called
	 * @param array $args optinal arguments
	 */
	public function callObjectsImplementing($interface, $functionName, $args=array()) 
	{
		$observerlist = array_keys($this->observer);

		foreach($observerlist as $key)
		{
			$object = $this->getObserver($key);
			if (!$object instanceof $interface) continue;
			call_user_func_array(array($object, $functionName), $args);
		}
	}

	/**
	 * Calls plugins implementing an interface
	 *
	 * Interfaces are declared in {@link ClientPlugin}.
	 * @param string $interface type of interface the plugin has to implement
	 * @param string $functionName name of the function being called
	 * @param array $args optinal arguments
	 */
	public function getObjectsImplementing($interface)
	{
		$retval = array();
		$observerlist = array_keys($this->observer);

		foreach($observerlist as $key)
		{
			$object = $this->getObserver($key);
			if (!$object instanceof $interface) continue;
			$retval[] = $object;
		}
		return $retval;
	}

	/**
	 * Check if user is in the admin section
	 * If so, special rules apply
	 *
	 * @see Request()
	 * @return boolean
	 */
	public function isAdminSection()
	{
		$request = Request::getInstance();
		return ($request->getRoot() == $this->getConfig()->admin_path);
	}

	/**
	 * Check if current request originates from XML RPC
	 * @see Request()
	 * @return boolean
	 */
	public function isRpc()
	{
		$request = Request::getInstance();
		return ($request->getRoot() == $this->getConfig()->rpc_path);
	}

	/**
	 * Check if current request originates from shell / cli 
	 * @see Request()
	 * @return boolean
	 */
	public function isCli()
	{
		$request = Request::getInstance();
		return $request->isCli();
	}

	/**
	 * Checks if database settings are set in config file
	 * and if the database actually exists
	 * @see getDb()
	 * @return boolean
	 */
	public function hasDb()
	{
		try{
			$db = $this->getDb();
		}
		catch(DbException $e)
		{
			return false;
		}
		return true;
	}

	/**
	 * Returns global reference to database object 
	 * Settings are retrieved from config file {@link $config}
	 *
	 * @see Pear::MDB2
	 * @return Pear::MDB2
	 */
	public function getDb()
	{
		if(isset($this->db)) return $this->db;

		$dsn = $this->getConfig()->dsn;
		$options = $this->getConfig()->dsnoptions;

		if(!$dsn) throw new DbException('DSN is missing');

		$tmpdb = MDB2::connect($dsn, $options);
		//$tmpdb = DebugDb::connect($dsn, $options);
		//Utils::checkDbError($this->db, 'Error connecting database');
		if (PEAR::isError($tmpdb)) 
		{
			$errorMsg = sprintf('Error connecting to database  Message: %s  Userinfo: %s', $tmpdb->getMessage(), $tmpdb->userinfo);
			throw new DbException($errorMsg);
		}

		//$this->db->setFetchMode(DB_FETCHMODE_ASSOC);
		$this->db = $tmpdb;
		return $this->db;
	}

	static public function setAppendDomain($append)
	{
		self::$append_domain = $append;
	}

	static public function getWebRoot()
	{
		$retval = DIF_VIRTUAL_WEB_ROOT;

		if(self::$append_domain)
		{
			$request = Request::getInstance();
			$retval = $request->getProtocol().$request->getDomain().$retval;
		}

		return $retval;
	}
}

?>
