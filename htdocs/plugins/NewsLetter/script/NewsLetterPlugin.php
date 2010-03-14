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
class NewsLetterPlugin extends Observer
{
	const TYPE_TEXT = 1;
	const TYPE_PLUGIN = 2;
	const TYPE_CODE = 3;
	const TYPE_CODE_HEADER = 4;
	const TYPE_CODE_FOOTER = 5;

	const PLUGIN_TAG = 'tpl_plugin';

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
	private $reference;

	/**
	 * pointer to global plugin plugin
	 * @var NewsLetter
	 */
	private $plugin;

	static public $types 	= array(self::TYPE_TEXT 	=> 'Local Text',
																self::TYPE_PLUGIN	=> 'External Plugin',
																self::TYPE_CODE	=> 'PHP source code',
																self::TYPE_CODE_HEADER	=> 'Message header (PHP source code)',
																self::TYPE_CODE_FOOTER	=> 'Unsubscribe message (PHP source code)');
	static private $typelist;

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
		$this->reference = array();
		
		$this->template = array();
		$this->templateFile = "newsletterplugin.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('newsletter_plugin', 'a');
		$this->sqlParser->addField(new SqlField('a', 'plug_nl_id', 'nl_id', 'NewsLetter id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('b', 'nl_name', 'name', 'Name', SqlParser::getTypeSelect()|SqlParser::NAME, SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'plug_tag', 'nl_tag', 'Tag name', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('b', 'nl_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER, false));
		$this->sqlParser->addField(new SqlField('b', 'nl_tag', 'tag', 'Tag', SqlParser::getTypeSelect(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('b', 'nl_active', 'nl_active', 'Actieve status', SqlParser::getTypeSelect(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'plug_type', 'type', 'Type of tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'plug_text', 'text', 'Content', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'plug_plugin_id', 'plugin_id', 'Plugin identifier', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'plug_plugin_type', 'plugin_type', 'Plugin type', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'plug_plugin_keys', 'plugin_keys', 'Plugin keys', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'plug_usr_id', 'usr_id', 'User', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'plug_own_id', 'own_id', 'Owner', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'plug_create', 'createdate', 'Create date', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'plug_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->addFrom("inner join newsletter as b on b.nl_id = a.plug_nl_id");

		$this->orderStatement = array('order by a.plug_tag %s');

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

	static public function getTypeList()
	{
		if(isset(self::$typelist)) return self::$typelist;

		self::$typelist = array();
		foreach(self::$types as $key=>$value)
		{
			self::$typelist[$key] = array('id' => $key, 'name' => $value);
		}
		return self::$typelist;
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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.plug_id', $value, '<>')); break;
				case 'nl_active' : $SqlParser->addCriteria(new SqlCriteria('b.nl_active', $value)); break;
				case 'search' : 
					$search = new SqlCriteria('a.plug_name', "%$value%", 'like'); 
					$search->addCriteria(new SqlCriteria('a.plug_text', "%$value%", 'like'), SqlCriteria::REL_OR); 
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
		$view = ViewManager::getInstance();

		switch($view->getType())
		{
			case NewsLetter::VIEW_PLUGIN_OVERVIEW : $this->handleOverview(); break;
			case NewsLetter::VIEW_PLUGIN_CONFIG : $this->handleConfigGet(); break;
			case NewsLetter::VIEW_PLUGIN_EDIT : $this->handleEditGet(); break;
			case NewsLetter::VIEW_PLUGIN_DELETE : $this->handleDeleteGet(); break;
			case NewsLetter::VIEW_PLUGIN_MOVE : $this->handleMoveGet(); break;
		}
	}

	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$view = ViewManager::getInstance();

		switch($view->getType())
		{
			case NewsLetter::VIEW_PLUGIN_OVERVIEW : $this->handleOverview(); break;
			case NewsLetter::VIEW_PLUGIN_CONFIG : $this->handleConfigPost(); break;
			case NewsLetter::VIEW_PLUGIN_EDIT : $this->handleEditPost(); break;
			case NewsLetter::VIEW_PLUGIN_DELETE : $this->handleDeletePost(); break;
			case NewsLetter::VIEW_PLUGIN_MOVE : $this->handleMovePost(); break;
		}

	} 

//}}}

/*-------  overview request {{{ -------*/
	/**
	 * handle  overview
	*/
	private function handleOverview()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_PLUGIN_OVERVIEW);

		if(!$request->exists('nl_id')) throw new Exception('Newsletter is missing.');
		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$nl_id = intval($request->getValue('nl_id'));
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('nl_id' => $nl_id);

		//$objOverview = $this->plugin->getObject(NewsLetter::TYPE_DEFAULT);
		//$detail = $objOverview->getDetail($key);

		//$theme = $this->director->themeManager->getThemeFromId(array('id' => $detail['theme_id']));
		//if(!$theme) throw new Exception("Theme does not exists. Select theme in Config section first.");
		//$template->setVariable('theme',  $theme->getDescription());

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('nl_id', $nl_id);
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// create urls
		$url_config = clone $url;
		$url_config->setParameter('id', $nl_id);
		$url_config->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);
		$template->setVariable('href_config', $url_config->getUrl(true));

		$url_att = clone $url;
		$url_att->setParameter($view->getUrlId(), NewsLetter::VIEW_FILE_OVERVIEW);
		$template->setVariable('href_att', $url_att->getUrl(true));

		$url_preview = clone $url;
		$url_preview->setParameter($view->getUrlId(), NewsLetter::VIEW_PREVIEW);
		$template->setVariable('href_preview', $url_preview->getUrl(true));

		$url_send = clone $url;
		$url_send->setParameter($view->getUrlId(), NewsLetter::VIEW_SEND);
		$template->setVariable('href_send', $url_send->getUrl(true));

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), NewsLetter::VIEW_PLUGIN_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), NewsLetter::VIEW_PLUGIN_DELETE);

		$url_conf = clone $url;
		$url_conf->setParameter($view->getUrlId(), NewsLetter::VIEW_PLUGIN_CONFIG);

		$url_move = clone $url;
		$url_move->setParameter($view->getUrlId(), NewsLetter::VIEW_PLUGIN_MOVE);

		$url_tag_edit = clone $url;
		$url_tag_edit->setParameter($view->getUrlId(), NewsLetter::VIEW_TAG_EDIT);

		$url_tag_del = clone $url;
		$url_tag_del->setParameter($view->getUrlId(), NewsLetter::VIEW_TAG_DELETE);

		// get theme tag list and user defined tag list
		$newsLetterTag = $this->plugin->getObject(NewsLetter::TYPE_TAG);
		$alltags= $newsLetterTag->getTagList($key);
		$taglist = array();
		$splitTaglist = array();

		// retrieve linked used tag list with the related plugins
		$list = $this->getList($key);
		$pluginlist = array();
		foreach($list['data'] as $item)
		{
			$pluginlist[$item['nl_tag']] = $item;
		}

		// process active tags
		foreach($alltags as $key=>$item)
		{
			$url_conf->setParameter('nl_tag', $item['id']);
			$url_move->setParameter('nl_tag', $item['id']);
			$url_edit->setParameter('nl_tag', $item['id']);
			$url_del->setParameter('nl_tag', $item['id']);
			$url_tag_edit->setParameter('parent_tag', $item['id']);
			$url_tag_del->setParameter('parent_tag', $item['id']);

			$pluginExists = array_key_exists($item['id'], $pluginlist);


			if($pluginExists)
			{
				// plugin is linked to tag
				if($pluginlist[$item['id']]['type'] == self::TYPE_PLUGIN)
				{
					$objPlugin = $this->director->pluginManager->getPluginFromId(array('id' => $pluginlist[$item['id']]['plugin_id']));
					$item['plugin_name'] = $objPlugin->getDescription();
				}
				else
					$item['plugin_name'] = self::$types[$pluginlist[$item['id']]['type']];


				$item['href_del'] = $url_del->getUrl(true);
				$item['href_edit'] = $url_edit->getUrl(true);
				$item['href_move'] = $url_move->getUrl(true);

				// clear existing plugin from list
				unset($pluginlist[$item['id']]);
			}
			else
			{
				$item['href_conf'] = $url_conf->getUrl(true);
				$item['plugin_name'] = '';
			}

			// handle split tags. Move parent tags to different list
			$item['href_tag'] = $url_tag_edit->getUrl(true);
			$item['href_tag_del'] = $url_tag_del->getUrl(true);
			if($item['type'] == NewsLetterTag::TYPE_PARENT)
				$splitTaglist[] = $item;
			else
				$taglist[] = $item;

		}
		$template->setVariable('taglist',  $taglist);
		$template->setVariable('splitTaglist',  $splitTaglist);

		// process inactive tags
		foreach($pluginlist as $tag=>&$pluginItem)
		{
			$url_move->setParameter('nl_tag', $tag);
			$url_edit->setParameter('nl_tag', $tag);
			$url_del->setParameter('nl_tag', $tag);

			if($pluginlist[$item['id']]['type'] == self::TYPE_PLUGIN)
			{
				// plugin is linked to tag
				$objPlugin = $this->director->pluginManager->getPluginFromId(array('id' => $pluginItem['plugin_id']));
				$pluginItem['plugin_name'] = $objPlugin->getDescription();
			}
			else
				$pluginItem['plugin_name'] = self::$types[$pluginItem['type']];

			$pluginItem['name'] = $tag;

			$pluginItem['href_move'] = $url_move->getUrl(true);
			$pluginItem['href_edit'] = $url_edit->getUrl(true);
			$pluginItem['href_del'] = $url_del->getUrl(true);
		}
		$template->setVariable('hiddentaglist',  $pluginlist);

		// add breadcrumb item
		$objOverview = $this->plugin->getObject(NewsLetter::TYPE_DEFAULT);
		$this->director->theme->handleAdminLinks($template, $objOverview->getName($key));

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}
//}}}

/*-------  settings {{{ -------*/
	private function handleSettings($template)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();

		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');
		$key = $this->getKey();

		$template->setVariable('tree_id',  $tree_id);
		$template->setVariable('tag',  $tag);
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

/*-------  config request {{{ -------*/
	/**
	 * handle  config
	*/
	private function handleConfigGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_PLUGIN_CONFIG);

		$key = $this->getKey();

		if($retrieveFields)
		{
			$fields = $this->exists($key) ? $this->getDetail($key) : $this->getFields(SqlParser::MOD_INSERT);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		$this->setFields($fields);
		$template->setVariable($fields);
		$template->setVariable('cbo_type', Utils::getHtmlCombo(self::getTypeList(), $fields['type']));

		// create plugin list
		$pluginManager = $this->director->pluginManager;

		//get default or current plugin id
		$plugins = array();
		$pluginManager->loadPlugins();
		$objPlugins = $this->director->getObjectsImplementing('PluginProvider');
		foreach($objPlugins as $obj)
		{
			$plugins[] = array('id' => $obj->getId(), 'name' => $obj->getDescription());
		}
		//$pluginResult = $pluginManager->getList(array('active' => 1));
		//foreach($pluginResult['data'] as $item)
		//{
			//$plugins[] = $item;
		//}

		if($fields['plugin_id']) 
		{
			$pluginId = $fields['plugin_id']; 
		}
		else
		{
			$tmp = current($plugins);
			$pluginId = $tmp['id'];
		}

		$template->setVariable('cbo_plugin', Utils::getHtmlCombo($plugins, $pluginId));

		// create type list
		$plugin = $pluginManager->getPluginFromId(array('id' => $pluginId));
		$template->setVariable('cbo_plugin_type', Utils::getHtmlCombo($plugin->getTypeList(), $fields['plugin_type']));

		// retrieve theme to see if there is a nice tag description
		$objOverview = $this->plugin->getObject(NewsLetter::TYPE_DEFAULT);
		$detail = $objOverview->getDetail($key);

		$theme = $this->director->themeManager->getThemeFromId(array('id' => $detail['theme_id']));
		if(!$theme) throw new Exception("Theme does not exists. Select theme in Config section first.");
		$taglist = $theme->getTagList();

		$nl_tag = $key['nl_tag'];
		$template->setVariable('tag_description', array_key_exists($nl_tag, $taglist) ? $taglist[$nl_tag]['name'] : $nl_tag);


		$this->handleSettings($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleConfigPost()
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
			$this->handleOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			$this->handleConfigGet(false);
		}

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
		$view->setType(NewsLetter::VIEW_PLUGIN_EDIT);

		$key = $this->getKey();
		if(!$this->exists($key)) throw new Exception(__CLASS__."::".__FUNCTION__." Plugin does not exists. Insert Plugin first.");

		$detail = $this->getDetail($key);

		if($retrieveFields)
			$fields = $detail;
		else
			$fields = $this->getFields(SqlParser::MOD_UPDATE);


		switch($detail['type'])
		{
			case self::TYPE_CODE_HEADER : if(!$fields['text']) $fields['text'] = $this->getHeader();
			case self::TYPE_CODE_FOOTER : if(!$fields['text']) $fields['text'] = $this->getFooter();
			case self::TYPE_CODE : $this->handleSourceCodeEditor($template, $detail); break;
			case self::TYPE_TEXT : $template->setVariable('fckBox',  $this->getEditor($fields['text']), false); break;
			case self::TYPE_PLUGIN : $this->handlePluginGet($template, $detail); break;
		}

		$this->setFields($fields);
		$template->setVariable($fields);

		$this->handleSettings($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function getHeader()
	{
		return 'Dear <?="{$user[\'gender_description\']} {$user[\'name\']}";?>,';
	}

	private function getFooter()
	{
		return 'You can unsubscribe <a href="<?=$href_unsubscribe;?>">here</a>';
	}

	private function handleEditPost()
	{
		try 
		{
			$key = $this->getKey();
			if(!$this->exists($key)) throw new Exception(__CLASS__."::".__FUNCTION__." Plugin does not exists. Insert Plugin first.");

			$detail = $this->getDetail($key);
			
			switch($detail['type'])
			{
				case self::TYPE_CODE : 
				case self::TYPE_CODE_HEADER : 
				case self::TYPE_CODE_FOOTER : 
				case self::TYPE_TEXT : $this->handleTextPost($key); break;
				case self::TYPE_PLUGIN : $this->handlePluginPost($key, $detail); break;
			}

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleEditGet(false);
		}

	} 

	private function getEditor($text)
	{
		// include fck editor
		require_once(DIF_WEB_ROOT."fckeditor/fckeditor.php");
		$oFCKeditor = new FCKeditor('text');
		$oFCKeditor->BasePath = DIF_VIRTUAL_WEB_ROOT.'fckeditor/';
		$oFCKeditor->Value = $text;
		$oFCKeditor->Width  = '700' ;
		$oFCKeditor->Height = '500';
		return $oFCKeditor->CreateHtml();
	}

	private function handleSourceCodeEditor($template, $detail)
	{
		$objOverview = $this->plugin->getObject(NewsLetter::TYPE_DEFAULT);
		$overview = $objOverview->getDetail(array('id' => $detail['nl_id']));
		$theme = $this->director->themeManager->getThemeFromId(array('id' => $overview['theme_id']));

		//$objUser = $this->plugin->getObject(NewsLetter::TYPE_USER);
		//$userFields = array_keys($objUser->getFields(SqlParser::MOD_INSERT));
		$userFields = array('user' => array('name' => '', 'email' => '', 'gender_description' => ''));

		$fileVars = array_merge($userFields, $theme->getFileVars());
		
		$template->setVariable('filevars',  $fileVars);

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
					});
');

	}

	private function handleTextPost($key)
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($key, false);
		$sqlParser->setFieldValue('text', $values['text']);

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

//}}}


/*-------  handle plugin request {{{ -------*/
	/**
	 * handle plugin request
	*/
	private function handlePluginGet($template, $detail)
	{
		$request = Request::getInstance();
		$pluginManager = $this->director->pluginManager;

		$select_keys = $request->exists(PluginProvider::KEY_SELECT) ? array_keys($request->getValue(PluginProvider::KEY_SELECT)) : array();
		$current_keys = $detail['plugin_keys'] ? unserialize($detail['plugin_keys']) : array();
		$keys = array_merge($select_keys, $current_keys);
		$params = array('keys' => $keys);

		//get default or current plugin id
		$parameters = array('pluginType' => $detail['plugin_type'],
												'requestType'	=> PluginProvider::TYPE_SELECT,
												'tag'	=> self::PLUGIN_TAG,
												'parameters' => $params);

		$key = array('id' => $detail['plugin_id']);
		$objPlugin = $pluginManager->getPluginFromId($key);
		$this->director->callObjectsImplementing('PluginProvider', 'handlePluginRequest', $parameters);
	}

	private function handlePluginPost($key, $detail)
	{
		$request = Request::getInstance();

		$current_keys = $detail['plugin_keys'] ? unserialize($detail['plugin_keys']) : array();
		$range_keys = $request->exists(PluginProvider::KEY_RANGE) ? $request->getValue(PluginProvider::KEY_RANGE) : $current_keys;
		$select_keys = $request->exists(PluginProvider::KEY_SELECT) ? array_keys($request->getValue(PluginProvider::KEY_SELECT)) : array();

		$include_keys = array_diff($current_keys, $range_keys);
		$insert_keys = array_merge($include_keys, $select_keys);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($key, false);
		$sqlParser->setFieldValue('plugin_keys', serialize($insert_keys));

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

//}}}

/*-------  delete request {{{ -------*/
	/**
	 * handle  delete
	*/
	private function handleDeleteGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_PLUGIN_DELETE);

		$key = $this->getKey();
		if(!$this->exists($key)) throw new Exception(__CLASS__."::".__FUNCTION__." Plugin does not exists. Insert Plugin first.");

		$detail = $this->getDetail($key);
		$template->setVariable($detail);
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
			$this->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleDeleteGet();
		}
	} 
//}}}

/*------- Move request {{{ -------*/
	/**
	 * handle Theme request
	*/
	private function handleMoveGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(NewsLetter::VIEW_PLUGIN_MOVE);

		$objTag = $this->plugin->getObject(NewsLetter::TYPE_TAG);

		$key = $this->getKey();
		$fields = $this->getDetail($key);

		// plugin is linked to tag
		if($fields['type'] == self::TYPE_PLUGIN)
		{
			$objPlugin = $this->director->pluginManager->getPluginFromId(array('id' => $fields['plugin_id']));
			$fields['plugin_name'] = $objPlugin->getDescription();
		}
		else
			$fields['plugin_name'] = self::$types[$fields['type']];


		$template->setVariable($fields);
     
		// get all tags
		$key = array('nl_id' => $key['nl_id']);
		$taglist = array();
		$alltags = $objTag->getTagList($key);
		foreach($alltags as $item)
		{
			if($item['type'] == NewsLetterTag::TYPE_PARENT || $item['id'] == $fields['nl_tag']) continue;
			$taglist[] = $item;
		}

		$template->setVariable('cbo_tag', Utils::getHtmlCombo($taglist, $request->getValue('newtag')));

		$this->handleSettings($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleMovePost()
	{
		$request = Request::getInstance();

		try 
		{
			$key = $this->getKey();
			if(!$request->exists('newtag')) throw new Exception("Destination tag cannot be empty");

			$sqlParser = clone $this->sqlParser;
			$sqlParser->parseCriteria($key, false);
			$this->parseCriteria($sqlParser, $key);
			$sqlParser->setFieldValue('nl_tag', $request->getValue('newtag'));

			$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

			$db = $this->getDb();
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			viewManager::getInstance()->setType(NewsLetter::VIEW_PLUGIN_OVERVIEW);
			$this->handleOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleMoveGet();
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
		$this->director->callObjectsImplementing('PluginProvider', 'renderPluginRequest', array($theme));

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
