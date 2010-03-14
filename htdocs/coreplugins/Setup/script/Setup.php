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
class Setup extends Observer implements GuiProvider
{
	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	private $configValues;
	private $configFile;

	public function __construct()
	{
		parent::__construct();
		$this->template = array();
		$this->templateFile = "setup.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$config = $this->director->getConfig();
		$this->configFile 	= $config->getFile();
		$this->configValues = $config->getArray();

		$this->log = Logger::getInstance();
	}

	private function getPath()
	{
		return $this->basePath;
	}

	/**
	 * returns the absolute htdocs path 
	 * absolute parameter specifies which path has to be retrieved:
	 * if absolute is true, it returns the absolute system path. eg. /var/www/site/themes/foobar/htdocs/
	 * if false it returns the absolute web path eg. /themes/foobar/htdocs/
	 *
	 * @param boolean specifies which path has to be retrieved, default false
	 * @return string path to htdocs directory
	 */
	public function getHtdocsPath($absolute=false)
	{
		return ($absolute) ? $this->getPath()."htdocs/" : DIF_VIRTUAL_WEB_ROOT."coreplugins/{$this->getClassName()}/htdocs/";
	}

/*----- handle http requests {{{ -------*/
	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		// if a database is entered, it means this is not the first visist to this script. 
		// therefore only enable settings if they were already set
		$dbExists = array_key_exists('dsn', $this->configValues) && $this->configValues['dsn'];

		$request = Request::getInstance();
		$plugin = (!$dbExists && $request->getRequestType() == Request::GET) || $request->exists('plugin');
		$theme = (!$dbExists && $request->getRequestType() == Request::GET) || $request->exists('theme');
		$extension = (!$dbExists && $request->getRequestType() == Request::GET) || $request->exists('extension');
		$caching = (!$dbExists && $request->getRequestType() == Request::GET) || $request->exists('caching') || (array_key_exists('enable_caching', $this->configValues) && $this->configValues['enable_caching']);

		if(!$dbExists && !(array_key_exists('email_address', $this->configValues) && $this->configValues['email_address']) && $request->getRequestType() == Request::GET)
		{
			$domain = $request->getDomain();
			$email = substr($domain, 0, 4) == 'www.' ? substr($domain, 4, strlen($domain)) : $domain;
			$this->configValues['email_address'] = 'info@'.$email;
		}

		if(!$dbExists && !(array_key_exists('admin_section_ip_allow', $this->configValues) && $this->configValues['admin_section_ip_allow']) && $request->getRequestType() == Request::GET)
		{
			$this->configValues['admin_section_ip_allow'] = $request->getValue('REMOTE_ADDR', Request::SERVER);
		}

		$template->setVariable('dsn', array_key_exists('dsn', $this->configValues) ? $this->configValues['dsn'] : '');
		$template->setVariable('username', array_key_exists('login_username', $this->configValues) ? $this->configValues['login_username'] : '');

		$template->setVariable('admin_section_ip_allow', array_key_exists('admin_section_ip_allow', $this->configValues) ? $this->configValues['admin_section_ip_allow'] : '');
		$template->setVariable('email_address', array_key_exists('email_address', $this->configValues) ? $this->configValues['email_address'] : '');
		$template->setVariable('email_host', array_key_exists('email_host', $this->configValues) ? $this->configValues['email_host'] : '');
		$template->setVariable('email_username', array_key_exists('email_username', $this->configValues) ? $this->configValues['email_username'] : '');
		$template->setVariable('email_password', array_key_exists('email_password', $this->configValues) ? $this->configValues['email_password'] : '');

		$template->setVariable('plugin',  $plugin, false);
		$template->setVariable('theme',  $theme, false);
		$template->setVariable('extension',  $extension, false);
		$template->setVariable('caching',  $caching, false);
		$template->setVariable('pageTitle', 'Welcome to DIF, the Dynamic Information Framework!');

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$request = Request::getInstance();

		try 
		{
			if(!$request->exists('dsn')) throw new Exception('DSN is missing.');
			if(!$request->exists('username')) throw new Exception('Username is missing.');

			$sqlfile = DIF_ROOT.'sql/dif.sql';
			if(!file_exists($sqlfile)) throw new Exception('Database file is missing');

			$this->configValues['login_username'] = $request->getValue('username');

			if($request->getValue('password'))
			{
				if($request->getValue('password') != $request->getValue('password1')) throw new Exception('Passwords do not match.'); 
				$this->configValues['login_password'] = crypt($request->getValue('password'));
				$this->log->info("change administration password");
			}

			$this->configValues['enable_caching'] = $request->exists('caching') ? true : false;

			$this->configValues['admin_section_ip_allow'] = $request->getValue('admin_section_ip_allow');
			$this->configValues['email_address'] 	= $request->getValue('email_address');
			$this->configValues['email_host'] 		= $request->getValue('email_host');
			$this->configValues['email_username'] = $request->getValue('email_username');
			$this->configValues['email_password'] = $request->getValue('email_password');

			$this->configValues['dsn'] = $request->getValue('dsn');
			$config = new Config();
			$config->setArray($this->configValues);
			$this->director->setConfig($config);

			// log actions
			$this->log->info("set administration username to {$this->configValues['login_username']}");
			$this->log->info("set DSN to {$this->configValues['dsn']}");
			$this->log->info("set caching to {$this->configValues['enable_caching']}");

			// insert or update dif tables
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

			Utils::storeIniValues($this->configFile, $this->configFile, $this->configValues);

			$logging = array();
			// install plugins
			if($request->exists('plugin')) $logging = $this->installPlugins();

			// install themes
			if($request->exists('theme')) array_merge($logging, $this->installThemes());

			// install extensions
			if($request->exists('extension')) array_merge($logging, $this->installExtensions());

			if($logging) throw new Exception(join("<br />", $logging));
			

			// if virtual webroot is set, create symlinks to global directories
			/*TODO remove because new version uses copies instead of symlinks
			if(strlen(DIF_VIRTUAL_WEB_ROOT) > 1)
			{
				if(!file_exists(DIF_INDEX_ROOT.'/images'))
					symlink(DIF_WEB_ROOT.'images', DIF_INDEX_ROOT.'images');

				if(!file_exists(DIF_INDEX_ROOT.'/userfiles'))
					symlink(DIF_WEB_ROOT.'userfiles', DIF_INDEX_ROOT.'userfiles');

				if(!file_exists(DIF_INDEX_ROOT.'/js'))
					symlink(DIF_WEB_ROOT.'js', DIF_INDEX_ROOT.'js');

				if(!file_exists(DIF_INDEX_ROOT.'/css'))
					symlink(DIF_WEB_ROOT.'css', DIF_INDEX_ROOT.'css');
			}
			*/

			// redirect to Site
			$admintree = $this->director->adminManager->tree;
			$sitePath = $admintree->getPath($admintree->getIdFromClassname('Site'));
			header("Location: $sitePath");
			exit;

		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->log->error($e->getMessage());
			$this->handleHttpGetRequest();
		}
	}

//}}}

/*----- install plugins {{{ -------*/
	private function installPlugins()
	{
		$pluginManager = $this->director->pluginManager;
		$settings = array('active' => 1);
		$setupPath =  DIF_SYSTEM_ROOT.$this->director->getConfig()->file_path."/".strtolower($this->getClassName()).'/plugin/';
		$tempPath = realpath($this->director->getTempPath());
		$logging = array();

		if(is_dir($setupPath))
		{
			$dh = dir($setupPath);
			while(FALSE !== ($entry = $dh->read()))
			{
				$file = $setupPath.$entry;
				$tempFile = "$tempPath/$entry";
				if(Utils::getExtension($file) != 'gz') continue;

				try
				{
					copy($file, $tempFile);
					$pluginManager->extractPlugin($tempPath, $tempFile);
					$logging = array_merge($logging, $pluginManager->installPlugin($tempPath, $settings, NULL));
				}
				catch(Exception $err)
				{
					$this->log->error($err->getMessage());
					$logging[] = $err->getMessage();
				}
			}
			$dh->close();
		}
		else
		{
			$this->log->error("$setupPath is missing.");
		}


		// try to link plugins that are uploaded but not in the database
		$tempPath = $pluginManager->getPluginPath();
		$dh = dir($tempPath);
		while(FALSE !== ($entry = $dh->read()))
		{
			if($entry == "." || $entry == "..") continue;

			$pluginDir = $tempPath.'/'.$entry.'/script';

			if(!$pluginManager->isPlugin($pluginDir)) continue;
			
			try
			{
				$logging = array_merge($logging, $pluginManager->installPlugin($pluginDir, $settings, NULL, PluginManager::ACTION_UPDATE));
			}
			catch(Exception $err)
			{
				$logging[] = $err->getMessage();
			}
		}
		$dh->close();

		return $logging;
	}
	//}}}

/*----- install themes {{{ -------*/
	private function installThemes()
	{
		$themeHandler = $this->director->adminManager->getPlugin('ThemeHandler');
		$settings = array('active' => 1);
		$settings['content_path'] = $themeHandler->getContentPath(true);

		$setupPath =  DIF_SYSTEM_ROOT.$this->director->getConfig()->file_path."/".strtolower($this->getClassName()).'/theme/';
		$tempPath = realpath($this->director->getTempPath());
		$logging = array();

		if(is_dir($setupPath))
		{
			$dh = dir($setupPath);
			while(FALSE !== ($entry = $dh->read()))
			{
				$file = $setupPath.$entry;
				$tempFile = "$tempPath/$entry";
				if(Utils::getExtension($file) != 'gz') continue;

				try
				{
					copy($file, $tempFile);
					$themeHandler->extractTheme($tempPath, $tempFile);
					$logging = array_merge($logging, $themeHandler->installTheme($tempPath, $settings, NULL));
				}
				catch(Exception $err)
				{
					$this->log->error($err->getMessage());
					$logging[] = $err->getMessage();
				}
			}
			$dh->close();
		}
		else
		{
			$this->log->error("$setupPath is missing.");
		}


		// try to link plugins that are uploaded but not in the database
		$tempPath = $themeHandler->getThemePath();
		$dh = dir($tempPath);
		while(FALSE !== ($entry = $dh->read()))
		{
			if($entry == "." || $entry == "..") continue;

			$themeDir = $tempPath.'/'.$entry.'/script';

			if(!$themeHandler->isTheme($themeDir)) continue;
			
			try
			{
				$logging = array_merge($logging, $themeHandler->installTheme($themeDir, $settings, NULL, ThemeManager::ACTION_UPDATE));
			}
			catch(Exception $err)
			{
				$logging[] = $err->getMessage();
			}
		}
		$dh->close();

		return $logging;
	}
	//}}}

/*----- install extensions {{{ -------*/
	private function installExtensions()
	{
		$extensionManager = $this->director->extensionManager;
		$settings = array('active' => 1);
		$setupPath =  DIF_SYSTEM_ROOT.$this->director->getConfig()->file_path."/".strtolower($this->getClassName()).'/extension/';
		$tempPath = realpath($this->director->getTempPath());
		$logging = array();

		if(is_dir($setupPath))
		{
			$dh = dir($setupPath);
			while(FALSE !== ($entry = $dh->read()))
			{
				$file = $setupPath.$entry;
				$tempFile = "$tempPath/$entry";
				if(Utils::getExtension($file) != 'gz') continue;

				try
				{
					copy($file, $tempFile);
					$extensionManager->extractExtension($tempPath, $tempFile);
					$logging = array_merge($logging, $extensionManager->installExtension($tempPath, $settings, NULL));
				}
				catch(Exception $err)
				{
					$this->log->error($err->getMessage());
					$logging[] = $err->getMessage();
				}
			}
			$dh->close();
		}
		else
		{
			$this->log->error("$setupPath is missing.");
		}


		// try to link plugins that are uploaded but not in the database
		$tempPath = $extensionManager->getExtensionPath();
		$dh = dir($tempPath);
		while(FALSE !== ($entry = $dh->read()))
		{
			if($entry == "." || $entry == "..") continue;

			$extensionDir = $tempPath.'/'.$entry.'/script';

			if(!$extensionManager->isExtension($extensionDir)) continue;
			
			try
			{
				$logging = array_merge($logging, $extensionManager->installExtension($extensionDir, $settings, NULL, ExtensionManager::ACTION_UPDATE));
			}
			catch(Exception $err)
			{
				$logging[] = $err->getMessage();
			}
		}
		$dh->close();

		return $logging;
	}
	//}}}

	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 */
	public function renderForm($theme)
	{
		$template = $theme->getTemplate();

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}

}

?>
