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
class Login extends Plugin implements GuiProvider
{
	const TYPE_LOGINOUT = 1;
	const TYPE_LOGIN = 2;
	const TYPE_LOGOUT = 3;

	protected	$types = array(self::TYPE_LOGINOUT => 'Login / Logout',
														self::TYPE_LOGIN => 'Login',
														self::TYPE_LOGOUT => 'Logout');

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

		$this->template = array();
		$this->templateFile = "login.tpl";

		$this->configFile = strtolower(__CLASS__.".ini");

		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('login', 'a');
		$this->sqlParser->addField(new SqlField('a', 'login_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'login_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'login_ref_tree_id', 'ref_tree_id', 'Referer Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'login_cap_username', 'cap_username', 'Caption user name', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'login_cap_password', 'cap_password', 'Caption password', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'login_cap_submit', 'cap_submit', 'Caption submit button', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'login_field_width', 'field_width', 'Input field width', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'login_usr_id', 'usr_id', 'User', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'login_own_id', 'own_id', 'Owner', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'login_create', 'createdate', 'Creation date', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'login_ts', 'ts', 'Modification date', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
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
			case 'cap_username' : return 'Username'; break;
			case 'cap_password' : return 'Password'; break;
			case 'cap_submit' : return 'Submit'; break;
			case 'field_width' : return 30; break;
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
		$view = ViewManager::getInstance();

		if($this->director->isAdminSection() && $view->isType(ViewManager::OVERVIEW)) 
			$view->setType(ViewManager::ADMIN_OVERVIEW);

		switch($view->getType())
		{
			case ViewManager::CONF_OVERVIEW : 
			case ViewManager::CONF_NEW : 
			case ViewManager::CONF_DELETE : 
			case ViewManager::CONF_EDIT : $this->handleConfEditPost(); break;
			case ViewManager::TREE_OVERVIEW : 
			case ViewManager::TREE_NEW :  
			case ViewManager::TREE_EDIT : $this->handleTreeEditPost(); break;
			default : $this->handlePost();
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

		if($view->isType(ViewManager::OVERVIEW) && $this->director->isAdminSection()) 
			$view->setType(ViewManager::ADMIN_OVERVIEW);

		switch($view->getType())
		{
			case ViewManager::CONF_OVERVIEW : 
			case ViewManager::CONF_NEW : 
			case ViewManager::CONF_DELETE : 
			case ViewManager::CONF_EDIT : $this->handleConfEditGet(); break;
			case ViewManager::TREE_OVERVIEW : 
			case ViewManager::TREE_NEW : 
			case ViewManager::TREE_EDIT : $this->handleTreeEditGet(); break;
			default : $this->handleGet();
		}

	}
//}}}

/*------- overview request {{{ -------*/
	/**
	 * handle overview request
	*/
	private function handleGet()
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();

		// disable caching of current page
		Cache::disableCache();

		$tree = $this->director->tree;

		$taglist = $this->getTagList();
		//if(!$taglist) return;
		//FIXME this is used by plugins that are connected on the fly (like the login plugin)  ( check what means the following statement: either way it has a tree_id bug)
		//ORIGINAL COMMENT: FIXME check if this is used (i dont think so, but you never know) (either way it has a tree_id bug)
		if(!$taglist)
		{
			$taglist[] = array('tree_id' => $tree->currentIdExists(), 'tag' => $this->director->theme->getConfig()->main_tag);
		}

		$autentication = Authentication::getInstance();
		$login = $autentication->isLogin();

		foreach($taglist as $item)
		{
			// clear login state
			if($login && ($item['plugin_type'] == Login::TYPE_LOGINOUT || $item['plugin_type'] == Login::TYPE_LOGOUT))
			{
				$autentication->logout();
				header("Location: /");
				exit;
			}

			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

			$settings = $this->exists($key) ? $this->getDetail($key) : $this->getFields(SqlParser::MOD_INSERT);
			$template->setVariable('settings',  $settings);
			$template->setVariable('tag',  $item['tag']);
			$template->setVariable('referer',  $request->getUrl());

			$this->template[$item['tag']] = $template;
		}
	} 
	
	private function handlePost()
	{
		$request = Request::getInstance();

		try 
		{
			$autentication = Authentication::getInstance();
			$autentication->login($request->getValue('username'), $request->getValue('password'));

			if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');
			$tree = $this->director->tree;

			$tag = $request->getValue('tag');
			$tree_id = $tree->getCurrentId();
			$key = array('tree_id' => $tree_id, 'tag' => $tag);

			$detail = $this->exists($key) ? $this->getDetail($key) : $this->getFields(SqlParser::MOD_INSERT);
			$referer = $detail['ref_tree_id'] ? $tree->getPath($detail['ref_tree_id'], '/', Tree::TREE_ORIGINAL) : ($request->exists('referer') ? $request->getValue('referer') : '/');

			header("Location: $referer");
			exit;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('formError',  $e->getMessage(), false);
			$this->handleHttpGetRequest();
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
		$site 			= new SystemSite();
		$tree = $site->getTree();

		$treelist = $tree->getList($tree_id);
		foreach($treelist as &$item)
		{
			$item['name'] = $tree->toString($item['id'], '/', 'name');
		}

		$template->setVariable('cbo_tree_id', Utils::getHtmlCombo($treelist, $fields['ref_tree_id']));


		$this->setFields($fields);
		$template->setVariable($this->getFields(SqlParser::MOD_UPDATE), NULL, false);
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

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->getReferer()->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleTreeEditGet(false);
		}
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
		$template->setVariable('pageTitle',  $this->description, false);
		
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleConfEditPost()
	{
		viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
		$this->referer->handleHttpGetRequest();
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

		// parse stylesheet to set variables
		$stylesheet_src = $this->getHtdocsPath(true)."css/style.css.in";
		$theme->addStylesheet($theme->fetchFile($stylesheet_src));


		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
