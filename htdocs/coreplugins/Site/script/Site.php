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
class Site extends SystemSite implements RpcProvider, GuiProvider
{
	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	private $pagesize = 20;

	/**
	 * current tree node id to highlight node in menu
	 * @var integer
	 */
	private $menuTreeId;

	/**
	 * user defined file variables to parse with theme fileVars
	 * @var array
	 */
	private $fileVars = array();

	/**
	 * extra view types
	 */
	const VIEW_THEME = 'st1';
	const VIEW_PLUGIN_CONF = 'st2';
	const VIEW_PLUGIN_DEL = 'st3';
	const VIEW_PLUGIN_MOVE = 'st9';
	const VIEW_TAG = 'st4';
	const VIEW_TAG_EDIT = 'st5';
	const VIEW_TAG_DEL = 'st6';
	const VIEW_MV_PREC = 'st7';
	const VIEW_MV_FOL = 'st8';
	const VIEW_MV_UP = 'st11';
	const VIEW_MV_DOWN = 'st12';
	const VIEW_ROOT_ACL = 'st10';

	public function __construct()
	{
		parent::__construct();
		$this->template = array();
		$this->templateFile = "site.tpl";

		$this->basePath = realpath(dirname(__FILE__)."/../")."/";
		$this->initialize();
	}

	private function initialize()
	{
		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_THEME, 'Theme settings');
		$view->insert(self::VIEW_PLUGIN_CONF, 'Add Plugin');
		$view->insert(self::VIEW_PLUGIN_DEL, 'Delete plugin');
		$view->insert(self::VIEW_PLUGIN_MOVE, 'Move plugin');
		$view->insert(self::VIEW_TAG, 'Tags');
		$view->insert(self::VIEW_TAG_EDIT, 'Edit Tag');
		$view->insert(self::VIEW_TAG_DEL, 'Delete tag');
		$view->insert(self::VIEW_MV_PREC, 'Move to previous node');
		$view->insert(self::VIEW_MV_FOL, 'Move to next node');
		$view->insert(self::VIEW_MV_UP, 'Move one level up');
		$view->insert(self::VIEW_MV_DOWN, 'Move one level down');
		$view->insert(self::VIEW_ROOT_ACL, 'Edit default Acl');
	
		// check if any site is initialized. if not, redirect to sitegroup page
		$siteGroup = $this->getSiteGroup();
		$sitegroupId = $siteGroup->getCurrentId();
		if(!isset($sitegroupId))
		{
			$admin = $this->director->adminManager;
			if(!$admin) throw new Exception('Admin section not loaded? this should not happen.');
			$admintree = $admin->tree;
			if(!$admintree) throw new Exception('Admin tree not loaded? this should not happen.');

			$sitegroupPath = $admintree->getPath($admintree->getIdFromClassname('SiteGroup'));
			header("Location: $sitegroupPath");
			exit;
		}

		// set current tree node id
		$request = Request::getInstance();
		$currentId = (!$request->exists('tree_id') || intval($request->getValue('tree_id')) < 1) ? $this->tree->getRootId() : intval($request->getValue('tree_id'));
		$this->setMenuId($currentId);
	}

	private function setMenuId($id)
	{
		$this->menuTreeId = $id;
	}

	private function getMenuId()
	{
		return $this->menuTreeId;
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

	/**
	 * returns the absolute htdocs path 
	 * absolute parameter specifies which path has to be retrieved:
	 * if absolute is true, it returns the absolute system path. eg. /var/www/site/themes/foobar/htdocs/
	 * if false it returns the absolute web path eg. /themes/foobar/htdocs/
	 *
	 * @param boolean specifies which path has to be retrieved, default false
	 * @return string path to htdocs directory
	 */
	public function getContentPath($absolute=false)
	{
		$path =  $this->director->getContentPath($absolute).strtolower($this->getClassName())."/";
		if($absolute && !is_dir($path))
		{
			if(!mkdir($path, 0775)) throw new Exception("Error creating direcotry ".$path);
		}
		return $path;
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
	public function getCachePath($absolute=false)
	{
		$path =  $this->director->getCachePath($absolute).strtolower($this->getClassName())."/";
		if($absolute && !is_dir($path))
		{
			if(!mkdir($path, 0775)) throw new Exception("Error creating direcotry ".$path);
		}
		return $path;
	}

	private function getSafeTreeList($tree_id=null)
	{
		//$parent = $this->tree->getParentId($tree_id);
		$parentList = $this->tree->getList($tree_id, null);
		foreach($parentList as &$item)
		{
			$item['name'] = (isset($item['external']) && $item['external']) ? $item['url'] : rtrim($this->tree->getPath($item['id']), '/');
		}
		array_unshift($parentList, array('id' => $this->tree->getRootId(), 'name' => '/'));
		return $parentList;
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
			case ViewManager::OVERVIEW : $this->handleOverview(); break;
			case self::VIEW_THEME: $this->handleThemePost(); break;
			case self::VIEW_PLUGIN_CONF : $this->handlePluginConfPost(); break;
			case self::VIEW_PLUGIN_DEL : $this->handlePluginDelPost(); break;
			case self::VIEW_PLUGIN_MOVE : $this->handlePluginMovePost(); break;
			case self::VIEW_TAG: $this->handleTagPost(); break;
			case self::VIEW_TAG_DEL : $this->handleTagDelPost(); break;
			case self::VIEW_ROOT_ACL : $this->handleAdminEditRootAclPost(); break;
			case ViewManager::TREE_OVERVIEW : 
			case ViewManager::TREE_NEW : 
			case ViewManager::TREE_EDIT : 
			case ViewManager::TREE_DELETE : 
			default : $this->handleTreePost(); break;
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
			case ViewManager::OVERVIEW : $this->handleOverview(); break;
			case ViewManager::ADMIN_OVERVIEW : $this->handleAdminOverview(); break;
			case ViewManager::ADMIN_NEW : $this->handleAdminNewGet(); break;
			case ViewManager::ADMIN_EDIT : $this->handleAdminEditGet(); break;
			case ViewManager::ADMIN_DELETE : $this->handleAdminDeleteGet(); break;
			case self::VIEW_THEME: $this->handleThemeGet(); break;
			case self::VIEW_PLUGIN_CONF : $this->handlePluginConfGet(); break;
			case self::VIEW_PLUGIN_DEL : $this->handlePluginDelGet(); break;
			case self::VIEW_PLUGIN_MOVE : $this->handlePluginMoveGet(); break;
			case self::VIEW_TAG: $this->handleTagGet(); break;
			case self::VIEW_TAG_DEL : $this->handleTagDelGet(); break;
			case self::VIEW_MV_PREC : $this->handleMove(); break;
			case self::VIEW_MV_FOL : $this->handleMove(); break;
			case self::VIEW_MV_UP : $this->handleMove(); break;
			case self::VIEW_MV_DOWN : $this->handleMove(); break;
			case self::VIEW_ROOT_ACL : $this->handleAdminEditRootAclGet(); break;
			case ViewManager::TREE_OVERVIEW : 
			case ViewManager::TREE_NEW : 
			case ViewManager::TREE_EDIT : 
			case ViewManager::TREE_DELETE : 
			default : $this->handleTreeGet(); break;
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

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('list',  $list, false);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} //}}}

/*------- sitegroup request {{{ -------*/
	/**
	 * handle admin overview request
	*/
	public function handleSitegroup()
	{
		$request = Request::getInstance();


		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$view = ViewManager::getInstance();

		$url = new Url(true);
		$url->clearParameter('id');
		$url->clearParameter('tree_id');
		$url->clearParameter('tag');

		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);

		// get theme tag list and user defined tag list
		$grouplist = $siteGroup->getList();


		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} 
//}}}

/*------- admin overview request {{{ -------*/
	/**
	 * handle admin overview request
	*/
	public function handleAdminOverview($id=NULL)
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$page = $this->getPage();
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		if(!isset($id))
		{
			if($request->exists('tree_id')) $id = $request->getValue('tree_id');
			elseif($request->exists('id')) $id = $request->getValue('id');
			else $id = $this->tree->getRootId();
		}

		$this->setMenuId($id);
		$tree_id = $id;

		// check if node exists
		if($id != $this->tree->getRootId() && !$this->tree->exists($id)) throw new HttpException('404');
		$this->tree->setCurrentId($id);
		$this->renderBreadcrumb();

		$authentication = Authentication::getInstance();
		$acl = new Acl();
		$isAdmin = $authentication->isRole(SystemUser::ROLE_ADMIN);
		$canCreate = $authentication->canCreate($id);
		$canModify = $authentication->canModify($id);
		$canDelete = $authentication->canDelete($id);

		// add link to sitegroup to add / modify / delete websites
		$adminTree = $this->director->adminManager->tree;
		$pluginAdminId = $adminTree->getIdFromClassname('PluginHandler');
		$canEditPluginAdmin = $adminTree->exists($pluginAdminId);

		$themeAdminId = $adminTree->getIdFromClassname('ThemeHandler');
		$canEditThemeAdmin = $adminTree->exists($themeAdminId);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$url = new Url(true);
		$url->clearParameter('id');
		$url->clearParameter('tree_id');
		$url->clearParameter('tag');

		$detail = $this->getDetail(array('id' => $id));
		$template->setVariable($detail, NULL, false);
		$template->setVariable('isAdmin',  $isAdmin, false);

		if($isAdmin)
		{
			$aclList = $acl->getList(array('tree_id' => $id));
			if($aclList['data'])
			$template->setVariable('acl',  $aclList, false);
		}

		$parent_id = $this->tree->getParentId($id);
		$root_id = $this->tree->getRootId();

		// root node is only usefull to create main nodes
		$rootnode = ($id == $root_id);
		if($rootnode)
		{
			$template->setVariable('root',  true, false);

			if($isAdmin)
			{
			$url_acl = clone $url;
			$url_acl->setParameter($view->getUrlId(), self::VIEW_ROOT_ACL);
			$url_acl->setParameter('id', $id);
			$template->setVariable('href_acl',  $url_acl->getUrl(true), false);
			}
		}

		// only if create rights on parent
		if($authentication->canCreate($parent_id))
		{
			// add link to create a child page if location is root, or sibling page if not root
			$url_new = clone $url;
			$url_new->setParameter($view->getUrlId(), ViewManager::ADMIN_NEW);
			$url_new->setParameter('parent', $parent_id);
			$url_new->setParameter('weight', $detail['weight']+10);
			$template->setVariable('href_new',   $url_new->getUrl(true), false);
		}

		if($canCreate)
		{
			// add link to create child page 
			$url_sub_new = clone $url;
			$url_sub_new->setParameter($view->getUrlId(), ViewManager::ADMIN_NEW);
			$url_sub_new->setParameter('parent', $id);
			$template->setVariable('href_sub_new',   $url_sub_new->getUrl(true), false);
		}

		if(!$rootnode && $canDelete)
		{
			$url_del = clone $url;
			$url_del->setParameter($view->getUrlId(), ViewManager::ADMIN_DELETE);
			$url_del->setParameter('id', $id);
			$template->setVariable('href_del',  $url_del->getUrl(true), false);
		}

		if(!$rootnode && $canModify)
		{
			$url_edit = clone $url;
			$url_edit->setParameter($view->getUrlId(), ViewManager::ADMIN_EDIT);
			$url_edit->setParameter('id', $id);
			$template->setVariable('href_edit',  $url_edit->getUrl(true), false);

			$url_mv_prev = clone $url;
			$url_mv_prev->setParameter($view->getUrlId(), self::VIEW_MV_PREC);
			$url_mv_prev->setParameter('id', $id);
			$template->setVariable('href_mv_prev',  $url_mv_prev->getUrl(true), false);

			$url_mv_next = clone $url;
			$url_mv_next->setParameter($view->getUrlId(), self::VIEW_MV_FOL);
			$url_mv_next->setParameter('id', $id);
			$template->setVariable('href_mv_next',  $url_mv_next->getUrl(true), false);

			if($parent_id != $root_id)
			{
				$url_mv_up = clone $url;
				$url_mv_up->setParameter($view->getUrlId(), self::VIEW_MV_UP);
				$url_mv_up->setParameter('id', $id);
				$template->setVariable('href_mv_up',  $url_mv_up->getUrl(true), false);
			}

			if($this->tree->getFollowingSiblingId($id) != $root_id)
			{
				$url_mv_down = clone $url;
				$url_mv_down->setParameter($view->getUrlId(), self::VIEW_MV_DOWN);
				$url_mv_down->setParameter('id', $id);
				$template->setVariable('href_mv_down',  $url_mv_down->getUrl(true), false);
			}
		}

		$url_up = clone $url;
		$url_up->setParameter('id', $parent_id);
		$template->setVariable('href_up',  $url_up->getUrl(true), false);

		$url_preview = new Url();
		$url_preview->setPath($this->tree->getPath($id));
		$template->setVariable('href_preview',  $url_preview->getUrl(true), false);

		// check if page is link to external page. if so: cut the crap
		if(!$rootnode && $detail['external'])
		{
			$this->template[$this->director->theme->getConfig()->main_tag] = $template;
			return;
		}

		// retrieve selected theme
		$themeSearch = array('tree_id' => $id);
		$theme = $this->getTheme($themeSearch);

		if($canModify)
		{
			$url_theme = clone $url;
			$url_theme->setParameter($view->getUrlId(), self::VIEW_THEME);
			$url_theme->setParameter('tree_id', $id);
			$template->setVariable('href_theme',  $url_theme->getUrl(true), false);
		}


		if(!$theme || !$theme['activated'])
		{
			// no default theme or user defined theme found, create link to themes module to select 1 as default
			if($theme && !$theme['activated'] && $canModify)
			{
				$url_theme_admin = new Url();
				$url_theme_admin->setPath($adminTree->getPath($themeAdminId));
				$url_theme_admin->setParameter($view->getUrlId(), 5);
				$url_theme_admin->setParameter('id', $theme['theme_id']);
				$theme['name'] .= ' (incompatible version)';
				$template->setVariable('href_theme_admin',	$url_theme_admin->getUrl(true));
				$template->setVariable('theme_name',	$theme['name']);
			}
			else
				$template->setVariable('theme_name',  'no theme specified', false);

		}
		elseif($authentication->canEdit($id) && (!$rootnode || $isAdmin)) 
		{                                      
			// if user can not edit the page, he is only authorized to view the menu item (to get to another node he can edit)
			// default theme or user defined theme found, create edit link

			$template->setVariable('theme_name',	$theme['name']);
			$template->setVariable('theme',  			$theme);

			// create default urls
			$url_plugin_admin = new Url();
			$url_plugin_admin->setPath($adminTree->getPath($pluginAdminId));
			$url_plugin_admin->setParameter($view->getUrlId(), 5);

			// create default urls
			$url_plugin = clone $url;
			$url_plugin->setParameter('tree_id', $id);

			$url_conf = clone $url_plugin;
			$url_conf->setParameter($view->getUrlId(), self::VIEW_PLUGIN_CONF);

			$url_move = clone $url_plugin;
			$url_move->setParameter($view->getUrlId(), self::VIEW_PLUGIN_MOVE);

			$url_edit = clone $url_plugin;
			$url_edit->setParameter($view->getUrlId(), ViewManager::TREE_OVERVIEW);

			$url_del = clone $url_plugin;
			$url_del->setParameter($view->getUrlId(), self::VIEW_PLUGIN_DEL);

			$url_data_source = clone $url;
			$url_data_source->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);

			// get theme tag list and user defined tag list
			$searchcriteria = array('tree_id' => $id);
			$taglist = $this->getTagList($searchcriteria);

			// retrieve linked used tag list with the related plugins
			$sitePlugin = $this->getSitePlugin();
			$pluginList = $sitePlugin->getTagList($searchcriteria);

			// store virtual tags in separate list
			$virtualTaglist = array();

			// process active tags
			foreach($taglist as $key=>&$item)
			{
				$url_conf->setParameter('tag', $item['id']);
				$url_move->setParameter('tag', $item['id']);
				$url_edit->setParameter('tag', $item['id']);
				$url_del->setParameter('tag', $item['id']);

				$pluginExists = array_key_exists($item['id'], $pluginList);
				$item['has_plugin'] = $pluginExists;

				if($canModify && !$pluginExists) $item['href_conf'] = $url_conf->getUrl(true);

				// default settings
				$item['plugin_name'] = '';
				$item['plugin_type'] = '';
				$item['virtual_type'] = SystemSitePlugin::TYPE_NORMAL;

				if($pluginExists)
				{
					$thisplugin = $pluginList[$item['id']];

					// copy settings from plugin to tag item
					$item['virtual_type'] = $thisplugin['virtual_type'];
					$item['plugin_name'] = $thisplugin['plugin_name'];
					$item['activated'] = $thisplugin['activated'];

					if($canEditPluginAdmin) 
					{
						$url_plugin_admin->setParameter('id', $thisplugin['plugin_id']);
						$item['href_plugin_admin'] = $url_plugin_admin->getUrl(true);
					}

					// plugin is active
					if($item['activated'])
					{
						$objPlugin = $this->director->pluginManager->getPlugin($thisplugin['classname']);
						$item['plugin_type'] = $objPlugin->getTypeDesc($thisplugin['plugin_type']);

						switch($item['virtual_type'])
						{
							case SystemSitePlugin::TYPE_VIRTUAL : 
								$item['href_conf'] = $url_conf->getUrl(true);
								$item['href_new'] = $url_edit->getUrl(true);
								break;
							case SystemSitePlugin::TYPE_VIRTUAL_OVERRIDE_SETTINGS : 
								$item['href_conf'] = $url_conf->getUrl(true);
								$item['href_new'] = $url_edit->getUrl(true);
								$item['href_del'] = $url_del->getUrl(true);
								break;
							case SystemSitePlugin::TYPE_VIRTUAL_OVERRIDE : 
							case SystemSitePlugin::TYPE_NORMAL : 
								$item['href_edit'] = $url_edit->getUrl(true);
								$item['href_del'] = $url_del->getUrl(true);
								$item['href_move'] = $url_move->getUrl(true);
						}
					}
					else
						$item['plugin_type'] = '<em class="error">incompatible version</em>';

					// provide additional info for virtual tags and add item to virtual taglist
					if($canModify && !$rootnode && $item['virtual_type'] != SystemSitePlugin::TYPE_NORMAL) 
					{
						// last tree id is where the plugin has modified tree settings, not necessarily the tree id where the plugin itself was modified
						$item['data_source'] = substr($this->tree->getTreeName().$this->tree->getPath($thisplugin['last_tree_id'], " &raquo; "), 0, 0 - strlen("&raquo; "));
						$url_data_source->setParameter('id', $thisplugin['last_tree_id']);
						$item['url_data_source'] = $url_data_source->getUrl(true);
						$virtualTaglist[] = $item;
					}

					// clear existing plugin from list
					unset($pluginList[$item['id']]);
				}
			}
			$template->setVariable('taglist',  $taglist);
			$template->setVariable('virtualTaglist',  $virtualTaglist);

			// process inactive tags
			$hiddenPlugin = array();
			if($canModify)
			{
				foreach($pluginList as $tag=>$pluginItem)
				{
					// skip missing virtual plugins
					if($pluginItem['virtual_type'] == SystemSitePlugin::TYPE_VIRTUAL) continue;

					$url_move->setParameter('tag', $tag);
					$url_edit->setParameter('tag', $tag);
					$url_del->setParameter('tag', $tag);
					$url_plugin_admin->setParameter('id', $pluginItem['plugin_id']);

					if($canEditPluginAdmin) $pluginItem['href_plugin_admin'] = $url_plugin_admin->getUrl(true);

					// plugin is linked to tag
					if($pluginItem['activated'])
					{
						$objPlugin = $this->director->pluginManager->getPlugin($pluginItem['classname']);
						$pluginItem['plugin_type'] = $objPlugin->getTypeDesc($pluginItem['plugin_type']);
						$pluginItem['href_edit'] = $url_edit->getUrl(true);
						$pluginItem['href_move'] = $url_move->getUrl(true);
						$pluginItem['href_del'] = $url_del->getUrl(true);
					}
					else
						$pluginItem['plugin_type'] = '<em class="error">incompatible version</em>';

					$pluginItem['name'] = $tag;
					$hiddenPlugin[] = $pluginItem;
				}
			}
			$template->setVariable('hiddentaglist',  $hiddenPlugin);
		}

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} 
//}}}

/*------- handle move request {{{ -------*/
	/**
	 * handle Move request
	*/
	private function handleMove()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if(!$request->exists('id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		$tree_id = intval($request->getValue('id'));

		// check if node exists
		if(!$this->tree->exists($tree_id)) throw new HttpException('404');
		$this->tree->setCurrentId($tree_id);

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canModify($tree_id)) throw new HttpException('403');

		$key = array('id' => $tree_id);

		try 
		{
			switch($view->getType())
			{
				case self::VIEW_MV_PREC : $this->movetoPreceding($key); break;
				case self::VIEW_MV_FOL : $this->movetoFollowing($key); break;
				case self::VIEW_MV_UP : $this->movetoParent($key); break;
				case self::VIEW_MV_DOWN : $this->movetoChild($key); break;
			}

			// reset site tree
			$this->tree = new SiteTree($this);

		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
		}

		viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
		$this->handleAdminOverview($tree_id);
	} 
//}}}

/*------- Theme request {{{ -------*/
	/**
	 * handle Theme request
	*/
	private function handleThemeGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$siteTheme = $this->getSiteTheme();

		if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		$tree_id = intval($request->getValue('tree_id'));

		// check if node exists
		if($tree_id != $this->tree->getRootId() && !$this->tree->exists($tree_id)) throw new HttpException('404');
		$this->tree->setCurrentId($tree_id);
		$this->renderBreadcrumb();

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canModify($tree_id)) throw new HttpException('403');

		$key = array('tree_id' => $tree_id);

		$theme = NULL;
		$themeId = NULL;

		$themeDetail = array();
		$themeManager = $this->director->themeManager;
		$siteThemeExists = $siteTheme->exists($key);
		if($siteThemeExists)
		{
			$themeDetail = $siteTheme->getDetail($key);
			$themeId = $themeDetail['theme_id'];
			$theme = $themeManager->getDetail(array('id' => $themeDetail['theme_id']));
		}
		else
		{
			$themeDetail = $this->getDefaultTheme();
			if($themeDetail)
			{
				$themeId = $themeDetail['theme_id'];
				$theme = $themeManager->getDetail(array('id' => $themeId));
			}
		}

		if($retrieveFields)
		{
			if($siteThemeExists)
				$siteTheme->setFields($themeDetail);
			elseif($themeId)
			{
				// get default site theme
				$siteTheme->setFields($theme);
			}
		}

		$fields = $siteTheme->getFields(SqlParser::MOD_INSERT);

		$template->setVariable($fields, NULL, false);
		$template->setVariable('tree_id',  $tree_id, false);

		// create theme combos
		$themes = $this->director->themeManager->getList(array('active' => 1));
		$template->setVariable('cbo_theme', Utils::getHtmlCombo($themes['data'], $themeId));

		if($themeId)
		{
			// url for tag edit
			$url_edit = new Url(true);
			$url_edit->setParameter($view->getUrlId(), self::VIEW_TAG);
			$url_edit->setParameter('tree_id', $tree_id);
			$url_del = clone $url_edit;
			$url_del->setParameter($view->getUrlId(), self::VIEW_TAG_DEL);

			// save the url for rpc calls
			$this->fileVars['href_edit_theme_tag'] = $url_edit->getUrl();
			$this->fileVars['href_del_theme_tag'] = $url_del->getUrl();

			// object that holds all tag related to the tree node
			$siteTag = $this->getSiteTag();
			
			// retrieve all tags related to the tree
			$searchcriteria = array('tree_id' => $tree_id);
			$usedTags = $siteTag->getTagList($searchcriteria);

			// load default theme tags
			$themeObj = $this->director->themeManager->getTheme($theme['classname']);
			$taglist = $themeObj->getTagList();
			foreach($taglist as &$item)
			{
				$item['userdefined'] = (array_key_exists($item['id'], $usedTags)) ? join(', ',$usedTags[$item['id']]) : '';
				$url_edit->setParameter('parent_tag', $item['id']);
				$url_del->setParameter('parent_tag', $item['id']);
				$item['href_edit'] = $url_edit->getUrl(true);
				$item['href_del'] = $item['userdefined'] ? $url_del->getUrl(true) : '';
			}
			$template->setVariable('tags',  $taglist, false);

			// display unused user defined tags
			$searchcriteria = array('tree_id' => $tree_id, 'no_parent_tag' => $themeObj->getTagList());
			$unusedTags = $siteTag->getList($searchcriteria);
			foreach($unusedTags['data'] as &$item)
			{
				$item['userdefined'] = (array_key_exists($item['parent_tag'], $usedTags)) ? join(', ',$usedTags[$item['parent_tag']]) : '';
				$url_edit->setParameter('parent_tag', $item['parent_tag']);
				$url_del->setParameter('parent_tag', $item['parent_tag']);
				$item['href_edit'] = $url_edit->getUrl(true);
				$item['href_del'] = $url_del->getUrl(true);
			}
			$template->setVariable('unusedTags',  $unusedTags, false);
		}

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$url->setParameter('id', $tree_id);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleThemePost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canModify($values['tree_id'])) throw new HttpException('403');

		$siteTheme = $this->getSiteTheme();
		$id = array('tree_id' => $values['tree_id']);

		// check if default theme is selected. if so, then remove the hard linked theme
		$theme = $this->getDefaultTheme();
		$defaultTheme = ($this->tree->getRootId() != $values['tree_id'] && $theme && $theme['theme_id'] == $values['theme_id']);

		try 
		{
			$themeExists = $siteTheme->exists($id);

			if($defaultTheme)
			{
				if($themeExists) $siteTheme->delete($id);
			}
			else
			{
				if($themeExists)
					$siteTheme->update($id, $values);
				else
				{
					$id = $siteTheme->insert($values);
					// if this is going to be a default theme, try to assing plugins to the tags
					if($this->tree->getRootId() == $values['tree_id'])
					{
						$themeDetail = $siteTheme->getDetail($id);
						$sitePlugin = $this->getSitePlugin();
						$newTheme = $this->director->themeManager->getTheme($themeDetail['classname']);
						$tagPluginList = $newTheme->getTagPluginList();
						if($tagPluginList)
						{
							foreach($tagPluginList as $tag=>$plugin)
							{
								// skip non existant plugins
								$pluginKey = array('classname' => $plugin['classname']);
								if(!$this->director->pluginManager->exists($pluginKey)) continue;
                $pluginDetail = $this->director->pluginManager->getDetail($pluginKey);
								//assign plugins
								$pluginItem = array('tree_id' => $values['tree_id'],
																		'tag'			=> $tag,
																		'plugin_id'	=> $pluginDetail['id'],
																		'plugin_type'	=> $plugin['type']);
								$sitePlugin->insert($pluginItem);
							}
						}
					}
				}
			}

			// clear buffered themes
			$this->clearThemeBuffer();

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview($values['tree_id']);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleThemeGet(false);
		}
	} 
//}}}

/*------- Tag request {{{ -------*/
	/**
	 * handle Tag request
	*/
	private function handleTagGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(self::VIEW_TAG); 

		$siteTag = $this->getSiteTag();

		if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		if(!$request->exists('parent_tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));

		// check if node exists
		if(!$this->tree->exists($tree_id)) throw new HttpException('404');
		$this->tree->setCurrentId($tree_id);
		$this->renderBreadcrumb();

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canModify($tree_id)) throw new HttpException('403');

		$parent_tag = $request->getValue('parent_tag');
		$key = array('tree_id' => $tree_id, 'parent_tag' => $parent_tag);

		if($retrieveFields)
		{
			if($siteTag->exists($key))
				$siteTag->setFields($siteTag->getDetail($key));
			else
				$siteTag->setFields($siteTag->getFields(SqlParser::MOD_INSERT));
		}

		$fields = $siteTag->getFields(SqlParser::MOD_INSERT);

		$template->setVariable($fields, NULL, false);
		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('parent_tag',  $parent_tag, false);

		// retrieve selected theme and populate variables to be used
		$themeDetail = $this->getTheme(array('tree_id' => $tree_id));
		$themeManager = $this->director->themeManager;
		$theme = $themeManager->getTheme($themeDetail['classname']);
		$template->setVariable('filevars',  $theme->getFileVars(), false);

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), self::VIEW_THEME);
		$url->setParameter('tree_id', $tree_id);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$theme = $this->director->theme;
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/editarea/edit_area/edit_area_full.js"></script>');
		$theme->addJavascript('
editAreaLoader.init({ 	id: "area1", 
							start_highlight: true, 
							allow_toggle: true, 
							allow_resize: true,
							language: "en", 
							syntax: "php", 
							toolbar: "load",
							toolbar: "load, |, search, go_to_line, |, undo, redo, |, select_font, |, change_smooth_selection, highlight, reset_highlight, |, help",
							syntax_selection_allow: "css,html,js,php", 
							load_callback: "createTags"
					});

editAreaLoader.init({ 	id: "area2", 
							start_highlight: true, 
							allow_toggle: true, 
							allow_resize: true,
							language: "en", 
							syntax: "css", 
							syntax_selection_allow: "css,html,js,php" 
					});
');

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTagPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canModify($values['tree_id'])) throw new HttpException('403');

		$siteTag = $this->getSiteTag();
		$id = array('tree_id' => $values['tree_id'], 'parent_tag' => $values['parent_tag']);
		try 
		{
			if($siteTag->exists($id))
				$siteTag->update($id, $values);
			else
				$id = $siteTag->insert($values);

			viewManager::getInstance()->setType(self::VIEW_THEME);
			$this->handleThemeGet($id['tree_id']);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleTagGet(false);
		}
	} 
//}}}

/*------- Tag delete request {{{ -------*/
	/**
	 * handle delete
	*/
	private function handleTagDelGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$siteTag = $this->getSiteTag();

		if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		if(!$request->exists('parent_tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));

		// check if node exists
		if(!$this->tree->exists($tree_id)) throw new HttpException('404');
		$this->tree->setCurrentId($tree_id);
		$this->renderBreadcrumb();

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canModify($tree_id)) throw new HttpException('403');

		$parent_tag = $request->getValue('parent_tag');
		$key = array('tree_id' => $tree_id, 'parent_tag' => $parent_tag);

		$template->setVariable('name',  $siteTag->getName($key), false);
		$template->setVariable($key, NULL, false);

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), self::VIEW_THEME);
		$url->setParameter('tree_id', $tree_id);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTagDelPost()
	{
		$request = Request::getInstance();
		$siteTag = $this->getSiteTag();

		try 
		{
			if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
			if(!$request->exists('parent_tag')) throw new Exception('Tag ontbreekt.');
		
			$tree_id = intval($request->getValue('tree_id'));
			$parent_tag = $request->getValue('parent_tag');
			$key = array('tree_id' => $tree_id, 'parent_tag' => $parent_tag);

			// check if user has execute rights
			$authentication = Authentication::getInstance();
			if(!$authentication->canModify($tree_id)) throw new HttpException('403');

			$siteTag->delete($key);
			viewManager::getInstance()->setType(self::VIEW_THEME);
			$this->handleThemeGet();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleTagDelGet();
		}

	} 
//}}}

/*------- Plugin conf request {{{ -------*/
	/**
	 * handle Theme request
	*/
	private function handlePluginConfGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(self::VIEW_PLUGIN_CONF);

		$sitePlugin = $this->getSitePlugin();

		if(!$request->exists('tag')) throw new Exception(__FUNCTION__.' tag ontbreekt.');
		if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		$tree_id 	= intval($request->getValue('tree_id'));
		$tag 			= $request->getValue('tag');

		// check if node exists
		if($tree_id != $this->tree->getRootId() && !$this->tree->exists($tree_id)) throw new HttpException('404');
		$this->tree->setCurrentId($tree_id);
		$this->renderBreadcrumb();

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canModify($tree_id)) throw new HttpException('403');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		// check if there is already a plugin linked to this tag
		//if( $sitePlugin->exists($key)) throw new Exception("There is already a plugin linked to this tag.");
		if( $sitePlugin->exists($key)) 
		{
			if($request->getRequestType() == Request::GET)
				$fields = $sitePlugin->getDetail($key);
			else
				$fields = $sitePlugin->getFields(SqlParser::MOD_UPDATE);
		}
		else
		{
			$fields = $sitePlugin->getFields(SqlParser::MOD_INSERT);
		}

		$pluginManager = $this->director->pluginManager;


		$template->setVariable($fields, NULL, false);

		// check if plugin is a virtual plugin. If so, add the option to not define a plugin 
		$pluginsearch = array('tree_id' => $tree_id);
		$pluginlist = $sitePlugin->getTagList($pluginsearch);
		$virtual = $pluginlist[$tag]['virtual_type'] != SystemSitePlugin::TYPE_NORMAL;
		$template->setVariable('virtual', $virtual);

		//get default or current plugin id
		$pluginResult = $pluginManager->getList(array('active' => 1));
		$plugins = $pluginResult['data'];

		if($fields['plugin_id']) 
		{
			$pluginId = $fields['plugin_id']; 
		}
		elseif(!$virtual)
		{
			$tmp = current($plugins);
			$pluginId = $tmp['id'];
		}

		// create plugin list
		$template->setVariable('cbo_plugin', Utils::getHtmlCombo($plugins, $pluginId, $virtual ? 'Use default plugin...' : ''));

		// create type list
		if(!$virtual)
		{
			$plugin = $pluginManager->getPluginFromId(array('id' => $pluginId));
			$template->setVariable('cbo_plugin_type', Utils::getHtmlCombo($plugin->getTypeList(), $fields['plugin_type']));
		}

		$template->setVariable('cbo_plugin_view', Utils::getHtmlCombo($sitePlugin->getViewTypeList(), $fields['plugin_view']));

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$url->setParameter('id', $tree_id);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handlePluginConfPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canModify($values['tree_id'])) throw new HttpException('403');

		$sitePlugin = $this->getSitePlugin();
		$id = array('tree_id' => $values['tree_id'], 'tag' => $values['tag']);
		try 
		{
			if($sitePlugin->exists($id))
				$sitePlugin->update($id, $values);
			else
				$id = $sitePlugin->insert($values);

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview($id['tree_id']);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handlePluginConfGet(false);
		}
	} 
//}}}

/*------- Plugin move request {{{ -------*/
	/**
	 * handle Theme request
	*/
	private function handlePluginMoveGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(self::VIEW_PLUGIN_MOVE);

		$sitePlugin = $this->getSitePlugin();

		if(!$request->exists('tag')) throw new Exception(__FUNCTION__.' tag ontbreekt.');
		if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		$tree_id 	= intval($request->getValue('tree_id'));
		$tag 			= $request->getValue('tag');

		// check if node exists
		if($tree_id != $this->tree->getRootId() && !$this->tree->exists($tree_id)) throw new HttpException('404');
		$this->tree->setCurrentId($tree_id);
		$this->renderBreadcrumb();

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canModify($tree_id)) throw new HttpException('403');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		// check if there is already a plugin linked to this tag
		if(!$sitePlugin->exists($key)) throw new Exception("There is no plugin linked to this tag.");

		if($request->getRequestType() == Request::GET)
		{
			$new_tree_id = $tree_id;
			$new_tag = $tag;
			$fields = $sitePlugin->getDetail($key);
		}
		else
		{
			$new_tree_id = $request->getValue('new_tree_id');
			$new_tag = $request->getValue('new_tag');
			$fields = $sitePlugin->getFields(SqlParser::MOD_UPDATE);
		}

		// plugin is linked to tag
		$objPlugin = $this->director->pluginManager->getPlugin($fields['classname']);
		$fields['plugin_name'] = $objPlugin->getPluginName();
		$fields['plugin_type'] = $objPlugin->getTypeDesc($fields['plugin_type']);

		$template->setVariable($fields, NULL, false);
		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

     
		$template->setVariable('cbo_tree_id', Utils::getHtmlCombo($this->getSafeTreeList(), $new_tree_id));
		$template->setVariable('cbo_tag', Utils::getHtmlCombo($this->getAvailableTagList($key), $new_tag));
		$template->setVariable('cbo_plugin_view', Utils::getHtmlCombo($sitePlugin->getViewTypeList(), $fields['plugin_view']));

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$url->setParameter('id', $tree_id);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handlePluginMovePost()
	{
		$request = Request::getInstance();

		try 
		{
			if(!$request->exists('tag')) throw new Exception(__FUNCTION__.' tag ontbreekt.');
			if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
			$tree_id 	= intval($request->getValue('tree_id'));
			$tag 			= $request->getValue('tag');

			// check if user has execute rights
			$authentication = Authentication::getInstance();
			if(!$authentication->canModify($tree_id)) throw new HttpException('403');

			$sitePlugin = $this->getSitePlugin();
			$sitePlugin->updateTag($tree_id, $tag, $request->getValue('new_tree_id'), $request->getValue('new_tag'), $request->getValue('plugin_view'), $request->getValue('recursive'));

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview($tree_id);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handlePluginMoveGet();
		}
	} 
//}}}

/*------- Plugin delete request {{{ -------*/
	/**
	 * handle Theme request
	*/
	private function handlePluginDelGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$sitePlugin = $this->getSitePlugin();

		if(!$request->exists('tag')) throw new Exception(__FUNCTION__.' tag ontbreekt.');
		if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		$tree_id 	= intval($request->getValue('tree_id'));
		$tag 			= $request->getValue('tag');

		// check if node exists
		if($tree_id != $this->tree->getRootId() && !$this->tree->exists($tree_id)) throw new HttpException('404');
		$this->tree->setCurrentId($tree_id);
		$this->renderBreadcrumb();

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canModify($tree_id)) throw new HttpException('403');

		$key = array('tree_id' => array($tree_id, $this->tree->getRootId()), 'tag' => $tag);

		$template->setVariable('name',  $sitePlugin->getName($key), false);
		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$url->setParameter('id', $tree_id);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handlePluginDelPost()
	{
		$request = Request::getInstance();

		$sitePlugin = $this->getSitePlugin();
		try 
		{
			if(!$request->exists('tag')) throw new Exception(__FUNCTION__.' tag ontbreekt.');
			if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
			$tree_id 	= intval($request->getValue('tree_id'));
			$tag 			= $request->getValue('tag');

			// check if user has execute rights
			$authentication = Authentication::getInstance();
			if(!$authentication->canModify($tree_id)) throw new HttpException('403');

			$key = array('tree_id' => $tree_id, 'tag' => $tag);
			$sitePlugin->delete($key);

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview($key['tree_id']);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handlePluginDelGet();
		}
	} 
//}}}

/*------- Plugin edit request {{{ -------*/
	/**
	 * handle Theme request
	*/
	private function handleTreeGet()
	{
		$template = new TemplateEngine();
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$sitePlugin = $this->getSitePlugin();

		if(!$request->exists('tag')) throw new Exception(__FUNCTION__.' tag ontbreekt.');
		if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		$tree_id 	= intval($request->getValue('tree_id'));
		$tag 			= $request->getValue('tag');

		// check if node exists
		if($tree_id != $this->tree->getRootId() && !$this->tree->exists($tree_id)) throw new HttpException('404');
		$this->tree->setCurrentId($tree_id);
		$this->renderBreadcrumb();

		//$key = array('tree_id' => array($tree_id, $this->tree->getRootId()), 'tag' => $tag);

		$template->setVariable('pageTitle',  $this->tree->getName($this->tree->getCurrentId()), false);

		// get tag name
		$searchcriteria = array('tree_id' => $tree_id);
		$taglist = $this->getTagList($searchcriteria);

		$tagname = array_key_exists($tag, $taglist) ? $taglist[$tag]['name'] : $tag;
		$template->setVariable('tagName',  $tagname, false);

		$url = new Url(true);
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);
		$url->setParameter($view->getUrlId(), ViewManager::TREE_OVERVIEW);
		$this->director->theme->handleAdminLinks($template, $tagname, $url);

		// render preview url
		$url_preview = new Url();
		$url_preview->setPath($this->tree->getPath($tree_id));
		$template->setVariable('href_preview',  $url_preview->getUrl(true), false);

		//get plugin details
		//$list = $sitePlugin->getList($key);
		// retrieve linked used tag list with the related plugins
		$key = array('tree_id' => $tree_id, 'tag' => $tag);
		$sitePlugin = $this->getSitePlugin();
		$pluginList = $sitePlugin->getTagList($key);
		$detail = current($pluginList);

		$plugin = $this->director->pluginManager->getPlugin($detail['classname']);
		$plugin->setReferer($this);
		$plugin->handleHttpGetRequest();
	}

	private function handleTreePost()
	{
		$template = new TemplateEngine();
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$sitePlugin = $this->getSitePlugin();

		if(!$request->exists('tag')) throw new Exception(__FUNCTION__.' tag ontbreekt.');
		if(!$request->exists('tree_id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		$tree_id 	= intval($request->getValue('tree_id'));
		$tag 			= $request->getValue('tag');
		$this->tree->setCurrentId($tree_id);
		$this->renderBreadcrumb();

		$key = array('tree_id' => $tree_id, 'tag' => $tag);
		$tagData = $sitePlugin->getTagList($key);
		if(!$tagData) throw new Exception("No tagList for tree id: '$tree_id' and tag : '$tag' in ".__FUNCTION__);
		$detail = current($tagData);
		if(!$detail) throw new Exception("No tagList for tree id: '$tree_id' and tag : '$tag' in ".__FUNCTION__);

		//pure_virtual
		switch($detail['virtual_type'])
		{
			case SystemSitePlugin::TYPE_VIRTUAL : 
				// insert (override) virtual plugin
				$detail['tree_id'] = $tree_id;
				$sitePlugin->insert($detail);
			case SystemSitePlugin::TYPE_VIRTUAL_OVERRIDE_SETTINGS : 
			case SystemSitePlugin::TYPE_VIRTUAL_OVERRIDE : 
				// edit virtual plugin
				$detail['tree_id'] = $tree_id;
				$sitePlugin->update($sitePlugin->getKey($detail), $detail);
				break;
		}

		// insert page title
		$template->setVariable('pageTitle',  $this->tree->getName($this->tree->getCurrentId()), false);

		// get tag name
		$searchcriteria = array('tree_id' => $tree_id);
		$taglist = $this->getTagList($searchcriteria);

		$tagname = array_key_exists($tag, $taglist) ? $taglist[$tag]['name'] : $tag;
		$template->setVariable('tagName',  $tagname, false);

		$url = new Url(true);
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);
		$url->setParameter($view->getUrlId(), ViewManager::TREE_OVERVIEW);
		$this->director->theme->handleAdminLinks($template, $tagname, $url);

		$plugin = $this->director->pluginManager->getPlugin($detail['classname']);
		$plugin->setReferer($this);
		$plugin->handleHttpPostRequest();
	}
//}}}

/*------- new request {{{ -------*/
	private function handleEdit($template, $fields)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();
		$auth = Authentication::getInstance();

		if($auth->isRole(SystemUser::ROLE_ADMIN))
		{
			$group = new SystemUserGroup();
			$groupList = $group->getList();
			$template->setVariable('groupList',  $groupList, false);

			$acl = new Acl();
			$template->setVariable('rightsList',  $acl->getRightsList(), false);
		}

		if(array_key_exists('hide', $fields))
			$template->setVariable('cbo_hide', Utils::getHtmlCombo($this->getHideList(), $fields['hide'],'...'));

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$url->setParameter('id', $view->getType() == ViewManager::ADMIN_NEW ? $request->getValue('parent') : $request->getValue('id'));
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
		Utils::getDatePicker($this->director->theme, $datefields);
	}

	/**
	 * handle new
	*/
	private function handleAdminNewGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$request = Request::getInstance();
		$parent =  $request->getValue('parent');
		$this->parentId = $parent;

		// check if node exists
		if($parent != $this->tree->getRootId() && !$this->tree->exists($parent)) throw new HttpException('404');
		$this->tree->setCurrentId($parent);
		$this->renderBreadcrumb();

		// check if user has execute rights
		$auth= Authentication::getInstance();
		if(!$auth->canCreate($parent)) throw new HttpException('403');

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$template->setVariable($fields, NULL, false);

		$this->handleEdit($template, $fields);

		$template->setVariable('parent',  $parent, false);
		if($request->exists('weight')) $template->setVariable('weight',  $request->getValue('weight'), false);
		$template->setVariable('id',  '', false);

		if($request->getRequestType(Request::GET) && $auth->isRole(SystemUser::ROLE_ADMIN))
		{
			$acl = new Acl();
			$groupSelect = $acl->getAclGroupList($parent);
		}
		else
		{
			$groupSelect = $request->getValue('acl');
		}

		if(!is_array($groupSelect)) $groupSelect = array();
		$template->setVariable('groupSelect',  $groupSelect, false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminNewPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);
		$acl = new Acl();

		// check if user has execute rights
		$auth = Authentication::getInstance();
		if(!$auth->canCreate($values['parent'])) throw new HttpException('403');

		try 
		{
			$id = $this->insert($values);

			if($auth->isRole(SystemUser::ROLE_ADMIN))
			{
				$groupSelect = $request->getValue('acl');
			}
			else
			{
				$groupSelect = $acl->getAclGroupList($values['parent']);
			}

			if(is_array($groupSelect))
			{
				foreach($groupSelect as $grp_id => $rightsList)
				{
					$rights = 0;
					foreach($rightsList as $item)
					{
						$rights |= $item;
					}
					$key = array('tree_id' => $id['id'], 'grp_id' => $grp_id);
					$aclValues = $key;
					$aclValues['rights'] = $rights;

					if($acl->exists($key))
						$acl->update($key, $aclValues);
					else
						$acl->insert($aclValues);
				}
			}

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);;
			$this->handleAdminOverview($id['id']);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			// reset date values
			$online = $this->sqlParser->getFieldByName('online');
			$this->sqlParser->setFieldValue('online', ($online->getValue()) ? strftime('%Y-%m-%d', strtotime($online->getValue())): '' );

			$offline = $this->sqlParser->getFieldByName('offline');
			$this->sqlParser->setFieldValue('offline', ($offline->getValue()) ? strftime('%Y-%m-%d', strtotime($offline->getValue())): '' );

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

		if(!$request->exists('id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);
		$key = array('id' => $id);

		// check if node exists
		if($id != $this->tree->getRootId() && !$this->tree->exists($id)) throw new HttpException('404');
		$this->tree->setCurrentId($id);
		$this->renderBreadcrumb();

		// check if user has execute rights
		$auth= Authentication::getInstance();
		if(!$auth->canModify($id)) throw new HttpException('403');

		$groupSelect = array();
		$fields = array();

		if($retrieveFields)
		{
			$fields = $this->getDetail($key);
			$fields['online'] = $fields['online'] ? strftime('%Y-%m-%d', $fields['online']) : '';
			$fields['offline'] = $fields['offline'] ? strftime('%Y-%m-%d', $fields['offline']) : '';

			if($auth->isRole(SystemUser::ROLE_ADMIN))
			{
				$acl = new Acl();
				$groupSelect = $acl->getAclGroupList($id);
			}
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);

			$groupSelect = $request->getValue('acl');
		}

		if(!is_array($groupSelect)) $groupSelect = array();
		$template->setVariable('groupSelect',  $groupSelect, false);

		$this->setFields($fields);
		$template->setVariable($fields, NULL, false);

		$this->handleEdit($template, $fields);

		$template->setVariable('cbo_parent', Utils::getHtmlCombo($this->getSafeTreeList($id), $fields['parent']));

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Node ontbreekt.');
			$id = intval($request->getValue('id'));

			// check if user has execute rights
			$auth= Authentication::getInstance();
			if(!$auth->canModify($id)) throw new HttpException('403');

			$key = array('id' => $id);
			$this->update($key, $values);

			if($auth->isRole(SystemUser::ROLE_ADMIN))
			{
				$acl = new Acl();
				$acl->delete(array('tree_id' => $id));
				$groupSelect = $request->getValue('acl');
				if(is_array($groupSelect))
				{
					foreach($groupSelect as $grp_id => $rightsList)
					{
						$rights = 0;
						foreach($rightsList as $item)
						{
							$rights |= $item;
						}
						$aclValues = array('tree_id' => $id, 'grp_id' => $grp_id, 'rights' => $rights);
						$acl->insert($aclValues);
					}
				}
			}

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview($id);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			// reset date values
			$online = $this->sqlParser->getFieldByName('online');
			$this->sqlParser->setFieldValue('online', ($online->getValue()) ? strftime('%Y-%m-%d', strtotime($online->getValue())): '' );

			$offline = $this->sqlParser->getFieldByName('offline');
			$this->sqlParser->setFieldValue('offline', ($offline->getValue()) ? strftime('%Y-%m-%d', strtotime($offline->getValue())): '' );

			$this->handleAdminEditGet(false);
		}

	} 
//}}}

/*------- edit root acl request {{{ -------*/
	/**
	 * handle edit
	*/
	private function handleAdminEditRootAclGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();

		if(!$request->exists('id')) throw new Exception(__FUNCTION__.' Node ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);
		$key = array('id' => $id);

		// check if node exists
		if($id != $this->tree->getRootId()) throw new HttpException('403');
		$this->tree->setCurrentId($id);
		$this->renderBreadcrumb();

		// check if user has execute rights
		$auth= Authentication::getInstance();
		// edit root node only for acl. only admins can edit acl
		if(!$auth->isRole(SystemUser::ROLE_ADMIN)) throw new HttpException('403');

		$groupSelect = array();

		if($retrieveFields)
		{
			$acl = new Acl();
			$groupSelect = $acl->getAclGroupList($id);
		}
		else
		{
			$groupSelect = $request->getValue('acl');
		}

		if(!is_array($groupSelect)) $groupSelect = array();
		$template->setVariable('groupSelect',  $groupSelect, false);

		$this->handleEdit($template, array());

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminEditRootAclPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Node ontbreekt.');
			$id = intval($request->getValue('id'));
			$isRootNode = ($id == $this->tree->getRootId()); 

			// check if user has execute rights
			$auth= Authentication::getInstance();
			if(!$auth->canModify($id)) throw new HttpException('403');

			$key = array('id' => $id);
			if(!$isRootNode)
				$this->update($key, $values);

			if($auth->isRole(SystemUser::ROLE_ADMIN))
			{
				$acl = new Acl();
				$acl->delete(array('tree_id' => $id));
				$groupSelect = $request->getValue('acl');
				if(is_array($groupSelect))
				{
					foreach($groupSelect as $grp_id => $rightsList)
					{
						$rights = 0;
						foreach($rightsList as $item)
						{
							$rights |= $item;
						}
						$aclValues = array('tree_id' => $id, 'grp_id' => $grp_id, 'rights' => $rights);
						$acl->insert($aclValues);
					}
				}
			}

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview($id);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			if(!$isRootNode)
			{
				// reset date values
				$online = $this->sqlParser->getFieldByName('online');
				$this->sqlParser->setFieldValue('online', ($online->getValue()) ? strftime('%Y-%m-%d', strtotime($online->getValue())): '' );

				$offline = $this->sqlParser->getFieldByName('offline');
				$this->sqlParser->setFieldValue('offline', ($offline->getValue()) ? strftime('%Y-%m-%d', strtotime($offline->getValue())): '' );
			}

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

		if(!$request->exists('id')) throw new Exception('Node ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		// check if node exists
		if(!$this->tree->exists($id)) throw new HttpException('404');
		$this->tree->setCurrentId($id);
		$this->renderBreadcrumb();

		// check if user has execute rights
		$authentication = Authentication::getInstance();
		if(!$authentication->canDelete($id)) throw new HttpException('403');

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
			if(!$request->exists('id')) throw new Exception('Node ontbreekt.');
			$id = intval($request->getValue('id'));

			// check if user has execute rights
			$authentication = Authentication::getInstance();
			if(!$authentication->canDelete($id)) throw new HttpException('403');

			$parent = $this->tree->getParentId($id);

			$this->delete(array('id' => $id));
			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview($parent);
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminDeleteGet();
		}

	} 
//}}}

	/*-------- render tree {{{--------------*/
	/**
	 * renders tree into menu template
	 * @return  object
	 */
	public function renderTree()
	{
		$template = new TemplateEngine($this->getPath()."templates/menu.tpl");
		$menu = $this->tree->getList();
		//print_r($menu);

		// get selected main menu item
		//$firstNode = $this->tree->getFirstAncestorNode($this->tree->getCurrentId());
		//$firstId = ($firstNode) ? $firstNode['id'] : 0;

		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if(($id = intval($request->getValue('tree_id', Request::GET))) < 1) $id = $this->tree->getFirstNodeId();
		$parent = $this->tree->getParentId($id);

		$tree = $this->director->tree;
		$url = new Url();
		$url->setPath($tree->getPath($tree->getCurrentId()));
		//$url->setPath($request->getPath());
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);

		$template->setVariable('current_node',  $this->getMenuId(), false);

		// add root node
		$siteGroup = $this->getSiteGroup();
		$rootId = $this->tree->getRootId();
		$parentRootId = $rootId -1;
		$this->fileVars['parentRootId'] = $parentRootId;
		$template->setVariable('parentRootId',  $parentRootId, false);
		$template->setVariable('rootId',  $rootId, false);

		$rootnode = array('id' => $rootId,
											'parent' => $parentRootId,
											'name'	=> sprintf("%s (%s)", $this->tree->getTreeName(), $siteGroup->getLanguage($siteGroup->getCurrentId())),
											'active' => 1,
											'visible' => 1,
											'activated' => 1);

		array_unshift($menu, $rootnode);

		foreach($menu as &$item)
		{
			$url->setParameter('tree_id', $item['id']);
			$item['href_overview'] = $url->getUrl(true);
			$item['name'] = addslashes($item['name']);
		}

		$template->setVariable('sitemenu',  $menu, false);
		return $template;
	}
	//}}}

	/*-------- render form {{{--------------*/

	private function renderBreadcrumb()
	{
		$breadcrumb = $this->tree->getAncestorList($this->tree->getCurrentId(), true);
		$theme = $this->director->theme;

		$view = ViewManager::getInstance();
		$url = new Url();
		$url->useCurrent(false);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		
		foreach($breadcrumb as &$item)
		{
			$url->setParameter('id', $item['id']);
			$item['path'] = $url->getUrl(true);
			$theme->addBreadcrumb($item);
		}
		//$theme->mergeBreadcrumb($this->breadcrumb);
	}

	private function renderSiteGroup()
	{
		$template = new TemplateEngine($this->getPath()."templates/sitegroup.tpl");

		$siteGroup = $this->getSiteGroup();
		$id = $siteGroup->getCurrentId();

		$url = new Url();
		$url->useCurrent(false);

		$grouplist = $siteGroup->getList();
		//if($grouplist['totalItems'] < 2) return;

		foreach($grouplist['data'] as &$item)
		{
			$url->setParameter(SystemSiteGroup::CURRENT_ID_KEY, $item['id']);
			$item['href_detail'] = $url->getUrl(true);
			$item['selected'] = ($item['id'] == $id);
		}
		$template->setVariable('sitegroup',  $grouplist, false);

		// add link to sitegroup to add / modify / delete websites
		$admin = $this->director->adminManager;
		$admintree = $admin->tree;
		$sitegroupPath = $admintree->getPath($admintree->getIdFromClassname('SiteGroup'));
		$template->setVariable('href_sitegroup',  $sitegroupPath, false);

		return $template;
	}

	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 */
	public function renderForm($theme)
	{
		$this->fileVars['htdocsPath'] = $this->getHtdocsPath();

		$template = $theme->getTemplate();
		$template->setVariable('nodeName',  $this->tree->getName($this->tree->getCurrentId()), false);
		$template->setVariable('htdocsPath',  $this->getHtdocsPath(), false);

		$template->setVariable('tpl_sitemenu',  $this->renderTree(), false);
		$template->setVariable('tpl_sitegroup',  $this->renderSiteGroup(), false);

		// add specific variables to list
		$theme->addFileVars($this->fileVars);

		// parse rpc file to set variables
		$rpcfile_src = $this->getHtdocsPath(true)."rpc.js.in";
		$theme->addJavascript($theme->fetchFile($rpcfile_src));
		//$rpcfile_dst = $this->getCachePath(true)."rpc.js";
		//$theme->parseFile($rpcfile_src, $rpcfile_dst);

		// parse dtree file to set variables
		$dtreefile_src = $this->getHtdocsPath(true)."dtree.js.in";
		$theme->addJavascript($theme->fetchFile($dtreefile_src));
		//$dtreefile_dst = $this->getCachePath(true)."dtree.js";
		//$theme->parseFile($dtreefile_src, $dtreefile_dst);

		$theme->addJavascript(file_get_contents($this->getHtdocsPath(true).'sync.js'));
		$theme->addStylesheet(file_get_contents($this->getHtdocsPath(true).'dtree.css'));

		// parse stylesheet to set variables
		$stylesheet_src = $this->getHtdocsPath(true)."css/style.css.in";
		$theme->addStylesheet($theme->fetchFile($stylesheet_src));
		//$stylesheet_dst = $this->getCachePath(true)."style.css";
		//$theme->parseFile($stylesheet_src, $stylesheet_dst);

		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_lib.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_wrappers.js"></script>');
		//$theme->addHeader('<script type="text/javascript" src="'.$this->getCachePath().'rpc.js"></script>');
/*
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');

		*/
		//$theme->addHeader('<script type="text/javascript" src="'.$this->getHtdocsPath().'sync.js"></script>');

		//$theme->addHeader('<link href="'.$this->getHtdocsPath().'dtree.css" rel="stylesheet" type="text/css" media="screen" />');
		//$theme->addHeader('<script type="text/javascript" src="'.$this->getCachePath().'dtree.js"></script>');

		//$theme->addHeader('<link href="'.$this->getCachePath().'style.css" rel="stylesheet" type="text/css" media="screen" />');

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
//}}}

		// get all tags
	private function getAvailableTagList($param)
	{
		$tree_id = $param['tree_id'];
		$tag = $param['tag'];
		$retval = array();

		try
		{
			$sitePlugin = $this->getSitePlugin();
			$searchcriteria = array('tree_id' => $tree_id);
			$taglist = $this->getTagList($searchcriteria);
			// get used tags
			$usedTags = $sitePlugin->getTagList($searchcriteria);
			foreach($usedTags as $skiptag)
			{
				// do not skip our own tag off course...
				if($skiptag['tag'] == $tag) continue;

				// do not skip pure virtual plugins
				if($skiptag['virtual_type'] == SystemSitePlugin::TYPE_VIRTUAL) continue;

				unset($taglist[$skiptag['tag']]);
			}
			return array_values($taglist);
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
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getAvailableTagList", array(&$this,'handleRpcRequest'));
	}

}

?>
