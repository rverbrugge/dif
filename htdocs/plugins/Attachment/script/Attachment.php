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
class Attachment extends Plugin implements GuiProvider, CliProvider
{
	const TYPE_DEFAULT = 1;
	const TYPE_HEADLINES = 2;
	const TYPE_ARCHIVE = 3;

	const DISP_BRIEF 	= 1;
	const DISP_INTRO 	= 2;
	const DISP_FULL		= 3;

	const ORDER_ASC		= 4;
	const ORDER_DESC	= 8;
	const ORDER_RAND	= 16;

	const VIEW_FILE = "file";
	const VIEW_FILE_IMPORT 	= "fimp";


	private $displaytypes 	= array(self::DISP_BRIEF 	=> 'Brief',
																self::DISP_INTRO	=> 'Intro');
	private $displaytypelist;

	private $displayorder 	= array(self::ORDER_ASC			=> 'Oplopend',
																	self::ORDER_DESC		=> 'Aflopend',
																	self::ORDER_RAND		=> 'Willekeurig');
	
	private $displayorderlist;

	protected	$types = array(self::TYPE_DEFAULT => 'Overzicht',
														self::TYPE_HEADLINES => 'Headlines',
														self::TYPE_ARCHIVE => 'Archief');

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
		$this->templateFile = "attachment.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('attachment_settings', 'a');
		$this->sqlParser->addField(new SqlField('a', 'att_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'att_display', 'display', 'Weergave', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'att_display_hdl', 'display_hdl', 'Weergave headlines', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'att_rows', 'rows', 'Aantal items', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'att_order', 'display_order', 'Volgorde', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'att_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'att_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'att_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'att_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_FILE, 'Bestand');
		$view->insert(self::VIEW_FILE_IMPORT, 'Bestanden Importeren');
	}


	public function getFilePath()
	{
		return DIF_SYSTEM_ROOT.$this->director->getConfig()->file_path."/".strtolower($this->getClassName()).'/';
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
			case 'display' : return self::DISP_BRIEF; break;
			case 'display_hdl' : return self::DISP_BRIEF; break;
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
				case ViewManager::CONF_EDIT : $this->handleConfEditPost(); break;
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
		$key = array('id' => $id, 'active' => true);

		$obj = $this->getObject(self::TYPE_DEFAULT);
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

		$template->setVariable('cbo_display', Utils::getHtmlCombo($this->getDisplayTypeList(), $fields['display']));
		$template->setVariable('cbo_display_hdl', Utils::getHtmlCombo($this->getDisplayTypeList(), $fields['display_hdl']));
		$template->setVariable('cbo_order', Utils::getHtmlCombo($this->getDisplayOrderList(), $fields['display_order']));

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
	protected function getObject($type)
	{
		if(isset($this->reference[$type])) return $this->reference[$type];

		switch($type)
		{
			case self::TYPE_DEFAULT : 
				require_once('AttachmentOverview.php');
				$this->reference[$type] = new AttachmentOverview($this);
				break;
			case self::TYPE_HEADLINES :
				require_once("AttachmentHeadlines.php");
				$this->reference[$type] = new AttachmentHeadlines($this);
				break;
			case self::TYPE_ARCHIVE :
				require_once("AttachmentArchive.php");
				$this->reference[$type] = new AttachmentArchive($this);
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
				require_once('AttachmentTreeRef.php');
				$treeref = new AttachmentTreeRef();
				$key = array('ref_tree_id' => $values['tree_id']);
				$treeref->delete($key);

				$key = array('tree_id' => $values['tree_id'], 'tag' => $values['tag']);
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
}

?>
