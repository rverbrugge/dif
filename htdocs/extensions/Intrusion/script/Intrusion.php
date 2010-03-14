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

require_once(DIF_ROOT."utils/SearchManager.php");

/**
 * Main configuration 
 * @package Common
 */
class Intrusion extends Extension implements ExtensionProvider
{

	const VIEW_OVERVIEW = 'i1';
	const VIEW_NEW = 'i2';
	const VIEW_EDIT = 'i3';
	const VIEW_DELETE = 'i4';

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $templateFile;

	protected $log;

	private $pagesize = 20;

	private $maxCount = 4; // max amount of posts before user gets suspicious
	private $timeout = 15; // 15 minutes timeout after suspicious requests

	// specify if intrusion record is updated for the user
	private $intrusionUpdated;

	// intruder db record
	private $intruder;

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
		$this->templateFile = "intrusion.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->intrusionUpdated = false;
		$this->pagesize = 20;

		$this->log = Logger::getInstance();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('intrusion', 'a');
		$this->sqlParser->addField(new SqlField('a', 'intr_ip', 'ip', 'Ip address', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'intr_permanent', 'permanent', 'Permanent', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'intr_count', 'count', 'Intrusion attempts', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'intr_create', 'createdate', 'Created', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'intr_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_OVERVIEW, 'Overview');
		$view->insert(self::VIEW_NEW, 'New');
		$view->insert(self::VIEW_EDIT, 'Edit');
		$view->insert(self::VIEW_DELETE, 'Delete');
	}

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
				case 'no_id' : $sqlParser->addCriteria(new SqlCriteria('a.intr_ip', $value, '<>')); break;
				case 'search' : $SqlParser->addCriteria(new SqlCriteria('a.intr_ip', "%$value%", 'like')); break;
			}
		}
	}


	protected function handlePostGetList($values)
	{
		$dateTs = time();
		$count = ($values['count'] > $this->maxCount) ? $values['count'] - $this->maxCount : 1;

		$expireTs = $values['ts'] + (60 * $count * $this->timeout);
		$expired = $expireTs <= $dateTs;

		$suspect = (!$expired && $values['count'] < $this->maxCount);
		$active = ($values['permanent'] || (!$expired && !$suspect));

		$values['activated'] = $active;
		$values['suspect'] = $suspect;
		$values['expire'] = $expireTs;

		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$dateTs = time();
		$count = ($values['count'] > $this->maxCount) ? $values['count'] - $this->maxCount : 1;

		$expireTs = $values['ts'] + (60 * $count * $this->timeout);
		$expired = $expireTs <= $dateTs;

		$suspect = (!$expired && $values['count'] < $this->maxCount);
		$active = ($values['permanent'] || (!$expired && !$suspect));

		$values['activated'] = $active;
		$values['suspect'] = $suspect;
		$values['expire'] = $expireTs;

		return $values;
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
			case 'permanent' : return 1; break;
			case 'count' : return 0; break;
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
		$fields['permanent'] = (array_key_exists('permanent', $fields) && $fields['permanent']);
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

		switch($view->getType())
		{
			case self::VIEW_NEW : $this->handleNewPost(); break;
			case self::VIEW_EDIT : $this->handleEditPost(); break;
			case self::VIEW_DELETE : $this->handleDeletePost(); break;
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

		switch($view->getType())
		{
			case self::VIEW_NEW : $this->handleNewGet(); break;
			case self::VIEW_EDIT : $this->handleEditGet(); break;
			case self::VIEW_DELETE : $this->handleDeleteGet(); break;
			default : $this->handleOverview(); break;
		}
	}
//}}}

/*------- handle request {{{ -------*/
	/**
	 * handle request
	*/
	public function handleRequest()
	{
		$request = Request::getInstance();
		$auth = Authentication::getInstance();
		if($request->getRequestType() == Request::POST && !$auth->isLogin())
		{
			$this->setIntrusion();
		}
		
		if($this->isIntrusion())
		{
			$error = ($intrusion->getExpiration()) ? 'Intrusion detected. Request disabled untill '.strftime('%c', $intrusion->getExpiration()) : 'Request disabled';
			throw new Exception($error);
		}
	}

	public function getExpiration()
	{
		if(!isset($this->intruder)) return 0;

		return $this->intruder['expire'];
	}

	public function isIntrusion()
	{
		$request = Request::getInstance();

		$ip = $request->getValue('REMOTE_ADDR', Request::SERVER);
		$key = array('ip' => $ip);
    
		//if(!$this->exists($key)) return false;

		$record = $this->getDetail($key);
		if(!$record) return false;

		// save record for later
		$this->intruder = $record;

		if($record['permanent']) 
		{
			$this->log->info("Intursion detected from ".$request->getValue('REMOTE_ADDR', Request::SERVER));
			return true;
		}

		if($record['activated'])
		{
			$this->log->info("Intursion detected from ".$request->getValue('REMOTE_ADDR', Request::SERVER));
			//throw new Exception('Intrusion detected. Request disabled until '.strftime('%c', $record['expire']));
			return true;
		}
		return false;
	}
//}}}

/*------- handle intrusion {{{ -------*/
	/**
	 * handle intrusion
	*/
	public function setIntrusion()
	{
		$request = Request::getInstance();

		$ip = $request->getValue('REMOTE_ADDR', Request::SERVER);
		$key = array('ip' => $ip);

		if($this->exists($key))
		{
			// check if intrusion already updated in this request.
			// if so, skip procedure
			if($this->intrusionUpdated) return;

			$record = $this->getDetail($key);

			if($record['activated'] || $record['suspect'])
			{
				// multiple tries, update count
				$record['count']++;
				// notify only once
				if($record['count'] == $this->maxCount) $this->notify($record);
			}
			else
			{
				// record is expired, give user another change and reset counter
				$record['count'] = 1;
			}

			$this->update($key, $record);
		}
		else
		{
 			$record = $this->getFields(SqlParser::MOD_INSERT);
			$record['ip'] = $ip;
			$record['count'] = 1;
			$record['permanent'] = 0;
			$key = $this->insert($record);
		}
		$this->intrusionUpdated = true;

	} 

	private function notify($record)
	{
		$request = Request::getInstance();

		$template = new TemplateEngine($this->getPath()."templates/intrusionemail.tpl");
		$template->setVariable($record);
		$template->setVariable('domain', $request->getDomain());
		$template->setVariable('host', gethostbyaddr($record['ip']));
		$template->setVariable('client', $request->getValue('HTTP_USER_AGENT', Request::SERVER));

		$this->director->systemUser->notify(NULL, 'Intrusion detected at '.$request->getDomain(), $template->fetch());
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

		$view->setType(self::VIEW_OVERVIEW);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('pageTitle', $this->description);

		if(!$request->exists('ext_id')) throw new Exception('Extension is missing.');
		$ext_id = intval($request->getValue('ext_id'));

		// handle searchcriteria
		$search = new SearchManager();
		$search->setUrl($this->pagerUrl);
		$search->setExclude($this->pagerKey);
		$search->setParameter('search');
		$search->setParameter('ext_id', $ext_id);
		$search->saveList();
		$searchcriteria = $search->getSearchParameterList();

		$url = new Url(true);
		$url->clearParameter('ip');
		$url->setParameter('ext_id', $ext_id);

		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), self::VIEW_NEW);
		$template->setVariable('href_new', $url_new->getUrl(true));

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), self::VIEW_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), self::VIEW_DELETE);

		$list = $this->getList($searchcriteria, $this->pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('ip', $item['ip']);
			$url_del->setParameter('ip', $item['ip']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
			if($item['suspect']) $item['state'] = 'suspect';
			elseif(!$item['activated']) $item['state'] = 'expired';
			else $item['state'] = 'intruder';
		}
		$template->setVariable('list', $list);
		$template->setVariable('searchparam', $search->getMandatoryParameterList());
		$template->setVariable('searchcriteria', $searchcriteria);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} 
//}}}

/*------- new request {{{ -------*/
	/**
	 * handle new
	*/
	private function handleNewGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(self::VIEW_NEW);

		$fields = $this->getFields(SqlParser::MOD_INSERT);

		$template->setVariable($fields);
		$template->clearVariable('ip');

		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleNewPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			$id = $this->insert($values);

			viewManager::getInstance()->setType(self::VIEW_OVERVIEW);
			$this->handleOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage', $e->getMessage(), false);

			$this->handleNewGet();
		}

	} 
//}}}

/*------- edit request {{{ -------*/
	/**
	 * handle edit
	*/
	private function handleEditGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(self::VIEW_EDIT);

		if(!$request->exists('ip')) throw new Exception('id is missing.');
		$ip = $request->getValue('ip');
		$template->setVariable('ip', $ip);
		$key = array('ip' => $ip);

		if($retrieveFields)
		{
			$fields = $this->getDetail($key);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		$this->setFields($fields);
		$template->setVariable($fields);

		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('ip')) throw new Exception('id is missing.');
			$ip = $request->getValue('ip');

			$key = array('ip' => $ip);
			$this->update($key, $values);

			viewManager::getInstance()->setType(self::VIEW_OVERVIEW);
			$this->handleOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage', $e->getMessage(), false);
			$this->handleEditGet(false);
		}

	} 
//}}}

/*------- tree delete request {{{ -------*/
	/**
	 * handle tree delete
	*/
	private function handleDeleteGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(self::VIEW_DELETE);

		if(!$request->exists('ip')) throw new Exception('id is missing.');
		$ip = $request->getValue('ip');
		$template->setVariable('ip', $ip);

		$template->setVariable($this->getDetail(array('ip' => $ip)));
		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleDeletePost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('ip')) throw new Exception('id is missing.');
			$ip = $request->getValue('ip');

			$key = array('ip' => $ip);
			$this->delete($key);

			viewManager::getInstance()->setType(self::VIEW_OVERVIEW);
			$this->handleOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage', $e->getMessage(), false);
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
		$template = $theme->getTemplate();

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key, $value);
		}
	}
}

?>
