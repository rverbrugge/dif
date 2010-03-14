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

/**
 * Main configuration 
 * @package Common
 */
class GalleryComment extends Observer
{
	const VIEW_KEY = 'com';
	const VIEW_ADD = 1;

	const DISP_FORM_TOP = 1;
	const DISP_FORM_BOTTOM = 2;

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

	static public $displaytypes 	= array(self::DISP_FORM_TOP 	=> 'Form above posts',
																				self::DISP_FORM_BOTTOM	=> 'Form under posts');
	static private $displaytypelist;

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
		$this->setPagerKey('pcom');
		
		$this->template = array();
		$this->templateFile = "gallerycomment.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('gallery_comment', 'a');
		$this->sqlParser->addField(new SqlField('a', 'com_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'com_gal_id', 'gal_id', 'Gallery id', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('b', 'gal_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER, false));
		$this->sqlParser->addField(new SqlField('b', 'gal_tag', 'tag', 'Tag', SqlParser::getTypeSelect(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('b', 'gal_active', 'gal_active', 'Actieve status', SqlParser::getTypeSelect(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'com_active', 'active', 'Active state', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'com_name', 'name', 'Name', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'com_email', 'email', 'Email', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'com_text', 'text', 'Content', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'com_ip', 'ip', 'Ip address', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'com_date', 'date', 'Date', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'com_create', 'createdate', 'Create date', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'com_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->addFrom("inner join gallery as b on b.gal_id = a.com_gal_id");

		$this->orderStatement = array('order by a.com_date %s');

		$this->settings = array();
	}

/*-------- Helper functions {{{------------*/
	public function setSettings($settings)
	{
		$this->settings = $settings;
	}

	private function getSettings()
	{
		return $this->settings;
	}

	private function getPath()
	{
		return $this->basePath;
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
		if(!$searchcriteria || !is_array($searchcriteria)) return;

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.com_id', $value, '<>')); break;
				case 'gal_active' : $SqlParser->addCriteria(new SqlCriteria('b.gal_active', $value)); break;
				case 'activated' : 
					// only active pages
					$SqlParser->addCriteria(new SqlCriteria('a.com_active', 1));
					$SqlParser->addCriteria(new SqlCriteria('b.gal_active', 1));
					break;
				case 'search' : 
					$search = new SqlCriteria('a.com_name', "%$value%", 'like'); 
					$search->addCriteria(new SqlCriteria('a.com_text', "%$value%", 'like'), SqlCriteria::REL_OR); 
					$SqlParser->addCriteria($search);
					break;
			}
		}
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
			case 'active' : return 1; break;
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
		$request = Request::getInstance();
		$fields['active'] = (array_key_exists('active', $fields) && $fields['active']);
		$fields['ip'] = $request->getValue('REMOTE_ADDR', Request::SERVER);
		$fields['text'] = strip_tags($fields['text']);
		$fields['name'] = strip_tags($fields['name']);
		$fields['email'] = strip_tags($fields['email']);
		$fields['date'] = (array_key_exists('date', $fields) && $fields['date']) ? strftime('%Y-%m-%d %T',strtotime($fields['date'])) : '';
		
		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$activated = 1;

		// hide attachmentitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif(!$values['gal_active']) 
			$activated = 0;

		$values['activated'] = $activated;
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$activated = 1;

		// hide attachmentitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif(!$values['gal_active']) 
			$activated = 0;

		$values['activated'] = $activated;
		return $values;
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
		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));
		$this->sqlParser->setFieldValue('date', date('Y-m-d H:i:s'));
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
			case Gallery::VIEW_COMMENT_OVERVIEW : $this->handleTreeOverview(); break;
			case Gallery::VIEW_COMMENT_EDIT : $this->handleTreeEditPost(); break;
			case Gallery::VIEW_COMMENT_DELETE : $this->handleTreeDeletePost(); break;
			default : $this->handleOverview(); break;
		}

	} 

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case Gallery::VIEW_COMMENT_OVERVIEW : $this->handleTreeOverview(); break;
			case Gallery::VIEW_COMMENT_EDIT : $this->handleTreeEditGet(); break;
			case Gallery::VIEW_COMMENT_DELETE : $this->handleTreeDeleteGet(); break;
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
		$tag = 'gallerycomment';

		if(!$request->exists('id')) throw new Exception('Gallery id is missing.');

		// add comment if requested
		if($request->exists(self::VIEW_KEY)) $this->addComment($request->getRequest(Request::POST));

		$this->template[$tag] = $this->getOverview($request->getValue('id'));
	}

	public function getOverview($gal_id)
	{
		$gal_id = intval($gal_id);
		$tag = 'gallerycomment';

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setPostfix($tag);
		$template->setCacheable(true);

		// check if template is in cache
		if(!$template->isCached())
		{
			// retrieve comments
			$searchcriteria = array('gal_id' 	=> $gal_id, 
															'activated' 		=> true);

			$settings = $this->getSettings();
			$order = (array_key_exists('comment_order_asc', $settings) && $settings['comment_order_asc']) ? SqlParser::ORDER_ASC : SqlParser::ORDER_DESC;
			$list = $this->getList($searchcriteria, 0, 1, $order);
			$template->setVariable('gallerysettings',  $settings);
			$template->setVariable('comment',  $list);
			$template->setVariable('gal_id', $gal_id);
		}
		return $template;
	} //}}}

/*------- tree overview request {{{ -------*/
	/**
	 * handle tree overview
	*/
	private function handleTreeOverview()
	{
		$pagesize = 20;
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(Gallery::VIEW_COMMENT_OVERVIEW);

		$page = $this->getPage();

		if(!$request->exists('gal_id')) throw new Exception('Gallery id is missing.');
		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$gal_id = intval($request->getValue('gal_id'));
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('gal_id' => $gal_id);

		$this->pagerUrl->addParameters($key);
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		// handle searchcriteria
		$search = new SearchManager();
		$search->setUrl($this->pagerUrl);
		$search->setExclude($this->pagerKey);
		$search->setParameter('search');
		$search->saveList();
		$searchcriteria = $search->getSearchParameterList();
		$searchcriteria = array_merge($searchcriteria, $key);

		// add breadcrumb item
		$this->director->theme->handleAdminLinks($template);

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('gal_id', $gal_id);
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);


		// create urls
		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), Gallery::VIEW_COMMENT_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), Gallery::VIEW_COMMENT_DELETE);

		$list = $this->getList($searchcriteria, $pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
		}
		$template->setVariable('list',  $list, false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}
//}}}

/*------- tree settings {{{ -------*/
	private function handleTreeSettings($template)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('gal_id')) throw new Exception('Gallery id is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$gal_id = intval($request->getValue('gal_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		// create href back url
		$url = new Url(true); 
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('gal_id', $gal_id);
		$url->setParameter('tag', $tag);
		$url->setParameter($view->getUrlId(), Gallery::VIEW_COMMENT_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		// add breadcrumb item
		$breadcrumb = array('name' => $view->getName(Gallery::VIEW_COMMENT_OVERVIEW), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		$this->director->theme->handleAdminLinks($template);

	}

//}}}

/*------- add request {{{ -------*/

	/**
	 * handle add
	*/
	public function addComment($values)
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$values['active'] = 1;
		
		try
		{
			$id = $this->insert($values);

			$gallery = $this->plugin->getObject(Gallery::TYPE_DEFAULT);
			$galleryDetail = $gallery->getDetail(array('id' => $values['gal_id']));

			$url = new Url();
			$url->setPath($this->director->tree->getPath($galleryDetail['tree_id']));
			$url->setParameter('id', $galleryDetail['id']);
			$url->setParameter($view->getUrlId(), Gallery::VIEW_DETAIL);

			// notify insert
			$ip = $request->getValue('REMOTE_ADDR', Request::SERVER);
			$template = new TemplateEngine($this->getPath()."templates/gallerycommentemail.tpl");
			$template->setVariable($values);
			$template->setVariable('galleryName', $galleryDetail['name']);
			$template->setVariable('href_detail', $url->getUrl());
			$template->setVariable('siteTitle', $this->director->tree->getTreeName());
			$template->setVariable('domain', $request->getDomain());
			$template->setVariable('protocol', $request->getProtocol());
			$template->setVariable('ip', $ip);
			$template->setVariable('host', gethostbyaddr($ip));
			$template->setVariable('client', $request->getValue('HTTP_USER_AGENT', Request::SERVER));

			$this->director->systemUser->notify($galleryDetail['tree_id'], 'Comment added at '.$request->getDomain(), $template->fetch());

		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('commentError',  $e->getMessage(), false);
			$template->setVariable('cmtValues',  $values, false);
		}
		
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
		$view->setType(Gallery::VIEW_COMMENT_EDIT);

		if(!$request->exists('id')) throw new Exception('Comment id is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);
		$key = array('id' => $id);

		if($retrieveFields)
		{
			$fields = $this->getDetail($key);
			$fields['date'] = $fields['date'] ? strftime('%Y-%m-%d %T', $fields['date']) : '';
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		$this->setFields($fields);


		$template->setVariable($fields, NULL, false);

		$this->handleTreeSettings($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Gallery id is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->update($key, $values);

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleTreeOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			// reset date values
			$date = $this->sqlParser->getFieldByName('date');
			$this->sqlParser->setFieldValue('date', strftime('%Y-%m-%d %T', strtotime($date->getValue())));

			$this->handleTreeEditGet(false);
		}

	} 
//}}}

/*------- tree delete request {{{ -------*/
	/**
	 * handle tree delete
	*/
	private function handleTreeDeleteGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(Gallery::VIEW_COMMENT_DELETE);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		if(!$request->exists('id')) throw new Exception('Post ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		$template->setVariable($this->getDetail(array('id' => $id)), NULL, false);
		$this->handleTreeSettings($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeDeletePost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Post ontbreekt.');
			$id = $request->getValue('id');

			$key = array('id' => $id);
			$this->delete($key);

			viewManager::getInstance()->setType(Gallery::VIEW_COMMENT_OVERVIEW);
			$this->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleTreeDeleteGet();
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

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
