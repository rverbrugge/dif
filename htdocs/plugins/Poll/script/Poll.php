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
class Poll extends Plugin implements GuiProvider, RpcProvider
{
	const TYPE_DEFAULT = 1;
	const TYPE_ARCHIVE = 3;

	const DISP_HORIZONTAL 	= 1;
	const DISP_VERTICAL 	= 2;
	const DISP_CIRCLE		= 3;

	const VIEW_DETAIL 			= "poll";
	const VIEW_CONFIG 			= "pcfg";

	const VIEW_ITEM_OVERVIEW 	= "i1";
	const VIEW_ITEM_NEW 			= "i2";
	const VIEW_ITEM_EDIT 			= "i3";
	const VIEW_ITEM_DELETE 		= "i4";

	static public $displaytypes 	= array(self::DISP_HORIZONTAL 	=> 'Horizontal',
																				self::DISP_VERTICAL	=> 'Vertical',
																				self::DISP_CIRCLE		=> 'Circular');
	static private $displaytypelist;

	protected	$types = array(self::TYPE_DEFAULT => 'Overview',
														self::TYPE_ARCHIVE => 'Archive');

	/**
	 * plugin settings of parent class
	 */
	private $settings;

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $templateFile;

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
		$this->templateFile = "poll.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('poll_settings', 'a');
		$this->sqlParser->addField(new SqlField('a', 'poll_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'poll_display', 'display', 'Weergave', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_rows', 'rows', 'Aantal items', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'poll_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_DETAIL, 'Details');
		$view->insert(self::VIEW_ITEM_OVERVIEW, 'Items overview');
		$view->insert(self::VIEW_ITEM_NEW, 'New item');
		$view->insert(self::VIEW_ITEM_EDIT, 'Edit items');
		$view->insert(self::VIEW_ITEM_DELETE, 'Items delete');
		$view->insert(self::VIEW_CONFIG, 'Configuration');
	}

/*-------- Helper functions {{{------------*/
	public function getSettings()
	{
		if($this->settings) return $this->settings;

		$this->settings = $this->getDetail(array());
		if(!$this->settings) $this->settings = $this->getFields(SqlParser::MOD_INSERT);

		return $this->settings;
	}

	static public function getDisplayTypeList()
	{
		if(isset(self::$displaytypelist)) return self::$displaytypelist;

		self::$displaytypelist = array();
		foreach(self::$displaytypes as $key=>$value)
		{
			self::$displaytypelist[$key] = array('id' => $key, 'name' => $value);
		}
		return self::$displaytypelist;
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
			case 'display' : return 1; break;
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
	}
	//}}}

/*----- handle http requests {{{ -------*/
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
				case ViewManager::CONF_EDIT : $this->handleConfPost(); break;
				default : if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
									$typelist = $this->getReferenceTypeList(intval($request->getValue('tree_id')), $request->getValue('tag'));
			}
		}
		else
			$typelist = $this->getReferenceTypeList();

		foreach($typelist as $type)
		{
			$reference = $this->getObject($type);
			$reference->handleHttpPostRequest();
		}
	}

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$typelist = array();
		$view = ViewManager::getInstance();
		if($this->director->isAdminSection())
		{
			$request = Request::getInstance();

			if($view->isType(ViewManager::OVERVIEW)) $view->setType(ViewManager::ADMIN_OVERVIEW);

			switch($view->getType())
			{
				case ViewManager::CONF_OVERVIEW : 
				case ViewManager::CONF_NEW : 
				case ViewManager::CONF_DELETE : 
				case ViewManager::CONF_EDIT : $this->handleConfGet(); break;
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
			$reference = $this->getObject($type);
			$reference->handleHttpGetRequest();
		}
	}
//}}}

/*------- conf  request {{{ -------*/
	/**
	 * handle conf 
	*/
	private function handleConfGet($retrieveFields=true)
	{
		viewManager::getInstance()->setType(ViewManager::CONF_EDIT);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('pageTitle', $this->description);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

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

		$template->setVariable('cbo_display', Utils::getHtmlCombo(self::getDisplayTypeList(), $fields['display']));

		$this->setFields($fields);
		$template->setVariable($this->getFields(SqlParser::MOD_UPDATE));
		$template->setVariable('id', ($detail) ? $detail['id'] : '');

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleConfPost()
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
			$this->handleConfGet(false);
		}
	} 
//}}}

/*----- handle plugin requests {{{ -------*/
	protected function getObject($type)
	{
		if(isset($this->reference[$type])) return $this->reference[$type];

		switch($type)
		{
			case self::TYPE_DEFAULT : 
				require_once('PollOverview.php');
				$this->reference[$type] = new PollOverview($this);
				break;
			case self::TYPE_ARCHIVE :
				require_once("PollArchive.php");
				$this->reference[$type] = new PollArchive($this);
				break;
			default :
				throw new Exception("Type {$type} not defined in {$this->getClassName()}.");
		}
		return $this->reference[$type];
	}

	public function getPluginList($tag, $tree_id, $plugin_type)
	{
		$ref = $this->getObject($plugin_type); 
		$searchcriteria = array('tag' => $tag, 'tree_id' => $tree_id);

		return $ref->getList($searchcriteria);
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
			case self::TYPE_DEFAULT :
				// delete reference ids
				require_once('PollTreeRef.php');
				$treeref = new PollTreeRef();
				$key = array('ref_tree_id' => $values['tree_id']);
				$treeref->delete($key);

				$key = array('tree_id' => $values['tree_id'], 'tag' => $values['tag']);

				// delete settings
				require_once('PollSettings.php');
				$settings = new PollSettings($this);
				$treeref->delete($key);

				// delete poll_ items
				$list = $ref->getList($key);
				foreach($list['data'] as $item)
				{
					$key = $ref->getKey($item);
					$ref->delete($key);
				}
				break;
			default :
				$key = $ref->getKey($values);
				$ref->delete($key);
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
		// parse stylesheet to set variables
		$stylesheet_src = $this->getHtdocsPath(true)."css/style.css.in";
		$theme->addStylesheet($theme->fetchFile($stylesheet_src));

		if($this->reference)
		{
			foreach($this->reference as $object)
			{
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

/*----- handle rpc requests {{{ -------*/

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function vote($search)
	{
		try
		{
			$obj = $this->getObject(self::TYPE_DEFAULT);
			return $obj->vote($search);
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
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".vote", array(&$this,'handleRpcRequest'));
	}
//}}}
}

?>
