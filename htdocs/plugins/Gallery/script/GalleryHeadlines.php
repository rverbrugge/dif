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

require_once('GalleryTreeRef.php');

/**
 * Main configuration 
 * @package Common
 */
class GalleryHeadlines extends Observer
{

	const DISP_NORMAL		= 1;
	const DISP_DETAIL		= 2;
	const DISP_LIGHTBOX 	= 3;

	private $displaytypes 	= array(self::DISP_NORMAL 	=> 'Link to gallery',
																self::DISP_DETAIL	=> 'Link to image detail page',
																self::DISP_LIGHTBOX	=> 'Show image with Lightbox');
	private $displaytypelist;

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	/**
	 * plugin settings of parent class
	 */
	private $settings;

	/**
	 * pointer to global plugin plugin
	 * @var Gallery
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

		$this->template = array();
		$this->templateFile = "galleryheadlines.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('gallery_headlines', 'a');
		$this->sqlParser->addField(new SqlField('a', 'gal_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_display', 'display', 'Weergave', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_display_order', 'display_order', 'Volgorde', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_rows', 'rows', 'Aantal items', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
	}

/*-------- helper functions {{{----------*/
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

	private function getPath()
	{
		return $this->basePath;
	}

	private function getSettings()
	{
		if($this->settings) return $this->settings;

		$this->settings = $this->plugin->getDetail(array());
		if(!$this->settings) $this->settings = $this->plugin->getFields(SqlParser::MOD_INSERT);

		return $this->settings;
	}

	private function getGalleryOverview()
	{
		if(isset($this->galleryOverview)) return $this->galleryOverview;

		require_once('GalleryOverview.php');
		$this->galleryOverview = new GalleryOverview($this->plugin);
		return $this->galleryOverview;
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

		// update treeref
		$treeRef = new GalleryTreeRef();
		$treeRef->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
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

		// update treeref
		$treeRef = new GalleryTreeRef();
		$treeRef->updateTreeId($sourceNodeId, $destinationNodeId);
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
			case 'rows' : return 5; break;
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
	protected function handlePostDelete($id, $values)
	{
		$treeRef = new GalleryTreeRef();
		$treeRef->delete($id);
	}
	//}}}

/*----- handle http requests {{{ -------*/
	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case ViewManager::TREE_OVERVIEW : 
			case ViewManager::TREE_NEW :  
			case ViewManager::TREE_EDIT : $this->handleTreeEditPost(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDelPost(); break;
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
		$viewManager = ViewManager::getInstance();

		if($viewManager->isType(ViewManager::OVERVIEW) && $this->director->isAdminSection()) 
			$viewManager->setType(ViewManager::ADMIN_OVERVIEW);

		switch($viewManager->getType())
		{
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
		$view = ViewManager::getInstance();

		$taglist = $this->plugin->getTagList(array('plugin_type' => Gallery::TYPE_HEADLINES));
		if(!$taglist) return;

		$tree = $this->director->tree;

		$url = new Url(true); 
		//$url->setParameter($view->getUrlId(), Gallery::VIEW_DETAIL);

		// link to gallery tree nodes
		$treeRef = new GalleryTreeRef();

		foreach($taglist as $tag)
		{
			$key = array('tree_id' => $tag['tree_id'], 'tag' => $tag['tag']);
			$detail = $this->getDetail($key);

			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$template->setPostfix($tag['tag']);

			$cacheable = ($detail['display_order'] != Gallery::ORDER_RANDOM);
			$template->setCacheable($cacheable);
			if(!$cacheable) Cache::disableCache();

			$template->setVariable($detail);

			// include lightbox if needed
			if($detail['display'] == self::DISP_LIGHTBOX)
			{
				$theme = $this->director->theme;
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/lightbox.js"></script>');
				$theme->addHeader('<link rel="stylesheet" href="'.DIF_VIRTUAL_WEB_ROOT.'css/lightbox.css" type="text/css" media="screen" />');
			}

			// check if template is in cache
			if(!$template->isCached())
			{
				// get settings
				$settings = $this->getSettings();

				$treeRefList = $treeRef->getList($key);
				$treeItemList = array();

				foreach($treeRefList['data'] as $treeRefItem)
				{
					if(!$tree->exists($treeRefItem['ref_tree_id'])) continue;
					$treeItemList[] = $treeRefItem['ref_tree_id'];
				}
				if(!$treeItemList) continue;

				$searchcriteria = array('activated' => true, 'tree_id' => $treeItemList); 

				$overview = $this->getGalleryOverview();
				$list = $overview->getList($searchcriteria, $detail['rows'], 1, $detail['display_order']);

				// skip if empty
				if($list['totalItems'] < 1) continue;

				foreach($list['data'] as &$item)
				{
					$url->setPath($tree->getPath($item['tree_id']));

					// go to detail if requested
					if($detail['display'] == self::DISP_DETAIL)
						$url->setParameter('id', $item['id']);

					$item['href_detail'] = $url->getUrl(true);
					if($item['image'])
					{
						$img = new Image($item['image'], $this->plugin->getContentPath(true));
						$item['image'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
					}
					if($item['thumbnail'])
					{
						$img = new Image($item['thumbnail'], $this->plugin->getContentPath(true));
						$item['thumbnail'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
					}
				}

				$template->setVariable('gallery',  $list);
			}

			$this->template[$tag['tag']] = $template;
		}
	} //}}}

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

		// get all tree nodes which have plugin modules
		$sitePlugin = $this->director->siteManager->systemSite->getSitePlugin();
		$tree = $this->plugin->getReferer()->getTree();

		$searchcriteria = array('classname' => 'Gallery',
														'plugin_type'			=> Gallery::TYPE_DEFAULT);

		$treeplugin = $sitePlugin->getList($searchcriteria);
		$treelist = array();
		foreach($treeplugin['data'] as $item)
		{
			if(!$tree->exists($item['tree_id'])) continue;
			$treelist[] = array('id' => $item['tree_id'], 'name' => $tree->toString($item['tree_id'],'/','name'));
		}

		// get all selected tree node connections
		$treeRef = new GalleryTreeRef();
		$treeRefTmp = $treeRef->getList($key);
		$treeRefLink = array();
		foreach($treeRefTmp['data'] as $item)
		{
			$treeRefLink[] = $item['ref_tree_id'];
		}

		$template->setVariable('ref_tree_id', Utils::getHtmlCheckbox($treelist, $treeRefLink, 'ref_tree_id', '<br />'));
		$template->setVariable('cbo_display_order', Utils::getHtmlCombo($this->plugin->getDisplayOrderList(), $fields['display_order']));
		$template->setVariable('cbo_display', Utils::getHtmlCombo($this->getDisplayTypeList(), $fields['display']));


		$this->setFields($fields);
		$template->setVariable($fields, NULL, false);
		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

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

			$treeRef = new GalleryTreeRef();
			$treeRef->delete($key);
			foreach($values['ref_tree_id'] as $ref_tree_id)
			{
				$key['ref_tree_id'] = $ref_tree_id;
				$treeRef->insert($key);
			}

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->plugin->getReferer()->handleHttpGetRequest();
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
		$template = $theme->getTemplate();

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
