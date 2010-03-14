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

require_once('CalendarTreeRef.php');

/**
 * Main configuration 
 * @package Common
 */
class CalendarArchive extends Observer
{

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
	 * @var Calendar
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
		$this->templateFile = "calendararchive.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('calendar_archive', 'a');
		$this->sqlParser->addField(new SqlField('a', 'cal_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'cal_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'cal_start', 'start', 'Start datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'cal_stop', 'stop', 'Stop datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'cal_rows', 'rows', 'Aantal items', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'cal_date_format', 'date_format', 'Date format', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'cal_display', 'display', 'Weergave', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'cal_group', 'group_type', 'Groepering', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'cal_cap_detail', 'cap_detail', 'Caption detail link', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'cal_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'cal_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'cal_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'cal_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
	}

/*-------- helper functions {{{----------*/
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
		$treeRef = new CalendarTreeRef();
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
		$treeRef = new CalendarTreeRef();
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

		$fields['start'] = (array_key_exists('start', $fields) && $fields['start']) ? strftime('%Y-%m-%d', strtotime($fields['start'])) : '';
		$fields['stop'] = (array_key_exists('stop', $fields) && $fields['stop']) ? strftime('%Y-%m-%d', strtotime($fields['stop'])) : '';

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
		$treeRef = new CalendarTreeRef();
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
			case Calendar::VIEW_ARCHIVE : $this->handleDetail(); break;
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
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		$taglist = $this->plugin->getTagList(array('plugin_type' => Calendar::TYPE_ARCHIVE));
		if(!$taglist) return;

		$tree = $this->director->tree;

		$url = new Url(true); 
		$url->setParameter($view->getUrlId(), Calendar::VIEW_ARCHIVE);

		// link to cal tree nodes
		$treeRef = new CalendarTreeRef();

		foreach($taglist as $item)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$template->setPostfix($item['tag']);
			$template->setCacheable(true);

			// check if template is in cache
			if(!$template->isCached())
			{
				$key = array('tree_id' => $item['tree_id'], 'tag' => $item['tag']);

				$detail = $this->getDetail($key);
				$template->setVariable('settings',  $detail);

				$treeRefList = $treeRef->getList($key);
				$treeItemList = array();

				foreach($treeRefList['data'] as $treeRefItem)
				{
					if(!$tree->exists($treeRefItem['ref_tree_id'])) continue;
					$treeItemList[] = $treeRefItem['ref_tree_id'];
				}
				if(!$treeItemList) continue;

				// get active items between a period x and y.
				// cal_start must by less or equal to end of period
				// cal_stop must by greater or equeal to start of period

				$searchcriteria = array('tree_id' 	=> $treeItemList, 
																'finished'	=> true,
																'active' 		=> true);


				if($detail['stop'] && !$detail['start']) 
				{
					// end period defined; limit result to start en end period
					$searchcriteria['archive_stop']	= $detail['stop'];
				}
				elseif(!$detail['stop'] && $detail['start'])
				{
					// only start period defined; limit result to inactive items from startperiod
					$searchcriteria['archive_start']	= $detail['start'];
				}
				elseif($detail['stop'] && $detail['start']) 
				{
					$searchcriteria['archive_start']	= $detail['start'];
					$searchcriteria['archive_stop']	= $detail['stop'];
				}

				$overviewObj = $this->plugin->getObject(Calendar::TYPE_DEFAULT);
				
				$template->setVariable('tpl_list', $overviewObj->getOverviewList($searchcriteria, $detail, $url));
			}

			$this->template[$item['tag']] = $template;
		}
	} //}}}

/*------- detail request {{{ -------*/
	/**
	 * handle detail request
	*/
	private function handleDetail()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		// it makes no sense to have multiple tags for this plugin. 
		// if someone did it, you get strange results and he probably can figure out why.. no multiple detail stuff in 1 page supported!
		// so take a shot and get the first tag to set the content
		$taglist = $this->plugin->getTagList(array('plugin_type' => Calendar::TYPE_ARCHIVE));
		if(!$taglist) return;
		$taginfo = current($taglist);

		// process attachments
		$attachment = $this->plugin->getObject(Calendar::TYPE_ATTACHMENT);
		$attachment->handleHttpGetRequest();

		// process images
		$image = $this->plugin->getObject(Calendar::TYPE_IMAGE);
		$image->handleHttpGetRequest();

		// clear subtitle
		$view->setName('');

		if(!$request->exists('id')) throw new Exception('Calendar id is missing.');
		$id = intval($request->getValue('id'));
		$key = array('id' => $id, 'active' => true, 'finished'	=> true);

		$overview = $this->plugin->getObject(Calendar::TYPE_DEFAULT);

		if(!$overview->exists($key)) return;
		$detail = $overview->getDetail($key);

		// check if tree node of cal item is accessable
		$tree = $this->director->tree;
		if(!$tree->exists($detail['tree_id'])) throw new HttpException('404');

		$objSettings = $this->plugin->getObject(Calendar::TYPE_SETTINGS);
		$settings = $objSettings->getSettings($detail['tree_id'], $detail['tag']);

		if($detail['thumbnail'])
		{
			$img = new Image($detail['thumbnail'], $this->plugin->getContentPath(true));
			$detail['thumbnail'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
		}

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setPostfix($detail['tag']);
		// disable cache because we want to count visits
		$template->setCacheable(false);
		Cache::disableCache();

		// update view counter
		$overview->updateCount($key);

		// overwrite default naming
		$template->setVariable('pageTitle',  $detail['name'], false);

		$url = new Url(true);
		$url->clearParameter('id');

		$url->setParameter($view->getUrlId(), ViewManager::OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$breadcrumb = array('name' => $detail['name'], 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		$template->setVariable('cal',  $detail, false);

		$template->setVariable('settings',  $settings);
		$template->setVariable('calsettings',  $settings, false);

		// get settings
		if($settings['comment'])
		{
			// process comments
			$comment = $this->plugin->getObject(Calendar::TYPE_COMMENT);
			$comment->setSettings($settings);
			$comment->handleHttpGetRequest();
		}

		$this->template[$taginfo['tag']] = $template;
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

		$key = $this->getKey();

		$fields = array();
		if($retrieveFields)
		{
 			if($this->exists($key))
			{
				$fields = $this->getDetail($key);
				$fields['start'] = $fields['start'] ? strftime('%Y-%m-%d', $fields['start']) : '';
				$fields['stop'] = $fields['stop'] ? strftime('%Y-%m-%d', $fields['stop']) : '';
			}
			else
			 $fields = $this->getFields(SqlParser::MOD_INSERT);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		$template->setVariable('cbo_group', Utils::getHtmlCombo(Calendar::getGroupTypeList(), $fields['group_type']));
		$template->setVariable('cbo_display', Utils::getHtmlCombo(Calendar::getDisplayTypeList(), $fields['display']));

		// get all tree nodes which have plugin modules
		$sitePlugin = $this->director->siteManager->systemSite->getSitePlugin();
		$tree = $this->plugin->getReferer()->getTree();

		$searchcriteria = array('classname' => 'Calendar',
														'plugin_type'			=> Calendar::TYPE_DEFAULT);

		$treeplugin = $sitePlugin->getList($searchcriteria);
		$treelist = array();
		foreach($treeplugin['data'] as $item)
		{
			if(!$tree->exists($item['tree_id'])) continue;
			$treelist[] = array('id' => $item['tree_id'], 'name' => $tree->toString($item['tree_id'],'/','name'));
		}

		// get all selected tree node connections
		$treeRef = new CalendarTreeRef();
		$treeRefTmp = $treeRef->getList($key);
		$treeRefLink = array();
		foreach($treeRefTmp['data'] as $item)
		{
			$treeRefLink[] = $item['ref_tree_id'];
		}

		$template->setVariable('ref_tree_id', Utils::getHtmlCheckbox($treelist, $treeRefLink, 'ref_tree_id', '<br />'));

		$datefields = array();
		$datefields[] = array('dateField' => 'start', 'triggerElement' => 'start');
		$datefields[] = array('dateField' => 'stop', 'triggerElement' => 'stop');
		Utils::getDatePicker($this->director->theme, $datefields);

		$this->setFields($fields);
		$template->setVariable($fields);
		$template->setVariable($key);

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

			$treeRef = new CalendarTreeRef();
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

			// reset date values
			$start = $this->sqlParser->getFieldByName('start');
			$this->sqlParser->setFieldValue('start', strftime('%Y-%m-%d', strtotime($start->getValue())));

			$stop = $this->sqlParser->getFieldByName('stop');
			$this->sqlParser->setFieldValue('stop', strftime('%Y-%m-%d', strtotime($stop->getValue())));

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
		$view = ViewManager::getInstance();

		$template = $theme->getTemplate();
		$template->setVariable($view->getUrlId(),  $view->getName(), false);

		// parse rpc javascript to set variables
		$theme->addFileVar('calendar_htdocs_path', $this->plugin->getHtdocsPath());
		$rpcfile_src = $this->plugin->getHtdocsPath(true)."js/rpc.js.in";
		$theme->addJavascript($theme->fetchFile($rpcfile_src));

		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_lib.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_wrappers.js"></script>');

		// render comments
		$objComment = $this->plugin->getObject(Calendar::TYPE_COMMENT);
		$objComment->renderForm($theme);

		$objAttachment = $this->plugin->getObject(Calendar::TYPE_ATTACHMENT);
		$objAttachment->renderForm($theme);

		$objImage = $this->plugin->getObject(Calendar::TYPE_IMAGE);
		$objImage->renderForm($theme);

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
