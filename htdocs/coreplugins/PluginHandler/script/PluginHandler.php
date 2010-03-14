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
class PluginHandler extends PluginManager implements RpcProvider, GuiProvider
{
	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	private $pagesize = 20;

	const VIEW_PLUGIN = 100;
	const VIEW_PLUGIN_KEY = 'pl';
	const VIEW_SUCCESS = 's1';
	const VIEW_UPDATE = 'u1';

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
		$this->template = array();
		$this->templateFile = "plugins.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_PLUGIN, 'Plugins');
		$view->insert(self::VIEW_PLUGIN_KEY, 'Plugin key');
		$view->insert(self::VIEW_SUCCESS, 'Update succesful');
		$view->insert(self::VIEW_UPDATE, 'Update');
	}

	private function getPath()
	{
		return $this->basePath;
	}

	/**
	* returns the absolute htdocs web path eg. /themes/foobar/htdocs/
	* @return string name of theme
	*/
	public function getHtdocsPath($absolute=false)
	{
		return DIF_VIRTUAL_WEB_ROOT."coreplugins/".$this->getClassName()."/htdocs/";
	}

/*----- handle http requests {{{ -------*/
	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case ViewManager::ADMIN_NEW : $this->handleAdminNewPost(); break;
			case ViewManager::ADMIN_EDIT : $this->handleAdminEditPost(); break;
			case ViewManager::ADMIN_DELETE : $this->handleAdminDeletePost(); break;
			case ViewManager::CONF_OVERVIEW : 
			case ViewManager::CONF_NEW : 
			case ViewManager::CONF_EDIT : 
			case ViewManager::CONF_DELETE : $this->handlePluginPost(); break;
			case self::VIEW_UPDATE : $this->handleAdminUpdatePost(); break;
			default : $this->handleAdminOverview(); break;
		}

	} 

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$viewManager = ViewManager::getInstance();

		if($viewManager->isType(ViewManager::OVERVIEW) && $this->director->isAdminSection()) 
			$viewManager->setType(ViewManager::ADMIN_OVERVIEW);

		switch($viewManager->getType())
		{
			case ViewManager::ADMIN_OVERVIEW : $this->handleAdminOverview(); break;
			case ViewManager::ADMIN_NEW : $this->handleAdminNewGet(); break;
			case ViewManager::ADMIN_EDIT : $this->handleAdminEditGet(); break;
			case ViewManager::ADMIN_DELETE : $this->handleAdminDeleteGet(); break;
			case ViewManager::CONF_OVERVIEW : 
			case ViewManager::CONF_NEW : 
			case ViewManager::CONF_EDIT : 
			case ViewManager::CONF_DELETE : $this->handlePluginGet(); break;
			case self::VIEW_UPDATE : $this->handleAdminUpdateGet(); break;
			default : $this->handleAdminOverview(); break;
		}
	}
//}}}

/*------- admin overview request {{{ -------*/
	/**
	 * handle admin overview request
	*/
	private function handleAdminOverview()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$page = $this->getPage();
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$url = new Url(true);
		$url->clearParameter('id');

		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::ADMIN_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_update = clone $url;
		$url_update->setParameter($view->getUrlId(), self::VIEW_UPDATE);
		$template->setVariable('href_update',  $url_update->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::ADMIN_EDIT);

		$url_plugin = clone $url;
		$url_plugin->setParameter($view->getUrlId(), ViewManager::CONF_OVERVIEW);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::ADMIN_DELETE);

		$searchcriteria = array();
		$list = $this->getList(NULL, $this->pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_plugin->setParameter('plug_id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_plugin'] = $url_plugin->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
		}
		$template->setVariable('list',  $list, false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} 
//}}}

/*------- new request {{{ -------*/
	/**
	 * handle new
	*/
	private function handleAdminNewGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$template->setVariable($fields, NULL, false);

		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminNewPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			$this->install($values);
			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);;
			$this->handleAdminOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminNewGet();
		}
	} 
//}}}

/*------- edit request {{{ -------*/
	/**
	 * handle edit
	*/
	private function handleAdminEditGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();

		if(!$request->exists('id')) throw new Exception('Plugin is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		$key = array('id' => $id);
		if(!$this->exists($key)) throw new Exception("Plugin does not exist.");

		if($retrieveFields)
		{
			$this->setFields($this->getDetail($key));
		}

		$fields = $this->getFields(SqlParser::MOD_UPDATE);
		$template->setVariable($fields, NULL, false);

		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminEditPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			if(!$request->exists('id')) throw new Exception('Plugin ontbreekt.');
			$id = intval($request->getValue('id'));

			$debug = $this->install($values, array('id' => $id));

			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$view = ViewManager::getInstance();
			$view->setType(self::VIEW_SUCCESS);

			$this->director->theme->handleAdminLinks($template);

			$template->setVariable('debug', is_array($debug) ? join('<br />', $debug) : '');
			$this->template[$this->director->theme->getConfig()->main_tag] = $template;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminEditGet(false);
		}

	} 
//}}}

/*------- delete request {{{ -------*/
	/**
	 * handle delete
	*/
	private function handleAdminDeleteGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();

		if(!$request->exists('id')) throw new Exception('Plugin is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		$template->setVariable('name',  $this->getName(array('id' => $id)), false);

		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminDeletePost()
	{
		$request = Request::getInstance();

		try 
		{
			if(!$request->exists('id')) throw new Exception('Plugin is missing.');
			$id = intval($request->getValue('id'));

			$this->delete(array('id' => $id));
			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);;
			$this->handleAdminOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminDeleteGet();
		}

	} 
//}}}


/*------- update request {{{ -------*/
	/**
	 * handle delete
	*/
	private function handleAdminUpdateGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$importPath = realpath($this->director->getImportPath());
		$template->setVariable('import_path',  $importPath, false);

		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminUpdatePost()
	{
		$request = Request::getInstance();

		try 
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

			$debug = $this->updatePlugins();

			$view = ViewManager::getInstance();
			$view->setType(self::VIEW_SUCCESS);

			$this->director->theme->handleAdminLinks($template);

			$template->setVariable('debug', is_array($debug) ? join('<br />', $debug) : '');
			$this->template[$this->director->theme->getConfig()->main_tag] = $template;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminUpdateGet();
		}

	} 
//}}}

/*------- plugin request {{{ -------*/
	/**
	 * handle plugin
	*/
	private function handlePluginGet()
	{
		$template = new TemplateEngine();
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if(!$request->exists('plug_id')) throw new Exception('Plugin ontbreekt.');
		$id = intval($request->getValue('plug_id'));
		$template->setVariable('plug_id',  $id, false);

		$plugin = $this->director->pluginManager->getPluginFromId(array('id' => $id));

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$url->clearParameter('plug_id');
		$this->director->theme->handleAdminLinks($template, $this->getName(array('id' => $id)), $url);

		$plugin->setReferer($this);
		$plugin->handleHttpGetRequest();
	}

	private function handlePluginPost()
	{
		$request = Request::getInstance();
		$template = new TemplateEngine();
		$view = ViewManager::getInstance();

		if(!$request->exists('plug_id')) throw new Exception('Plugin ontbreekt.');
		$id = intval($request->getValue('plug_id'));
		$template->setVariable('plug_id',  $id, false);

		$plugin = $this->director->pluginManager->getPluginFromId(array('id' => $id));

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$url->clearParameter('plug_id');
		$this->director->theme->handleAdminLinks($template, $this->getName(array('id' => $id)), $url);

		$plugin->setReferer($this);
		$plugin->handleHttpPostRequest();
	} 

	private function handlePluginForm($theme)
	{
		$request = Request::getInstance();

		try 
		{
			if(!$request->exists('plug_id')) throw new Exception('Plugin ontbreekt.');
			$id = intval($request->getValue('plug_id'));

			$plugin = $this->director->pluginManager->getPluginFromId(array('id' => $id));
			$plugin->renderForm($theme);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
		}

	} 
//}}}

/*----- update plugins {{{ -------*/
	private function updatePlugins()
	{
		$pluginManager = $this->director->pluginManager;
		$settings = array('active' => 1);
		$tempPath = realpath($this->director->getImportPath());
		$logging = array();

		// try to import plugin packages
		$dh = dir($tempPath);
		while(FALSE !== ($entry = $dh->read()))
		{
			$file = $tempPath.'/'.$entry;
			if(Utils::getExtension($file) != 'gz') continue;

			try
			{
				$pluginManager->extractPlugin($tempPath, $file);
				$logging = array_merge($logging, $pluginManager->installPlugin($tempPath, $settings, NULL));
			}
			catch(Exception $err)
			{
				$logging[] = $err->getMessage();
			}
		}
		$dh->close();

		// try to link plugins that are uploaded but not in the database
		$tempPath = $this->getPluginPath();
		$dh = dir($tempPath);
		while(FALSE !== ($entry = $dh->read()))
		{
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

	/*-------- render form {{{--------------*/
	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 */
	public function renderForm($theme)
	{
		//handle plugin form 
		$request = Request::getInstance();
		if($request->exists(self::VIEW_PLUGIN_KEY))
		{
			$this->handlePluginForm($theme);
		}

		$template = $theme->getTemplate();

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
//}}}


	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function getTypeList($id)
	{
		if(!$id) return;
		
		try
		{
			$plugin = $this->director->pluginManager->getPluginFromId(array('id' => $id));
			return $plugin->getTypeList();
		}
		catch(Exception $e)
		{
			return $e->getMessage();
		}
	}

	public function handleRpcRequest($method_name, $params, $app_data)
	{
		list($class,$method) = explode('.',$method_name);

		return $this->$method($params[0]);
	}


	/**
	 * registers xml rpc functions
	 * @see RpcProvider::registerRpcMethods
	 */
	public function registerRpcMethods(RpcServer $rpcServer)
	{
		//xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getTypeList", array(&$this,'getTypeList'));
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getTypeList", array(&$this,'handleRpcRequest'));
	}

}

?>
