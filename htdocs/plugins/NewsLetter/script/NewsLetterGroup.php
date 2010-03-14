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

require_once "NewsLetterUser.php";

/**
 * Main configuration 
 * @package Common
 */
class NewsLetterGroup extends Observer
{
	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	/**
	 * pointer to global plugin plugin
	 * @var NewsLetter
	 */
	private $plugin;

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct($plugin)
	{
		parent::__construct();
		$this->plugin = $plugin;
		$this->pagerKey = 'nlgrp';
		
		$this->template = array();
		$this->templateFile = "newslettergroup.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('newsletter_group', 'a');
		$this->sqlParser->addField(new SqlField('a', 'grp_id', 'id', 'Id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'grp_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_name', 'name', 'Name', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_count', 'count', 'Newsletters sent', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'grp_usr_id', 'usr_id', 'User', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_own_id', 'own_id', 'Owner', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_create', 'createdate', 'Created', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
	}

/*-------- Helper functions {{{------------*/
	private function getPath()
	{
		return $this->basePath;
	}

	private function getHtDocsPath($absolute=false)
	{
		return $this->plugin->getHtDocsPath($absolute);
	}

	/**
	 * remove user from group
   *
	 * @param array key of user
	 * @return string name of user
	 * @see AuthenticationUser::getUserName
	 */
	public function removeUser($groupId)
	{
		$query = sprintf("delete from  newsletter_usergroup where grp_id = %d", $groupId['id']);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * remove user from group
   *
	 * @param array key of user
	 * @return string name of user
	 * @see AuthenticationUser::getUserName
	 */
	public function deleteUserGroup($tree_id, $tag)
	{
		$query = sprintf("delete a from newsletter_usergroup as a inner join newsletter_group as b on b.grp_id = a.grp_id where b.grp_tree_id = %d and b.grp_tag = '%s'", $tree_id, $tag);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	public function updateTag($tree_id, $tag, $new_tree_id, $new_tag)
	{
		$searchcriteria = array('tag' => $tag, 'tree_id' => $tree_id);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria, false);
		$this->parseCriteria($sqlParser, $searchcriteria);
		$sqlParser->setFieldValue('tag', $new_tag);
		$sqlParser->setFieldValue('tree_id', $new_tree_id);

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	public function updateTreeId($sourceNodeId, $destinationNodeId)
	{
		$searchcriteria = array('tree_id' => $sourceNodeId);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria, false);
		$this->parseCriteria($sqlParser, $searchcriteria);
		$sqlParser->setFieldValue('tree_id', $destinationNodeId);

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	public function updateCount($key)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($key, false);
		$this->parseCriteria($sqlParser, $key, false);
		$sqlParser->setFieldValue('count', 'grp_count+1', false);

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}
//}}}

/*-------- DbConnector insert function {{{------------*/

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	protected function parseCriteria($SqlParser, $searchcriteria)
	{
		if(!$searchcriteria || !is_array($searchcriteria)) return;

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.grp_id', $value, '<>')); break;
				case 'search' : $SqlParser->addCriteria(new SqlCriteria('a.grp_name', "%$value%", 'like')); break;
			}
		}
	}

	/**
	 * returns default value of a field
	 * @return mixed
	 * @see DbConnector::getDefaultValue
	 */
	public function getDefaultValue($fieldname)
	{
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$fields['usr_id'] = $userId['id'];

		$fields['comment_order_asc'] = (array_key_exists('comment_order_asc', $fields) && $fields['comment_order_asc']);
		$fields['comment'] = (array_key_exists('comment', $fields) && $fields['comment']);
		$fields['comment_notify'] = (array_key_exists('comment_notify', $fields) && $fields['comment_notify']);
		
		return $fields;
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$this->sqlParser->setFieldValue('own_id', $userId['id']);

		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));

		// check if email is unique
		$searchcriteria = array('tree_id' => $values['tree_id'], 'tag' => $values['tag'], 'name' => $values['name']);
		if($this->exists($searchcriteria)) throw new Exception("Name already exists");
	}

	/**
	 * handle pre insert checks and additions 
	 * eg. check for uniqueness of set default values
   *
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePreInsert
	 */
	protected function handlePreUpdate($values)
	{
		// check if email is unique
		$searchcriteria = array('no_id' => $values['id'], 'tree_id' => $values['tree_id'], 'tag' => $values['tag'], 'name' => $values['name']);
		if($this->exists($searchcriteria)) throw new Exception("Name already exists");
	}

	protected function handlePreDelete($id, $values)
	{
		// remove users
		$this->removeUser($id);
	}
	//}}}

/*----- handle http requests {{{ -------*/
	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$view = ViewManager::getInstance();

		switch($view->getType())
		{
			case NewsLetter::VIEW_GROUP_OVERVIEW : $this->handleOverview(); break;
			case NewsLetter::VIEW_GROUP_NEW : $this->handleNewGet(); break;
			case NewsLetter::VIEW_GROUP_EDIT : $this->handleEditGet(); break;
			case NewsLetter::VIEW_GROUP_DELETE : $this->handleDeleteGet(); break;
			case NewsLetter::VIEW_GROUP_USER : $this->handleUserGet(); break;
		}
	}

	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$view = ViewManager::getInstance();

		switch($view->getType())
		{
			case NewsLetter::VIEW_GROUP_OVERVIEW : $this->handleOverview(); break;
			case NewsLetter::VIEW_GROUP_NEW : $this->handleNewPost(); break;
			case NewsLetter::VIEW_GROUP_EDIT : $this->handleEditPost(); break;
			case NewsLetter::VIEW_GROUP_DELETE : $this->handleDeletePost(); break;
			case NewsLetter::VIEW_GROUP_USER : $this->handleUserPost(); break;
		}

	} 

//}}}

/*------- overview request {{{ -------*/
	/**
	 * handle overview
	*/
	private function handleOverview()
	{
		$pagesize = 20;
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_GROUP_OVERVIEW);

		$page = $this->getPage();

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$this->pagerUrl->addParameters($key);
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		// handle searchcriteria
		$search = new SearchManager();
		$search->setUrl($this->pagerUrl);
		$search->setExclude($this->pagerKey);
		$search->setParameter('search');
		$search->saveList();
		$searchcriteria = $search->getSearchParameterList();
		$searchcriteria = array_merge($searchcriteria, $key);

		$url = new Url(true);
		$url->clearParameter('id');
		$url->clearParameter('nl_id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), NewsLetter::VIEW_GROUP_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), NewsLetter::VIEW_GROUP_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), NewsLetter::VIEW_GROUP_DELETE);

		$url_user = clone $url;
		$url_user->setParameter($view->getUrlId(), NewsLetter::VIEW_GROUP_USER);

		$list = $this->getList($searchcriteria, $pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);
			$url_user->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
			$item['href_user'] = $url_user->getUrl(true);
		}
		$template->setVariable('list',  $list, false);
		$template->setVariable('searchparam',  $search->getMandatoryParameterList(), false);
		$template->setVariable('searchcriteria',  $searchcriteria, false);

		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}
//}}}

/*------- handle breadcrumb {{{ -------*/
	/**
	 * handle breadcrumb
	*/
	private function handleBreadcrumb($template)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');
		$template->setVariable('tree_id', $tree_id);
		$template->setVariable('tag', $tag);

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter($view->getUrlId(), NewsLetter::VIEW_GROUP_OVERVIEW);
		$breadcrumb = array('name' => $view->getName(NewsLetter::VIEW_GROUP_OVERVIEW), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		$this->director->theme->handleAdminLinks($template);
	}
//}}}

/*-------  new request {{{ -------*/
	/**
	 * handle  new
	*/
	private function handleNewGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_GROUP_NEW);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$fields['tree_id'] 	= $tree_id;
		$fields['tag'] 			= $tag;


		$template->setVariable($fields);
		$template->clearVariable('id');

		// add breadcrumb item
		$this->handleBreadcrumb($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleNewPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			$id = $this->insert($values);

			viewManager::getInstance()->setType(NewsLetter::VIEW_GROUP_OVERVIEW);
			$this->handleOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleNewGet();
		}

	} 
//}}}

/*-------  edit request {{{ -------*/
	/**
	 * handle  edit
	*/
	private function handleEditGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_GROUP_EDIT);

		if(!$request->exists('id')) throw new Exception('Newsletter is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);
		$key = array('id' => $id);

		if($retrieveFields)
		{
			$fields = $this->getDetail($key);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		$this->setFields($fields);
		$template->setVariable($fields);

		// add breadcrumb item
		$this->handleBreadcrumb($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleEditPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			if(!$request->exists('id')) throw new Exception('Newsletter is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->update($key, $values);

			viewManager::getInstance()->setType(NewsLetter::VIEW_GROUP_OVERVIEW);
			$this->handleOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleEditGet(false);
		}

	} 
//}}}

/*-------  delete request {{{ -------*/
	/**
	 * handle  delete
	*/
	private function handleDeleteGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_GROUP_DELETE);

		if(!$request->exists('id')) throw new Exception('Newsletter is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		$template->setVariable($this->getDetail(array('id' => $id)), NULL, false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleDeletePost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Newsletter is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->delete($key);

			viewManager::getInstance()->setType(NewsLetter::VIEW_GROUP_OVERVIEW);
			$this->handleOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleDeleteGet();
		}
	} 
//}}}

/*------- user request {{{ -------*/
	/**
	 * handle user
	*/
	private function handleUserGet($retrieveFields=true)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		if(!$request->exists('id')) throw new Exception('User group is missing.');
		$id = intval($request->getValue('id'));

		$template->setVariable('id',  $id, false);
		$key = array('id' => $id);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$user = new NewsLetterUser($this->plugin);

		$usr_used = $request->getValue('usr_used');

		if($retrieveFields)
		{
			$searchcriteria = array('grp_id' => $id);
			$tmp = $user->getList($searchcriteria);
			$usr_used = $tmp['data'];
		}

		$search_used = ($usr_used) ? array('id' => $usr_used) : NULL;
		$search_free = ($usr_used) ? array('no_id' => $usr_used, 'tree_id' => $tree_id, 'tag' => $tag) : array('tree_id' => $tree_id, 'tag' => $tag);
		$user_used = ($usr_used) ? $user->getList($search_used) : array('data'=>'');
		$user_free = $user->getList($search_free);
		$template->setVariable('cbo_usr_used', Utils::getHtmlCombo($user_used['data'], NULL, NULL, 'id', 'formatName'));
		$template->setVariable('cbo_usr_free', Utils::getHtmlCombo($user_free['data'], NULL, NULL, 'id', 'formatName'));

		$this->handleBreadcrumb($template);
		$template->setVariable('title',  $this->getName($key), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleUserPost()
	{
		$request = Request::getInstance();
		$user = new NewsLetterUser($this->plugin);

		$usr_used = $request->getValue('usr_used');
		if(!$usr_used) $usr_used = array();

		try 
		{
			if(!$request->exists('id')) throw new Exception('User group is missing.');
			$id = intval($request->getValue('id'));
			$key = array('id' => $id);

			$this->removeUser($key);
			foreach($usr_used as $item)
			{
				$user->addGroup(array('id' => $item), $key);
			}

			viewManager::getInstance()->setType(NewsLetter::VIEW_GROUP_OVERVIEW);
			$this->handleOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleUserGet(false);
		}

	} 
//}}}

	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 * @see GuiProvider::renderForm
	 */
	public function renderForm($theme)
	{

		$view = ViewManager::getInstance();

		$template = $theme->getTemplate();
		$template->setVariable($view->getUrlId(), $view->getName());

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key, $value);
		}
	}
}

?>
