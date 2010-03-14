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
class ReservationUserGroup extends Observer
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
	 * @var Reservation
	 */
	private $plugin;

	/**
	 * array with acl group identifiers linked to the node of the reservation plugin
	 * @var array
	 */
	private $groupList;

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
		$this->pagerKey = 'ugrp';
		
		$this->template = array();
		$this->templateFile = "reservationusergroup.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('reservation_usergroup', 'a');
		$this->sqlParser->addField(new SqlField('a', 'grp_id', 'id', 'Id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'grp_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_usr_id', 'usr_id', 'User id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('b', 'usr_name', 'name', 'Name', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('b', 'usr_firstname', 'firstname', 'First Name', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'grp_create', 'createdate', 'Created', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->addFrom('inner join users as b on a.grp_usr_id = b.usr_id');

		$this->sqlParser->setGroupby('group by b.usr_name asc');
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
//}}}

/*-------- DbConnector insert function {{{------------*/

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	protected function parseCriteria($sqlParser, $searchcriteria, $prefix=true)
	{
		if(!$searchcriteria || !is_array($searchcriteria)) return;
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
		return $fields;
	}

	protected function handlePostGetList($values)
	{
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
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
		$sqlParser->addCriteria(new SqlCriteria('grp_usr_id', $values['usr_id']));
		$sqlParser->addCriteria(new SqlCriteria('grp_tree_id', $values['tree_id']));
		$sqlParser->addCriteria(new SqlCriteria('grp_tag', $values['tag']));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('User group already exists.');
	}

	/**
	 * handle pre insert checks and additions 
	 * eg. check for uniqueness of set default values
   *
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePreInsert
	 */
	protected function handlePreUpdate($id, $values)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('grp_usr_id', $values['usr_id']));
		$sqlParser->addCriteria(new SqlCriteria('grp_tree_id', $values['tree_id']));
		$sqlParser->addCriteria(new SqlCriteria('grp_tag', $values['tag']));
		$sqlParser->addCriteria(new SqlCriteria('grp_id', $id['id'], '<>'));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('User group already exists.');
	}

	protected function handlePreDelete($id, $values)
	{
	}

	/**
	 * handle pre update checks and additions 
	 * eg. check for uniqueness of set default values
   *
	 * @param array filtered values for insertion
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePreInsert
	 */
	protected function handlePostDelete($id, $values)
	{
		$userLink = new ReservationUserLink();
		$userLink->delete(array('grp_id' => $id['id']));
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
			case Reservation::VIEW_USER_GROUP_OVERVIEW : $this->handleOverview(); break;
			case Reservation::VIEW_USER_GROUP_NEW : $this->handleNewGet(); break;
			case Reservation::VIEW_USER_GROUP_EDIT : $this->handleEditGet(); break;
			case Reservation::VIEW_USER_GROUP_DELETE : $this->handleDeleteGet(); break;
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
			case Reservation::VIEW_USER_GROUP_OVERVIEW : $this->handleOverview(); break;
			case Reservation::VIEW_USER_GROUP_NEW : $this->handleNewPost(); break;
			case Reservation::VIEW_USER_GROUP_EDIT : $this->handleEditPost(); break;
			case Reservation::VIEW_USER_GROUP_DELETE : $this->handleDeletePost(); break;
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
		$view->setType(Reservation::VIEW_USER_GROUP_OVERVIEW);

		$page = $this->getPage();

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$this->pagerUrl->addParameters($key);
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());


		$url = new Url(true);
		$url->clearParameter('id');
		$url->clearParameter('nl_id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), Reservation::VIEW_USER_GROUP_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), Reservation::VIEW_USER_GROUP_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), Reservation::VIEW_USER_GROUP_DELETE);

		$list = $this->getList($key, $pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
		}
		$template->setVariable('list',  $list, false);
		$template->setVariable('searchcriteria',  $searchcriteria, false);

		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}
//}}}

/*------- Handle breadcrumb and user combo lists {{{ -------*/
	private function getGroupList($tree_id)
	{
		if($this->groupList) return $this->groupList;

		$acl = new Acl();
		$aclList = $acl->getAclGroupList($tree_id);

		$this->groupList = array();
		foreach($aclList as $grp_id=>$rights)
		{
			if(!in_array(Acl::VIEW, $rights)) continue;
			$this->groupList[] = $grp_id;
		}
		return $this->groupList;
	}

	public function getUserSelection($tree_id, $usr_id, $grp_id=NULL, $usr_used=NULL)
	{
		$user = $this->director->systemUser;
		// get user selection combobox
		//if($view->isType(Reservation::VIEW_USER_GROUP_EDIT) && $request->getRequestType() == Request::GET)
		if($grp_id && !$usr_used)
		{
			$userLink = new ReservationUserLink();
			$usrlist = $userLink->getList(array('grp_id' => $grp_id));
			$search = array();
			foreach($usrlist['data'] as $item)
			{
				$search[] = $item['usr_id'];
			}
			$tmp = $user->getList(array('id' => $search));
			$usr_used = $tmp['data'];
		}

		$groupList = $this->getGroupList($tree_id);

		if(!$usr_used) $usr_used = array();

		$usr_used_free_search = $usr_used;
		/*
 		if(is_array(current($usr_used_free_search)))
			$usr_used_free_search[] = array('id' => $usr_id);
		else
			*/
			$usr_used_free_search[] = $usr_id;

		$search_used = ($usr_used) ? array('id' => $usr_used) : NULL;
		$search_free = ($usr_used) ? array('grp_id' => $groupList, 'no_id' => $usr_used_free_search) : array('grp_id' => $groupList, 'no_id' => $usr_id);
		$user_used = ($usr_used) ? $user->getList($search_used) : array('data'=>'');
		$user_free = $user->getList($search_free);

		$template = new TemplateEngine($this->getPath()."templates/reservationusergroupselect.tpl");
		$template->setVariable('cbo_usr_used', Utils::getHtmlCombo($user_used['data'], NULL, NULL, 'id', 'formatName'));
		$template->setVariable('cbo_usr_free', Utils::getHtmlCombo($user_free['data'], NULL, NULL, 'id', 'formatName'));
		return $template;
	}

	/**
	 * handle overview
	*/
	private function handleEdit($template, $usr_id)
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id', $tree_id, false);
		$template->setVariable('tag', $tag, false);

		$user = $this->director->systemUser;
		$usr_used = $request->getValue('usr_used');

		// get list of users that have access to the reservation module
		$groupList = $this->getGroupList($tree_id);

		$theme = $this->director->theme;
		$theme->addJavascript(file_get_contents($this->plugin->getHtdocsPath(true).'js/multibox.js'));

		// get list of group owners
		if($view->isType(Reservation::VIEW_USER_GROUP_NEW))
		{
			$userList = $user->getList(array('grp_id' => $groupList));
			$template->setVariable('cbo_user', Utils::getHtmlCombo($userList['data'], $usr_id, 'select a user...', 'id', 'formatName'));
		
			$theme->addFileVar('reservation_htdocs_path', $this->plugin->getHtdocsPath());

			// parse rpc file to set variables
			$rpcfile_src = $this->plugin->getHtdocsPath(true)."js/rpc.js.in";
			$theme->addJavascript($theme->fetchFile($rpcfile_src));

			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');
			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_lib.js"></script>');
			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_wrappers.js"></script>');
		}
		
		if($view->isType(Reservation::VIEW_USER_GROUP_EDIT) || $request->getRequestType() == Request::POST)
		{
			$grp_id = intval($request->getValue('id'));
			$template->setVariable('tpl_userselect', $this->getUserSelection($tree_id, $usr_id, $grp_id, $usr_used));
		}

		// add overview link
		$view = ViewManager::getInstance();
		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);
		$url->setParameter($view->getUrlId(), Reservation::VIEW_USER_GROUP_OVERVIEW);
		$breadcrumb = array('name' => $view->getName(Reservation::VIEW_USER_GROUP_OVERVIEW), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		// create up & back links and create breadcrumb
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
		$view->setType(Reservation::VIEW_USER_GROUP_NEW);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$fields['tree_id'] 	= $tree_id;
		$fields['tag'] 			= $tag;

		$template->setVariable($fields);
		$template->clearVariable('id');

		$this->handleEdit($template, $fields['usr_id']);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleNewPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			$id = $this->insert($values);
			$key = array('grp_id' => $id['id']);

			$user = $this->director->systemUser;
			$usr_used = $request->getValue('usr_used');
			if(!$usr_used) $usr_used = array();
			$userLink = new ReservationUserLink();
			foreach($usr_used as $item)
			{
				$key['usr_id'] = $item;
				$userLink->insert($key);
			}
			
			viewManager::getInstance()->setType(Reservation::VIEW_USER_GROUP_OVERVIEW);
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
		$view->setType(Reservation::VIEW_USER_GROUP_EDIT);

		if(!$request->exists('id')) throw new Exception('Reservation is missing.');
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

		// add breadcrumb and groups
		$this->handleEdit($template, $fields['usr_id']);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Reservation is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->update($key, $values);
			
			$key = array('grp_id' => $id);

			$user = $this->director->systemUser;
			$usr_used = $request->getValue('usr_used');
			if(!$usr_used) $usr_used = array();
			$userLink = new ReservationUserLink();

			// remove users from group
			$userLink->delete($key);

			foreach($usr_used as $item)
			{
				$key['usr_id'] = $item;
				$userLink->insert($key);
			}
			
			viewManager::getInstance()->setType(Reservation::VIEW_USER_GROUP_OVERVIEW);
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
		$view->setType(Reservation::VIEW_USER_GROUP_DELETE);

		if(!$request->exists('id')) throw new Exception('Reservation is missing.');
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
			if(!$request->exists('id')) throw new Exception('Reservation is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->delete($key);

			viewManager::getInstance()->setType(Reservation::VIEW_USER_GROUP_OVERVIEW);
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
