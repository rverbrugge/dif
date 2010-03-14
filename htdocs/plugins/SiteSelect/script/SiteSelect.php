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
class SiteSelect extends Plugin implements GuiProvider
{
	const TYPE_DEFAULT = 1;

	const VIEW_DEFAULT 	= 1;
	const VIEW_COMBO 	= 2;
	const VIEW_IMAGE 	= 3;

	private $selectType 	= array(self::VIEW_DEFAULT			=>'List',
																self::VIEW_COMBO			=>'Pulldown select',
																self::VIEW_IMAGE			=>'Images');

	private $selectTypelist;

	protected	$types = array(self::TYPE_DEFAULT => 'Standaard');

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $templateFile;
	private $filePath;

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
		$this->templateFile = "siteselect.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('siteselect', 'a');
		$this->sqlParser->addField(new SqlField('a', 'site_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'site_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'site_type', 'type', 'Type', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'site_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'site_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'site_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'site_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

	}

/*------- helper function {{{ -------*/
	public function getSelectTypeList()
	{
		if(isset($this->selectTypelist)) return $this->selectTypelist;

		$this->selectTypelist = array();
		foreach($this->selectType as $key=>$value)
		{
			$this->selectTypelist[$key] = array('id' => $key, 'name' => $value);
		}
		return $this->selectTypelist;
	}

	private function getSiteGroup()
	{
		if(isset($this->siteGroup)) return $this->siteGroup;

		$this->siteGroup = $this->director->siteManager->systemSite->getSiteGroup();
		return $this->siteGroup;
	}

	/**
	 * load default images from configuration file
	 */
	private function loadImages()
	{
		$this->images = array();

		$images = $this->getConfig()->images;
		if(!$images) return;

		$imagePath = $this->getHtdocsPath()."images/";

		foreach($images as $key=>$value)
		{
			$img = explode(',',$value);
			if(!$img || sizeof($img) != 3) continue;

			list($src, $width, $height) = $img;
			$image =  array('src' => $imagePath.$src, 'width' => $width, 'height' => $height);
			$this->images[$key] = $image;
		}
	}

	public function getImage($name)
	{
		if(!isset($this->images)) $this->loadImages();

		if(array_key_exists($name, $this->images)) return $this->images[$name];
		return NULL;
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
			case 'type' : return self::VIEW_IMAGE; break;
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
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if($this->director->isAdminSection() && $view->isType(ViewManager::OVERVIEW)) $view->setType(ViewManager::ADMIN_OVERVIEW);

		switch($view->getType())
		{
			case ViewManager::CONF_OVERVIEW :
			case ViewManager::CONF_NEW :
			case ViewManager::CONF_EDIT :
			case ViewManager::CONF_DELETE :$this->handleConfOverview(); break;
			case ViewManager::TREE_OVERVIEW : 
			case ViewManager::TREE_NEW :  
			case ViewManager::TREE_EDIT : $this->handleTreeEditPost(); break;
			default : $this->handleOverview(); break;
		}
	}

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if($this->director->isAdminSection() && $view->isType(ViewManager::OVERVIEW)) $view->setType(ViewManager::ADMIN_OVERVIEW);

		switch($view->getType())
		{
			case ViewManager::CONF_OVERVIEW :
			case ViewManager::CONF_NEW :
			case ViewManager::CONF_EDIT :
			case ViewManager::CONF_DELETE :$this->handleConfOverview(); break;
			case ViewManager::TREE_OVERVIEW : 
			case ViewManager::TREE_NEW : 
			case ViewManager::TREE_EDIT : $this->handleTreeEditGet(); break;
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
		$taglist = $this->getTagList();
		if(!$taglist) return;
		$tree = $this->director->tree;

		foreach($taglist as $item)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$template->setCacheable(true);
			$template->setPostfix($item['tag']);
			$key = array('tree_id' => $item['tree_id'], 'tag' => $item['tag']);

			// check if template is in cache
			if(!$template->isCached()) 
			{
				$siteGroup = $this->getSiteGroup();
				$id = $siteGroup->getCurrentId();

				$detail = $this->exists($key) ? $this->getDetail($key) : $this->getFields(SqlParser::MOD_INSERT);
				$template->setVariable('detail',  $detail);

				$url = new Url();
				$url->setPath('/');

				$grouplist = $siteGroup->getList(array('active' => true));
				if($grouplist['totalItems'] < 2) continue;

				foreach($grouplist['data'] as &$list)
				{
					$url->setParameter(SystemSiteGroup::CURRENT_ID_KEY, $list['id']);
					$list['path'] = $url->getUrl(true);
					$list['selected'] = ($list['id'] == $id);

					$list['img'] = $this->getImage($list['language']);
				}
				$template->setVariable('sitegroup',  $grouplist, false);
				$template->setVariable('sitegroupId',  $id);
			}

			$this->template[$item['tag']] = $template;
		}
	} 

//}}}

/*------- conf edit request {{{ -------*/
	/**
	 * handle conf edit
	*/
	private function handleConfOverview($retrieveFields=true)
	{
		viewManager::getInstance()->setType(ViewManager::CONF_EDIT);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('pageTitle',  $this->description, false);
		
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
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
		$view->setType(ViewManager::TREE_EDIT);

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

		$this->setFields($fields);
		$template->setVariable($fields, NULL, false);
		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		$template->setVariable('cbo_type', Utils::getHtmlCombo($this->getSelectTypeList(), $fields['type'], 'geen weergave'));

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeEditPost()
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

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->referer->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleTreeEditGet(false);
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

		$template = $theme->getTemplate();

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
