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
class ReservationBlockPeriod extends Observer
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
		$this->pagerKey = 'rblk';
		
		$this->template = array();
		$this->templateFile = "reservationblockperiod.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('reservation_blockperiod', 'a');
		$this->sqlParser->addField(new SqlField('a', 'res_id', 'id', 'Id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'res_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'res_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'res_start', 'date_start', 'Start date', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'res_stop', 'date_stop', 'End date', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'res_create', 'createdate', 'Created', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'res_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->setGroupby('group by a.res_start asc');
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

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'activated' : 
					$sqlParser->addCriteria(new SqlCriteria('a.res_start', 'now()', '>='));
					$sqlParser->addCriteria(new SqlCriteria('a.res_stop', 'now()', '<'));
					break;
				case 'period' : 
					$sqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.res_start)', $value, '<='));
					$sqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.res_stop)', $value, '>='));
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

		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$activated = 1;
		$date = mktime(0,0,0);

		// hide newlettersitem if not active
		if(($values['start'] > 0 && $values['start'] > $date) || ($values['stop'] > 0 && $values['stop'] <= $date))
			$activated = 0;

		$values['activated'] = $activated;
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$activated = 1;
		$date = mktime(0,0,0);

		// hide attachmentitem if not active
		if(($values['start'] > 0 && $values['start'] > $date) || ($values['stop'] > 0 && $values['stop'] <= $date))
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
	}

	protected function handlePreDelete($id, $values)
	{
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
			case Reservation::VIEW_BLOCK_PERIOD_OVERVIEW : $this->handleOverview(); break;
			case Reservation::VIEW_BLOCK_PERIOD_NEW : $this->handleNewGet(); break;
			case Reservation::VIEW_BLOCK_PERIOD_EDIT : $this->handleEditGet(); break;
			case Reservation::VIEW_BLOCK_PERIOD_DELETE : $this->handleDeleteGet(); break;
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
			case Reservation::VIEW_BLOCK_PERIOD_OVERVIEW : $this->handleOverview(); break;
			case Reservation::VIEW_BLOCK_PERIOD_NEW : $this->handleNewPost(); break;
			case Reservation::VIEW_BLOCK_PERIOD_EDIT : $this->handleEditPost(); break;
			case Reservation::VIEW_BLOCK_PERIOD_DELETE : $this->handleDeletePost(); break;
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
		$view->setType(Reservation::VIEW_BLOCK_PERIOD_OVERVIEW);

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
		$url_new->setParameter($view->getUrlId(), Reservation::VIEW_BLOCK_PERIOD_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), Reservation::VIEW_BLOCK_PERIOD_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), Reservation::VIEW_BLOCK_PERIOD_DELETE);

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

/*------- Handle breadcrumb and group combo lists {{{ -------*/
	/**
	 * handle overview
	*/
	private function handleEdit($template)
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
		$url->setParameter($view->getUrlId(), Reservation::VIEW_BLOCK_PERIOD_OVERVIEW);
		$breadcrumb = array('name' => $view->getName(Reservation::VIEW_BLOCK_PERIOD_OVERVIEW), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		// create up & back links and create breadcrumb
		$this->director->theme->handleAdminLinks($template);

		$datefields = array();
		$datefields[] = array('dateField' => 'date_start', 'triggerElement' => 'date_start');
		$datefields[] = array('dateField' => 'date_stop', 'triggerElement' => 'date_stop');
		Utils::getDatePicker($this->director->theme, $datefields);
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
		$view->setType(Reservation::VIEW_BLOCK_PERIOD_NEW);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$fields['tree_id'] 	= $tree_id;
		$fields['tag'] 			= $tag;

		$template->setVariable($fields);
		$template->clearVariable('id');

		$this->handleEdit($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleNewPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			$id = $this->insert($values);

			viewManager::getInstance()->setType(Reservation::VIEW_BLOCK_PERIOD_OVERVIEW);
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
		$view->setType(Reservation::VIEW_BLOCK_PERIOD_EDIT);

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
		$this->handleEdit($template);

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

			viewManager::getInstance()->setType(Reservation::VIEW_BLOCK_PERIOD_OVERVIEW);
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
		$view->setType(Reservation::VIEW_BLOCK_PERIOD_DELETE);

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

			viewManager::getInstance()->setType(Reservation::VIEW_BLOCK_PERIOD_OVERVIEW);
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
