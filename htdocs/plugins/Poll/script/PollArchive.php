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

require_once('PollTreeRef.php');

/**
 * Main configuration 
 * @package Common
 */
class PollArchive extends Observer
{

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	/**
	 * refernce objects (attachment)
	 */
	private $reference;

	/**
	 * plugin settings of parent class
	 */
	private $settings;

	/**
	 * pointer to global plugin plugin
	 * @var Poll
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
		$this->templateFile = "poll_archive.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";
		$this->reference = array();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('poll_archive', 'a');
		$this->sqlParser->addField(new SqlField('a', 'poll_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_online', 'online', 'Online datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'poll_offline', 'offline', 'Offline datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'poll_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'poll_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
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

	private function getPollOverview()
	{
		if(isset($this->pollOverview)) return $this->pollOverview;

		require_once('PollOverview.php');
		$this->pollOverview = new PollOverview($this->plugin);
		return $this->pollOverview;
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
		$treeRef = new PollTreeRef();
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
		$treeRef = new PollTreeRef();
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

		$fields['online'] = (array_key_exists('online', $fields) && $fields['online']) ? strftime('%Y-%m-%d', strtotime($fields['online'])) : '';
		$fields['offline'] = (array_key_exists('offline', $fields) && $fields['offline']) ? strftime('%Y-%m-%d', strtotime($fields['offline'])) : '';

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
		$treeRef = new PollTreeRef();
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
			case Poll::VIEW_DETAIL : $this->handleDetail(); break;
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
		$page = $this->getPage();
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		$taglist = $this->plugin->getTagList();
		if(!$taglist) return;

		$tree = $this->director->tree;

		$url = new Url(true); 
		$url->setParameter($view->getUrlId(), Poll::VIEW_DETAIL);

		// link to poll tree nodes
		$treeRef = new PollTreeRef();

		foreach($taglist as $item)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$template->setPostfix($item['tag']);
			$template->setCacheable(true);

			// check if template is in cache
			if(!$template->isCached())
			{
				// get settings
				$settings = $this->getSettings();

				$key = array('tree_id' => $item['tree_id'], 'tag' => $item['tag']);

				$detail = $this->getDetail($key);
				$treeRefList = $treeRef->getList($key);
				$treeItemList = array();

				foreach($treeRefList['data'] as $treeRefItem)
				{
					if(!$tree->exists($treeRefItem['ref_tree_id'])) continue;
					$treeItemList[] = $treeRefItem['ref_tree_id'];
				}
				if(!$treeItemList) continue;

				$searchcriteria = array('tree_id' => $treeItemList, 'active' => true);
				if($detail['online']) $searchcriteria['archiveonline'] = $detail['online'];
				$searchcriteria['archiveoffline'] = $detail['offline'] ? $detail['offline'] : time();


				$overview = $this->getPollOverview();
				$list = $overview->getList($searchcriteria, $settings['rows'], $page);
				foreach($list['data'] as &$poll_item)
				{
					$url->setParameter('id', $poll_item['id']);

					$poll_item['href_detail'] = $url->getUrl(true);
					if($poll_item['thumbnail'])
					{
						$img = new Image($poll_item['thumbnail'], $this->plugin->getContentPath(true));
						$poll_item['thumbnail'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
					}
				}

				$template->setVariable('poll',  $list);
				$template->setVariable('display',  $settings['display'], false);
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
		$taglist = $this->plugin->getTagList();
		if(!$taglist) return;
		$taginfo = current($taglist);

		// clear subtitle
		$view->setName('');

		if(!$request->exists('id')) continue;
		$id = intval($request->getValue('id'));
		$key = array('id' => $id, 'active' => true);

		$overview = $this->getPollOverview();

		if(!$overview->exists($key)) throw new HttpException('404');
		$detail = $overview->getDetail($key);

		// check if tree node of poll item is accessable
		$tree = $this->director->tree;
		if(!$tree->exists($detail['tree_id'])) throw new HttpException('404');

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setPostfix($detail['tag']);
		// disable cache because we want to count visits
		$template->setCacheable(false);
		Cache::disableCache();

		// overwrite default naming
		$template->setVariable('pageTitle',  $detail['name'], false);

		$template->setVariable('poll',  $detail, false);

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter($view->getUrlId(), ViewManager::OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$breadcrumb = array('name' => $detail['name'], 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

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

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$fields = array();
		if($retrieveFields)
		{
 			if($this->exists($key))
			{
				$fields = $this->getDetail($key);
				$fields['online'] = $fields['online'] ? strftime('%Y-%m-%d', $fields['online']) : '';
				$fields['offline'] = $fields['offline'] ? strftime('%Y-%m-%d', $fields['offline']) : '';
			}
			else
			 $fields = $this->getFields(SqlParser::MOD_INSERT);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		// get all tree nodes which have plugin modules
		$sitePlugin = $this->director->siteManager->systemSite->getSitePlugin();
		$tree = $this->plugin->getReferer()->getTree();

		$searchcriteria = array('classname' => 'Poll',
														'plugin_type'			=> Poll::TYPE_DEFAULT);

		$treeplugin = $sitePlugin->getList($searchcriteria);
		$treelist = array();
		foreach($treeplugin['data'] as $item)
		{
			if(!$tree->exists($item['tree_id'])) continue;
			$treelist[] = array('id' => $item['tree_id'], 'name' => $tree->toString($item['tree_id'],'/','name'));
		}

		// get all selected tree node connections
		$treeRef = new PollTreeRef();
		$treeRefTmp = $treeRef->getList($key);
		$treeRefLink = array();
		foreach($treeRefTmp['data'] as $item)
		{
			$treeRefLink[] = $item['ref_tree_id'];
		}

		$template->setVariable('ref_tree_id', Utils::getHtmlCheckbox($treelist, $treeRefLink, 'ref_tree_id', '<br />'));

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
		Utils::getDatePicker($this->director->theme, $datefields);

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

			$treeRef = new PollTreeRef();
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
			$online = $this->sqlParser->getFieldByName('online');
			$this->sqlParser->setFieldValue('online', strftime('%Y-%m-%d', strtotime($online->getValue())));

			$offline = $this->sqlParser->getFieldByName('offline');
			$this->sqlParser->setFieldValue('offline', strftime('%Y-%m-%d', strtotime($offline->getValue())));

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

		if($this->reference)
		{
			foreach($this->reference as $object)
			{
				$object->renderForm($theme);
			}
		}

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
