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
class User extends SystemUser implements GuiProvider
{
	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	private $pagesize = 20;

	const VIEW_IMPORT = 'u1';
	const VIEW_IMPORT_TEMPL = 'u2';
	const VIEW_EXPORT = 'u3';

	private $exportColumns;

	public function __construct()
	{
		parent::__construct();
		$this->template = array();
		$this->templateFile = "user.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		// data tha will be exported or imported (updated)
		$this->exportColumns = array( 'active' => 'active',
																	'notify' => 'notify',
																	'role' => 'role',
																	'name' => 'name',
																	'firstname' => 'firstname',
																	'address' => 'address',
																	'address_nr' => 'address_nr',
																	'zipcode' => 'zipcode',
																	'city' => 'city',
																	'country' => 'country',
																	'phone' => 'phone',
																	'mobile' => 'mobile',
																	'email' => 'email',
																	'username' => 'username',
																	'password' => 'password');

		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_IMPORT, 'Gebruikers importeren');
		$view->insert(self::VIEW_IMPORT_TEMPL, 'Csv template');
		$view->insert(self::VIEW_EXPORT, 'Gebruikers exporteren');

	}

	private function getPath()
	{
		return $this->basePath;
	}

	/**
	* returns the absolute htdocs web path eg. /themes/foobar/htdocs/
	* @return string name of theme
	*/
	public function getHtdocsPath($absolute=false)
	{
		return ($absolute) ? $this->getPath()."htdocs/" : DIF_VIRTUAL_WEB_ROOT."coreplugins/".$this->getClassName()."/htdocs/";
	}


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
			case ViewManager::ADMIN_NEW : $this->handleAdminNewPost(); break;
			case ViewManager::ADMIN_EDIT : $this->handleAdminEditPost(); break;
			case ViewManager::ADMIN_DELETE : $this->handleAdminDeletePost(); break;
			case self::VIEW_IMPORT : $this->handleImportPost(); break;
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
			case ViewManager::ADMIN_OVERVIEW : $this->handleAdminOverview(); break;
			case ViewManager::ADMIN_NEW : $this->handleAdminNewGet(); break;
			case ViewManager::ADMIN_EDIT : $this->handleAdminEditGet(); break;
			case ViewManager::ADMIN_DELETE : $this->handleAdminDeleteGet(); break;
			case self::VIEW_IMPORT : $this->handleImportGet(); break;
			case self::VIEW_IMPORT_TEMPL : $this->handleImportTemplate(); break;
			case self::VIEW_EXPORT : $this->handleExport(); break;
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

		$searchcriteria = array();
		$list = $this->getList(NULL, $this->pagesize, $page);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('list',  $list, false);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} //}}}

/*------- admin overview request {{{ -------*/
	/**
	 * handle admin overview request
	*/
	private function handleAdminOverview()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$page = $this->getPage();
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$url = new Url(true);
		$url->clearParameter('id');

		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::ADMIN_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_import = clone $url;
		$url_import->setParameter($view->getUrlId(), self::VIEW_IMPORT);
		$template->setVariable('href_import',  $url_import->getUrl(true), false);

		$url_export = clone $url;
		$url_export->setParameter($view->getUrlId(), self::VIEW_EXPORT);
		$template->setVariable('href_export',  $url_export->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::ADMIN_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::ADMIN_DELETE);

		// handle searchcriteria
		$search = new SearchManager();
		$search->setUrl($this->pagerUrl);
		$search->setExclude($this->pagerKey);
		$search->setParameter('search');
		$search->saveList();
		$searchcriteria = $search->getSearchParameterList();

		$list = $this->getList($searchcriteria, $this->pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
		}
		$template->setVariable('list',  $list, false);
		$template->setVariable('searchparam',  $search->getMandatoryParameterList(), false);
		$template->setVariable('searchcriteria',  $searchcriteria, false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} 
//}}}

/*------- new request {{{ -------*/
	private function handleEdit($template, $grp_used)
	{
		$search_used = ($grp_used) ? array('id' => $grp_used) : NULL;
		$search_free = ($grp_used) ? array('no_id' => $grp_used) : NULL;
		$group_used = ($grp_used) ? $this->usergroup->getList($search_used) : array('data'=>'');
		$group_free = $this->usergroup->getList($search_free);
		$template->setVariable('cbo_grp_used',  Utils::getHtmlCombo($group_used['data']), false);
		$template->setVariable('cbo_grp_free',  Utils::getHtmlCombo($group_free['data']), false);

		$view = ViewManager::getInstance();

		$url = new Url(true);

		$breadcrumb = array('name' => $view->getName(), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);
		
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);
	}

	/**
	 * handle new
	*/
	private function handleAdminNewGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$request = Request::getInstance();
		$grp_used = $request->getValue('grp_used');

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$template->setVariable($fields, NULL, false);
		$this->handleEdit($template, $grp_used);
		$template->setVariable('cbo_role', Utils::getHtmlCombo($this->getRoleList(), $fields['role']));

		$template->setVariable('id',  '', false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminNewPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		$grp_used = $request->getValue('grp_used');
		if(!$grp_used) $grp_used = array();

		try 
		{
			$id = $this->insert($values);
			$this->setPassword($id, $values['password']);

			foreach($grp_used as $item)
			{
				$this->addGroup($id, array('id' => $item));
			}

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminNewGet();
		}


	} 
//}}}

/*------- edit request {{{ -------*/
	/**
	 * handle edit
	*/
	private function handleAdminEditGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();

		if(!$request->exists('id')) throw new Exception('Gebruiker ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);
		$key = array('id' => $id);

		$grp_used = $request->getValue('grp_used');

		if($retrieveFields)
		{
			$this->setFields($this->getDetail($key));
			$grp_used = $this->getGroup(array('usr_id' => $id));
		}

		$fields = $this->getFields(SqlParser::MOD_UPDATE);
		$template->setVariable($fields, NULL, false);
		$template->setVariable('cbo_role', Utils::getHtmlCombo($this->getRoleList(), $fields['role']));
		$this->handleEdit($template, $grp_used);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		$grp_used = $request->getValue('grp_used');
		if(!$grp_used) $grp_used = array();

		try 
		{
			if(!$request->exists('id')) throw new Exception('Gebruiker ontbreekt.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);

			// hide old password and change with password validation 
			$newpass1 = $request->getValue('newpass1');
			$newpass2 = $request->getValue('newpass2');

			if($newpass1 || $newpass2) 
			{
				if($newpass1 == $newpass2)
					$this->setPassword($key, $newpass1);
				else
				{
					$this->setFields($values);
					throw new Exception("Passwords do not match.");
				}
			}

			$this->update($key, $values);

			$this->removeGroup($key);
			foreach($grp_used as $item)
			{
				$this->addGroup($key, array('id' => $item));
			}

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminEditGet(false);
		}

	} 
//}}}

/*------- delete request {{{ -------*/
	/**
	 * handle delete
	*/
	private function handleAdminDeleteGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();

		if(!$request->exists('id')) throw new Exception('Gebruiker ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		$template->setVariable('name',  $this->getName(array('id' => $id)), false);

		$view = ViewManager::getInstance();

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminDeletePost()
	{
		$request = Request::getInstance();

		try 
		{
			if(!$request->exists('id')) throw new Exception('Gebruiker ontbreekt.');
			$id = intval($request->getValue('id'));

			$this->delete(array('id' => $id));
			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminDeleteGet();
		}

	} 
//}}}

/*------- import request {{{ -------*/
	/**
	 * handle import
	*/
	private function handleImportGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$grp_used = $request->getValue('grp_used');

		$this->handleEdit($template, $grp_used);

		$template->setVariable('id',  '', false);

		$url_import_template = new Url(true);
		$url_import_template->setParameter($view->getUrlId(), self::VIEW_IMPORT_TEMPL);
		$template->setVariable('href_import_template',  $url_import_template->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleImportPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		$grp_used = $request->getValue('grp_used');
		if(!$grp_used) $grp_used = array();

		try 
		{
			require_once(DIF_ROOT."utils/CsvFile.php");

			// check if import file is uploaded
			if(!array_key_exists('import_file', $values) && !is_array($values['import_file'])) throw new Exception('No import file set');

			// validate file is really a uploaded file
			$file = $values['import_file'];
			if(!array_key_exists('tmp_name', $file) || !is_uploaded_file($file['tmp_name'])) throw new Exception('wrong file.');

			$csvFile = new CsvFile();
			$records = $csvFile->import($file['tmp_name']);

			// check fields
			$fields = array_intersect($csvFile->getFields(), $this->exportColumns);
			if(!$fields || !in_array('username', $fields)) throw new Exception("Username is not present in import file");

			$db = $this->getDb();

			// create temporary table
			$query = "create temporary table userimport like ".$this->sqlParser->getTable();
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			// filter and insert records

			$fieldNames = array_intersect($this->sqlParser->getFieldNames(), $fields);
			ksort($fieldNames);
			$query = "insert into userimport(".join(",",array_keys($fieldNames)).") values ";

			$recordFields = array();
			foreach($fieldNames as $name)
			{
				$recordFields[$name] = $name;
			}

			$rows = array();
			foreach($records as $record)
			{
				$record = array_intersect_key($record, $recordFields);
				if(!$record) next;

				ksort($record);
				if(array_key_exists('role', $record) && $record['role']) $record['role'] = $this->getRoleId($record['role']);
				foreach($record as &$item)
				{
					//if(!$item) $item = "NULL";
					$item = addslashes($item);
				}
				$rows[] = "('".join("','",$record)."')";
			}
			$query .= join(",",$rows);

			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			// update records
			$update = array();
			$insert = array();
			foreach($fieldNames as $key=>$value)
			{
				$insert[] = "a.$key";
				// skip username and password
				if($key == 'username' || $key == 'password') continue;

				$update[] = "a.$key = b.$key";
			}
			$tablename = $this->sqlParser->getTable();
			$query = sprintf("update %s as a inner join userimport as b on a.usr_username = b.usr_username set %s",$tablename, join(",", $update));

			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			// insert records
			$query = sprintf("insert into %s (%s,usr_create) select %s, now() from userimport as a left join %s as b on a.usr_username = b.usr_username where b.usr_id is NULL",
											$tablename,
											join(",",array_keys($fieldNames)),
											join(",",$insert),
											$tablename);

			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			// update group
			foreach($grp_used as $grp_id)
			{
				$query = sprintf("insert into usergroup (usr_id, grp_id) 
													select a.usr_id, %d 
													from %s as a 
													inner join userimport as b 
													on a.usr_username = b.usr_username 
													left join usergroup as c 
													on a.usr_id = c.usr_id and c.grp_id = %d 
													where c.usr_id is NULL", 
												$grp_id, $this->sqlParser->getTable(), $grp_id);

				$res = $db->query($query);
				if($db->isError($res)) throw new Exception($res->getDebugInfo());
			}
			

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleImportGet();
		}
	}
//}}}

/*------- import template request {{{ -------*/
	private function handleImportTemplate()
	{
		require_once(DIF_ROOT."utils/CsvFile.php");
		$fields = $this->getFields(SqlParser::MOD_INSERT);
		foreach($fields as &$item)
		{
			$item = '';
		}
		unset($fields['createdate']);
		$result = array($fields);

		$csvFile = new CsvFile();
		$csvContent = join("\n",$csvFile->array2csv($result));
		$filename = strtolower($this->getClassName())."_template.csv";

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		echo $csvContent;
		exit;
	}
//}}}

/*------- export request {{{ -------*/
	/**
	 * handle export 
	*/
	private function handleExport()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$cache = Cache::getInstance();
		$filename = strtolower($this->getClassName()).".csv";
		$csvContent = '';

		if(!$cache->isCached($filename))
		{
			require_once(DIF_ROOT."utils/CsvFile.php");

			// handle searchcriteria
			$search = new SearchManager();
			$search->setUrl($this->pagerUrl);
			$search->setExclude($this->pagerKey);
			$search->setParameter('search');
			$search->saveList();
			$searchcriteria = $search->getSearchParameterList();

			$exportlist = array();
			$list = $this->getList($searchcriteria);
			foreach($list['data'] as $item)
			{
				$row = array_intersect_key($item, $this->exportColumns);
				$row['password'] = '';
				$row['role'] = $this->getRoleDesc($row['role']);
				$exportlist[] = $row;
			}

			$csvFile = new CsvFile();
			$csvContent = join("\n",$csvFile->array2csv($exportlist));
			$cache->save($csvContent, $filename);
		}
		else
			$csvContent = $cache->getCache($filename);

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		echo $csvContent;
		exit;
	}
//}}}

	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 */
	public function renderForm($theme)
	{
		$template = $theme->getTemplate();
		$theme->addHeader('<script type="text/javascript" src="'.$this->getHtdocsPath().'user.js"></script>');

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
