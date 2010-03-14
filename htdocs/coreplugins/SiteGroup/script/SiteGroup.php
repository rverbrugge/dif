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
class SiteGroup extends SystemSiteGroup implements GuiProvider
{

	const NEWSITE_SUCCESS = 'ns1';

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	private $pagesize = 20;

	public function __construct()
	{
		parent::__construct();
		$this->template = array();
		$this->templateFile = "sitegroup.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";
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
		return ($absolute) ? $this->getPath()."htdocs/" : DIF_VIRTUAL_WEB_ROOT."coreplugins/".$this->getClassName()."/htdocs/";
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
			default : $this->handleOverview(); break;
		}

	} 

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$request = Request::getInstance();
		$viewManager = ViewManager::getInstance();

		if($viewManager->isType(ViewManager::OVERVIEW) && $this->director->isAdminSection()) 
			$viewManager->setType(ViewManager::ADMIN_OVERVIEW);

		switch($viewManager->getType())
		{
			case ViewManager::ADMIN_OVERVIEW : $this->handleAdminOverview(); break;
			case ViewManager::ADMIN_NEW : $this->handleAdminNewGet(); break;
			case ViewManager::ADMIN_EDIT : $this->handleAdminEditGet(); break;
			case ViewManager::ADMIN_DELETE : $this->handleAdminDeleteGet(); break;
			default : $this->handleOverview(); break;
		}

	}
//}}}

/*------- overview request {{{ -------*/
	/**
	 * handle overview request
	*/
	private function handleOverview()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$page = $this->getPage();
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		$searchcriteria = array();
		$list = $this->getList(NULL, $this->pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$item['type_name'] = $this->getTypeDesc($item['type']);
		}

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('list',  $list, false);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} //}}}

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

		// check if there are sites. If not, redirect to new site script
		if(!$this->exists(NULL))
		{
			$view->setType(ViewManager::ADMIN_NEW);
			$this->handleHttpGetRequest();
		}

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$url = new Url(true);
		$url->clearParameter('id');

		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::ADMIN_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::ADMIN_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::ADMIN_DELETE);

		$searchcriteria = array();
		$list = $this->getList(NULL, $this->pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
			$item['language_name'] = $this->getLanguageDesc($item['language']);
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
		$template->setVariable('cbo_language', Utils::getHtmlCombo($this->getLanguageList(), $fields['language']), false);

		$view = ViewManager::getInstance();

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);
		$template->setVariable('id',  '', false);
		$template->setVariable('newsite',  !$this->exists(array()), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminNewPost()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			$newsite = !$this->exists(NULL);

			$this->insert($values);

			if($newsite)
			{
				$view->setType(self::NEWSITE_SUCCESS);
				$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

				// get link to site menu
				$admintree = $this->director->adminManager->tree;

				$href_site = $admintree->getPath($admintree->getIdFromClassname('Site'));
				$template->setVariable('href_site',  $href_site, false);

				$href_theme = $admintree->getPath($admintree->getIdFromClassname('ThemeHandler'));
				$template->setVariable('href_theme',  $href_theme, false);

				$this->template[$this->director->theme->getConfig()->main_tag] = $template;
			}
			else
			{
				$view->setType(ViewManager::ADMIN_OVERVIEW);
				$this->handleAdminOverview();
			}
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

		if(!$request->exists('id')) throw new Exception('Gebruikersgroep ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		if($retrieveFields)
		{
			$this->setFields($this->getDetail(array('id' => $id)));
		}

		$fields = $this->getFields(SqlParser::MOD_UPDATE);
		$template->setVariable($fields, NULL, false);
		$template->setVariable('cbo_language', Utils::getHtmlCombo($this->getLanguageList(), $fields['language']), false);

		$view = ViewManager::getInstance();

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Gebruiker ontbreekt.');
			$id = intval($request->getValue('id'));

			$this->update(array('id' => $id), $values);
			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);;
			$this->handleAdminOverview();
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

	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 */
	public function renderForm($theme)
	{
		$template = $theme->getTemplate();

		$theme->addJavascript(file_get_contents($this->getHtdocsPath(true).'sync.js'));

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}

}

?>
