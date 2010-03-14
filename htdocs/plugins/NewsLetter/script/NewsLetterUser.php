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

require_once "NewsLetterGroup.php";

/**
 * Main configuration 
 * @package Common
 */
class NewsLetterUser extends Observer
{
	const GENDER_MALE = 1;
	const GENDER_FEMALE = 2;
	const GENDER_OTHER = 3;

	const KEY_UNSUBSCRIBE = 'm';

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

	static public $gendertypes 	= array(self::GENDER_MALE 			=> 'Dhr.',
																			self::GENDER_FEMALE		=> 'Mevr.',
																			self::GENDER_OTHER		=> 'n.v.t.');
	static private $gendertypelist;

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
		$this->pagerKey = 'nlusr';
		
		$this->template = array();
		$this->templateFile = "newsletteruser.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('newsletter_user', 'a');
		$this->sqlParser->addField(new SqlField('a', 'usr_id', 'id', 'Id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'usr_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'usr_gender', 'gender', 'Gender', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_name', 'name', 'Name', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_email', 'email', 'Email', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'usr_count', 'count', 'Newsletters sent', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'usr_bounce', 'bounce', 'Bounce count', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'usr_ip', 'ip', 'Ip adres', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_host', 'host', 'Host', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_client', 'client', 'Client', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_optin', 'optin', 'Opt-in', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'usr_unsubscribe', 'unsubscribe_date', 'Unsubscribe date', SqlParser::getTypeSelect()|SqlParser::MOD_DELETE, SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'usr_create', 'createdate', 'Created', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->setGroupby('group by a.usr_id');
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

	static public function getGenderTypeList()
	{
		if(isset(self::$gendertypelist)) return self::$gendertypelist;

		self::$gendertypelist = array();
		foreach(self::$gendertypes as $key=>$value)
		{
			self::$gendertypelist[$key] = array('id' => $key, 'name' => $value);
		}
		return self::$gendertypelist;
	}

	static public function getGenderTypeDesc($type)
	{
		if(array_key_exists($type, self::$gendertypes)) return self::$gendertypes[$type];
	}

	/**
	 * Add user to group
   *
	 * @param array key of user
	 * @return string name of user
	 * @see AuthenticationUser::getUserName
	 */
	public function addGroup($userId, $groupId)
	{
		/*
		 * difpluginexport searches files for set table to retrieve database tables to export
		 * so trick the script and add the statement as comment to export the usergroup table
		 * setTable('newsletter_usergroup', 'a');
		 */
		$query = sprintf("insert into newsletter_usergroup (usr_id, grp_id) values(%d, %d)", $userId['id'], $groupId['id']);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * Add user to group
   *
	 * @param array key of user
	 * @return string name of user
	 * @see AuthenticationUser::getUserName
	 */
	public function removeGroup($userId)
	{
		$query = sprintf("delete from newsletter_usergroup where usr_id = %d", $userId['id']);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * returns a list  of the groups that the user is part of
	 * @param array identifier (key = name of identifier, valeu = value)
	 * @return array
	 */
	public function getGroup($id)
	{
		$db = $this->getDb();

		$query = sprintf("select distinct a.grp_id as retval from newsletter_group as a inner join newsletter_usergroup as b on b.grp_id = a.grp_id where b.usr_id = %d", $id['id']);
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = array();
		while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC))
		{
			$retval[] = $row['retval'];
		}
		return $retval;
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

	public function enable($key)
	{
		// ignore empty keys
		if(!is_array($key) || !array_key_exists('optin', $key) || !$key['optin']) return;

		// ignore error
		if(!$this->exists($key)) return;

		// check if email is unique
		$detail = $this->getDetail($key);
		$searchcriteria = array('tree_id' => $detail['tree_id'], 'tag' => $detail['tag'], 'email' => $detail['email'], 'no_id' => $detail['id'], 'activated' => true);
		if($this->exists($searchcriteria)) throw new Exception("Email address {$detail['email']} already exists");

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($key, false);
		$this->parseCriteria($sqlParser, $key);
		$sqlParser->setFieldValue('optin', '');

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
		$sqlParser->setFieldValue('count', 'usr_count+1', false);

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

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'no_id' : $sqlParser->addCriteria(new SqlCriteria('a.usr_id', $value, '<>')); break;
				case 'grp_id'	:
					$sqlParser->addFrom('left join newsletter_usergroup as b on b.usr_id = a.usr_id');
					$sqlParser->addCriteria(new SqlCriteria('b.grp_id', $value, '=')); 
					break;
				case 'no_grp_id'	:
					throw new Exception("NewsletterUser search for no_grp_id not implemented! add logic for tree_id and tag?");
					$sqlParser->addFrom('left join usergroup as b on b.usr_id = a.usr_id');
					$sqlParser->addCriteria(new SqlCriteria('b.grp_id', $value, '<>')); 
					break;
				case 'activated' : 
					$sqlParser->addCriteria(new SqlCriteria($sqlParser->getFieldByName('active')->getField($prefix), 1));
					$sqlParser->addCriteria(new SqlCriteria($sqlParser->getFieldByName('optin')->getField($prefix), ''));

					$objUnsubscribe = $sqlParser->getFieldByName('unsubscribe_date');
					$subscribe = new SqlCriteria($objUnsubscribe->getField($prefix), 'null', '=');
					$subscribe->addCriteria(new SqlCriteria($objUnsubscribe->getField($prefix), 0, '='), SqlCriteria::REL_OR);
					$sqlParser->addCriteria($subscribe); 
					break;
				case 'search' : $sqlParser->addCriteria(new SqlCriteria('a.usr_name', "%$value%", 'like')); break;
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$fields['usr_id'] = $userId['id'];

		$fields['active'] = (array_key_exists('active', $fields) && $fields['active']);
		$fields['name'] = strip_tags($fields['name']);
		$fields['email'] = strip_tags($fields['email']);
		$fields['gender'] = strip_tags($fields['gender']);

		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$activated = 1;

		// hide newlettersitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif($values['unsubscribe_date'] > 0)
			$activated = 0;
		elseif($values['optin'] > 0)
			$activated = 0;

		$values['activated'] = $activated;
		$values['gender_description'] = $this->getGenderTypeDesc($values['gender']);
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$activated = 1;

		// hide attachmentitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif($values['unsubscribe_date'] > 0)
			$activated = 0;
		elseif($values['optin'] > 0)
			$activated = 0;

		$values['activated'] = $activated;
		$values['gender_description'] = $this->getGenderTypeDesc($values['gender']);
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$this->sqlParser->setFieldValue('own_id', $userId['id']);

		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));

		if(!Utils::isEmail($values['email'])) throw new Exception("Email address {$values['email']} invalid");

		// check if email is unique
		$searchcriteria = array('tree_id' => $values['tree_id'], 'tag' => $values['tag'], 'activated' => true, 'email' => $values['email']);
		if($this->exists($searchcriteria)) throw new Exception("Email address already exists in database");
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
		if(!Utils::isEmail($values['email'])) throw new Exception("Email address {$values['email']} invalid");

		// check if email is unique
		$searchcriteria = array('no_id' => $values['id'], 'tree_id' => $values['tree_id'], 'tag' => $values['tag'], 'activated' => true, 'email' => $values['email']);
		if($this->exists($searchcriteria)) throw new Exception("Email address already exists in database");
	}

	protected function handlePreDelete($id, $values)
	{
		// remove users
		$this->removeGroup($id);
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
			case NewsLetter::VIEW_USER_OVERVIEW : $this->handleOverview(); break;
			case NewsLetter::VIEW_USER_NEW : $this->handleNewGet(); break;
			case NewsLetter::VIEW_USER_EDIT : $this->handleEditGet(); break;
			case NewsLetter::VIEW_USER_DELETE : $this->handleDeleteGet(); break;
			case NewsLetter::VIEW_USER_UNSUBSCRIBE : $this->handleUnsubscribeGet(); break;
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
			case NewsLetter::VIEW_USER_OVERVIEW : $this->handleOverview(); break;
			case NewsLetter::VIEW_USER_NEW : $this->handleNewPost(); break;
			case NewsLetter::VIEW_USER_EDIT : $this->handleEditPost(); break;
			case NewsLetter::VIEW_USER_DELETE : $this->handleDeletePost(); break;
			case NewsLetter::VIEW_USER_UNSUBSCRIBE : $this->handleUnsubscribePost(); break;
		}

	} 

//}}}

/*------- unsubscribe request {{{ -------*/
	/**
	 * handle overview request
	*/
	private function handleUnsubscribeGet()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$email = $request->getValue(self::KEY_UNSUBSCRIBE);
		if(!$email) return;

		// retrieve tags that are linked to this plugin
		$taglist = $this->plugin->getTagList();
		if(!$taglist) return;

		$objSettings = $this->plugin->getObject(NewsLetter::TYPE_SETTINGS);
		$objGroup = $this->plugin->getObject(NewsLetter::TYPE_GROUP);

		foreach($taglist as $tag)
		{
			$settings = $objSettings->getSettings($tag['tree_id'], $tag['tag']);

			$searchcriteria = array('tree_id' => $tag['tree_id'], 'tag' => $tag['tag'], 'email' => $email, 'activated' => true);
			if($this->exists($searchcriteria))
			{
				$sqlParser = clone $this->sqlParser;
				$sqlParser->parseCriteria($searchcriteria, false);
				$this->parseCriteria($sqlParser, $searchcriteria, false);
				$sqlParser->setFieldValue('unsubscribe_date', 'now()', false);

				$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

				$db = $this->getDb();

				$res = $db->query($query);
				if($db->isError($res)) throw new Exception($res->getDebugInfo());
			}

			$location = $settings['del_tree_id'] ? $this->director->tree->getPath($settings['del_tree_id']) : '/';
			header("Location: $location");
			exit;
		}
	}

	/**
	 * handle overview request
	*/
	private function handleOverviewPost()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		// retrieve tags that are linked to this plugin
		if(!$request->exists('tag')) throw new Exception('Tag not defined');
		$tag = $request->getValue('tag');
		$tree_id = $this->director->tree->getCurrentId();

		// get objects
		$objSettings = $this->plugin->getObject(NewsLetter::TYPE_SETTINGS);
		$objUser = $this->plugin->getObject(NewsLetter::TYPE_USER);
		$objGroup = $this->plugin->getObject(NewsLetter::TYPE_GROUP);

		// get settings
		$globalSettings = $this->plugin->getSettings();
		$settings = array_merge($globalSettings, $objSettings->getSettings($tree_id, $tag));

		//$mailfrom = $settings['mailfrom'];

		try 
		{
			$values = $request->getRequest(Request::POST);
			$values['tree_id'] = $tree_id;
			$values['tag'] = $tag;

			$ip = $request->getValue('REMOTE_ADDR', Request::SERVER);
			$values['active']	= true;
			$values['ip']			= $ip;
			$values['host']   = gethostbyaddr($ip);
			$values['client'] = $request->getValue('HTTP_USER_AGENT', Request::SERVER);
			$values['optin'] = ($settings['action'] == NewsLetterSettings::ACTION_OPTIN) ? md5(session_id().time()) : '';
			
			$group = is_array($request->getValue('group')) ? array_keys($request->getValue('group')) : array();

			$id = $objUser->insert($values);
			foreach($group as $item)
			{
				$objUser->addGroup($id, array('id' => $item));
			}

			$settings['optin_key'] = $values['optin'];

			if($settings['action'])
			{
				$info = array_merge($settings, $values);
				//add intro / activation text to email
				$tplContent = $this->composeText($info);

				$this->sendMail($values['email'], $this->director->getConfig()->email_address, $settings['subject'], $tplContent->fetch(), false);
			}

			$location = $settings['ref_tree_id'] ? $this->director->tree->getPath($settings['ref_tree_id']) : '/';
			header("Location: $location");
			exit;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('newsLetterErrorMessage',  $e->getMessage(), false);

			$this->handleOverviewGet();
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
		$view->setType(NewsLetter::VIEW_USER_OVERVIEW);

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
		$searchcriteria['optin'] = '';

		$url = new Url(true);
		$url->clearParameter('id');
		$url->clearParameter('nl_id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), NewsLetter::VIEW_USER_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), NewsLetter::VIEW_USER_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), NewsLetter::VIEW_USER_DELETE);

		$list = $this->getList($searchcriteria, $pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
		}
		$template->setVariable('list',  $list, false);
		$template->setVariable('searchparam',  $search->getMandatoryParameterList(), false);
		$template->setVariable('searchcriteria',  $searchcriteria, false);

		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}
//}}}

/*------- Handle breadcrumb and group combo lists {{{ -------*/
	/**
	 * handle overview
	*/
	private function handleEdit($template, $grp_used)
	{
		$request = Request::getInstance();
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id', $tree_id, false);
		$template->setVariable('tag', $tag, false);

		// add overview link
		$view = ViewManager::getInstance();
		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);
		$url->setParameter($view->getUrlId(), NewsLetter::VIEW_USER_OVERVIEW);
		$breadcrumb = array('name' => $view->getName(NewsLetter::VIEW_USER_OVERVIEW), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		// create up & back links and create breadcrumb
		$this->director->theme->handleAdminLinks($template);
	
		// create user groups comboboxes
		$userGroup = new NewsLetterGroup($this->plugin);
		$search_used = ($grp_used) ? array('id' => $grp_used) : NULL;
		$search_free = ($grp_used) ? array('no_id' => $grp_used, 'tree_id' => $tree_id, 'tag' => $tag) : array('tree_id' => $tree_id, 'tag' => $tag);
		$group_used = ($grp_used) ? $userGroup->getList($search_used) : array('data'=>'');
		$group_free = $userGroup->getList($search_free);
		$template->setVariable('cbo_grp_used',  Utils::getHtmlCombo($group_used['data']), false);
		$template->setVariable('cbo_grp_free',  Utils::getHtmlCombo($group_free['data']), false);
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
		$view->setType(NewsLetter::VIEW_USER_NEW);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$fields['tree_id'] 	= $tree_id;
		$fields['tag'] 			= $tag;


		$template->setVariable($fields);
		$template->clearVariable('id');

		$template->setVariable('cbo_gender', Utils::getHtmlCombo($this->getGenderTypeList(), $fields['gender']));

		// add breadcrumb item
		$grp_used = $request->getValue('grp_used');
		$this->handleEdit($template, $grp_used);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleNewPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		$ip = $request->getValue('REMOTE_ADDR', Request::SERVER);
		$values['ip']			= $ip;
		$values['host']   = gethostbyaddr($ip);
		$values['client'] = $request->getValue('HTTP_USER_AGENT', Request::SERVER);

		
		$grp_used = is_array($request->getValue('grp_used')) ? $request->getValue('grp_used') : array();

		try 
		{
			$id = $this->insert($values);

			foreach($grp_used as $item)
			{
				$this->addGroup($id, array('id' => $item));
			}

			viewManager::getInstance()->setType(NewsLetter::VIEW_USER_OVERVIEW);
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
		$view->setType(NewsLetter::VIEW_USER_EDIT);

		if(!$request->exists('id')) throw new Exception('Newsletter is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);
		$key = array('id' => $id);

		$grp_used = $request->getValue('grp_used');

		if($retrieveFields)
		{
			$fields = $this->getDetail($key);
			$grp_used = $this->getGroup($key);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		$this->setFields($fields);
		$template->setVariable($fields);

		$template->setVariable('cbo_gender', Utils::getHtmlCombo($this->getGenderTypeList(), $fields['gender']));

		// add breadcrumb and groups
		$this->handleEdit($template, $grp_used);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		$grp_used = $request->getValue('grp_used');
		if(!$grp_used) $grp_used = array();

		try 
		{
			if(!$request->exists('id')) throw new Exception('Newsletter is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->update($key, $values);

			$this->removeGroup($key);
			foreach($grp_used as $item)
			{
				$this->addGroup($key, array('id' => $item));
			}

			viewManager::getInstance()->setType(NewsLetter::VIEW_USER_OVERVIEW);
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
		$view->setType(NewsLetter::VIEW_USER_DELETE);

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

			viewManager::getInstance()->setType(NewsLetter::VIEW_USER_OVERVIEW);
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

/*------- tree edit request {{{ -------*/
	/**
	 * handle tree edit
	*/
	private function handleConfigGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_CONFIG);

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$fields = array();
		if($retrieveFields)
		{
 			$fields = ($this->exists($key)) ? $this->getDetail($key) : $this->getFields(SqlParser::MOD_INSERT);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		$template->setVariable('cbo_display', Utils::getHtmlCombo(NewsLetter::getDisplayTypeList(), $fields['display']));

		$fields['tree_id'] = $tree_id;
		$fields['tag'] = $tag;

		$this->setFields($fields);
		$template->setVariable($fields);

		// add breadcrumb item
		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleConfigPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
			if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

			$tree_id = intval($request->getValue('tree_id'));
			$tag = $request->getValue('tag');

			$key = array('tree_id' => $tree_id, 'tag' => $tag);

			if($this->exists($key))
			{
				$this->update($key, $values);
			}
			else
			{
				$this->insert($values);
			}

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->plugin->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage', $e->getMessage(), false);
			$this->handleConfigGet(false);
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
