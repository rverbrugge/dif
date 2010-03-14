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

require_once("NewsLetterUser.php");

/**
 * Main configuration 
 * @package Common
 */
class NewsLetter extends Plugin implements GuiProvider, RpcProvider
{
	const TYPE_DEFAULT = 1;
	const TYPE_ARCHIVE = 2;

	const TYPE_ATTACHMENT = 3;
	const TYPE_GROUP 			= 4;
	const TYPE_USER 			= 5;
	const TYPE_TAG 				= 6;
	const TYPE_PLUGIN 		= 7;
	const TYPE_SETTINGS 	= 8;

	const DISP_BRIEF 	= 1;
	const DISP_INTRO 	= 2;
	const DISP_IMAGE	= 3;
	const DISP_FULL		= 4;

	const VIEW_DETAIL 			= "nl";
	const VIEW_FILE 				= "nfile";
	const VIEW_FILE_IMPORT 	= "fimp";
	const VIEW_PREVIEW 			= "np";
	const VIEW_SEND 			= "ns";
	const VIEW_SEND_SUCCESS 			= "nss";

	const VIEW_FILE_OVERVIEW 	= "n1";
	const VIEW_FILE_NEW 			= "n2";
	const VIEW_FILE_EDIT 			= "n3";
	const VIEW_FILE_DELETE 		= "n4";

	const VIEW_GROUP_OVERVIEW 	= "g1";
	const VIEW_GROUP_NEW 			= "g2";
	const VIEW_GROUP_EDIT 			= "g3";
	const VIEW_GROUP_DELETE 		= "g4";
	const VIEW_GROUP_USER 		= "g5";

	const VIEW_USER_OVERVIEW 	= "u1";
	const VIEW_USER_NEW 			= "u2";
	const VIEW_USER_EDIT 			= "u3";
	const VIEW_USER_DELETE 		= "u4";
	const VIEW_USER_UNSUBSCRIBE = "u5";

	const VIEW_TAG_EDIT 			= "t2";
	const VIEW_TAG_DELETE 		= "t3";

	const VIEW_PLUGIN_OVERVIEW 	= "p1";
	const VIEW_PLUGIN_NEW 			= "p2";
	const VIEW_PLUGIN_EDIT 			= "p3";
	const VIEW_PLUGIN_DELETE 		= "p4";
	const VIEW_PLUGIN_MOVE 		= "p5";
	const VIEW_PLUGIN_CONFIG 		= "p6";

	const VIEW_OPTIN = "nl6";
	const VIEW_CONFIG 	= "cfg";

	static public $displaytypes 	= array(self::DISP_BRIEF 	=> 'Name',
																				self::DISP_INTRO	=> 'Name & intro',
																				self::DISP_IMAGE	=> 'Name, Intro & image',
																				self::DISP_FULL		=> 'Full');
	static private $displaytypelist;

	protected	$types = array(self::TYPE_DEFAULT => 'Overview',
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
		$this->templateFile = "newsletter.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('newsletter_settings', 'a');
		$this->sqlParser->addField(new SqlField('a', 'set_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'set_image_width', 'image_width', 'Breedte afbeelding', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'set_image_height', 'image_height', 'Hoogte afbeelding', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'set_image_max_width', 'image_max_width', 'maximale breedte afbeelding', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'set_theme_id', 'theme_id', 'Theme', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'set_rows', 'rows', 'Aantal items', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'set_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'set_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_DETAIL, 'Details');
		$view->insert(self::VIEW_FILE, 'Attachments');
		$view->insert(self::VIEW_FILE_OVERVIEW, 'Attachments overview');
		$view->insert(self::VIEW_CONFIG, 'Configuration');
		$view->insert(self::VIEW_OPTIN, 'Opt-in');
		$view->insert(self::VIEW_PREVIEW, 'Preview');
		$view->insert(self::VIEW_SEND, 'Send newsletter');

		$view->insert(self::VIEW_FILE_NEW, 'Attachments new');
		$view->insert(self::VIEW_FILE_EDIT, 'Attachment edit');
		$view->insert(self::VIEW_FILE_DELETE, 'Attachment delete');
		$view->insert(self::VIEW_FILE_IMPORT, 'Attachment import');

		$view->insert(self::VIEW_GROUP_OVERVIEW, 'Groups overview');
		$view->insert(self::VIEW_GROUP_NEW, 'Group new');
		$view->insert(self::VIEW_GROUP_EDIT, 'Group edit');
		$view->insert(self::VIEW_GROUP_DELETE, 'Group delete');
		$view->insert(self::VIEW_GROUP_USER, 'Group users');

		$view->insert(self::VIEW_USER_OVERVIEW, 'Users overview');
		$view->insert(self::VIEW_USER_NEW, 'User new');
		$view->insert(self::VIEW_USER_EDIT, 'User edit');
		$view->insert(self::VIEW_USER_DELETE, 'User delete');
		$view->insert(self::VIEW_USER_UNSUBSCRIBE, 'Unsubscribe user');

		$view->insert(self::VIEW_TAG_EDIT, 'Tag edit');
		$view->insert(self::VIEW_TAG_DELETE, 'Tag delete');

		$view->insert(self::VIEW_PLUGIN_OVERVIEW, 'Plugins overview');
		$view->insert(self::VIEW_PLUGIN_NEW, 'Plugin new');
		$view->insert(self::VIEW_PLUGIN_EDIT, 'Plugin edit');
		$view->insert(self::VIEW_PLUGIN_DELETE, 'Plugin delete');
		$view->insert(self::VIEW_PLUGIN_MOVE, 'Plugin move');
		$view->insert(self::VIEW_PLUGIN_CONFIG, 'Plugin config');
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
				default : if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
									$typelist = $this->getReferenceTypeList(intval($request->getValue('tree_id')), $request->getValue('tag'));
			}
		}
		else
		{
			switch($view->getType())
			{
				case self::VIEW_FILE : $this->handleFile(); break;
				case self::VIEW_OPTIN : $this->handleOptin(); break;
				default : $typelist = $this->getReferenceTypeList();
			}
		}

		foreach($typelist as $type)
		{
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
				default : if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
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
//}}}

/*------- file request {{{ -------*/
	/**
	 * handle file 
	*/
	private function handleFile()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if(!$request->exists('id')) throw new Exception('Id is missing.');
		$id = intval($request->getValue('id'));
		// if admin section, dont check if active
		$key = $this->director->isAdminSection() ? array('id' => $id) : array('id' => $id, 'active' => true, 'news_active' => true);

		$obj = $this->getObject(NewsLetter::TYPE_ATTACHMENT);

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
		$template->setVariable($fields);
		$template->setVariable('id', ($detail) ? $detail['id'] : '');

		// get theme list
		$themeManager = new ThemeManager();
		$searchcriteria = array('active' => true);
		$themelist = $themeManager->getList($searchcriteria);
		$template->setVariable('cbo_theme', Utils::getHtmlCombo($themelist['data'], $fields['theme_id']));

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

/*------- Optin confirm request {{{ -------*/
	/**
	* handle optin confirm
	*/
	private function handleOptin()
	{
		$taglist = $this->getTagList();
		if(!$taglist) return;

		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$objUser = $this->getObject(self::TYPE_USER);
		$objSettings = $this->getObject(self::TYPE_SETTINGS);

		try
		{
			if(!$request->exists('key')) throw new Exception('Parameter does not exist.');
			$keyValue = $request->getValue('key');
			if(!$keyValue)throw new Exception('Parameter is empty.');

			$key = array('optin' => $keyValue);
			$objUser->enable($key);

			// retrieve settings to get redirect location
			$searchcriteria = array();
			foreach($taglist as $item)
			{
				$searchcriteria = array('tree_id' => $item['tree_id'], 'tag' => $item['tag']);
			}

			$settings = $objSettings->getSettings($searchcriteria['tree_id'], $searchcriteria['tag']);

			$location = $settings['optin_tree_id'] ? $this->director->tree->getPath($settings['optin_tree_id']) : '/';
			header("Location: $location");
			exit;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('newsLetterErrorMessage',  $e->getMessage(), false);
			$this->log->info($e->getMessage());

			$view->setType(ViewManager::OVERVIEW);
			$this->handleHttpGetRequest();
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
				require_once('NewsLetterOverview.php');
				$this->reference[$type] = new NewsLetterOverview($this);
				break;
			case self::TYPE_ARCHIVE :
				require_once("NewsLetterArchive.php");
				$this->reference[$type] = new NewsLetterArchive($this);
				break;
			case self::TYPE_ATTACHMENT :
				require_once("NewsLetterAttachment.php");
				$this->reference[$type] = new NewsLetterAttachment($this);
				break;
			case self::TYPE_GROUP :
				require_once("NewsLetterGroup.php");
				$this->reference[$type] = new NewsLetterGroup($this);
				break;
			case self::TYPE_USER :
				require_once("NewsLetterUser.php");
				$this->reference[$type] = new NewsLetterUser($this);
				break;
			case self::TYPE_TAG :
				require_once("NewsLetterTag.php");
				$this->reference[$type] = new NewsLetterTag($this);
				break;
			case self::TYPE_PLUGIN :
				require_once("NewsLetterPlugin.php");
				$this->reference[$type] = new NewsLetterPlugin($this);
				break;
			case self::TYPE_SETTINGS :
				require_once("NewsLetterSettings.php");
				$this->reference[$type] = new NewsLetterSettings($this);
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
				require_once('NewsLetterTreeRef.php');
				$treeref = new NewsLetterTreeRef();
				$key = array('ref_tree_id' => $values['tree_id']);
				$treeref->delete($key);

				$key = array('tree_id' => $values['tree_id'], 'tag' => $values['tag']);

				// delete settings
				require_once('NewsLetterSettings.php');
				$obj = new NewsLetterSettings($this);
				$obj->delete($key);

				// delete users
				require_once('NewsLetterUser.php');
				$obj = new NewsLetterUser($this);
				$obj->delete($key);

				// delete user groups
				require_once('NewsLetterGroup.php');
				$obj = new NewsLetterGroup($this);
				$obj->delete($key);

				// delete usergroup links
				$obj->deleteUserGroup($values['tree_id'], $values['tag']);

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
	public function subscribe($search)
	{
		try
		{

			$objOverview = $this->getObject(self::TYPE_DEFAULT);
			$objSettings = $this->getObject(self::TYPE_SETTINGS);
			$objUser = $this->getObject(self::TYPE_USER);

			// check if user is authorized to view tree node
			if(!$this->director->tree->exists($search['tree_id'])) throw new Exception("Access denied");

			$settings = $objSettings->getSettings($search['tree_id'], $search['tag']);

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
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".subscribe", array(&$this,'handleRpcRequest'));
	}
//}}}
}

?>
