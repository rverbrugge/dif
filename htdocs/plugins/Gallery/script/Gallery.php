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

require_once "GalleryComment.php";

/**
 * Main configuration 
 * @package Common
 */
class Gallery extends Plugin implements GuiProvider, RpcProvider, CliProvider
{
	const ORDER_LINEAR = 4;
	const ORDER_RANDOM = 8;
	const ORDER_PREVIOUS = 16;

	const TYPE_DEFAULT = 1;
	const TYPE_HEADLINES = 2;
	const TYPE_COMMENT = 5;
	const TYPE_SETTINGS = 6;

	const DISP_NORMAL		= 1;
	const DISP_LIGHTBOX 	= 2;
	const DISP_SLIDESHOW 	= 3;

	const DISP_IMG = 4;
	const DISP_BRIEF_TOP = 5;
	const DISP_BRIEF_BOT = 6;
	const DISP_FULL_TOP = 7;
	const DISP_FULL_BOT = 8;

	const VIEW_DETAIL 	= "gallery";
	const VIEW_IMPORT 	= "imp";
	const VIEW_RESIZE 	= "res";

	const VIEW_COMMENT_OVERVIEW 	= "c1";
	const VIEW_COMMENT_NEW 			= "c2";
	const VIEW_COMMENT_EDIT 			= "c3";

	const VIEW_COMMENT_DELETE 		= "c4";

	const VIEW_CONFIG 	= "cfg";


	static public $displaytypes 	= array(self::DISP_NORMAL 	=> 'Image in detail page',
																				self::DISP_LIGHTBOX	=> 'Full screen with Lightbox',
																				self::DISP_SLIDESHOW		=> 'Slideshow');
	static private $displaytypelist;

	static public $displayoverview 	= array(self::DISP_IMG 				=> 'Image only',
																					self::DISP_BRIEF_TOP	=> 'Text above image',
																					self::DISP_BRIEF_BOT	=> 'Text below image',
																					self::DISP_FULL_TOP		=> 'Text and description above image',
																					self::DISP_FULL_BOT		=> 'Text and description below image');
	static private $displayoverviewlist;

	protected	$types = array(self::TYPE_DEFAULT => 'Overview',
														self::TYPE_HEADLINES => 'Headlines');

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $templateFile;

	static private $displayorder 	= array(self::ORDER_LINEAR		=> 'Lineair',
																				self::ORDER_RANDOM		=> 'Random');
	
	static private $displayorderlist;

	/**
	 * plugin settings of parent class
	 * @var array
	 */
	private $settings;

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
		$this->templateFile = "gallery.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('gallery_settings', 'a');
		$this->sqlParser->addField(new SqlField('a', 'gal_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_display', 'display', 'Weergave', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_display_overview', 'display_overview', 'Display overview', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_image_width', 'image_width', 'Breedte afbeelding', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_image_height', 'image_height', 'Hoogte afbeelding', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_image_max_width', 'image_max_width', 'Maximale Breedte afbeelding', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_rows', 'rows', 'Aantal items', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_date_format', 'date_format', 'Date format', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_template', 'template', 'Template', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_comment_order_asc', 'comment_order_asc', 'Order ascending', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'gal_comment', 'comment', 'Enable comment', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'gal_comment_notify', 'comment_notify', 'Enable comment notification', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'gal_comment_title', 'comment_title', 'Comment title', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_comment_display', 'comment_display', 'Comment display', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_comment_width', 'comment_width', 'Comment width', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_cap_name', 'cap_name', 'Caption name', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_cap_email', 'cap_email', 'Caption email', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'gal_cap_desc', 'cap_desc', 'Caption description', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_cap_submit', 'cap_submit', 'Caption submit button', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_cap_previous', 'cap_previous', 'Caption previous link', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_cap_next', 'cap_next', 'Caption next link', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_cap_back', 'cap_back', 'Caption back link', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_cap_detail', 'cap_detail', 'Caption detail link', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_DETAIL, 'Details');
		$view->insert(self::VIEW_IMPORT, 'Importeren');
		$view->insert(self::VIEW_RESIZE, 'Image resize');
		$view->insert(self::VIEW_COMMENT_OVERVIEW, 'Posts overview');
		$view->insert(self::VIEW_COMMENT_EDIT, 'Posts edit');
		$view->insert(self::VIEW_COMMENT_DELETE, 'Posts delete');
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

	static public function getDisplayOverviewList()
	{
		if(isset(self::$displayoverviewlist)) return self::$displayoverviewlist;

		self::$displayoverviewlist = array();
		foreach(self::$displayoverview as $key=>$value)
		{
			self::$displayoverviewlist[$key] = array('id' => $key, 'name' => $value);
		}
		return self::$displayoverviewlist;
	}

	public function getDisplayOrderList()
	{
		if(isset(self::$displayorderlist)) return self::$displayorderlist;

		self::$displayorderlist = array();
		foreach(self::$displayorder as $key=>$value)
		{
			self::$displayorderlist[$key] = array('id' => $key, 'name' => $value);
		}
		return self::$displayorderlist;
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
			case 'rows' : return 20; break;
			case 'image_width' : return 100; break;
			case 'image_height' : return 100; break;
			case 'image_max_width' : return 800; break;
			case 'display' : return self::DISP_LIGHTBOX; break;
			case 'display_overview' : return self::DISP_IMG; break;
			case 'date_format' : return '%A %d %B %Y'; break;
			case 'comment' : return 1; break;
			case 'comment_notify' : return 1; break;
			case 'comment_order_asc' : return 1; break;
			case 'comment_display' : return GalleryComment::DISP_FORM_BOTTOM; break;
			case 'comment_width' : return 50; break;
			case 'cap_previous' : return 'previous'; break;
			case 'cap_next' : return 'next'; break;
			case 'cap_back' : return 'back'; break;
			case 'cap_detail' : return 'more info &raquo;'; break;
			case 'cap_name' : return 'Name'; break;
			case 'cap_email' : return 'Email'; break;
			case 'cap_desc' : return 'Comment'; break;
			case 'cap_submit' : return 'Submit'; break;
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

		$fields['comment_order_asc'] = (array_key_exists('comment_order_asc', $fields) && $fields['comment_order_asc']);
		$fields['comment'] = (array_key_exists('comment', $fields) && $fields['comment']);
		$fields['comment_notify'] = (array_key_exists('comment_notify', $fields) && $fields['comment_notify']);

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
//}}}

/*----- handle cli requests {{{ -------*/
	/**
	 * Handles data coming from a post request  
	 * @param array cli request
	 */
	public function handleCliRequest(CliServer $cliServer)
	{
		$reference = $this->getObject(self::TYPE_DEFAULT);
		$reference->handleCliRequest($cliServer);
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

		$this->setFields($fields);
		$template->setVariable($this->getFields(SqlParser::MOD_UPDATE));
		$template->setVariable('id', ($detail) ? $detail['id'] : '');
		$template->setVariable('cbo_display', Utils::getHtmlCombo($this->getDisplayTypeList(), $fields['display']));
		$template->setVariable('cbo_display_overview', Utils::getHtmlCombo($this->getDisplayOverviewList(), $fields['display_overview']));
		$template->setVariable('cbo_comment_display', Utils::getHtmlCombo(GalleryComment::getDisplayTypeList(), $fields['comment_display']));

		// add source code editor
		$theme = $this->director->theme;
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/editarea/edit_area/edit_area_full.js"></script>');
		$theme->addJavascript('
editAreaLoader.init({ 	id: "area1", 
							start_highlight: true, 
							allow_toggle: true, 
							allow_resize: true,
							language: "en", 
							syntax: "php", 
							syntax_selection_allow: "css,html,js,php", 
					});
');

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
			case self::TYPE_DEFAULT : 
				require_once('GalleryOverview.php');
				$this->reference[$type] = new GalleryOverview($this);
				break;
			case self::TYPE_HEADLINES :
				require_once("GalleryHeadlines.php");
				$this->reference[$type] = new GalleryHeadlines($this);
				break;
				/*
			case self::TYPE_ARCHIVE :
				require_once("GalleryArchive.php");
				$this->reference[$type] = new GalleryArchive($this);
				break;
				*/
			case self::TYPE_SETTINGS :
				require_once("GallerySettings.php");
				$this->reference[$type] = new GallerySettings($this);
				break;
			case self::TYPE_COMMENT :
				require_once("GalleryComment.php");
				$this->reference[$type] = new GalleryComment($this);
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
				require_once('GalleryTreeRef.php');
				$treeref = new GalleryTreeRef();
				$key = array('ref_tree_id' => $values['tree_id']);
				$treeref->delete($key);

				$key = array('tree_id' => $values['tree_id'], 'tag' => $values['tag']);

				// delete settings
				$settings = $this->getObject(self::TYPE_SETTINGS);
				$settings->delete($key);

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

/*----- handle rpc requests {{{ -------*/

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function addComment($search)
	{
		try
		{

			$gallery = $this->getObject(self::TYPE_DEFAULT);
			$gallerySettings = $this->getObject(self::TYPE_SETTINGS);
			$comment = $this->getObject(self::TYPE_COMMENT);

			// check if gallery exists
			$searchcriteria = array('id' => $search['gal_id'], 'activated' => true);
			if(!$gallery->exists($searchcriteria)) throw new Exception("Gallery item does not exist");

			$detail = $gallery->getDetail($searchcriteria);

			// check if user is authorized to view tree node
			if(!$this->director->tree->exists($detail['tree_id'])) throw new Exception("Access denied");

			$settings = $gallerySettings->getSettings($detail['tree_id'], $detail['tag']);

			// convert html code to text and remove apos character
			foreach($search as &$item)
			{
				$item = html_entity_decode($item);
				$item = str_replace("&apos;", "'", $item);
			}

			$comment->setSettings($settings);
			$comment->addComment($search);
			$template = $comment->getOverview($detail['id']);
			$template->setVariable('gallery', $detail);
			
			return $template->fetch();
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
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".addComment", array(&$this,'handleRpcRequest'));
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
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addJavascript("
function toggleCheckBoxes(formName)   {
	var form = formName;
	var i=form.getElements('checkbox');
	i.each(
	function(item)
	{
			if (item.checked)
			{
			item.checked=false;
		}
		else 
		{
			item.checked=true;
		}
	}
	);
}");


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
