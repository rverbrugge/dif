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
class ExtensionHandler extends ExtensionManager implements RpcProvider, GuiProvider
{
	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;
	private $renderExtension;

	private $pagesize = 20;

	const VIEW_EXT = 'e1';
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
		$this->templateFile = "extensionhandler.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";
		$this->renderExtension = false;
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
		return DIF_VIRTUAL_WEB_ROOT."extensions/".$this->getClassName()."/htdocs/";
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
			case ViewManager::ADMIN_OVERVIEW : $this->handleAdminOverview(); break;
			case ViewManager::ADMIN_NEW : $this->handleAdminNewPost(); break;
			case ViewManager::ADMIN_EDIT : $this->handleAdminEditPost(); break;
			case ViewManager::ADMIN_DELETE : $this->handleAdminDeletePost(); break;
			case self::VIEW_UPDATE : $this->handleAdminUpdatePost(); break;
			default : $this->handleExtensionPost(); break;
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
			case self::VIEW_UPDATE : $this->handleAdminUpdateGet(); break;
			default : $this->handleExtensionGet(); break;
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

		$url_extension = clone $url;
		$url_extension->setParameter($view->getUrlId(), self::VIEW_EXT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::ADMIN_DELETE);

		$searchcriteria = array();
		$list = $this->getList(NULL, $this->pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_extension->setParameter('ext_id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_extension'] = $url_extension->getUrl(true);
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

		$view = ViewManager::getInstance();

		$url = new Url(true);

		$breadcrumb = array('name' => $view->getName(), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);
		$template->setVariable('id',  '', false);

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

		if(!$request->exists('id')) throw new Exception('Extension ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		if($retrieveFields)
		{
			$this->setFields($this->getDetail(array('id' => $id)));
		}

		$fields = $this->getFields(SqlParser::MOD_UPDATE);
		$template->setVariable($fields, NULL, false);

		$view = ViewManager::getInstance();

		$url = new Url(true);

		$breadcrumb = array('name' => $view->getName(), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminEditPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			if(!$request->exists('id')) throw new Exception('Extension ontbreekt.');
			$id = intval($request->getValue('id'));

			$debug = $this->install($values, array('id' => $id));

			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$view = ViewManager::getInstance();
			$view->setType(self::VIEW_SUCCESS);

			$url = new Url(true);
			$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
			$template->setVariable('href_back',  $url->getUrl(true), false);

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

		if(!$request->exists('id')) throw new Exception('Gebruikersgroep ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		$template->setVariable('name',  $this->getName(array('id' => $id)), false);

		$view = ViewManager::getInstance();

		$url = new Url(true);

		$breadcrumb = array('name' => $view->getName(), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminDeletePost()
	{
		$request = Request::getInstance();

		try 
		{
			if(!$request->exists('id')) throw new Exception('Gebruikersgroep ontbreekt.');
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
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$importPath = realpath($this->director->getImportPath());
		$template->setVariable('import_path',  $importPath, false);

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminUpdatePost()
	{
		$request = Request::getInstance();

		try 
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

			$debug = $this->updateExtensions();

			$view = ViewManager::getInstance();
			$view->setType(self::VIEW_SUCCESS);

			$url = new Url(true);
			$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
			$template->setVariable('href_back',  $url->getUrl(true), false);

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

/*------- extension request {{{ -------*/
	/**
	 * handle extension
	*/
	private function handleExtensionGet()
	{
		$template = new TemplateEngine();
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$this->renderExtension = true;

		if(!$request->exists('ext_id')) throw new Exception('Extension ontbreekt.');
		$id = intval($request->getValue('ext_id'));
		$template->setVariable('ext_id',  $id, false);

		$url = new Url(true);
		$url_back = clone($url);
		$url_back->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$url_back->clearParameter('ext_id');

		$url_detail = clone($url);
		$url_detail->setParameter($view->getUrlId(), self::VIEW_EXT);
		$url_detail->setParameter('ext_id', $id);

		$extension = $this->director->extensionManager->getExtensionFromId(array('id' => $id));
		$extension->setReferer($this);

		$this->director->theme->handleAdminLinks($template, $this->getName(array('id' => $id)), $url_detail);

		$extension->handleHttpGetRequest();
	}

	private function handleExtensionPost()
	{
		$request = Request::getInstance();
		$template = new TemplateEngine();
		$view = ViewManager::getInstance();

		$this->renderExtension = true;

		if(!$request->exists('ext_id')) throw new Exception('Extension ontbreekt.');
		$id = intval($request->getValue('ext_id'));
		$template->setVariable('ext_id',  $id, false);

		$url = new Url(true);

		$url_back = clone($url);
		$url_back->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$url_back->clearParameter('ext_id');

		$extension = $this->director->extensionManager->getExtensionFromId(array('id' => $id));
		$extension->setReferer($this);

		$this->director->theme->handleAdminLinks($template, $this->getName(array('id' => $id)), $url_detail);

		$extension->handleHttpPostRequest();
	} 

	private function handleExtensionForm($theme)
	{
		$request = Request::getInstance();

		try 
		{
			if(!$request->exists('ext_id')) throw new Exception('Extension ontbreekt.');
			$id = intval($request->getValue('ext_id'));

			$extension = $this->director->extensionManager->getExtensionFromId(array('id' => $id));
			$extension->renderForm($theme);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
		}

	} 
//}}}

/*----- update extensions {{{ -------*/
	private function updateExtensions()
	{
		$extensionManager = $this->director->extensionManager;
		$settings = array('active' => 1);
		$tempPath = realpath($this->director->getImportPath());
		$logging = array();

		$dh = dir($tempPath);
		while(FALSE !== ($entry = $dh->read()))
		{
			$file = $tempPath.'/'.$entry;
			if(Utils::getExtension($file) != 'gz') continue;

			try
			{
				$extensionManager->extractExtension($tempPath, $file);
				$logging = array_merge($logging, $extensionManager->installExtension($tempPath, $settings, NULL));
			}
			catch(Exception $err)
			{
				$logging[] = $err->getMessage();
			}
		}
		$dh->close();

		// try to link plugins that are uploaded but not in the database
		$tempPath = $this->getExtensionPath();
		$dh = dir($tempPath);
		while(FALSE !== ($entry = $dh->read()))
		{
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

	/*-------- render form {{{--------------*/
	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 */
	public function renderForm($theme)
	{
		//handle extension form 
		if($this->renderExtension)
		{
			$this->handleExtensionForm($theme);
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
		try
		{
			$extension = $this->director->extensionManager->getExtensionFromId(array('id' => $id));
			return $extension->getTypeList();
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
