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
class NewsLetterTag extends Observer
{
	const TYPE_THEME = 1;
	const TYPE_PARENT = 2;
	const TYPE_CHILD = 3;

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
	 * @var NewsLetter
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
		$this->templateFile = "newslettertag.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('newsletter_tag', 'a');
		$this->sqlParser->addField(new SqlField('b', 'nl_name', 'name', 'Newsletter title', SqlParser::getTypeSelect()|SqlParser::NAME, SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'tag_nl_id', 'nl_id', 'NewsLetter id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'tag_parent_tag', 'parent_tag', 'Parent tag name', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('b', 'nl_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER, false));
		$this->sqlParser->addField(new SqlField('b', 'nl_tag', 'tag', 'Tag', SqlParser::getTypeSelect(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'tag_tags', 'tags', 'Replacement tags', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'tag_template', 'template', 'Template', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'tag_stylesheet', 'stylesheet', 'Stylesheet', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'tag_usr_id', 'usr_id', 'User', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'tag_own_id', 'own_id', 'Owner', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'tag_create', 'createdate', 'Create date', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'tag_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->addFrom("inner join newsletter as b on b.nl_id = a.tag_nl_id");

		//$this->orderStatement = array('order by a.tag_tag %s');

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

	private function getHtDocsPath($absolute=false)
	{
		return $this->plugin->getHtDocsPath($absolute);
	}

	public function getTagList($key)
	{
		if(!array_key_exists('nl_id', $key)) throw new Exception(__CLASS__."::".__FUNCTION__." nl_id identifier is missing.");

		$objOverview = $this->plugin->getObject(NewsLetter::TYPE_DEFAULT);
		$newsletter = $objOverview->getDetail($key);
		$usertaglist = $this->getList($key);

		$theme = $this->director->themeManager->getThemeFromId(array('id' => $newsletter['theme_id']));
		if(!$theme) throw new Exception("Theme does not exists. Select theme in Config section first.");

		$taglist = $theme->getTagList();
		foreach($taglist as &$tag)
		{
			$tag['type'] = self::TYPE_THEME;
		}

		foreach($usertaglist['data'] as $item)
		{
			// create array of new tags and add tags to current list
			$newtags = preg_split("/\s+/", $item['tags'], -1, PREG_SPLIT_NO_EMPTY);
			foreach($newtags as $newtag)
			{
				$taglist[$newtag] = array('id' => $newtag, 'name' => $newtag, 'type' => self::TYPE_CHILD);
			}
			$taglist[$item['parent_tag']]['type'] = self::TYPE_PARENT;
			$taglist[$item['parent_tag']]['child_tags'] = $newtags;
		}

		return $taglist;
	}

	public function getReplacementTemplates($key, $theme)
	{
		// process user defined templates and their stylesheets
		$retval = array();

		$list = $this->getList($key);
		foreach($list['data'] as $item)
		{
			$substitute = new TemplateEngine($item['template'], false);
			//$template->setVariable($item['parent_tag'], $substitute, false);
			$retval[$item['parent_tag']] = $substitute;

			// skip the rest if no stylesheet is set
			if(!$item['stylesheet']) continue;

			// save stylesheet to a file and then inlcude it in the theme headers
			$theme->addStylesheet($theme->fetchFile($item['stylesheet']));
		}
		return $retval;
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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.tag_id', $value, '<>')); break;
				case 'nl_active' : $SqlParser->addCriteria(new SqlCriteria('b.nl_active', $value)); break;
				case 'search' : 
					$search = new SqlCriteria('a.tag_name', "%$value%", 'like'); 
					$search->addCriteria(new SqlCriteria('a.tag_text', "%$value%", 'like'), SqlCriteria::REL_OR); 
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$fields['usr_id'] = $userId['id'];
		$fields['tags'] = trim($fields['tags'], "\r\n\t ");

		if(array_key_exists('stylesheet', $fields)) $fields['stylesheet'] = trim($fields['stylesheet']);
		
		return $fields;
	}

	protected function handlePostGetList($values)
	{
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$this->sqlParser->setFieldValue('own_id', $userId['id']);

		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));
	}
	//}}}

/*----- handle http requests {{{ -------*/
	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case NewsLetter::VIEW_TAG_EDIT : $this->handleEditGet(); break;
			case NewsLetter::VIEW_TAG_DELETE : $this->handleDeleteGet(); break;
		}
	}

	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case NewsLetter::VIEW_TAG_EDIT : $this->handleEditPost(); break;
			case NewsLetter::VIEW_TAG_DELETE : $this->handleDeletePost(); break;
		}

	} 

//}}}

/*-------  settings {{{ -------*/
	private function handleSettings($template)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();

		$key = $this->getKey();
		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);
		$template->setVariable($key);

		// create href back url
		$url = new Url(true); 
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);
		$url->setParameter('nl_id', $key['nl_id']);
		$url->setParameter($view->getUrlId(), NewsLetter::VIEW_PLUGIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		// add breadcrumb item
		$objOverview = $this->plugin->getObject(NewsLetter::TYPE_DEFAULT);
		$key = array('id' => $key['nl_id']);
		$breadcrumb = array('name' => $objOverview->getName($key), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		$this->director->theme->handleAdminLinks($template);
	}
//}}}

/*-------  edit request {{{ -------*/
	/**
	 * handle  edit
	*/
	private function handleEditGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_TAG_EDIT);

		$key = $this->getKey();
		$tagExists = $this->exists($key);

		if($retrieveFields)
		{
			$fields = $tagExists ? $this->getDetail($key) : $this->getFields(SqlParser::MOD_INSERT);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		$this->setFields($fields);
		$template->setVariable($fields);

		$objOverview = $this->plugin->getObject(NewsLetter::TYPE_DEFAULT);
		$overview = $objOverview->getDetail(array('id' => $key['nl_id']));
		$theme = $this->director->themeManager->getThemeFromId(array('id' => $overview['theme_id']));
		$template->setVariable('filevars',  $theme->getFileVars());

		$theme = $this->director->theme;
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/editarea/edit_area/edit_area_full.js"></script>');
		$theme->addJavascript('
editAreaLoader.init({ 	id: "area1", 
							start_highlight: true, 
							allow_toggle: true, 
							allow_resize: true,
							language: "en", 
							syntax: "php", 
							toolbar: "load",
							toolbar: "load, |, search, go_to_line, |, undo, redo, |, select_font, |, change_smooth_selection, highlight, reset_highlight, |, help",
							syntax_selection_allow: "css,html,js,php", 
							load_callback: "createTags"
					});

editAreaLoader.init({ 	id: "area2", 
							start_highlight: true, 
							allow_toggle: true, 
							allow_resize: true,
							language: "en", 
							syntax: "css", 
							syntax_selection_allow: "css,html,js,php" 
					});
');

		$this->handleSettings($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			$key = $this->getKey();

			if(!$this->exists($key))
				$key = $this->insert($values);
			else
				$this->update($key, $values);

			viewManager::getInstance()->setType(NewsLetter::VIEW_PLUGIN_OVERVIEW);
			$plugin = $this->plugin->getObject(NewsLetter::TYPE_PLUGIN);
			$plugin->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			$this->handleEditGet(false);
		}

	} 
//}}}

/*-------  delete request {{{ -------*/
	/**
	 * handle  delete
	*/
	private function handleDeleteGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_TAG_DELETE);

		$key = $this->getKey();

		$template->setVariable('formatName',$this->getName($key));
		$this->handleSettings($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleDeletePost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			$key = $this->getKey();
			$this->delete($key);

			viewManager::getInstance()->setType(NewsLetter::VIEW_PLUGIN_OVERVIEW);
			$plugin = $this->plugin->getObject(NewsLetter::TYPE_PLUGIN);
			$plugin->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleDeleteGet();
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

		$theme->addJavascript(file_get_contents($this->getHtdocsPath(true).'sync.js'));

		$template = $theme->getTemplate();
		$template->setVariable($view->getUrlId(),  $view->getName(), false);

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
