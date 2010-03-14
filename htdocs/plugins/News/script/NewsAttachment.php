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
class NewsAttachment extends Observer
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
	 * @var Attachment
	 */
	private $plugin;

	/**
	 * specifies wheter file import is started.
	 * if so, panic file upload check is disabled
	 * @var boolean
	 */
	private $importing;

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
		$this->setPagerKey('patt');

		$this->importing = false;
		
		$this->template = array();
		$this->templateFile = "newsattachment.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('news_attachment', 'a');
		$this->sqlParser->addField(new SqlField('a', 'att_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'att_news_id', 'news_id', 'News item', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('b', 'news_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER, false));
		$this->sqlParser->addField(new SqlField('b', 'news_tag', 'tag', 'Tag', SqlParser::getTypeSelect(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('b', 'news_active', 'news_active', 'Actieve status', SqlParser::getTypeSelect(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'att_weight', 'weight', 'Index', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'att_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'att_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'att_file', 'file', 'File', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'att_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'att_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'att_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'att_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->addFrom("inner join news as b on b.news_id = a.att_news_id");

		$this->orderStatement = array('order by a.att_weight asc, a.att_name asc');
	}

/*-------- Helper functions {{{------------*/
	private function getPath()
	{
		return $this->basePath;
	}

	private function getFilePath()
	{
		return $this->plugin->getFilePath();
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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.att_id', $value, '<>')); break;
				case 'news_active' : $SqlParser->addCriteria(new SqlCriteria('b.news_active', $value)); break;
				case 'activated' : 
					// only active pages
					$SqlParser->addCriteria(new SqlCriteria('a.att_active', 1));
					$SqlParser->addCriteria(new SqlCriteria('b.news_active', 1));
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
			case 'weight' : return $this->getNextWeight(); break;
		}
	}

	private function getNextWeight()
	{
		$request = Request::getInstance();
		$news_id = intval($request->getValue('news_id'));

		$retval = 0;
		$offset = 10;
		$query = sprintf("select max(att_weight) from news_attachment where att_news_id = %d", $news_id);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = $res->fetchOne();
		return $retval + $offset;
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

		$fields['active'] = (array_key_exists('active', $fields) && $fields['active']);

		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$date = mktime(0,0,0);

		$activated = 1;

		// hide attachmentitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif(!$values['news_active']) 
			$activated = 0;

		$values['activated'] = $activated;
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$date = mktime(0,0,0);

		$activated = 1;

		// hide attachmentitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif(!$values['news_active']) 
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$this->sqlParser->setFieldValue('own_id', $userId['id']);

		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));
	}

	/**
	 * handle post insert checks and additions 
	 * eg. insert image
   *
	 * @param integer id of inserted object
	 * @param array filtered values for insertion
	 * @return void
	 */
	protected function handlePostInsert($id, $values)
	{
		$this->insertFile($id, $values);
	}

	protected function insertFile($id, $values)
	{
		$tmpfile = $values['file'];
		if(!$this->importing && (!is_array($tmpfile) ||  !$tmpfile['tmp_name'])) return;

		$extension = Utils::getFileExtension($tmpfile['name']);
		$path = $this->getFilePath();

		$filename = strtolower($this->getClassName())."_{$id['id']}.$extension";

		// delete current file
		$detail = $this->getDetail($id);
		$this->deleteFile($detail);

		//check if path exists and otherwise create it
		if(!is_dir($path))
		{
			if(!mkdir($path, 0775)) throw new Exception("Error creating direcotry ".$path);
		}

		if($this->importing)
		{
			if(!is_file($tmpfile['tmp_name'])) throw new Exception("File {$tmpfile['tmp_name']} does not exist.");
			rename($tmpfile['tmp_name'], $path.$filename);
		}
		elseif(!move_uploaded_file($tmpfile['tmp_name'], $path.$filename)) 
		{
			$this->infoLog(__FUNCTION__, array('try to use illegal upload file' => $tmpfile['tmp_name']), false);
			throw new Exception("Error processing uploaded file. Your request has been logged. (are you doing something illegal?.)");
		}

		$db = $this->getDb();
		$query = sprintf("update news_attachment set att_file = '%s' where att_id = %d", addslashes($filename), $id['id']);
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	private function deleteFile($values)
	{
		if(!$values['file']) return;
		
		$filename = $this->getFilePath().$values['file'];
		if(!unlink($filename)) throw new Exception("Error removing $filename");
		return true;
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
		if(!isset($values['file_delete'])) return;

		$detail = $this->getDetail($id);
		if(!$this->deleteFile($detail)) return;

		$db = $this->getDb();
		$query = "update news_attachment set att_file = '' where att_id = {$id['id']}";
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	protected function handlePostUpdate($id, $values)
	{
		$this->insertFile($id, $values);
	}

	protected function handlePostDelete($id, $values)
	{
		$this->deleteFile($values);
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
			case News::VIEW_FILE_OVERVIEW : $this->handleTreeOverview(); break;
			case News::VIEW_FILE_NEW :  $this->handleTreeNewPost(); break;
			case News::VIEW_FILE_EDIT : $this->handleTreeEditPost(); break;
			case News::VIEW_FILE_DELETE : $this->handleTreeDeletePost(); break;
			case News::VIEW_FILE_IMPORT : $this->handleImportPost(); break;
			case News::VIEW_DETAIL : $this->handleOverview(); break;
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
			case News::VIEW_FILE_OVERVIEW : $this->handleTreeOverview(); break;
			case News::VIEW_FILE_NEW : $this->handleTreeNewGet(); break;
			case News::VIEW_FILE_EDIT : $this->handleTreeEditGet(); break;
			case News::VIEW_FILE_DELETE : $this->handleTreeDeleteGet(); break;
			case News::VIEW_FILE_IMPORT : $this->handleImportGet(); break;
			case News::VIEW_DETAIL : $this->handleOverview(); break;
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
		$tag = 'newsattachment';

		if(!$request->exists('id')) throw new Exception('News item is missing.');
		$news_id = intval($request->getValue('id'));

		$template = $this->getAttachmentTemplate($news_id, $tag);
		$this->template[$tag] = $template;
	}

	public function getAttachmentTemplate($news_id, $tag)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setPostfix($tag.$news_id);
		$template->setCacheable(true);

		// check if template is in cache
		if(!$template->isCached())
		{
			$searchcriteria = array('news_id' 	=> $news_id, 
															'activated' 		=> true);

			$list = $this->getList($searchcriteria);
			foreach($list['data'] as &$item)
			{
				$item['href_detail'] = $item['file'] ? $this->plugin->getFileUrl($item['id']) : '';
			}

			$template->setVariable('attachment',  $list);
		}
		return $template;
	} //}}}

/*------- tree overview request {{{ -------*/
	/**
	 * handle tree overview
	*/
	private function handleTreeOverview()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(News::VIEW_FILE_OVERVIEW);

		if(!$request->exists('news_id')) throw new Exception('News item is missing.');
		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$news_id = intval($request->getValue('news_id'));
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('news_id' => $news_id);

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('news_id', $news_id);
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// add breadcrumb item
		$this->director->theme->handleAdminLinks($template);

		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), News::VIEW_FILE_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_import = clone $url;
		$url_import->setParameter($view->getUrlId(), News::VIEW_FILE_IMPORT);
		$template->setVariable('href_import',  $url_import->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), News::VIEW_FILE_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), News::VIEW_FILE_DELETE);

		$list = $this->getList($key);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);

			if($item['file']) $item['file_url'] = $this->plugin->getFileUrl($item['id']);
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

		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('news_id')) throw new Exception('News item is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$tree_id = intval($request->getValue('tree_id'));
		$news_id = intval($request->getValue('news_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		// create href back url
		$url = new Url(true); 
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('news_id', $news_id);
		$url->setParameter('id', $news_id);
		$url->setParameter('tag', $tag);
		$url->setParameter($view->getUrlId(), News::VIEW_FILE_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		// add breadcrumb item
		$breadcrumb = array('name' => $view->getName(News::VIEW_FILE_OVERVIEW), 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		$this->director->theme->handleAdminLinks($template);

	}

//}}}

/*------- tree new request {{{ -------*/
	/**
	 * handle tree new
	*/
	private function handleTreeNewGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(News::VIEW_FILE_NEW);

		$news_id = intval($request->getValue('news_id'));

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		$key = array('news_id' => $news_id);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$fields['news_id'] 	= $news_id;

		$this->handleTreeSettings($template);

		$template->setVariable($fields, NULL, false);
		$template->clearVariable('id');

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeNewPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			$id = $this->insert($values);

			viewManager::getInstance()->setType(News::VIEW_FILE_OVERVIEW);
			$this->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			$this->handleTreeNewGet();
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
		$view->setType(News::VIEW_FILE_EDIT);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		if(!$request->exists('id')) throw new Exception('File is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);
		$key = array('id' => $id);

		if($retrieveFields)
		{
			$fields = $this->getDetail($key);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
			$detail = $this->getDetail($key);
			$fields['file'] = $detail['file'];
		}

		$this->setFields($fields);

		// get path to file
		if($fields['file']) $template->setVariable('file_url',  $this->plugin->getFileUrl($id), false);

		$template->setVariable($fields, NULL, false);

		$this->handleTreeSettings($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeEditPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			if(!$request->exists('id')) throw new Exception('File is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->update($key, $values);

			viewManager::getInstance()->setType(News::VIEW_FILE_OVERVIEW);
			$this->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

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
		$view->setType(News::VIEW_FILE_DELETE);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		if(!$request->exists('id')) throw new Exception('File is missing.');
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
			if(!$request->exists('id')) throw new Exception('File is missing.');
			$ids = $request->getValue('id');

			if(!is_array($ids)) $ids = array($ids);
			foreach($ids as $id)
			{
				$key = array('id' => $id);
				$this->delete($key);
			}

			viewManager::getInstance()->setType(News::VIEW_FILE_OVERVIEW);
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

/*------- import request {{{ -------*/
	/**
	 * handle import
	*/
	private function handleImportGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(News::VIEW_FILE_IMPORT);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');
		$news_id = intval($request->getValue('news_id'));

		$template->setVariable('news_id',  $news_id, false);
		$template->setVariable('importPath',  $this->director->getImportPath(), false);

		$this->handleTreeSettings($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleImportPost()
	{
		$request = Request::getInstance();
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$tree_id = intval($request->getValue('tree_id'));
		$news_id = intval($request->getValue('news_id'));
		$tag = $request->getValue('tag');

		$this->handleTreeSettings($template);

		try 
		{
			$debug = $this->import($tree_id, $tag, $news_id, false);
			$template->setVariable('debug',  $debug, false);
			$this->template[$this->director->theme->getConfig()->main_tag] = $template;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			$this->handleImportGet();
		}
	} 

	private function import($tree_id, $tag, $news_id, $stdout=true)
	{
		// enable processing of local files in insertimage
		$this->importing = true;

		$debug = array();

		$importPath = $this->director->getImportPath()."/";
		$values = array('tree_id'	=> $tree_id,
										'tag'			=> $tag,
										'news_id'	=> $news_id,
										'active'	=> 1);

		if(!is_dir($importPath)) throw new Exception("Import path $importPath does not exist. Create it and fill it with attachments first\n");

		$debug[] = "Starting import at ".date('d-m-Y H:i:s');
		$debug[] = "Path: $importPath";
		$debug[] = "Tree: $tree_id";
		$debug[] = "Tag: $tag";
		$debug[] = "----------------------------------\n";
		if($stdout) echo join("\n", $debug);

		$files = array();
		$dh = dir($importPath);
		while(FALSE !== ($entry = $dh->read()))
		{
			$file = $importPath.$entry;
			if(!is_file($file)) continue;
			$files[] = $file;
		}
		// sort alphabetical
		sort($files);

		foreach($files as $file)
		{
			$name = basename($file, ".".Utils::getExtension($file));

			$values['name'] = $name;
			$values['weight']	= $this->getNextWeight($tree_id, $tag);
			$values['file'] = array('tmp_name' => $file, 'name' => $name);

			$debug[] = "Processing $name";
			$id = $this->insert($values);
			if($stdout) echo join("\n", $debug);
		}
		$debug[] = "----------------------------------";
		$debug[] = "Import finished at ".date('d-m-Y H:i:s');

		if($stdout) echo join("\n", $debug);

		return $debug;
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
