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

require_once('phpmailer/class.phpmailer.php');
require_once('LoginRequest.php');

/**
 * Main configuration 
 * @package Common
 */
class LoginMailer extends Plugin implements GuiProvider
{
	const TYPE_DEFAULT = 1;

	const VIEW_ACTIVATE = 'act';

	protected	$types = array(self::TYPE_DEFAULT => 'Standaard');

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
		$this->templateFile = "loginmailer.tpl";
		$this->templateEmail = "loginmailer_email.tpl";

		//$this->configFile = strtolower(__CLASS__.".ini");

		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('login_mail', 'a');
		$this->sqlParser->addField(new SqlField('a', 'login_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'login_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'login_ref_tree_id', 'ref_tree_id', 'Reference Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'login_fin_tree_id', 'fin_tree_id', 'Reference Node success', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'login_intro', 'intro', 'Introductie', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'login_subject', 'subject', 'Email subject', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'login_content', 'content', 'Email content', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'login_cap_submit', 'submit', 'Submit button', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'login_cap_fin_submit', 'fin_submit', 'Submit button finish', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'login_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'login_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'login_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'login_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_ACTIVATE, 'Activate');
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
			case 'submit' : return 'Submit'; break;
			case 'fin_submit' : return 'Submit'; break;
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
			case self::VIEW_ACTIVATE : $this->handleActivatePost(); break;
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
			case self::VIEW_ACTIVATE : $this->handleActivateGet(); break;
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

		$taglist = $this->getTagList();
		if(!$taglist) return;

		foreach($taglist as $item)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

			$settingsKey = array('tag' => $item['tag'], 'tree_id' => $item['tree_id']);
			$settings = $this->getDetail($settingsKey);
			$template->setVariable('settings',  $settings);
			$template->setVariable('tag',  $item['tag']);

			$this->template[$item['tag']] = $template;
		}
	} 
	
	private function handlePost()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		try 
		{
			$autentication = Authentication::getInstance();
			$usermail = $request->getValue('email');
			if(!$usermail) throw new Exception("Email adres ontbreekt.");

			if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');
			$tree = $this->director->tree;

			$tag = $request->getValue('tag');
			$tree_id = $tree->getCurrentId();
			$key = array('tree_id' => $tree_id, 'tag' => $tag);

			$detail = $this->getDetail($key);
			if(!$detail) 
			{
				$this->log->info("Request login information for unknown user at ".$request->getValue('REMOTE_ADDR', Request::SERVER));
				throw new Exception("Error creating request");
			}

			// get userinfo
			$systemUser = new SystemUser();
			$users = $systemUser->getList(array('email' => $usermail));
			foreach($users['data'] as $user)
			{
				$detail = array_merge($detail, $user);

				$loginKey = md5(time().$user['username']);
				$requestValues = 	array('request_key'=> $loginKey,
																'usr_id' => $user['id']);

				// register request
				$loginRequest = new LoginRequest();
				$loginRequest->insert($requestValues);

				$url = new Url(true);
				$url->setParameter($view->getUrlId(), self::VIEW_ACTIVATE);
				$url->setParameter('key', $loginKey);

				// mail userinfo (only if user is present)
				if($user) $this->sendMail($user['email'], $this->director->getConfig()->email_address, $detail['subject'], $detail, $request->getProtocol().$request->getDomain().$url->getUrl());

			}
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

		$treelist = $tree->getList();
		foreach($treelist as &$item)
		{
			$item['name'] = $tree->toString($item['id'], '/', 'name');
		}

		$template->setVariable('cbo_tree_id', Utils::getHtmlCombo($treelist, $fields['ref_tree_id']));
		$template->setVariable('cbo_fin_tree_id', Utils::getHtmlCombo($treelist, $fields['fin_tree_id']));

		$this->setFields($fields);
		$template->setVariable($this->getFields(SqlParser::MOD_UPDATE), NULL, false);
		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		$this->handleEditor();

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

/*------- handle editor {{{ -------*/
	/**
	 * handle tree edit
	*/
	private function handleEditor()
	{
		$theme = $this->director->theme;
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/editarea/edit_area/edit_area_full.js"></script>');
		$theme->addJavascript('
editAreaLoader.init({ 	id: "area1", 
							start_highlight: true, 
							allow_toggle: true, 
							allow_resize: true,
							language: "en", 
							syntax: "css", 
							syntax_selection_allow: "css,html,js,php" 
					});
');
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

/*------- activate request {{{ -------*/
	/**
	 * handle activate request
	*/
	private function handleActivateGet()
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();
		$systemUser = new SystemUser();
		$loginRequest = new LoginRequest();
		$view->setType(self::VIEW_ACTIVATE);

		if(!$request->exists('key')) throw new Exception('Key is missing.');
		$key = $request->getValue('key');
		if(!$key) throw new Exception('Key is missing.');

		// delete expired requests
		$loginRequest->delete(array('expired' => true));

		// get request details
		$requestKey = array('request_key' => $key);
		if(!$loginRequest->exists($requestKey)) throw new Exception('Request does not exist.');
		$requestInfo = $loginRequest->getDetail($requestKey);

		// get user details
		$userKey = array('id' => $requestInfo['usr_id']);
		if(!$systemUser->exists($userKey)) throw new Exception('Request does not exist.');
		$user = $systemUser->getDetail(array('id' => $requestInfo['usr_id']));

		// disable caching of current page
		Cache::disableCache();

		$taglist = $this->getTagList();
		if(!$taglist) return;

		foreach($taglist as $item)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

			$settingsKey = array('tag' => $item['tag'], 'tree_id' => $item['tree_id']);
			$settings = $this->getDetail($settingsKey);
			$template->setVariable('settings',  $settings);

			$template->setVariable('userinfo',  $user, false);
			$template->setVariable($requestInfo, NULL, false);
			$template->setVariable('tag',  $item['tag']);

			$this->template[$item['tag']] = $template;
		}
	} 
	
	private function handleActivatePost()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$systemUser = new SystemUser();
		$loginRequest = new LoginRequest();
		$view->setType(self::VIEW_ACTIVATE);

		try 
		{
			if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');
			if(!$request->exists('key')) throw new Exception('Key is missing.');
			$key = $request->getValue('key');
			if(!$key) throw new Exception('Key is missing.');

			// get request details
			$requestKey = array('request_key' => $key);
			if(!$loginRequest->exists($requestKey)) throw new Exception('Request does not exist.');
			$requestInfo = $loginRequest->getDetail($requestKey);

			// get user details
			$userKey = array('id' => $requestInfo['usr_id']);
			if(!$systemUser->exists($userKey)) throw new Exception('Request does not exist.');
			$user = $systemUser->getDetail(array('id' => $requestInfo['usr_id']));

			$tree = $this->director->tree;

			$tag = $request->getValue('tag');
			$tree_id = $tree->getCurrentId();
			$key = array('tree_id' => $tree_id, 'tag' => $tag);

			$settings = $this->getDetail($key);

			// hide old password and change with password validation 
			$newpass1 = $request->getValue('newpass1');
			$newpass2 = $request->getValue('newpass2');

			if(!$newpass1 && !$newpass2) throw new Exception("Password is missing.");
			
			if($newpass1 == $newpass2)
				$systemUser->setPassword($userKey, $newpass1);
			else
				throw new Exception("Passwords do not match.");

			// delete request
			//$loginRequest->delete($requestKey);

			$referer = $settings['fin_tree_id'] ? $tree->getPath($settings['fin_tree_id'], '/', Tree::TREE_ORIGINAL) : ($request->exists('referer') ? $request->getValue('referer') : '/');
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

/*------- send mail {{{ -------*/
	/**
	 * send email
	*/
	private function sendMail($mailto, $mailfrom, $subject, $settings, $link)
	{
		$recepients = explode(",", $mailto);

		$template_content = new TemplateEngine($settings['content'], false);
		$template_content->setVariable($settings);
		$template_content->setVariable('password_url',  $link);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateEmail);
		$template->setVariable('tpl_content', $template_content);
		$template->setVariable('settings',  $settings);

		$request = Request::getInstance();
		$ip = $request->getValue('REMOTE_ADDR', Request::SERVER);
		$template->setVariable('ip',   $ip);
		$template->setVariable('host',   gethostbyaddr($ip));
		$template->setVariable('client',  $request->getValue('HTTP_USER_AGENT', Request::SERVER));

		$mail = new PHPMailer();
		$mail->From = $mailfrom;
		$mail->FromName = $mailfrom;

		foreach($recepients as $email)
		{
			$mail->AddAddress(trim($email));
		}

		$mail->WordWrap = 80;
		$mail->Subject = $subject;
		$mail->Body = $template->fetch();

		if(!$mail->Send()) throw new Exception("Error sending message: ".$mail->ErrorInfo);
		if(!$this->log->isInfoEnabled()) return;
		$this->log->info("login info sent to $mailto");

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
		/*
		$src = "css/style.css.in";
		$dest = "css/".strtolower($this->getClassName()).".css";
		$theme->parseFile($this->getHtdocsPath(true).$src, $theme->getHtdocsPath(true).$dest);
		$theme->addHeader('<link href="'.$theme->getHtdocsPath().$dest.'" rel="stylesheet" type="text/css" media="screen" />');
		*/

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
