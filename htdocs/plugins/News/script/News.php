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

require_once "NewsComment.php";

/**
 * Main configuration 
 * @package Common
 */
class News extends Plugin implements GuiProvider, RpcProvider, PluginProvider, CliProvider
{
	const TYPE_DEFAULT = 1;
	const TYPE_HEADLINES = 2;
	const TYPE_ARCHIVE = 3;
	const TYPE_ATTACHMENT = 4;
	const TYPE_COMMENT = 5;
	const TYPE_SETTINGS = 6;
	const TYPE_IMAGE = 7;

	const DISP_BRIEF 	= 1;
	const DISP_INTRO 	= 2;
	const DISP_IMAGE	= 3;
	const DISP_FULL		= 4;
	const DISP_INTRO_IMAGE	= 5;

	const VIEW_DETAIL 			= "news";
	const VIEW_FILE 				= "nfile";
	const VIEW_FILE_IMPORT 	= "fimp";

	const VIEW_FILE_OVERVIEW 	= "n1";
	const VIEW_FILE_NEW 			= "n2";
	const VIEW_FILE_EDIT 			= "n3";
	const VIEW_FILE_DELETE 		= "n4";

	const VIEW_IMAGE_OVERVIEW 	= "i1";
	const VIEW_IMAGE_NEW 			= "i2";
	const VIEW_IMAGE_EDIT 			= "i3";
	const VIEW_IMAGE_DELETE 		= "i4";
	const VIEW_IMAGE_IMPORT 		= "i5";
	const VIEW_IMAGE_RESIZE 		= "i6";

	const VIEW_COMMENT_OVERVIEW 	= "c1";
	const VIEW_COMMENT_NEW 			= "c2";
	const VIEW_COMMENT_EDIT 			= "c3";
	const VIEW_COMMENT_DELETE 		= "c4";

	const VIEW_CONFIG 	= "cfg";

	static public $displaytypes 	= array(self::DISP_BRIEF 	=> 'Name',
																				self::DISP_INTRO	=> 'Name & intro',
																				self::DISP_IMAGE	=> 'Name & image',
																				self::DISP_INTRO_IMAGE	=> 'Name, intro & image',
																				self::DISP_FULL		=> 'Full');
	static private $displaytypelist;

	protected	$types = array(self::TYPE_DEFAULT => 'Overview',
														self::TYPE_HEADLINES => 'Headlines',
														self::TYPE_ARCHIVE => 'Archive');

	/**
	 * plugin settings of parent class
	 * @var array
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
		$this->templateFile = "news.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('news_settings', 'a');
		$this->sqlParser->addField(new SqlField('a', 'news_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_display', 'display', 'Display', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'news_image_width', 'image_width', 'Breedte afbeelding', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_image_height', 'image_height', 'Hoogte afbeelding', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_image_max_width', 'image_max_width', 'maximale breedte afbeelding', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_rows', 'rows', 'Aantal items', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_date_format', 'date_format', 'Date format', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));

		$this->sqlParser->addField(new SqlField('a', 'news_template', 'template', 'Template', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'news_detail_img', 'detail_img', 'Show thumbnail in details', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'news_comment_order_asc', 'comment_order_asc', 'Order ascending', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'news_comment', 'comment', 'Enable comment', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'news_comment_notify', 'comment_notify', 'Enable comment notification', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'news_comment_title', 'comment_title', 'Comment title', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'news_comment_display', 'comment_display', 'Comment display', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'news_comment_width', 'comment_width', 'Comment width', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'news_cap_name', 'cap_name', 'Caption name', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'news_cap_email', 'cap_email', 'Caption email', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'news_cap_desc', 'cap_desc', 'Caption description', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'news_cap_submit', 'cap_submit', 'Caption submit button', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'news_cap_back', 'cap_back', 'Caption back link', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'news_cap_detail', 'cap_detail', 'Caption detail link', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'news_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'news_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'news_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'news_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_DETAIL, 'Details');
		$view->insert(self::VIEW_FILE, 'Attachments');
		$view->insert(self::VIEW_FILE_OVERVIEW, 'Attachments overview');
		$view->insert(self::VIEW_FILE_NEW, 'Attachments new');
		$view->insert(self::VIEW_FILE_EDIT, 'Attachments edit');
		$view->insert(self::VIEW_FILE_DELETE, 'Attachments delete');
		$view->insert(self::VIEW_FILE_IMPORT, 'Attachments import');
		$view->insert(self::VIEW_IMAGE_OVERVIEW, 'Images overview');
		$view->insert(self::VIEW_IMAGE_NEW, 'Images new');
		$view->insert(self::VIEW_IMAGE_EDIT, 'Images edit');
		$view->insert(self::VIEW_IMAGE_DELETE, 'Images delete');
		$view->insert(self::VIEW_IMAGE_IMPORT, 'Images import');
		$view->insert(self::VIEW_IMAGE_RESIZE, 'Images resize');
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

	public function getFilePath()
	{
		return DIF_SYSTEM_ROOT.$this->director->getConfig()->file_path."/".strtolower($this->getClassName()).'/';
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

	public function getFileUrl($id,$htmlentities=true)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();

		$url_file = new Url();
		$url_file->useCurrent(false);
		$url_file->setParameter($view->getUrlId(), self::VIEW_FILE);
		$url_file->setParameter('id', $id);
		$url_file->setParameter('tag', $request->getValue('tag'));
		$url_file->setParameter('tree_id', $request->getValue('tree_id'));

		return $url_file->getUrl($htmlentities);
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
			case 'display' : return self::DISP_FULL; break;
			case 'date_format' : return '%A %d %B %Y'; break;
			case 'comment' : return 1; break;
			case 'detail_img' : return 1; break;
			case 'comment_notify' : return 1; break;
			case 'comment_order_asc' : return 1; break;
			case 'comment_display' : return NewsComment::DISP_FORM_BOTTOM; break;
			case 'comment_width' : return 50; break;
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
		$fields['detail_img'] = (array_key_exists('detail_img', $fields) && $fields['detail_img']);
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
				case self::VIEW_FILE : $this->handleFile(); break;
				default : if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
									$typelist = $this->getReferenceTypeList(intval($request->getValue('tree_id')), $request->getValue('tag'));
			}
		}
		else
		{
			switch($view->getType())
			{
				case self::VIEW_FILE : $this->handleFile(); break;
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

/*----- handle referer plugin requests {{{ -------*/
	/**
	 * Handles data coming from another plugin 
	 */
	public function handlePluginRequest($pluginType, $requestType, $templateTag, $parameters=NULL)
	{
		$template = new TemplateEngine();
		$template->setVariable('pluginProviderRequest', $requestType, false);

		switch($pluginType)
		{
			case self::TYPE_DEFAULT :
				$reference = $this->getObject($pluginType);
				$reference->handlePluginRequest($requestType, $templateTag, $parameters);
				break;
		}
	}
//}}}

/*------- file request {{{ -------*/
	/**
	 * handle file 
	*/
	private function handleFile()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if(!$request->exists('id')) throw new Exception('Id ontbreekt.');
		$id = intval($request->getValue('id'));
		// if admin section, dont check if active
		$key = $this->director->isAdminSection() ? array('id' => $id) : array('id' => $id, 'active' => true, 'news_active' => true);

		$obj = $this->getObject(self::TYPE_ATTACHMENT);

		if(!$obj->exists($key)) throw new HttpException('404');

		// check if file is set
		$detail = $obj->getDetail($key);
		if(!$detail['file']) throw new HttpException('404');

		// if admin section, dont do panic checks
		if(!$this->director->isAdminSection())
		{
			// check if tree node of news item is accessable
			$tree = $this->director->tree;
			if(!$tree->exists($detail['tree_id'])) throw new HttpException('404');

			// check if type is not archive. if so, check if file is activated (online offline dates)
			if($tree->getCurrentId() != $detail['tree_id'] && !$detail['activated']) throw new HttpException('404');
		}

		$extension = Utils::getFileExtension($detail['file']);
		$filename = $detail['name'].".$extension";
		$file = $this->getFilePath().$detail['file'];

		header("Content-type: application/$extension");
		header("Content-Length: ".filesize($file));
		// stupid bastards of microsnob: ie does not like attachment option
		$browser = $request->getValue('HTTP_USER_AGENT', Request::SERVER);
		if (strstr($browser, 'MSIE'))
			header("Content-Disposition: filename=\"$filename\"");
		else
			header("Content-Disposition: attachment; filename=\"$filename\"");

		readfile($file);
		exit;
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
		$template->setVariable('cbo_comment_display', Utils::getHtmlCombo(NewsComment::getDisplayTypeList(), $fields['comment_display']));

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
				require_once('NewsOverview.php');
				$this->reference[$type] = new NewsOverview($this);
				break;
			case self::TYPE_HEADLINES :
				require_once("NewsHeadlines.php");
				$this->reference[$type] = new NewsHeadlines($this);
				break;
			case self::TYPE_ARCHIVE :
				require_once("NewsArchive.php");
				$this->reference[$type] = new NewsArchive($this);
				break;
			case self::TYPE_ATTACHMENT :
				require_once("NewsAttachment.php");
				$this->reference[$type] = new NewsAttachment($this);
				break;
			case self::TYPE_IMAGE :
				require_once("NewsImage.php");
				$this->reference[$type] = new NewsImage($this);
				break;
			case self::TYPE_COMMENT :
				require_once("NewsComment.php");
				$this->reference[$type] = new NewsComment($this);
				break;
			case self::TYPE_SETTINGS :
				require_once("NewsSettings.php");
				$this->reference[$type] = new NewsSettings($this);
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
				require_once('NewsTreeRef.php');
				$treeref = new NewsTreeRef();
				$key = array('ref_tree_id' => $values['tree_id']);
				$treeref->delete($key);

				$key = array('tree_id' => $values['tree_id'], 'tag' => $values['tag']);

				// delete settings
				$settings = $this->getObject(self::TYPE_SETTINGS);
				$settings->delete($key);

				// delete news items
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

			$news = $this->getObject(self::TYPE_DEFAULT);
			$newsSettings = $this->getObject(self::TYPE_SETTINGS);
			$comment = $this->getObject(self::TYPE_COMMENT);

			// check if news exists
			$searchcriteria = array('id' => $search['news_id'], 'activated' => true);
			if(!$news->exists($searchcriteria)) throw new Exception("News item does not exist");

			$detail = $news->getDetail($searchcriteria);

			// check if user is authorized to view tree node
			if(!$this->director->tree->exists($detail['tree_id'])) throw new Exception("Access denied");

			$settings = $newsSettings->getSettings($detail['tree_id'], $detail['tag']);

			// convert html code to text and remove apos character
			foreach($search as &$item)
			{
				$item = html_entity_decode($item);
				$item = str_replace("&apos;", "'", $item);
			}

			$comment->setSettings($settings);
			$comment->addComment($search);
			$template = $comment->getOverview($detail['id']);
			$template->setVariable('news', $detail);
			
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

	public function renderPluginRequest($theme)
	{
		$this->renderForm($theme);
	}

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
