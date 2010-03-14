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

/**
 * Main configuration 
 * @package Common
 */
class PollItem extends Observer
{
	const VIEW_KEY = 'pit';
	const VIEW_ADD = 1;

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	/**
	 * pointer to global plugin plugin
	 * @var Poll
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
		$this->setPagerKey('pit');
		
		$this->template = array();
		$this->templateFile = "pollitem.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('poll_item', 'a');
		$this->sqlParser->addField(new SqlField('a', 'item_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'item_poll_id', 'poll_id', 'Poll id', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('b', 'poll_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER, false));
		$this->sqlParser->addField(new SqlField('b', 'poll_tag', 'tag', 'Tag', SqlParser::getTypeSelect(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('b', 'poll_active', 'poll_active', 'Actieve status', SqlParser::getTypeSelect(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'item_active', 'active', 'Active state', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'item_weight', 'weight', 'Weight', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'item_name', 'name', 'Name', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'item_votes', 'votes', 'Votes', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, false));
		$this->sqlParser->addField(new SqlField('a', 'item_create', 'createdate', 'Create date', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'item_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->addFrom("inner join poll as b on b.poll_id = a.item_poll_id");

		$this->orderStatement = array('order by a.item_weight asc, a.item_create asc');
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

 public function insertVote($key)
 {
		$query = sprintf("update poll_item set item_votes = item_votes+1 where item_id = %d", $key['id']);
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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.item_id', $value, '<>')); break;
				case 'poll_active' : $SqlParser->addCriteria(new SqlCriteria('b.poll_active', $value)); break;
				case 'activated' : 
					// only active pages
					$SqlParser->addCriteria(new SqlCriteria('a.item_active', 1));
					$SqlParser->addCriteria(new SqlCriteria('b.poll_active', 1));
					break;
				case 'search' : 
					$SqlParser->addCriteria(new SqlCriteria('a.item_name', "%$value%", 'like'));
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
			case 'votes' : return 0; break;
			case 'weight' : return $this->getNextWeight(); break;
		}
	}

	private function getNextWeight()
	{
		$request = Request::getInstance();
		$poll_id = intval($request->getValue('poll_id'));

		$retval = 0;
		$offset = 10;
		$query = sprintf("select max(item_weight) from poll_item where item_poll_id = %d", $poll_id);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = $res->fetchOne();
		return $retval + $offset;
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
		$request = Request::getInstance();
		$fields['active'] = (array_key_exists('active', $fields) && $fields['active']);
		$fields['name'] = strip_tags($fields['name']);
		
		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$activated = 1;

		// hide attachmentitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif(!$values['poll_active']) 
			$activated = 0;

		$values['activated'] = $activated;
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$activated = 1;

		// hide attachmentitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif(!$values['poll_active']) 
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
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case Poll::VIEW_ITEM_OVERVIEW : $this->handleTreeOverview(); break;
			case Poll::VIEW_ITEM_NEW : $this->handleTreeNewPost(); break;
			case Poll::VIEW_ITEM_EDIT : $this->handleTreeEditPost(); break;
			case Poll::VIEW_ITEM_DELETE : $this->handleTreeDeletePost(); break;
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

		switch($viewManager->getType())
		{
			case Poll::VIEW_ITEM_OVERVIEW : $this->handleTreeOverview(); break;
			case Poll::VIEW_ITEM_NEW : $this->handleTreeNewGet(); break;
			case Poll::VIEW_ITEM_EDIT : $this->handleTreeEditGet(); break;
			case Poll::VIEW_ITEM_DELETE : $this->handleTreeDeleteGet(); break;
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
		$tag = 'poll_item';

		if(!$request->exists('id')) throw new Exception('Poll is missing.');
		$poll_id = intval($request->getValue('id'));

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setPostfix($tag);
		$template->setCacheable(true);

		// add item if requested
		if($request->exists(self::VIEW_KEY)) $this->handleItemAdd($template);

		// check if template is in cache
		if(!$template->isCached())
		{
			// retrieve items
			$searchcriteria = array('poll_id' 	=> $poll_id, 
															'activated' 		=> true);

			$list = $this->getList($searchcriteria);
			$template->setVariable('item',  $list);
		}

		$this->template[$tag] = $template;
	} //}}}

/*------- tree overview request {{{ -------*/
	/**
	 * handle tree overview
	*/
	private function handleTreeOverview()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(Poll::VIEW_ITEM_OVERVIEW);


		if(!$request->exists('poll_id')) throw new Exception('Poll is missing.');
		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$poll_id = intval($request->getValue('poll_id'));
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('poll_id' => $poll_id);

		// add breadcrumb item
		$this->director->theme->handleAdminLinks($template);

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('poll_id', $poll_id);
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), Poll::VIEW_ITEM_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), Poll::VIEW_ITEM_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), Poll::VIEW_ITEM_DELETE);

		$list = $this->getList($key);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
		}
		$template->setVariable('list',  $list, false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}
//}}}

/*------- tree settings {{{ -------*/
	private function handleTreeSettings($template)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();

		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('poll_id')) throw new Exception('Poll is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$tree_id = intval($request->getValue('tree_id'));
		$poll_id = intval($request->getValue('poll_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		// create href back url
		$url = new Url(true); 
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('poll_id', $poll_id);
		$url->setParameter('tag', $tag);
		$url->setParameter($view->getUrlId(), Poll::VIEW_ITEM_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		// add breadcrumb item
		$breadcrumb = array('name' => $view->getName(Poll::VIEW_ITEM_OVERVIEW), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		$this->director->theme->handleAdminLinks($template);
	}

//}}}

/*------- add request {{{ -------*/
	/**
	 * handle add
	*/
	private function handleItemAdd($template)
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);
		$values['active'] = 1;
		
		try
		{
			$id = $this->insert($values);
		}
		catch(Exception $e)
		{
			$template->setVariable('itemError',  $e->getMessage(), false);
			$template->setVariable('cmtValues',  $values);
		}
		
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
		$view->setType(Poll::VIEW_ITEM_NEW);

		if(!$request->exists('poll_id')) throw new Exception('Poll is missing.');

		$poll_id = intval($request->getValue('poll_id'));
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$fields['poll_id'] 	= $poll_id;
		$fields['tree_id'] 	= $tree_id;
		$fields['tag'] 			= $tag;


		$this->setFields($fields);
		$template->setVariable($fields, NULL, false);

		$this->handleTreeSettings($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeNewPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			$this->insert($values);

			if($request->exists('addnew'))
			{
				$fields = $this->getFields(SqlParser::MOD_INSERT);
				$fields['name'] = '';
				$fields['weight'] = $this->getNextWeight();
				$this->setFields($fields);
				$this->handleTreeNewGet();

			}
			else
			{
				viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
				$this->handleTreeOverview();
			}
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
		$view->setType(Poll::VIEW_ITEM_EDIT);

		if(!$request->exists('id')) throw new Exception('Item id is missing.');
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


		$template->setVariable($fields, NULL, false);

		$this->handleTreeSettings($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Poll is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->update($key, $values);

			if($request->exists('addnew'))
			{
				$fields = $this->getFields(SqlParser::MOD_INSERT);
				$fields['name'] = '';
				$fields['weight'] = $this->getNextWeight();
				$this->setFields($fields);
				$this->handleTreeNewGet();

			}
			else
			{
				viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
				$this->handleTreeOverview();
			}
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
		$view->setType(Poll::VIEW_ITEM_DELETE);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		if(!$request->exists('id')) throw new Exception('Post is missing.');
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
			if(!$request->exists('id')) throw new Exception('Post is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->delete($key);

			viewManager::getInstance()->setType(Poll::VIEW_ITEM_OVERVIEW);
			$this->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleTreeDeleteGet();
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
		$template->setVariable($view->getUrlId(),  $view->getName(), false);

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
