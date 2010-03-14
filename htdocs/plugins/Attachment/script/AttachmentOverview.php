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
class AttachmentOverview extends Observer
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
		
		$this->importing = false;

		$this->template = array();
		$this->templateFile = "attachmentoverview.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('attachment', 'a');
		$this->sqlParser->addField(new SqlField('a', 'att_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'att_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'att_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'att_ref_id', 'ref_id', 'Referentie', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, false));
		$this->sqlParser->addField(new SqlField('a', 'att_weight', 'weight', 'Index', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'att_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'att_online', 'online', 'Online datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'att_offline', 'offline', 'Offline datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'att_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'att_intro', 'intro', 'Introductie', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'att_file', 'file', 'Bestand', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'att_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'att_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'att_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'att_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->orderStatement = array(Attachment::ORDER_ASC	=> 'order by a.att_weight asc, a.att_name asc',
																	Attachment::ORDER_DESC => 'order by a.att_weight desc, a.att_name asc',
																	Attachment::ORDER_RAND	=> "order by rand()");
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

	private function getHtDocsPath($absolute=false)
	{
		return $this->plugin->getHtDocsPath($absolute);
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
		if($tree_id != $new_tree_id)
		{
			$treeRef = new AttachmentTreeRef();
			$treeRef->updateRefTreeId($tree_id, $new_tree_id);
		}
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
		$treeRef = new AttachmentTreeRef();
		$treeRef->updateRefTreeId($sourceNodeId, $destinationNodeId);
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
				case 'archiveonline' : 
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.att_online)', $value, '>'));
					break;
				case 'archiveoffline' : 
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.att_offline)', $value, '<='));
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.att_offline)', 0, '>'));
					break;
				case 'activated' : 
					// only active pages
					$SqlParser->addCriteria(new SqlCriteria('a.att_active', 1));

					// only pages that are online
					$SqlParser->addCriteria(new SqlCriteria('a.att_online', 'now()', '<='));

					$offline = new SqlCriteria('a.att_offline', 'now()', '>');
					$offline->addCriteria(new SqlCriteria('unix_timestamp(a.att_offline)', 0, '='), SqlCriteria::REL_OR);
					$SqlParser->addCriteria($offline); 
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
			case 'online' : return strftime('%Y-%m-%d'); break;
			//case 'offline' : return strftime('%Y-%m-%d', mktime(0,0,0,date('m')+2)); break;
			case 'weight' : return $this->getNextWeight(); break;
		}
	}

	private function getNextWeight()
	{
		$request = Request::getInstance();
		$tree_id = intval($request->getValue('tree_id'));
		$ref_id = intval($request->getValue('ref_id'));
		$tag = $request->getValue('tag');

		$retval = 0;
		$offset = 10;
		$query = sprintf("select max(att_weight) from attachment where att_tree_id = %d and att_tag = '%s' and att_ref_id = %d", $tree_id, $tag, $ref_id);

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
		$fields['online'] = (array_key_exists('online', $fields) && $fields['online']) ? strftime('%Y-%m-%d', strtotime($fields['online'])) : '';
		$fields['offline'] = (array_key_exists('offline', $fields) && $fields['offline']) ? strftime('%Y-%m-%d', strtotime($fields['offline'])) : '';

		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$date = mktime(0,0,0);

		$activated = 1;

		// hide attachmentitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif($values['online'] > 0 && $values['online'] > $date)
			$activated = 0;
		elseif($values['offline'] > 0 && $values['offline'] <= $date)
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
		elseif($values['online'] > 0 && $values['online'] > $date)
			$activated = 0;
		elseif($values['offline'] > 0 && $values['offline'] <= $date)
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
		$query = sprintf("update attachment set att_file = '%s' where att_id = %d", addslashes($filename), $id['id']);
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
		$query = "update attachment set att_file = '' where att_id = {$id['id']}";
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
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW :  $this->handleTreeNewPost(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditPost(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeletePost(); break;
			case Attachment::VIEW_FILE_IMPORT : $this->handleImportPost(); break;
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
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW : $this->handleTreeNewGet(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditGet(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeleteGet(); break;
			case Attachment::VIEW_FILE_IMPORT : $this->handleImportGet(); break;
			default : $this->handleOverview(); break;
		}
	}
//}}}

/*----- handle cli requests {{{ -------*/
	/**
	 * Handles data coming from a post request  
	 * @param array cli request
	 */
	public function handleCliRequest($cliServer)
	{
		if(!$cliServer->parameterExists('tree_id')) throw new Exception("Parameter tree_id not set.\n");
		if(!$cliServer->parameterExists('tag')) throw new Exception("Parameter tag not set.\n");
		if(!$cliServer->parameterExists('task')) throw new Exception("Parameter task not set.\n");

		$tree_id = $cliServer->getParameter('tree_id');
		$tag = $cliServer->getParameter('tag');

		if(!$tree_id) throw new Exception("Parameter tree_id not set.\n");
		if(!$tag) throw new Exception("Parameter tag not set.\n");

		switch($cliServer->getParameter('task'))
		{
			case 'import' :
				$this->import($tree_id, $tag, true);
				break;
			default :
			 throw new Exception("Unknown task. Use 'import' for file import.\n");
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

		// retrieve tags that are linked to this plugin
		$taglist = $this->plugin->getTagList();
		if(!$taglist) return;

		// get settings
		$settings = $this->getSettings();

		foreach($taglist as $tag)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$template->setPostfix($tag['tag']);
			$template->setCacheable(true);
			$template->setVariable($settings, NULL, false);

			// check if template is in cache
			if(!$template->isCached())
			{

				$pagesize = $settings['rows'];

				$searchcriteria = array('tree_id' 	=> $tag['tree_id'], 
																'tag' 			=> $tag['tag'], 
																'activated' 		=> true);

				$list = $this->getList($searchcriteria, $pagesize, $page, $settings['display_order']);
				foreach($list['data'] as &$item)
				{
					$item['href_detail'] = $item['file'] ? $this->plugin->getFileUrl($item['id']) : '';
				}

				$template->setVariable('attachment',  $list);
			}

			$this->template[$tag['tag']] = $template;
		}
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
		$view->setType(ViewManager::TREE_OVERVIEW);

		$page = $this->getPage();

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$this->pagerUrl->addParameters($key);
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::TREE_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_import = clone $url;
		$url_import->setParameter($view->getUrlId(), Attachment::VIEW_FILE_IMPORT);
		$template->setVariable('href_import',  $url_import->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::TREE_DELETE);

		// get settings
		$settings = $this->getSettings();

		$list = $this->getList($key, $pagesize, $page, $settings['display_order']);
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

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		$key = array('id' => $request->getValue('id'));
		$name = ($view->isType(ViewManager::TREE_EDIT) || $view->isType(ViewManager::TREE_DELETE)) ? $this->getName($key) : NULL;

		$this->director->theme->handleAdminLinks($template, $name);
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
		$view->setType(ViewManager::TREE_NEW);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$fields['tree_id'] 	= $tree_id;
		$fields['tag'] 			= $tag;

		$this->handleTreeSettings($template);

		$template->setVariable($fields, NULL, false);
		$template->clearVariable('id');

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
		Utils::getDatePicker($this->director->theme, $datefields);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeNewPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			$id = $this->insert($values);

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleHttpGetRequest();
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
		$view->setType(ViewManager::TREE_EDIT);

		if(!$request->exists('id')) throw new Exception('Bestand ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);
		$key = array('id' => $id);

		if($retrieveFields)
		{
			$fields = $this->getDetail($key);
			$fields['online'] = $fields['online'] ? strftime('%Y-%m-%d', $fields['online']) : '';
			$fields['offline'] = $fields['offline'] ? strftime('%Y-%m-%d', $fields['offline']) : '';
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

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
		Utils::getDatePicker($this->director->theme, $datefields);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeEditPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			if(!$request->exists('id')) throw new Exception('Bestand ontbreekt.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->update($key, $values);

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleHttpGetRequest();
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

/*------- tree delete request {{{ -------*/
	/**
	 * handle tree delete
	*/
	private function handleTreeDeleteGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(ViewManager::TREE_DELETE);

		if(!$request->exists('id')) throw new Exception('Bestand ontbreekt.');
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
			if(!$request->exists('id')) throw new Exception('Bestand ontbreekt.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->delete($key);

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
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
		$view->setType(Attachment::VIEW_FILE_IMPORT);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('importPath',  $this->director->getImportPath(), false);

		$this->handleTreeSettings($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleImportPost()
	{
		$request = Request::getInstance();
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$this->handleTreeSettings($template);

		try 
		{
			$debug = $this->import($tree_id, $tag, false);
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

	private function import($tree_id, $tag, $stdout=true)
	{
		// enable processing of local files in insertimage
		$this->importing = true;

		$debug = array();

		$importPath = $this->director->getImportPath()."/";
		$values = array('tree_id'	=> $tree_id,
										'tag'			=> $tag,
										'active'	=> 1);

		if(!is_dir($importPath)) throw new Exception("Import path $importPath does not exist. Create it and fill it with attachments first\n");

		$debug[] = "Starting import at ".strftime('%c');
		$debug[] = "Path: $importPath";
		$debug[] = "Tree: $tree_id";
		$debug[] = "Tag: $tag";
		$debug[] = "----------------------------------\n";
		if($stdout) echo join("\n", $debug);

		$now = strftime('%Y-%m-%d');
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
			$values['online'] = $now; 
			$values['weight']	= $this->getNextWeight($tree_id, $tag);
			$values['file'] = array('tmp_name' => $file, 'name' => $name);

			$debug[] = "Processing $name";
			$id = $this->insert($values);
			if($stdout) echo join("\n", $debug);
		}
		$debug[] = "----------------------------------";
		$debug[] = "Import finished at ".strftime('%c');

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
