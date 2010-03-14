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
class Banner extends Plugin implements RpcProvider, GuiProvider
{
	const TYPE_DEFAULT = 1;

	const DISP_SINGLE 	= 1;
	const DISP_STATIC		= 2;
	const DISP_CROSS		= 3;
	const DISP_SWAP			= 4;

	const DISP_ORDER_LINEAR = 1;
	const DISP_ORDER_RANDOM = 2;

	const PROP_STRICT 		= 1;
	const PROP_MAX 				= 2;
	const PROP_MAX_WIDTH	= 3;
	const PROP_MAX_HEIGHT = 4;

	const VIEW_SETTINGS = "bnr";


	private $displaytypes 	= array(self::DISP_SINGLE		=> 'Enkel',
																	self::DISP_STATIC		=> 'Wissel zonder animatie',
																	self::DISP_CROSS		=> 'Wissel crossfade',
																	self::DISP_SWAP			=> 'Wissel swapfade');

	private $displayorder 	= array(self::DISP_ORDER_LINEAR		=> 'Lineair',
																	self::DISP_ORDER_RANDOM		=> 'Random');
	
	private $displaytypelist;
	private $displayorderlist;

	protected	$types = array(self::TYPE_DEFAULT => 'Standaard');

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
		$this->templateFile = "banner.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('banner_settings', 'a');
		$this->sqlParser->addField(new SqlField('a', 'bnr_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_display', 'display', 'Weergave', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_display_order', 'display_order', 'Volgorde', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_url', 'url', 'Weergave url', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'bnr_transition_speed', 'transition_speed', 'Wissel snelheid', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_img_width', 'image_width', 'Breedte', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_img_height', 'image_height', 'Hoogte', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_img_max_width', 'image_max_width', 'Maximale breedte', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_image', 'image', 'Layer image', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'bnr_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_SETTINGS, 'Instellingen');
	}


/*-------- helper functions {{{------------*/
	public function getDisplayOrderList()
	{
		if(isset($this->displayorderlist)) return $this->displayorderlist;

		$this->displayorderlist = array();
		foreach($this->displayorder as $key=>$value)
		{
			$this->displayorderlist[$key] = array('id' => $key, 'name' => $value);
		}
		return $this->displayorderlist;
	}

	public function getDisplayTypeList()
	{
		if(isset($this->displaytypelist)) return $this->displaytypelist;

		$this->displaytypelist = array();
		foreach($this->displaytypes as $key=>$value)
		{
			$this->displaytypelist[$key] = array('id' => $key, 'name' => $value);
		}
		return $this->displaytypelist;
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
			case 'display' : return self::DISP_SINGLE; break;
			case 'url' : return 0; break;
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

		$fields['url'] = (array_key_exists('url', $fields) && $fields['url']);

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
	 * handle post insert checks and additions 
	 * eg. insert image
   *
	 * @param integer id of inserted object
	 * @param array filtered values for insertion
	 * @return void
	 */
	protected function handlePostInsert($id, $values)
	{
		$this->insertImage($id, $values);
	}

	protected function insertImage($id, $values)
	{
		$request = Request::getInstance();

		$img = $values['image'];
		if(!is_array($img) ||  !$img['tmp_name']) return;

		// get settings
		$searchcriteria = array('tree_id' => $values['tree_id'], 'tag'	=> $values['tag']);

		$destWidth = $values['image_width'];
		$destHeight = $values['image_height'];

		$image = new Image($img);
		$ext = Utils::getExtension(is_array($img) ? $img['name'] : $img);

		$path 			= $this->getContentPath(true);
		$filename 	= strtolower($this->getClassName())."_layer_".$id['tree_id'].".".$ext;

		// delete current image if filename new filename is different
		$detail = $this->getDetail($id);
		if($detail['image'] && $detail['image'] != $filename) $this->deleteImage($detail);

		// resize image
		$image->resize($destWidth, $destHeight);
		$image->save($path.$filename);

		$db = $this->getDb();
		$query = sprintf("update banner_settings set bnr_image= '%s' where bnr_tree_id = %d and bnr_tag = '%s'", 
										addslashes($filename), 
										$values['tree_id'],
										$values['tag']);

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	private function deleteImage($values)
	{
		$retval = false;

		if($values['image']) 
		{
			$image = new Image($values['image'], $this->getContentPath(true));
			$image->delete();
			$retval = true;
		}
		return $retval;
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
		// only process if delete request
		if(!isset($values['thumbnail_delete'])) return;

		$detail = $this->getDetail($id);
		if(!$this->deleteImage($detail)) return;

		$db = $this->getDb();
		$query = sprintf("update banner_settings set bnr_image = '' where bnr_tree_id = %d and bnr_tag = '%s'", $id['tree_id'], addslashes($id['tag']));
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	protected function handlePostUpdate($id, $values)
	{
		$this->insertImage($id, $values);
	}

	protected function handlePostDelete($id, $values)
	{
		$this->deleteImage($values);
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
				case ViewManager::CONF_EDIT : $this->handleConfEdit(); break;
				case self::VIEW_SETTINGS : $this->handleSettingsPost(); break;
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
		$view = ViewManager::getInstance();
		$typelist = array();
		if($this->director->isAdminSection())
		{
			$request = Request::getInstance();

			if($view->isType(ViewManager::OVERVIEW)) $view->setType(ViewManager::ADMIN_OVERVIEW);

			if($view->isType(ViewManager::TREE_OVERVIEW))
			{
				$key = array('tree_id' => intval($request->getValue('tree_id')), 'tag' => $request->getValue('tag'));
				if(!$this->exists($key)) $view->setType(self::VIEW_SETTINGS);
			}

			switch($view->getType())
			{
				case ViewManager::CONF_OVERVIEW : 
				case ViewManager::CONF_NEW : 
				case ViewManager::CONF_DELETE : 
				case ViewManager::CONF_EDIT : $this->handleConfEdit(); break;
				case self::VIEW_SETTINGS : $this->handleSettingsGet(); break;
				default : if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
									$typelist = $this->getReferenceTypeList(intval($request->getValue('tree_id')), $request->getValue('tag'));
			}
		}
		else
			$typelist = $this->getReferenceTypeList();

		foreach($typelist as $type)
		{
			$reference = $this->getObject($type);
			$reference->handleHttpGetRequest();
		}
	}
//}}}

/*------- conf edit request {{{ -------*/
	/**
	 * handle conf edit
	*/
	private function handleConfEdit()
	{
		viewManager::getInstance()->setType(ViewManager::CONF_EDIT);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('pageTitle', $this->description);

		$this->director->theme->handleAdminLinks($template);
		
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

//}}}

/*------- settings edit request {{{ -------*/
	/**
	 * handle settings edit
	*/
	private function handleSettingsGet($retrieveFields=true)
	{
		viewManager::getInstance()->setType(self::VIEW_SETTINGS);

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
			if(!array_key_exists('image', $fields)) $fields['image'] = '';
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
			$detail = $this->getDetail($key);
			$fields['image'] = $detail['image'];
		}

		if($fields['image'])
		{
			$img = new Image($fields['image'], $this->getContentPath(true));
			$fields['image'] = array('src' => $this->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
		}

		$fields['tree_id'] = $tree_id;
		$fields['tag'] = $tag;

		$this->setFields($fields);
		$template->setVariable($fields);


		$template->setVariable('cbo_display', Utils::getHtmlCombo($this->getDisplayTypeList(), $fields['display']));
		$template->setVariable('cbo_display_order', Utils::getHtmlCombo($this->getDisplayOrderList(), $fields['display_order']));

		// add breadcrumb item
		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleSettingsPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

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
			$this->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage', $e->getMessage(), false);
			$this->handleSettingsGet(false);
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
				require_once('BannerView.php');
				$this->reference[$type] = new BannerView($this);
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
		parent::updateTag($tree_id, $tag, $new_tree_id, $new_tag, $plugin_type);

		$ref = $this->getObject($plugin_type); 
		return $ref->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
	}

	public function updateTreeId($sourceNodeId, $destinationNodeId, $pluginType)
	{
		parent::updateTreeId($sourceNodeId, $destinationNodeId, $pluginType);

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
		
		// delete all bannerview items (including images)
		$key = array('tree_id' => $values['tree_id'], 'tag' => $values['tag']);
		$list = $ref->getList($key);
		foreach($list['data'] as $item)
		{
			$key = $ref->getKey($item);
			$ref->delete($key);
		}
		
		// delete self
		$key = $this->getKey($values);
		$this->delete($key);
	}
//}}}

	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 * @see GuiProvider::renderForm
	 */
	public function renderForm($theme)
	{
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
	public function getRandom($search)
	{
		try
		{
			$obj = $this->getObject(self::TYPE_DEFAULT);
			return $obj->getRandom($search);
		}
		catch(Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function getLinear($search)
	{
		try
		{
			$obj = $this->getObject(self::TYPE_DEFAULT);
			return $obj->getLinear($search);
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
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getRandom", array(&$this,'handleRpcRequest'));
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getLinear", array(&$this,'handleRpcRequest'));
	}
//}}}

}

?>
