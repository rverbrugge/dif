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
class Form extends Plugin implements GuiProvider
{
	const TYPE_OVERVIEW = 1;
	const TYPE_ELEMENT = 2;
	const TYPE_RECORD = 3;
	const TYPE_RECORD_ITEM = 4;
	const TYPE_SETTINGS = 5;

	protected	$types = array(self::TYPE_OVERVIEW => 'Form');

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $templateFile;

	/**
	 * extra view types
	 */
	const VIEW_ELEMENT_OVERVIEW 	= "e1";
	const VIEW_ELEMENT_NEW 				= "e2";
	const VIEW_ELEMENT_EDIT 			= "e3";
	const VIEW_ELEMENT_DELETE 		= "e4";

	const VIEW_MV_PREC = 'fr1';
	const VIEW_MV_FOL = 'fr2';

	const VIEW_RECORD_DEL_ALL = "r3";
	const VIEW_RECORD_EXPORT 	= "r5";

	const VIEW_OPTIN = "r6";

	const VIEW_CONFIG = "cfg";

	const ACTION_CC 		= 1;
	const ACTION_OPTIN 	= 2;

	public $actionList = array(array('id' => self::ACTION_CC, 		'name' => 'Send CC'),
															array('id' => self::ACTION_OPTIN, 'name' => 'Send Opt-in request'));

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct()
	{
		parent::__construct();

		$this->configFile = strtolower(__CLASS__.".ini");

		$this->template = array();
		$this->templateFile = "form.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";
		$this->settings = array();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('form_settings', 'a');
		$this->sqlParser->addField(new SqlField('a', 'form_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'form_subject', 'subject', 'Onderwerp', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'form_from', 'mailfrom', 'Van', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_EMAIL, true));
		$this->sqlParser->addField(new SqlField('a', 'form_to', 'mailto', 'Naar', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_EMAIL));
		$this->sqlParser->addField(new SqlField('a', 'form_caption', 'caption', 'Caption', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'form_action', 'action', 'Action', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'form_mail_text', 'mailtext', 'Mail intro', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'form_templatefield', 'templatefield', 'Template', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'form_mandatorysign', 'mandatorysign', 'Verplicht symbool', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'form_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'form_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'form_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'form_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_ELEMENT_OVERVIEW, 'Overview elements');
		$view->insert(self::VIEW_ELEMENT_NEW, 'New element');
		$view->insert(self::VIEW_ELEMENT_EDIT, 'Edit element');
		$view->insert(self::VIEW_ELEMENT_DELETE, 'Delete element');
		$view->insert(self::VIEW_OPTIN, 'Opt-in');
		$view->insert(self::VIEW_RECORD_DEL_ALL, 'Delete all records');
		$view->insert(self::VIEW_RECORD_EXPORT, 'Export all records');
		$view->insert(self::VIEW_CONFIG, 'Configuration');
		$view->insert(self::VIEW_MV_PREC, 'Move to previous element');
		$view->insert(self::VIEW_MV_FOL, 'Move to next element');
	}

/*-------- Helper functions {{{------------*/
	public function getGlobalSettings()
	{
		if($this->globalSettings) return $this->globalSettings;

		$this->globalSettings = $this->getDetail(array());
		if(!$this->globalSettings) $this->globalSettings = $this->getFields(SqlParser::MOD_INSERT);

		return $this->globalSettings;
	}

	public function getSettings($tree_id, $tag)
	{
		if(array_key_exists("$tree_id$tag", $this->settings)) return $this->settings["$tree_id$tag"];
		$settingsObj= $this->getObject(Form::TYPE_SETTINGS);
		$this->settings["$tree_id$tag"] = array_merge($this->getGlobalSettings(), $settingsObj->getSettings($tree_id, $tag));
		return $this->settings["$tree_id$tag"];
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
			case 'action' : return 1; break;
			case 'caption' : return 'Submit'; break;
			case 'mailfrom' : return $this->director->getConfig()->email_address; break;
			case 'mailto' : return $this->director->getConfig()->email_address; break;
			case 'subject' : return "Request from ".Request::getInstance()->getDomain(); break;
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

		if(array_key_exists('templatefield', $fields)) $fields['templatefield'] = trim($fields['templatefield']);

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
	}

//}}}

/*----- handle http requests {{{ -------*/
	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$typelist = array();
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if($this->director->isAdminSection())
		{

			if($view->isType(ViewManager::OVERVIEW)) $view->setType(ViewManager::ADMIN_OVERVIEW);

			switch($view->getType())
			{
				case ViewManager::CONF_OVERVIEW : 
				case ViewManager::CONF_NEW : 
				case ViewManager::CONF_DELETE : 
				case ViewManager::CONF_EDIT : $this->handleConfEditGet(); break;
				default : if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
									$typelist = $this->getReferenceTypeList(intval($request->getValue('tree_id')), $request->getValue('tag'));
			}
		}
		else
		{
			switch($view->getType())
			{
				default : $typelist = $this->getReferenceTypeList();
			}
		}

		foreach($typelist as $type)
		{
			$this->addRenderList($type);
			$reference = $this->getObject($type);
			$reference->handleHttpGetRequest();
		}
	}

	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$typelist = array();
		if($this->director->isAdminSection())
		{
			$request = Request::getInstance();
			$view = ViewManager::getInstance();

			if($view->isType(ViewManager::OVERVIEW)) $view->setType(ViewManager::ADMIN_OVERVIEW);

			switch($view->getType())
			{
				case ViewManager::CONF_OVERVIEW : 
				case ViewManager::CONF_NEW : 
				case ViewManager::CONF_DELETE : 
				case ViewManager::CONF_EDIT : $this->handleConfEditPost(); break;
				default : if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
									$typelist = $this->getReferenceTypeList(intval($request->getValue('tree_id')), $request->getValue('tag'));
			}
		}
		else
			$typelist = $this->getReferenceTypeList();

		foreach($typelist as $type)
		{
			$this->addRenderList($type);
			$reference = $this->getObject($type);
			$reference->handleHttpPostRequest();
		}
	}

//}}}

/*------- conf edit request {{{ -------*/
	/**
	 * handle conf edit
	*/
	private function handleConfEditGet($retrieveFields=true)
	{
		viewManager::getInstance()->setType(ViewManager::CONF_EDIT);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('pageTitle', $this->description);

		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(ViewManager::CONF_EDIT);

		$detail = $this->getDetail(array());

		$fields = array();
		if($retrieveFields)
		{
 			$fields = ($this->exists(array())) ? $detail : $this->getFields(SqlParser::MOD_INSERT);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		// get action type
		$template->setVariable('cbo_action', Utils::getHtmlCombo($this->actionList, $fields['action'], '...'));

		$this->setFields($fields);
		$template->setVariable($this->getFields(SqlParser::MOD_UPDATE));
		$template->setVariable('id', ($detail) ? $detail['id'] : '');
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleConfEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if($this->exists(array()))
			{
				$this->update($this->getKey($values), $values);
			}
			else
			{
				$this->insert($values);
			}

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->referer->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage', $e->getMessage(), false);
			$this->handleConfEditGet(false);
		}
	}
//}}}

/*----- handle plugin requests {{{ -------*/
	public function getObject($type)
	{
		if(isset($this->reference[$type])) return $this->reference[$type];

		switch($type)
		{
			case self::TYPE_OVERVIEW : 
				require_once('FormOverview.php');
				$this->reference[$type] = new FormOverview($this);
				break;
			case self::TYPE_ELEMENT : 
				require_once('FormElement.php');
				$this->reference[$type] = new FormElement($this);
				break;
			case self::TYPE_RECORD :
				require_once("FormRecord.php");
				$this->reference[$type] = new FormRecord($this);
				break;
			case self::TYPE_RECORD_ITEM :
				require_once("FormRecordItem.php");
				$this->reference[$type] = new FormRecordItem($this);
				break;
			case self::TYPE_SETTINGS :
				require_once("FormSettings.php");
				$this->reference[$type] = new FormSettings($this);
				break;
			default :
				throw new Exception("Type {$type} not defined in {$this->getClassName()}.");
		}
		return $this->reference[$type];
	}

	public function updateTag($tree_id, $tag, $new_tree_id, $new_tag, $plugin_type)
	{
		$ref = $this->getObject($plugin_type); 
		return $ref->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
	}

	public function updateTreeId($sourceNodeId, $destinationNodeId, $pluginType)
	{
		$ref = $this->getObject($pluginType); 
		return $ref->updateTreeId($sourceNodeId, $destinationNodeId);
	}


 /**
	 * delete a plugin item
	 *
	 * @param array whith id [fieldname => value]
	 * @param string name of the tag that is being deleted
	 * @param integer id of the tree 
	 * @return void
	 */
	public function deletePlugin($values, $plugin_type)
	{
		$ref = $this->getObject($plugin_type); 

		switch($plugin_type)
		{
			case self::TYPE_OVERVIEW :
					$key = array('tree_id' => $values['tree_id'], 'tag' => $values['tag']);

					// delete settings
					$settings = $this->getObject(self::TYPE_SETTINGS);
					$settings->delete($key);

					$record = $this->getObject(self::TYPE_RECORD);
					$recordItem = $this->getObject(self::TYPE_RECORD_ITEM);
					$element = $this->getObject(self::TYPE_ELEMENT);

					$element->delete($key);

					$record->delete($key);
					$recordItem->delete($key);
				break;
			default :
				$key = $ref->getKey($values);
				$ref->delete($key);
		}
	}
//}}}

/*------- settings edit request {{{ -------*/
	/**
	 * handle settings edit
	*/
	private function handleSettingsGet($retrieveFields=true)
	{
		viewManager::getInstance()->setType(self::VIEW_CONFIG);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

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

		$fields['tree_id'] = $tree_id;
		$fields['tag'] = $tag;
		$this->setFields($fields);
		$template->setVariable($fields);

		// get all tree nodes which have plugin modules
		$site 			= new SystemSite();
		$tree = $site->getTree();

		$treelist = $tree->getList();
		foreach($treelist as &$item)
		{
			$item['name'] = $tree->toString($item['id'], '/', 'name');
		}

		$template->setVariable('cbo_tree_id', Utils::getHtmlCombo($treelist, $fields['ref_tree_id']));
		$template->setVariable('cbo_optin_tree_id', Utils::getHtmlCombo($treelist, $fields['optin_tree_id']));

		// get action type
		$template->setVariable('cbo_action', Utils::getHtmlCombo($this->actionList, $fields['action'], '...'));

		// add breadcrumb item
		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleSettingsPost()
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
			$this->getReferer()->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage', $e->getMessage(), false);
			$this->handleTreeOverviewGet(false);
		}
	} 
//}}}

/*------- element overview request {{{ -------*/
	/**
	 * handle element overview
	*/
	private function handleElementOverview($template)
	{
		
		$reference = $this->getObject(self::TYPE_ELEMENT);

		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(ViewManager::TREE_OVERVIEW);

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
		$url_new->setParameter($view->getUrlId(), ViewManager::TREE_NEW);
		$template->setVariable('href_new', $url_new->getUrl(true));

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::TREE_DELETE);

		$list = $reference->getList($key);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['type_id'] = $reference->getTypeId($item['id']);
			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
		}
		$template->setVariable('list', $list);
		return $template;

	}
//}}}


	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 * @see GuiProvider::renderForm
	 */
	public function renderForm($theme)
	{
		// parse stylesheet to set variables
		$stylesheet_src = $this->getHtdocsPath(true)."css/style.css.in";
		$theme->addStylesheet($theme->fetchFile($stylesheet_src));

		$renderList = $this->getRenderList();

		if($renderList)
		{
			foreach($renderList as $type)
			{
				$object = $this->getObject($type);
				$object->renderForm($theme);
			}
		}
		else
		{
			$template = $theme->getTemplate();

			foreach($this->template as $key => $value)
			{
				$template->setVariable($key, $value);
			}
		}
	}
}

?>
