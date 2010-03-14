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

require_once('FormType.php');
require_once('phpmailer/class.phpmailer.php');

require_once('FormRecord.php');
require_once('FormRecordItem.php');

/**
 * Main configuration 
 * @package Common
 */
class FormElement extends Observer
{
	const WEIGHT_OFFSET = 10;

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;
	private $templateEmail;

	/**
	 * plugin settings of parent class
	 */
	private $settings;

	/**
	 * pointer to global plugin plugin
	 * @var News
	 */
	private $plugin;

	/**
	 * list of all class types
	 * @var News
	 */
	private $type;

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
		$this->templateFile = "formelement.tpl";
		$this->templateEmail = "formemail.tpl";
		$this->templateEmailItem = "formemailitem.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->type = array(
												'InputTextField'								=> 'Text field',
												'InputTextArea'									=> 'Text area',
												'InputEmail'										=> 'Email field',
												'InputEmailSender'							=> 'Email field (sender)',
												'InputNumeric'									=> 'Numeric field',
												'InputPhone'										=> 'Phone field',
												'InputDate'											=> 'Date field',
												'InputHidden'										=> 'Hidden field',
												'InputConstant'									=> 'Constant value',
												'InputLogin'										=> 'Authenticated User name',
												'InputCombo'										=> 'Pull-down list',
												'InputCheckbox'									=> 'Checkbox',
												'InputRadio'										=> 'Radio button list Vertical',
												'InputRadioHorizontal'					=> 'Radio button list Horizontal',
												'InputRadioScale'								=> 'Radio button list Scale',
												'InputRadioExtra'								=> 'Radio button list Vertical + text field',
												'InputMultiCheckbox'						=> 'Checkbox list Vertical',
												'InputMultiCheckboxHorizontal'	=> 'Checkbox list Horizontal',
												'InputMultiCheckboxExtra'				=> 'Checkbox list Vertical + text field',
												'InputDescription'							=> 'Description');


		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('form', 'a');
		$this->sqlParser->addField(new SqlField('a', 'form_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'form_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'form_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'form_type', 'type', 'Type element', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'form_weight', 'weight', 'Index', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'form_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'form_active', 'activated', 'Actieve status', SqlParser::getTypeSelect(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'form_mandatory', 'mandatory', 'Verplicht status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'form_size', 'size', 'Lengte', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'form_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'form_description', 'description', 'Omschrijving', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'form_default', 'def', 'Standaard waarde', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'form_options', 'options', 'Opties', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'form_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'form_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'form_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'form_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->orderStatement = array('order by a.form_weight %s, a.form_name asc');

	}


/*-------- Helper functions {{{------------*/
	private function getPath()
	{
		return $this->basePath;
	}

	private function getSettings($tree_id, $tag)
	{
		if($this->settings) return $this->settings;

		$this->settings = $this->plugin->getDetail(array('tree_id' => $tree_id, 'tag' => $tag));
		if(!$this->settings) $this->settings = $this->plugin->getFields(SqlParser::MOD_INSERT);

		return $this->settings;
	}

	private function getTypeList()
	{
		$retval = array();

		foreach($this->type as $key=>$item)
		{
			$retval[] = array('id' => $key, 'name' => $item);
		}
		return $retval;
	}

	private function getTypeDesc($type)
	{
		if(array_key_exists($type, $this->type)) return $this->type[$type];
	}

	public function getObjectList($searchcriteria, $values)
	{
		$retval = array();

		$list = $this->getList($searchcriteria, 0, 1, SqlParser::ORDER_ASC);
		foreach($list['data'] as $item)
		{
			$obj = $this->getObject($item['type'], $item);
			$obj->setId($this->getTypeId($item['id']));
			$obj->setWeight($item['weight']);
		}

		$retval[] = $obj;
		return $retval;
	}

	public function getObject($type, $settings)
	{
		if(!array_key_exists($type, $this->type)) throw new exception("Type $type does not exist.");
		$classname = $type;

		return new $classname($type, $settings['name'], $settings['mandatory'], $settings['size'], $settings['options'], $settings['def']);
	}

	public function getTypeId($id)
	{
		return "i$id";
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

	private function getNextWeight()
	{
		$request = Request::getInstance();
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$retval = 0;
		$query = sprintf("select max(form_weight) from form where form_tree_id = %d and form_tag = '%s'", $tree_id, $tag);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = $res->fetchOne();
		return $retval + self::WEIGHT_OFFSET;
	}

	private function getMaxSize()
	{
		$request = Request::getInstance();
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$retval = 0;
		$query = sprintf("select max(form_size) from form where form_tree_id = %d and form_tag = '%s'", $tree_id, $tag);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		return $res->fetchOne();
	}

	public function movetoPreceding($id)
	{
		$current = $this->getDetail($id);
		if(!$current) return;

		$searchcriteria = array('tree_id' => $current['tree_id'],
														'tag'			=> $current['tag'],
														'next_id'	=> $current['id']);

		$previous = $this->getDetail($searchcriteria, SqlParser::ORDER_DESC);
		if(!$previous) return;

		if($previous['weight'] == $current['weight'])
		{
			$this->decreaseWeight($current['tree_id'], $current['tag'], 0, $current['weight']);
			$previous['weight'] = $previous['weight'] - self::WEIGHT_OFFSET;
		}

		$this->updateWeight($current['id'], $previous['weight']);
		$this->updateWeight($previous['id'], $current['weight']);

		// clear cache
		$cache = Cache::getInstance();
		$cache->disableCache();
		$cache->clear();
	}

	public function movetoFollowing($id)
	{
		$current = $this->getDetail($id);
		if(!$current) return;

		$searchcriteria = array('tree_id' => $current['tree_id'],
														'tag'			=> $current['tag'],
														'previous_id'	=> $current['id']);

		$next = $this->getDetail($searchcriteria, SqlParser::ORDER_ASC);
		if(!$next) return;

		if($next['weight'] == $current['weight'])
		{
			$this->increaseWeight($current['tree_id'], $current['tag'], $current['weight']);
			$next['weight'] = $next['weight'] + self::WEIGHT_OFFSET;
		}

		$this->updateWeight($current['id'], $next['weight']);
		$this->updateWeight($next['id'], $current['weight']);

		// clear cache
		$cache = Cache::getInstance();
		$cache->disableCache();
		$cache->clear();
	}

	private function updateWeight($tree_id, $weight)
	{
		$query = sprintf("update form set form_weight = %d where form_id = %d", $weight, $tree_id);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * increase weight with self::weight_offset for child nodes of parent id that have at least min_weight and maximun max_weight
	 */
	private function increaseWeight($tree_id, $tag, $min_weight=0, $max_weight=0)
	{
		$query = sprintf("update form set form_weight = form_weight + %d where form_tree_id = %d and form_tag = '%s'", self::WEIGHT_OFFSET, $tree_id, $tag);
		if($min_weight) $query .= sprintf(" and form_weight >= %d", $min_weight);
		if($max_weight) $query .= sprintf(" and form_weight <= %d", $max_weight);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * decrease weight with self::weight_offset for child nodes of parent id that have at least min_weight and maximun max_weight
	 */
	private function decreaseWeight($tree_id, $tag, $min_weight=0, $max_weight=0)
	{
		$query = sprintf("update form set form_weight = form_weight - %d where form_tree_id = %d and form_tag = '%s'", self::WEIGHT_OFFSET, $tree_id, $tag);
		if($min_weight) $query .= sprintf(" and form_weight >= %d", $min_weight);
		if($max_weight) $query .= sprintf(" and form_weight <= %d", $max_weight);

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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.form_id', $value, '<>')); break;
				case 'no_type' : $SqlParser->addCriteria(new SqlCriteria('a.form_type', $value, '<>')); break;
				case 'previous_id' : 
					$SqlParser->addFrom("left join form as b on b.form_tree_id = a.form_tree_id and b.form_tag = a.form_tag");
					$SqlParser->addCriteria(new SqlCriteria('b.form_id', $value, '=')); 
					$SqlParser->addCriteria(new SqlCriteria('a.form_weight', 'b.form_weight', '>', true)); 
					break;
				case 'next_id' : 
					$SqlParser->addFrom("left join form as b on b.form_tree_id = a.form_tree_id and b.form_tag = a.form_tag");
					$SqlParser->addCriteria(new SqlCriteria('b.form_id', $value, '=')); 
					$SqlParser->addCriteria(new SqlCriteria('a.form_weight', 'b.form_weight', '<', true)); 
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
			case 'mandatory' : return 1; break;
			case 'size' : return $this->getMaxSize(); break;
			case 'weight' : return $this->getNextWeight(); break;
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
		$fields['mandatory'] = (array_key_exists('mandatory', $fields) && $fields['mandatory']);

		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$values['type_name'] = $this->getTypeDesc($values['type']);
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

		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('form_name', $values['name']));
		$sqlParser->addCriteria(new SqlCriteria('form_tree_id', $values['tree_id']));
		$sqlParser->addCriteria(new SqlCriteria('form_tag', $values['tag']));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('Element already exists.');

		// check if index is unique. if not, reindex nodes
		$searchcriteria = array('weight' => $values['weight'],
														'tree_id'	=> $values['tree_id'],
														'tag'	=> $values['tag']);
		if($this->exists($searchcriteria))
		{
			$this->increaseWeight($values['tree_id'], $values['tag'], $values['weight']);
		}

	}

	/**
	 * handle post insert checks and additions 
	 * eg. insert image
   *
	 * @param integer id of inserted object
	 * @param array filtered values for insertion
	 * @return void
	 */
	protected function handlePostInsert($id, $values)
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
	protected function handlePreUpdate($id, $values)
	{
		// check uniqueness
		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('form_name', $values['name']));
		$sqlParser->addCriteria(new SqlCriteria('form_tree_id', $values['tree_id']));
		$sqlParser->addCriteria(new SqlCriteria('form_tag', $values['tag']));
		$sqlParser->addCriteria(new SqlCriteria('form_id', $id['id'], '<>'));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('Element already exists.');

		// if name has changed, update record items accordingly
		$detail = $this->getDetail($id);
		if($detail['name'] != $values['name'])
		{
			$recordItem = new FormRecordItem();
			$recordItem->updateName($values['tree_id'], $values['tag'], $detail['name'], $values['name']);
		}

		// check if index is unique. if not, reindex nodes
		$searchcriteria = array('no_id'		=> $values['id'],
														'weight' => $values['weight'],
														'tree_id'	=> $values['tree_id'],
														'tag'	=> $values['tag']);
		if($this->exists($searchcriteria))
		{
			$this->increaseWeight($values['tree_id'], $values['tag'], $values['weight']);
		}

	}

	protected function handlePostUpdate($id, $values)
	{
	}

	protected function handlePostDelete($id, $values)
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
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case Form::VIEW_ELEMENT_OVERVIEW : $this->handleTreeOverviewGet(); break; 
			case Form::VIEW_ELEMENT_NEW : $this->handleTreeNewGet(); break;
			case Form::VIEW_ELEMENT_EDIT : $this->handleTreeEditGet(); break;
			case Form::VIEW_ELEMENT_DELETE : $this->handleTreeDeleteGet(); break;
			case Form::VIEW_MV_FOL : $this->handleMove(); break;
			case Form::VIEW_MV_PREC : $this->handleMove(); break;
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
			case Form::VIEW_ELEMENT_OVERVIEW : $this->handleTreeOverviewPost(); break; 
			case Form::VIEW_ELEMENT_NEW :  $this->handleTreeNewPost(); break;
			case Form::VIEW_ELEMENT_EDIT : $this->handleTreeEditPost(); break;
			case Form::VIEW_ELEMENT_DELETE : $this->handleTreeDeletePost(); break;
		}
	} 
//}}}

/*------- tree settings {{{ -------*/
	private function handleTreeSettings($template)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		// add breadcrumb item
		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);
		$url->setParameter($view->getUrlId(), Form::VIEW_ELEMENT_OVERVIEW);

		$this->director->theme->handleAdminLinks($template, $view->getName(Form::VIEW_ELEMENT_OVERVIEW), $url);
		$this->director->theme->handleAdminLinks($template);
	}

//}}}

/*------- tree overview request {{{ -------*/
	/**
	 * handle tree overview
	*/
	private function handleTreeOverviewGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		// add breadcrumb item
		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), Form::VIEW_ELEMENT_NEW);
		$template->setVariable('href_new', $url_new->getUrl(true));

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), Form::VIEW_ELEMENT_EDIT);

		$url_mv_prev = clone $url;
		$url_mv_prev->setParameter($view->getUrlId(), Form::VIEW_MV_PREC);

		$url_mv_next = clone $url;
		$url_mv_next->setParameter($view->getUrlId(), Form::VIEW_MV_FOL);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), Form::VIEW_ELEMENT_DELETE);

		$list = $this->getList($key, 0, 1, SqlParser::ORDER_ASC);

		$counter = 0;
		$maxcount = $list['totalItems'];
		foreach($list['data'] as &$item)
		{
			$counter++;

			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);
			$url_mv_prev->setParameter('id', $item['id']);
			$url_mv_next->setParameter('id', $item['id']);

			$item['type_id'] = $this->getTypeId($item['id']);
			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
			if($counter > 1) $item['href_mv_prev'] = $url_mv_prev->getUrl(true);
			if($counter < $maxcount) $item['href_mv_next'] = $url_mv_next->getUrl(true);
		}
		$template->setVariable('list', $list);

		//$this->handleTreeSettings($template);
		$this->director->theme->handleAdminLinks($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
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
		$view->setType(Form::VIEW_ELEMENT_NEW);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$fields['tree_id'] 	= $tree_id;
		$fields['tag'] 			= $tag;

		$this->handleTreeSettings($template);

		$template->setVariable('cbo_type', Utils::getHtmlCombo($this->getTypeList(), $fields['type']));

		$template->setVariable($fields, NULL, false);
		$template->clearVariable('id');

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeNewPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			$id = $this->insert($values);

			if($request->exists('addnew'))
			{
				$fields = $this->getFields(SqlParser::MOD_INSERT);
				$fields['name'] = '';
				$fields['description'] = '';
				$fields['def'] = '';
				$fields['options'] = '';
				$fields['weight'] = $this->getNextWeight();
				$this->setFields($fields);
				$this->handleTreeNewGet();

			}
			else
			{
				viewManager::getInstance()->setType(Form::VIEW_ELEMENT_OVERVIEW);
				$this->plugin->handleHttpGetRequest();
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
		$view->setType(Form::VIEW_ELEMENT_EDIT);

		if(!$request->exists('id')) throw new Exception('Element ontbreekt.');
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
			$detail = $this->getDetail($key);
		}

		$this->setFields($fields);
		$this->handleTreeSettings($template);

		$template->setVariable('cbo_type', Utils::getHtmlCombo($this->getTypeList(), $fields['type']));
		$template->setVariable($fields, NULL, false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Element ontbreekt.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->update($key, $values);

			if($request->exists('addnew'))
			{
				$fields = $this->getFields(SqlParser::MOD_INSERT);
				$fields['name'] = '';
				$fields['description'] = '';
				$fields['def'] = '';
				$fields['options'] = '';
				$fields['weight'] = $this->getNextWeight();
				$this->setFields($fields);
				$this->handleTreeNewGet();

			}
			else
			{
				viewManager::getInstance()->setType(Form::VIEW_ELEMENT_OVERVIEW);
				$this->plugin->handleHttpGetRequest();
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
		$view->setType(Form::VIEW_ELEMENT_DELETE);

		if(!$request->exists('id')) throw new Exception('Element ontbreekt.');
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
			if(!$request->exists('id')) throw new Exception('Element ontbreekt.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->delete($key);

			viewManager::getInstance()->setType(Form::VIEW_ELEMENT_OVERVIEW);
			$this->plugin->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleTreeDeleteGet();
		}
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

		if(!$request->exists('id')) throw new Exception(__FUNCTION__.' Element is missing.');
		$form_id = intval($request->getValue('id'));

		// check if node exists
		if(!$this->exists($form_id)) throw new HttpException('404');

		$key = array('id' => $form_id);

		try 
		{
			switch($view->getType())
			{
				case Form::VIEW_MV_PREC : $this->movetoPreceding($key); break;
				case Form::VIEW_MV_FOL : $this->movetoFollowing($key); break;
			}
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
		}

		viewManager::getInstance()->setType(Form::VIEW_ELEMENT_OVERVIEW);
		$this->handleTreeOverviewGet();
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

		$theme->addStylesheet(file_get_contents($this->plugin->getHtdocsPath(true).'css/style.css.in'));

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
