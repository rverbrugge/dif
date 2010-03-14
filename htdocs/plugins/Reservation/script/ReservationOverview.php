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

require_once(DIF_ROOT."utils/ParseFile.php");
require_once(DIF_ROOT."utils/SearchManager.php");

/**
 * Main configuration 
 * @package Common
 */
class ReservationOverview extends Observer
{

	const ORDER_TIME_ASC = 4;
	const ORDER_TIME_DESC = 8;

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
		
		$this->template = array();
		$this->templateFile = "reservationoverview.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('reservation', 'a');
		$this->sqlParser->addField(new SqlField('a', 'res_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'res_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'res_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'res_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('b', 'usr_name', 'name', 'AchterNaam', SqlParser::getTypeSelect()|SqlParser::NAME, SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('b', 'usr_firstname', 'firstname', 'Voornaam', SqlParser::getTypeSelect()|SqlParser::NAME, SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'res_usr_id', 'usr_id', 'User', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'res_date', 'reservation_date', 'Date', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'res_time', 'reservation_time', 'Time', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'res_vip', 'vip', 'VIP reservation', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'res_own_id', 'own_id', 'Owner', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'res_create', 'createdate', 'Created', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'res_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->addFrom('left join users as b on b.usr_id = a.res_usr_id');
		$this->orderStatement = array(self::ORDER_TIME_ASC => 'order by a.res_date %s, a.res_time asc , b.usr_name asc',
																	self::ORDER_TIME_DESC => 'order by a.res_date %s, a.res_time desc , b.usr_name asc');
	}

/*-------- Helper functions {{{------------*/
	private function getPath()
	{
		return $this->basePath;
	}

	public function getVipCount($search)
	{
		if(!array_key_exists('date', $search)) throw new Exception('item "date" is missing in '.__FUNCTION__);
		if(!array_key_exists('hour', $search)) throw new Exception('item "hour" is missing in '.__FUNCTION__);
		if(!array_key_exists('tree_id', $search)) throw new Exception('item "tree_id" is missing in '.__FUNCTION__);
		if(!array_key_exists('tag', $search)) throw new Exception('item "tag" is missing in '.__FUNCTION__);

		$query = sprintf("select count(res_id) from reservation where res_vip = 1 and res_date = '%s' and res_time = %d and res_tree_id = %d and res_tag = '%s'", 
											addslashes($search['date']), 
											$search['hour'], 
											$search['tree_id'], 
											addslashes($search['tag']));

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		return $res->fetchOne();
	}

	public function getSubscriptionCount($search)
	{
		if(!array_key_exists('date', $search)) throw new Exception('item "date" is missing in '.__FUNCTION__);
		if(!array_key_exists('hour', $search)) throw new Exception('item "hour" is missing in '.__FUNCTION__);
		if(!array_key_exists('tree_id', $search)) throw new Exception('item "tree_id" is missing in '.__FUNCTION__);
		if(!array_key_exists('tag', $search)) throw new Exception('item "tag" is missing in '.__FUNCTION__);

		$query = sprintf("select count(res_id) from reservation where res_date = '%s' and res_time = %d and res_tree_id = %d and res_tag = '%s'", 
											addslashes($search['date']), 
											$search['hour'], 
											$search['tree_id'], 
											addslashes($search['tag']));

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		return $res->fetchOne();
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
		
		// update settings
		$settings = $this->plugin->getObject(Reservation::TYPE_SETTINGS);
		$settings->updateTag($tree_id, $tag, $new_tree_id, $new_tag);

		// update block periods
		$period = $this->plugin->getObject(Reservation::TYPE_BLOCK_PERIOD);
		$period->updateTag($tree_id, $tag, $new_tree_id, $new_tag);

		// update user group
		$userGroup = $this->plugin->getObject(Reservation::TYPE_USER_GROUP);
		$userGroup->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
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

		// update settings
		$settings = $this->plugin->getObject(Reservation::TYPE_SETTINGS);
		$settings->updateTreeId($tag, $tree_id, $newTag);

		// update block periods
		$period = $this->plugin->getObject(Reservation::TYPE_BLOCK_PERIOD);
		$period->updateTreeId($tag, $tree_id, $newTag);

		// update user group
		$userGroup = $this->plugin->getObject(Reservation::TYPE_USER_GROUP);
		$userGroup->updateTreeId($tag, $tree_id, $newTag);
	}

	public function getDatePicker($settings)
	{
		$theme = $this->director->theme;

		$template = new TemplateEngine();
		$template->setVariable('adminSection', $this->director->isAdminSection(), false);
		$template->setVariable($settings, Null, false);
		$theme->addFileVar('reservation_htdocs_path', $this->plugin->getHtdocsPath());

		// parse rpc file to set variables
		$rpcfile_src = $this->plugin->getHtdocsPath(true)."js/rpc.js.in";
		$theme->addJavascript($theme->fetchFile($rpcfile_src));

		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_lib.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_wrappers.js"></script>');

		$datefields = array();
		$datefields[] = array('dateField' => 'datevalue', 'triggerElement' => 'datevalue', 'selectHandler' => 'onDateChange');
		Utils::getDatePicker($theme, $datefields);
	}

	private function getUserCombo($tree_id, $usr_id)
	{
		$acl = new Acl();
		$aclList = $acl->getAclGroupList($tree_id);

		$groupList = array();
		foreach($aclList as $grp_id=>$rights)
		{
			if(!in_array(Acl::VIEW, $rights)) continue;
			$groupList[] = $grp_id;
		}

		$userList = $this->director->systemUser->getList(array('grp_id' => $groupList));
		return Utils::getHtmlCombo($userList['data'], $usr_id, NULL, 'id', 'formatName');
	}

	public function getUserCount($usr_id, $max_subscribe)
	{
		$searchcriteria = array('usr_id' => $usr_id, 'activated' => true, 'max_count' => $max_subscribe);

		//TODO check if sel_count works and then fix getCount function in DbConnector
		//return $this->getCount($searchcriteria);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria);
		$this->parseCriteria($sqlParser, $searchcriteria);

		$query = $sqlParser->getSql(SqlParser::SEL_COUNT);

		$db = $this->getDb();

		$rowcount = $db->queryOne($query);
		if($db->isError($rowcount)) throw new Exception($rowcount->getDebugInfo());
		return $rowcount;
	}

	public function getLegitimateUserList($usr_id, $max_subscribe)
	{
		/*
		$today = date('Y-m-d');//, mktime(0,0,0));
		$time = date('H');
		$query = "select distinct a.res_usr_id 
							from reservation as a 
							where not exists (
								select b.res_id 
								from reservation as b  
								where a.res_usr_id = b.res_usr_id 
								and (b.res_date > '$today' or b.res_date = '$today' and b.res_time > $time)
							) 
							or count(a.res_id) < $max_subscribe
							group by res_usr_id";*/

		if(is_numeric($usr_id))
		{
			// get list of users that have exceeded their reservations
			if($this->getUserCount($usr_id, $max_subscribe) > 0)
				return array();
			else
				return array($usr_id);
		}
		else
		{
			// get list of users that have exceeded their reservations
			$searchcriteria = array('usr_id' => $usr_id, 'activated' => true, 'max_count' => $max_subscribe);
			$list = $this->getList($searchcriteria);
			$dellist = array();
			// substract list of exceeders with requested users
			foreach($list['data'] as $item)
			{
				$dellist[] = $item['usr_id'];
			}
			return array_diff($usr_id, $dellist);
		}

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

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'no_id' : $sqlParser->addCriteria(new SqlCriteria('a.res_id', $value, '<>')); break;
				case 'start' : $sqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.res_date)', $value, '>')); break;
				case 'stop' : $sqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.res_date)', $value, '<=')); break;
				case 'max_count' : 
					//$sqlParser->addField(new SqlField('', 'count(a.res_id)', 'res_count', 'Reservation Count', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
					//$sqlParser->addCriteria(new SqlCriteria('a.res_count', $value, '>=')); break;
					$sqlParser->setGroupby("group by a.res_usr_id having count(a.res_id) >= $value");
					//$sqlParser->setGroupby("having count(a.res_id) >= $value");
				case 'activated' : 
					// only active pages
					$today = date('Y-m-d');//, mktime(0,0,0));
					$time = date('H');
					$search = new SqlCriteria($sqlParser->getFieldByName('reservation_date')->getField($prefix), $today, '>');
					$addsearch = new SqlCriteria($sqlParser->getFieldByName('reservation_date')->getField($prefix), $today, '=');
					$addsearch->addCriteria(new SqlCriteria($sqlParser->getFieldByName('reservation_time')->getField($prefix), $time, '>'), SqlCriteria::REL_AND);
					$search->addCriteria($addsearch, SqlCriteria::REL_OR);

					$sqlParser->addCriteria(new SqlCriteria($sqlParser->getFieldByName('active')->getField($prefix), 1));
					//$sqlParser->addCriteria(new SqlCriteria($sqlParser->getFieldByName('reservation_date')->getField($prefix), 'now()', '>'));
					$sqlParser->addCriteria($search);

					break;
				case 'search' : 
					$search = new SqlCriteria('b.usr_name', "%$value%", 'like'); 
					$search->addCriteria(new SqlCriteria('unix_timestamp(a.res_date)', strtotime($value), '='), SqlCriteria::REL_OR); 
					$search->addCriteria(new SqlCriteria('b.usr_firstname', "%$value%", 'like'), SqlCriteria::REL_OR); 
					$search->addCriteria(new SqlCriteria('b.usr_email', "%$value%", 'like'), SqlCriteria::REL_OR); 
					$sqlParser->addCriteria($search);
					break;
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
		switch($fieldname)
		{
			case 'active' : return 1; break;
			case 'reservation_date' : return strftime('%Y-%m-%d'); break;
		}
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
		$fields['active'] = (array_key_exists('active', $fields) && $fields['active']);
		$fields['reservation_date'] = (array_key_exists('reservation_date', $fields) && $fields['reservation_date']) ? strftime('%Y-%m-%d',strtotime($fields['reservation_date'])) : '';

		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$date = mktime(0,0,0);
		$time = date('H');

		$activated = 1;

		// hide reservationitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif($values['reservation_date'] > 0 && ($values['reservation_date'] < $date || ($values['reservation_date'] == $date && $values['reservation_time'] < $time) ))
			$activated = 0;

		$values['activated'] = $activated;
		
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$date = mktime(0,0,0);
		$time = date('H');

		$activated = 1;

		// hide attachmentitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif($values['reservation_date'] > 0 && ($values['reservation_date'] < $date || ($values['reservation_date'] == $date && $values['reservation_time'] < $time) ))
			$activated = 0;

		$values['activated'] = $activated;
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
	}

	//}}}

/*----- handle http requests {{{ -------*/
	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$viewManager = ViewManager::getInstance();

		// check if settings defined
		if($viewManager->isType(ViewManager::TREE_OVERVIEW))
		{
			$request = Request::getInstance();
			$settings = $this->plugin->getObject(Reservation::TYPE_SETTINGS);
			$key = array('tree_id' => intval($request->getValue('tree_id')), 'tag' => $request->getValue('tag'));
			if(!$settings->exists($key)) $viewManager->setType(Reservation::VIEW_CONFIG);
		}

		switch($viewManager->getType())
		{
			case Reservation::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW : $this->handleTreeNewGet(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeleteGet(); break;
			case Reservation::VIEW_CONFIG : $this->handleObjectGet(Reservation::TYPE_SETTINGS); break;
			case Reservation::VIEW_BLOCK_PERIOD_OVERVIEW :
			case Reservation::VIEW_BLOCK_PERIOD_NEW :
			case Reservation::VIEW_BLOCK_PERIOD_EDIT :
			case Reservation::VIEW_BLOCK_PERIOD_DELETE : $this->handleObjectGet(Reservation::TYPE_BLOCK_PERIOD); break;
			case Reservation::VIEW_USER_GROUP_OVERVIEW :
			case Reservation::VIEW_USER_GROUP_NEW :
			case Reservation::VIEW_USER_GROUP_EDIT :
			case Reservation::VIEW_USER_GROUP_DELETE : $this->handleObjectGet(Reservation::TYPE_USER_GROUP); break;
			default : $this->handleOverview(); break;
		}
	}

	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case Reservation::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW :  $this->handleTreeNewPost(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeletePost(); break;
			case Reservation::VIEW_CONFIG : $this->handleObjectPost(Reservation::TYPE_SETTINGS); break;
			case Reservation::VIEW_BLOCK_PERIOD_OVERVIEW :
			case Reservation::VIEW_BLOCK_PERIOD_NEW :
			case Reservation::VIEW_BLOCK_PERIOD_EDIT :
			case Reservation::VIEW_BLOCK_PERIOD_DELETE : $this->handleObjectPost(Reservation::TYPE_BLOCK_PERIOD); break;
			case Reservation::VIEW_USER_GROUP_OVERVIEW :
			case Reservation::VIEW_USER_GROUP_NEW :
			case Reservation::VIEW_USER_GROUP_EDIT :
			case Reservation::VIEW_USER_GROUP_DELETE : $this->handleObjectPost(Reservation::TYPE_USER_GROUP); break;
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

		// retrieve tags that are linked to this plugin
		$taglist = $this->plugin->getTagList(array('plugin_type' => Reservation::TYPE_DEFAULT));
		if(!$taglist) return;

		foreach($taglist as $tag)
		{
			//print_r($tag);
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$template->setPostfix($tag['tag']);
			$template->setCacheable(false);
			$template->setVariable('htdocs_path', $this->plugin->getHtdocsPath(false));

			$this->getDatePicker($tag);

			$this->template[$tag['tag']] = $template;
		}

		$this->director->theme->addJavascript('Event.observe( window, "load", function() { getTimeList(); } );');
	} //}}}

/*------- detail request {{{ -------*/
	/**
	 * handle detail request
	*/
	private function handleDetail()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		// clear subtitle
		$view->setName('');

		// check security
		if(!$request->exists('id')) throw new Exception('Reservation item is missing.');
		$id = intval($request->getValue('id'));
		$key = array('id' => $id, 'activated' => true);

		if(!$this->exists($key)) throw new HttpException('404');
		$detail = $this->getDetail($key);

		// check if tree node of reservation item is accessable
		$tree = $this->director->tree;
		if(!$tree->exists($detail['tree_id'])) throw new HttpException('404');

		// process request
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setPostfix($detail['tag']);
		$template->setCacheable(true);

		// overwrite default naming
		$template->setVariable('pageTitle',  $detail['name'], false);

		// add breadcrumb item
		$url = new Url(true);
		$url->clearParameter('id');

		$breadcrumb = array('name' => $detail['name'], 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		// check if template is in cache
		if(!$template->isCached())
		{
			$template->setVariable('reservation',  $detail);
		}

		$objSettings = $this->plugin->getObject(Reservation::TYPE_SETTINGS);
		$settings = $objSettings->getSettings($detail['tree_id'], $detail['tag']);
		$template->setVariable('reservationsettings',  $settings, false);

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter($view->getUrlId(), ViewManager::OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$detail['tag']] = $template;

	} 
//}}}

/*------- tree overview request {{{ -------*/
	/**
	 * handle tree overview
	*/
	private function handleTreeOverview()
	{
		$pagesize = 20;
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(ViewManager::TREE_OVERVIEW);

		$page = $this->getPage();

		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

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
		$url->clearParameter('res_id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);


		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::TREE_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_conf = clone $url;
		$url_conf->setParameter($view->getUrlId(), Reservation::VIEW_CONFIG);
		$template->setVariable('href_conf',  $url_conf->getUrl(true), false);

		$url_period = clone $url;
		$url_period->setParameter($view->getUrlId(), Reservation::VIEW_BLOCK_PERIOD_OVERVIEW);
		$template->setVariable('href_period',  $url_period->getUrl(true), false);

		$url_usergroup = clone $url;
		$url_usergroup->setParameter($view->getUrlId(), Reservation::VIEW_USER_GROUP_OVERVIEW);
		$template->setVariable('href_usergroup',  $url_usergroup->getUrl(true), false);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::TREE_DELETE);

		$list = $this->getList($searchcriteria, $pagesize, $page, self::ORDER_TIME_DESC|SqlParser::ORDER_DESC);
		foreach($list['data'] as &$item)
		{
			$url_del->setParameter('id', $item['id']);

			$item['href_del'] = $url_del->getUrl(true);
		}
		$template->setVariable('list',  $list, false);
		$template->setVariable('searchparam',  $search->getMandatoryParameterList(), false);
		$template->setVariable('searchcriteria',  $searchcriteria, false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}
//}}}

/*------- tree settings {{{ -------*/
	private function handleTreeSettings($template)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();

		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		$key = array('id' => $request->getValue('id'));
		$name = ($view->isType(ViewManager::TREE_EDIT) || $view->isType(ViewManager::TREE_DELETE)) ? $this->getName($key) : NULL;

		$this->director->theme->handleAdminLinks($template, $name);
	}

//}}}

/*------- tree new request {{{ -------*/
	/**
	 * handle tree new
	*/
	private function handleTreeNewGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(ViewManager::TREE_NEW);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$fields['tree_id'] 	= $tree_id;
		$fields['tag'] 			= $tag;

		$this->handleTreeSettings($template);

		$template->setVariable($fields);
		$template->clearVariable('id');

		$template->setVariable('cbo_user', $this->getUserCombo($tree_id, $fields['usr_id']));
		$this->getDatePicker($key);
		$this->director->theme->addJavascript('Event.observe( window, "load", function() { getTimeList(); } );');

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeNewPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			$id = $this->insert($values);

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleTreeOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			$this->handleTreeNewGet();
		}

	} 
//}}}

/*------- tree delete request {{{ -------*/
	/**
	 * handle tree delete
	*/
	private function handleTreeDeleteGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(ViewManager::TREE_DELETE);

		if(!$request->exists('id')) throw new Exception('Reservation is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id);

		$template->setVariable($this->getDetail(array('id' => $id)), NULL, false);
		$this->handleTreeSettings($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeDeletePost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Reservation is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->delete($key);

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleTreeOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleTreeDeleteGet();
		}
	} 
//}}}

/*------- handle navigation for sub classes / pages {{{ -------*/
	/**
	 * handle attachment request
	*/
	private function handleSubNavigation()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$template =  new TemplateEngine();

		if(!$request->exists('res_id')) return;

		$res_id = $request->getValue('res_id');
		$newsName = $this->getName(array('id' => $res_id));
		$template->setVariable('pageTitle', $newsName, false);

		$tree_id = $request->getValue('tree_id');
		$tag = $request->getValue('tag');
		$template->setVariable('tree_id', $tree_id, false);
		$template->setVariable('tag', $tag, false);
		$template->setVariable('res_id', $res_id, false);
	}
//}}}

/*------- handle object requests {{{ -------*/

	/**
	 * handle object get request
	*/
	private function handleObjectGet($objectType)
	{
		// add object to renderlist
		$this->plugin->addRenderList($objectType);

		$this->handleSubNavigation();
		$obj = $this->plugin->getObject($objectType);
		$obj->handleHttpGetRequest();
	}

	/**
	 * handle object post request
	*/
	private function handleObjectPost($objectType)
	{
		// add object to renderlist
		$this->plugin->addRenderList($objectType);

		$this->handleSubNavigation();
		$obj = $this->plugin->getObject($objectType);
		$obj->handleHttpPostRequest();
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
		$template->setVariable($view->getUrlId(),  $view->getName(), false);

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
