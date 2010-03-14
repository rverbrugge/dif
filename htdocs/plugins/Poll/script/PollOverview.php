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
class PollOverview extends Observer
{

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	/**
	 * refernce objects (attachment)
	 */
	private $reference;

	/**
	 * pointer to global plugin plugin
	 * @var Poll
	 */
	private $plugin;

	/**
	 * name of cookie that specifies if user already voted
	 * @var string
	 */
	const COOKIE_KEY = 'poll';

	/**
	 * value of cookie that specifies if user already voted
	 * @var string
	 */
	 private $cookie;

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
		$this->templateFile = "polloverview.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";
		$this->reference = array();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('poll', 'a');
		$this->sqlParser->addField(new SqlField('a', 'poll_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'poll_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'poll_online', 'online', 'Online datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_offline', 'offline', 'Offline datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'poll_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_text', 'text', 'Inhoud', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'poll_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->orderStatement = array('order by a.poll_online desc, a.poll_id desc');
	}

/*-------- Helper functions {{{------------*/
	private function getPath()
	{
		return $this->basePath;
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

		// update treeref
		if($tree_id != $new_tree_id)
		{
			$treeRef = new PollTreeRef();
			$treeRef->updateRefTreeId($tree_id, $new_tree_id);
		}
		
		// update settings
		$settings = $this->getPollSettings();
		$settings->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
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

		// update treeref
		$treeRef = new PollTreeRef();
		$treeRef->updateRefTreeId($sourceNodeId, $destinationNodeId);

		// update settings
		$settings = $this->getPollSettings();
		$settings->updateTreeId($tag, $tree_id, $newTag);
	}

	private function hasVoted($id)
	{
		$request = Request::getInstance();
		$list = $request->exists(self::COOKIE_KEY, Request::COOKIE) ? unserialize($request->getValue(self::COOKIE_KEY, Request::COOKIE)) : array();
		return in_array($id, $list);
	}

	private function getVotedCookieInfo($id)
	{
		$request = Request::getInstance();
		$list = $request->exists(self::COOKIE_KEY, Request::COOKIE) ? unserialize($request->getValue(self::COOKIE_KEY, Request::COOKIE)) : array();
		$list[$id] = $id;
		return array(	'key' => self::COOKIE_KEY,
									'value'	=> serialize($list),
									'expire' => strftime("%c", time()+(86400*100))); // expiration in 100 days
		//$request->setValue(self::COOKIE_KEY, serialize($list), Request::COOKIE, time()+(86400*100)); // expiration in 100 days
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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.poll_id', $value, '<>')); break;
				case 'archiveonline' : 
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.poll_online)', $value, '>'));
					break;
				case 'archiveoffline' : 
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.poll_offline)', $value, '<='));
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.poll_offline)', 0, '>'));
					break;
				case 'activated' : 
					// only active pages
					$SqlParser->addCriteria(new SqlCriteria('a.poll_active', 1));

					// only pages that are online
					$SqlParser->addCriteria(new SqlCriteria('a.poll_online', 'now()', '<='));

					$offline = new SqlCriteria('a.poll_offline', 'now()', '>');
					$offline->addCriteria(new SqlCriteria('unix_timestamp(a.poll_offline)', 0, '='), SqlCriteria::REL_OR);
					$SqlParser->addCriteria($offline); 
					break;
				case 'search' : 
					$search = new SqlCriteria('a.poll_name', "%$value%", 'like'); 
					$search->addCriteria(new SqlCriteria('a.poll_text', "%$value%", 'like'), SqlCriteria::REL_OR); 
					$SqlParser->addCriteria($search);
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
			case 'online' : return strftime('%Y-%m-%d'); break;
			case 'offline' : return strftime('%Y-%m-%d', mktime(0,0,0,date('m')+2)); break;
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
		$fields['online'] = (array_key_exists('online', $fields) && $fields['online']) ? strftime('%Y-%m-%d',strtotime($fields['online'])) : '';
		$fields['offline'] = (array_key_exists('offline', $fields) && $fields['offline']) ? strftime('%Y-%m-%d',strtotime($fields['offline'])) : '';

		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$date = mktime(0,0,0);

		$activated = 1;

		// hide poll_item if not active
		if(!$values['active']) 
			$activated = 0;
		elseif($values['online'] > 0 && $values['online'] > $date)
			$activated = 0;
		elseif($values['offline'] > 0 && $values['offline'] <= $date)
			$activated = 0;

		$values['activated'] = $activated;
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$date = mktime(0,0,0);

		$activated = 1;

		// hide attachmentitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif($values['online'] > 0 && $values['online'] > $date)
			$activated = 0;
		elseif($values['offline'] > 0 && $values['offline'] <= $date)
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$this->sqlParser->setFieldValue('own_id', $userId['id']);

		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));
	}
	//}}}

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
			case Poll::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW :  $this->handleTreeNewPost(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditPost(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeletePost(); break;
			case Poll::VIEW_ITEM_OVERVIEW : 
			case Poll::VIEW_ITEM_NEW : 
			case Poll::VIEW_ITEM_EDIT : 
			case Poll::VIEW_ITEM_DELETE : $this->handleItemPost(); break;
			case Poll::VIEW_CONFIG : $this->handlePollSettingsPost(); break;
			default : $this->handleOverview(); break;
		}

	} 

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
			$newsSettings = $this->getPollSettings();
			$key = array('tree_id' => intval($request->getValue('tree_id')), 'tag' => $request->getValue('tag'));
			if(!$newsSettings->exists($key)) $viewManager->setType(Poll::VIEW_CONFIG);
		}


		switch($viewManager->getType())
		{
			case Poll::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW : $this->handleTreeNewGet(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditGet(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeleteGet(); break;
			case Poll::VIEW_ITEM_OVERVIEW : 
			case Poll::VIEW_ITEM_NEW : 
			case Poll::VIEW_ITEM_EDIT : 
			case Poll::VIEW_ITEM_DELETE : $this->handleItemGet(); break;
			case Poll::VIEW_CONFIG : $this->handlePollSettingsGet(); break;
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

		// retrieve tags that are linked to this plugin
		$taglist = $this->plugin->getTagList();
		if(!$taglist) return;

		$url = new Url(true); 
		$url->setParameter($view->getUrlId(), Poll::VIEW_DETAIL);

		Cache::disableCache();
		foreach($taglist as $tag)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			//$template->setPostfix($tag['tag']);
			$template->setCacheable(false);

			// check if template is in cache
			if(!$template->isCached())
			{
				// get settings
				$settings = array_merge($this->getPollSettings()->getSettings($tag['tag'], $tag['tree_id']), $this->plugin->getSettings());
				$template->setVariable('settings',  $settings);

				$searchcriteria = array('tree_id' 	=> $tag['tree_id'], 
																'tag' 			=> $tag['tag'], 
																'activated' 		=> true);

				if(!$this->exists($searchcriteria)) continue;

				$poll = $this->getDetail($searchcriteria);

				$searchcriteria['poll_id'] = $poll['id'];

				$voted = $this->hasVoted($poll['id']);
				$template->setVariable('voted',  $voted);

				$template->setVariable('poll',  $poll);
				$itemlist = $this->getPollResult($poll['id'], $tag['tag'], $settings, $voted);
				$template->setVariable('tpl_poll_result',  $itemlist);
			}

			$this->template[$tag['tag']] = $template;
		}
	} //}}}

/*------- vote {{{ -------*/
	public function vote($item_id)
	{
		$pollItem = $this->getItem();

		// check if poll exists
		$key = array('id' => $item_id, 'activated' => true);
		if(!$pollItem->exists($key)) throw new Exception("Poll does not exist");

		$item = $pollItem->getDetail($key);

		// check if user is authorized to view tree node
		if(!$this->director->tree->exists($item['tree_id'])) throw new Exception("Access denied");

		// check if user already voted
		if($this->hasVoted($item['poll_id'])) throw new Exception("Already voted");

		// insert vote
		$pollItem->insertVote($key);

		// disable voting for this poll
		$cookie = $this->getVotedCookieInfo($item['poll_id']);

		// get settings
		$settings = array_merge($this->getPollSettings()->getSettings($item['tag'], $item['tree_id']), $this->plugin->getSettings());

		$itemlist = $this->getPollResult($item['poll_id'], $item['tag'], $settings, true);

		// return both cookie info and data 
		// settings cookies and returning xml data does not seem to work.
		$retval = array('cookie' => $cookie, 'data' => $itemlist->fetch());
		return $retval;
	}
	//}}}

/*------- get poll result {{{ -------*/
	private function getPollResult($poll_id, $tag, $settings, $voted=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/pollresult.tpl");
		$template->setCacheable(false);
				
		$template->setVariable('settings',  $settings);
		$template->setVariable('voted',  $voted);

		$searchcriteria = array('activated' => true,
														'poll_id'		=> $poll_id);

		$pollItem = $this->getItem();
		$list = $pollItem->getList($searchcriteria);

		// get total votes
		$votes = 0;
		foreach($list['data'] as $item)
		{
			$votes += $item['votes'];
		}

		// only calculate percentage if there is at least 1 vote (division by zero)
		if($votes)
		{
			foreach($list['data'] as &$item)
			{
				$item['percentage'] = $item['votes'] / $votes * 100;
			}
		}
		$template->setVariable('pollitem',  $list, false);
		return $template;
	} //}}}

/*------- detail request {{{ -------*/
	/**
	 * handle detail request
	*/
	private function handleDetail()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		// process attachments
		$attachment = $this->getAttachment();
		$attachment->handleHttpGetRequest();

		// clear subtitle
		$view->setName('');

		// check security
		if(!$request->exists('id')) throw new Exception('Poll item is missing.');
		$id = intval($request->getValue('id'));
		$key = array('id' => $id, 'activated' => true);

		if(!$this->exists($key)) throw new HttpException('404');
		$detail = $this->getDetail($key);


		// check if tree node of poll item is accessable
		$tree = $this->director->tree;
		if(!$tree->exists($detail['tree_id'])) throw new HttpException('404');

		// process request
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setPostfix($detail['tag']);
		// disable cache because we want to count visits
		$template->setCacheable(false);
		Cache::disableCache();

		// update view counter
		$this->updateCount($key);

		// overwrite default naming
		$template->setVariable('pageTitle',  $detail['name'], false);

		// add breadcrumb item
		$url = new Url(true);
		$url->clearParameter('id');

		$breadcrumb = array('name' => $detail['name'], 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		// check if template is in cache
		/*
		if(!$template->isCached())
		{
			$template->setVariable('poll',  $detail, false);
		}
		*/
		$template->setVariable('poll',  $detail, false);

		$settings = $this->getPollSettings();
		$treeSettings = $settings->getSettings($detail['tag'], $detail['tree_id']);
		$template->setVariable('newssettings',  $treeSettings, false);



		// get settings
		if($treeSettings['item'])
		{
			// process items
			$item = $this->getItem();
			$item->handleHttpGetRequest();
		}


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
		$url->clearParameter('poll_id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);


		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::TREE_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_conf = clone $url;
		$url_conf->setParameter($view->getUrlId(), Poll::VIEW_CONFIG);
		$template->setVariable('href_conf',  $url_conf->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::TREE_DELETE);

		$url_item = clone $url;
		$url_item->setParameter($view->getUrlId(), Poll::VIEW_ITEM_OVERVIEW);

		$list = $this->getList($searchcriteria, $pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);
			$url_item->setParameter('poll_id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
			$item['href_item'] = $url_item->getUrl(true);
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

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
		Utils::getDatePicker($this->director->theme, $datefields);

		$template->setVariable($fields, NULL, false);
		$template->clearVariable('id');

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeNewPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

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

/*------- tree edit request {{{ -------*/
	/**
	 * handle tree edit
	*/
	private function handleTreeEditGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(ViewManager::TREE_EDIT);

		if(!$request->exists('id')) throw new Exception('Poll is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);
		$key = array('id' => $id);

		if($retrieveFields)
		{
			$fields = $this->getDetail($key);
			$fields['online'] = $fields['online'] ? strftime('%Y-%m-%d', $fields['online']) : '';
			$fields['offline'] = $fields['offline'] ? strftime('%Y-%m-%d', $fields['offline']) : '';
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
			$detail = $this->getDetail($key);
		}

		$this->setFields($fields);
		$template->setVariable($fields, NULL, false);

		$url_item = new Url(true);
		$url_item->clearParameter('id');
		$url_item->setParameter('poll_id', $id);
		$url_item->setParameter($view->getUrlId(), Poll::VIEW_ITEM_OVERVIEW);
		$template->setVariable('href_item',  $url_item->getUrl(true), false);

		$this->handleTreeSettings($template);

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
		Utils::getDatePicker($this->director->theme, $datefields);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeEditPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			if(!$request->exists('id')) throw new Exception('Poll is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->update($key, $values);

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleTreeOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			$this->handleTreeEditGet(false);
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

		if(!$request->exists('id')) throw new Exception('Poll is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

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
			if(!$request->exists('id')) throw new Exception('Poll is missing.');
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

/*------- handle sub navigation result {{{ -------*/
	/**
	 * handle sub navigation
	*/
	private function handleSubNavigation()
	{
		$request = Request::getInstance();
		if(!$request->exists('poll_id')) return;

		$view = ViewManager::getInstance();
		$template =  new TemplateEngine();

		$poll_id = $request->getValue('poll_id');
		$url = new Url(true);
		$url->setParameter('tree_id', $request->getValue('tree_id'));
		$url->setParameter('tag', $request->getValue('tag'));
		$url->setParameter('id', $poll_id);
		$url->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);
		$breadcrumb = array('name' => $this->getName(array('id' => $poll_id)), 'path' => $url->getUrl(true));

		$this->director->theme->addBreadcrumb($breadcrumb);
	}//}}}

/*------- item request {{{ -------*/

	public function getItem()
	{
		if(array_key_exists('PollItem', $this->reference)) return $this->reference['PollItem'];

		require_once "PollItem.php";
		$item = new PollItem($this->plugin);
		$this->reference[$item->getClassName()] = $item;
		return $item;
	}

	private function handleItemGet()
	{
		$this->handleSubNavigation();
		$item = $this->getItem();

		$item->handleHttpGetRequest();
	}

	/**
	 * handle item request
	*/
	private function handleItemPost()
	{
		$this->handleSubNavigation();
		$item = $this->getItem();

		$item->handleHttpPostRequest();
	}

//}}}

/*------- pollSettings request {{{ -------*/

	public function getPollSettings()
	{
		if(array_key_exists('PollSettings', $this->reference)) return $this->reference['PollSettings'];

		require_once "PollSettings.php";
		$pollSettings = new PollSettings($this->plugin);
		$this->reference[$pollSettings->getClassName()] = $pollSettings;
		return $pollSettings;
	}

	private function handlePollSettingsGet()
	{
		$pollSettings = $this->getPollSettings();

		$pollSettings->handleHttpGetRequest();
	}

	/**
	 * handle pollSettings request
	*/
	private function handlePollSettingsPost()
	{
		$pollSettings = $this->getPollSettings();

		$pollSettings->handleHttpPostRequest();
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

		// parse rpc javascript to set variables
		$theme->addFileVar('poll_htdocs_path', $this->plugin->getHtdocsPath());
		$rpcfile_src = $this->plugin->getHtdocsPath(true)."js/rpc.js.in";
		$theme->addJavascript($theme->fetchFile($rpcfile_src));

		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_lib.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_wrappers.js"></script>');

		if($this->reference)
		{
			foreach($this->reference as $object)
			{
				$object->renderForm($theme);
			}
		}

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
