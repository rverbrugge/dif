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
require_once(DIF_ROOT."utils/SearchManager.php");

/**
 * Main configuration 
 * @package Common
 */
class FormOverview extends Observer
{

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;
	private $templateEmail;

	/**
	 * pointer to global plugin plugin
	 * @var Form
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
		$this->templateFile = "formoverview.tpl";
		$this->templateEmail = "formemail.tpl";
		$this->templateEmailItem = "formemailitem.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

	}

/*-------- Helper functions {{{------------*/
	private function getPath()
	{
		return $this->basePath;
	}

	public function updateTag($tree_id, $tag, $new_tree_id, $new_tag)
	{
		// update records
		$record = $this->plugin->getObject(Form::TYPE_RECORD);
		$record->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
		
		// update treeref
		if($tree_id != $new_tree_id)
		{
			$treeRef = new FormTreeRef();
			$treeRef->updateRefTreeId($tree_id, $new_tree_id);
		}

		// update settings
		$settings = $this->plugin->getObject(Form::TYPE_SETTINGS);
		$settings->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
	}

	public function updateTreeId($sourceNodeId, $destinationNodeId)
	{
		// update records
		$record = $this->plugin->getObject(Form::TYPE_RECORD);
		$record->updateTreeId($sourceNodeId, $destinationNodeId);

		// update treeref
		$treeRef = new FormTreeRef();
		$treeRef->updateRefTreeId($sourceNodeId, $destinationNodeId);

		// update settings
		$settings = $this->plugin->getObject(Form::TYPE_SETTINGS);
		$settings->updateTreeId($tag, $tree_id, $newTag);
	}

	private function composeText($settings)
	{
		$template = new TemplateEngine($settings['mailtext'], false);
		$view = ViewManager::getInstance();
		$request = Request::getInstance();
		// if multiple sites are active, refer to the current site. otherwise link can fail if other site is default
		$siteGroup = $this->director->siteManager->systemSite->getSiteGroup();

		// parse introduction text
		$url = new Url(true);
		$url->setParameter($view->getUrlId(), Form::VIEW_OPTIN);
		$url->setParameter('key', $settings['optin_key']);
		$url->setParameter(SystemSiteGroup::CURRENT_ID_KEY, $siteGroup->getCurrentId());

		$template->setVariable('optin_url',  $request->getProtocol().$request->getDomain().$url->getUrl(false), false);
		return $template;
	}

	public function composeMail($settings, $fields)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateEmail);
		$items = new TemplateEngine($this->getPath()."templates/".$this->templateEmailItem);

		$padsize = 0;
		foreach($fields as &$element)
		{
			$length = strlen($element['name']);
			if($length > $padsize) $padsize = $length;

			// add to template
			$template->setVariable($element['elm_id'],  $element['value'], false);
		}

		$template->setVariable('subject',  $settings['subject'], false);
		$items->setVariable('fields',  $fields);
		$items->setVariable('padsize',  $padsize);
		$template->setVariable('fields', $items, false);

		$request = Request::getInstance();
		$ip = $request->getValue('REMOTE_ADDR', Request::SERVER);
		$template->setVariable('ip',   $ip, false);
		$template->setVariable('host',   gethostbyaddr($ip), false);
		$template->setVariable('client',  $request->getValue('HTTP_USER_AGENT', Request::SERVER));

		return $template;
	}

	public function sendMail($mailto, $mailfrom, $subject, $content)
	{
		$recepients = explode(",", $mailto);

		$mail = new PHPMailer();
		$mail->From = $mailfrom;
		$mail->FromName = $mailfrom;

		foreach($recepients as $email)
		{
			$mail->AddAddress(trim($email));
		}

		$mail->WordWrap = 80;
		$mail->Subject = $subject;
		$mail->Body = html_entity_decode($content);

		if(!$mail->Send()) throw new Exception("Error sending message: ".$mail->ErrorInfo);
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

		
		// check if settings defined
		if($viewManager->isType(ViewManager::TREE_OVERVIEW))
		{
			$request = Request::getInstance();
			$settings = $this->plugin->getObject(Form::TYPE_SETTINGS);
			$element = $this->plugin->getObject(Form::TYPE_ELEMENT);
			$key = array('tree_id' => intval($request->getValue('tree_id')), 'tag' => $request->getValue('tag'));

			if(!$settings->exists($key)) 
				$viewManager->setType(Form::VIEW_CONFIG);
			elseif(!$element->exists($key)) 
				$viewManager->setType(Form::VIEW_ELEMENT_OVERVIEW);
		}

		switch($viewManager->getType())
		{
			case Form::VIEW_RECORD_DEL_ALL : $this->handleRecordDeleteAllGet(); break;
			case Form::VIEW_RECORD_EXPORT : $this->handleRecordExport(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW : $this->handleTreeNewGet(); break;
			case ViewManager::TREE_EDIT :  $this->handleTreeEditGet(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeleteGet(); break;
			case Form::VIEW_OPTIN : $this->handleOptin(); break;
			case Form::VIEW_MV_PREC : 
			case Form::VIEW_MV_FOL : 
			case Form::VIEW_ELEMENT_OVERVIEW : 
			case Form::VIEW_ELEMENT_NEW : 
			case Form::VIEW_ELEMENT_EDIT : 
			case Form::VIEW_ELEMENT_DELETE : $this->handleObjectGet(Form::TYPE_ELEMENT); break;
			case Form::VIEW_CONFIG : $this->handleObjectGet(Form::TYPE_SETTINGS); break;
			default : $this->handleOverviewGet(); break;
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
			case Form::VIEW_RECORD_DEL_ALL : $this->handleRecordDeleteAllPost(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW : $this->handleTreeNewPost(); break;
			case ViewManager::TREE_EDIT :  $this->handleTreeEditPost(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeletePost(); break;
			case Form::VIEW_MV_PREC : 
			case Form::VIEW_MV_FOL : 
			case Form::VIEW_ELEMENT_OVERVIEW : 
			case Form::VIEW_ELEMENT_NEW : 
			case Form::VIEW_ELEMENT_EDIT : 
			case Form::VIEW_ELEMENT_DELETE : $this->handleObjectPost(Form::TYPE_ELEMENT); break;
			case Form::VIEW_CONFIG : $this->handleObjectPost(Form::TYPE_SETTINGS); break;
			case ViewManager::OVERVIEW : $this->handleOverviewPost(); break;
		}
	} 

//}}}

/*------- tree overview request {{{ -------*/
	/**
	 * handle tree overview
	*/
	private function handleTreeOverview()
	{
		$pagesize = 10;
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$record = $this->plugin->getObject(Form::TYPE_RECORD);
		$recordItem = $this->plugin->getObject(Form::TYPE_RECORD_ITEM);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(ViewManager::TREE_OVERVIEW);

		$page = $this->getPage();

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);
		$template->setVariable($key);

		$this->pagerUrl->addParameters($key);
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		// store updated pager url to record object
		$record->setPagerUrl($this->pagerUrl);

		// handle searchcriteria
		$search = new SearchManager();
		$search->setUrl($this->pagerUrl);
		$search->setExclude($this->pagerKey);
		$search->setParameter('search');
		$search->saveList();
		$searchcriteria = $search->getSearchParameterList();
		$searchcriteria = array_merge($searchcriteria, $key);

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// create urls
		$url_conf = clone $url;
		$url_conf->setParameter($view->getUrlId(), Form::VIEW_CONFIG);
		$template->setVariable('href_conf',  $url_conf->getUrl(true), false);

		$url_elem = clone $url;
		$url_elem->setParameter($view->getUrlId(), Form::VIEW_ELEMENT_OVERVIEW);
		$template->setVariable('href_elem',  $url_elem->getUrl(true), false);

		$url_del_all = clone $url;
		$url_del_all->setParameter($view->getUrlId(), Form::VIEW_RECORD_DEL_ALL);
		$template->setVariable('href_del_all',  $url_del_all->getUrl(true), false);

		$url_export = clone $url;
		$url_export->setParameter($view->getUrlId(), Form::VIEW_RECORD_EXPORT);
		$template->setVariable('href_export',  $url_export->getUrl(true), false);

		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::TREE_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);

		// get columns and create template column list because not every record may have all the columns.
		$recordTemplate = array();
		$columns = $recordItem->getColumns($tree_id, $tag);
		$columns[] = 'timestamp';
		$columns[] = 'host';

		foreach($columns as $column)
		{
			$recordTemplate[$column] = '';
		}
		$template->setVariable('columns', $columns);

		$themeVars = $this->director->theme->getFileVars();
		$img_edit = $themeVars['img_edit'];
		$editString = '<a href="%s">'.sprintf('<img class="noborder" src="%s" width="%s" height="%s" alt="edit" title="edit" /></a>',$img_edit['src'], $img_edit['width'], $img_edit['height']);

		$delString = '<input type="checkbox" name="id[]" value="%d" class="noborder" />';

		// retrieve all records within this tree node
		// array[record id] => timestamp
		$searchcriteria = array_merge($searchcriteria, $key);
		$searchcriteria['optin'] = '';

		$recordlist = array();
		$records = $record->getList($searchcriteria, $pagesize, $page);
		foreach($records['data'] as $item)
		{
			$recordlist[$item['id']] = $item;
		}
		$template->setVariable('records', $records);

		$recordItemListFinal = array();
		if($recordlist)
		{
			// search for all form elements within the specified records
			$elemsearch = array('rcd_id' => array_keys($recordlist));

			$recordItemList = $recordItem->getItems($elemsearch);
			foreach($recordItemList as $rcd_id=>$recordElement)
			{
				// set values to all the columns
				$element = $recordTemplate;
				foreach($recordElement as $recordColumn)
				{
					// columnames are forced to lowercase, do the same for this result to merge case sensitive versions of columns
					$element[strtolower($recordColumn['name'])] = $recordColumn['value'];
				}
				$element['timestamp'] = strftime('%c',$recordlist[$rcd_id]['createdate']);
				$element['host'] = $recordlist[$rcd_id]['host'];

				$url_edit->setParameter('rcd_id', $rcd_id);
				$urls = array();
	
				$urls[] = sprintf($delString, $rcd_id);
				$urls[] = sprintf($editString, $url_edit->getUrl(true));
				array_unshift($element, join('',$urls));
				$recordItemListFinal[] = $element;
			}
		}

		$template->setVariable('list', $recordItemListFinal);
		$template->setVariable('searchparam',  $search->getMandatoryParameterList(), false);
		$template->setVariable('searchcriteria',  $searchcriteria, false);
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
		//$name = ($view->isType(ViewManager::TREE_EDIT) || $view->isType(ViewManager::TREE_DELETE)) ? $this->getName($key) : NULL;
		$name = NULL;

		$this->director->theme->handleAdminLinks($template, $name);
	}
//}}}

/*------- overview request {{{ -------*/
	/**
	 * handle overview request
	*/
	private function handleOverviewGet()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		// retrieve tags that are linked to this plugin
		$taglist = $this->plugin->getTagList();
		if(!$taglist) return;

		$request = Request::getInstance();

		foreach($taglist as $tag)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

			// get settings
			$settings = $this->plugin->getSettings($tag['tree_id'], $tag['tag']);

			// check if user defined template is set
			$userdefined = ($settings['templatefield']);

			if($userdefined)
				$templateItem = new TemplateEngine($settings['templatefield'], false);
			else
				$templateItem = new TemplateEngine($this->getPath()."templates/formoverviewitem.tpl");

			$template->setPostfix($tag['tag']);
			$template->setCacheable(true);

			// check if template is in cache
			if(!$template->isCached())
			{
				$templateItem->setVariable('settings',  $settings);

				$searchcriteria = array('tree_id' 	=> $tag['tree_id'], 
																'tag' 			=> $tag['tag'], 
																'active' 		=> true);

				$element = $this->plugin->getObject(Form::TYPE_ELEMENT);
				$list = $element->getList($searchcriteria, 0, 1, SqlParser::ORDER_ASC );
				foreach($list['data'] as &$item)
				{
					$obj = $element->getObject($item['type'], $item);
					$obj->setId($element->getTypeId($item['id']));

					$request->getRequestType() == Request::POST ? $obj->handlePostRequest() : $obj->handleGetRequest();
					$item['html'] = $obj->getHtml();
					$item['class'] = get_class($obj);

					if($userdefined) $templateItem->setVariable($obj->getId(),  $item);
				}

				// if user defined template: dont add array but add 
				if(!$userdefined) $templateItem->setVariable('fields',  $list);

				$template->setVariable('tag',  $tag['tag']);
				$template->setVariable('settings',  $settings);
				$template->setVariable('tpl_element_item',  $templateItem);
			}

			$this->template[$tag['tag']] = $template;
		}
	}

	private function handleOverviewPost()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		// retrieve tags that are linked to this plugin
		if(!$request->exists('tag')) throw new Exception('Tag not defined');
		$tag = $request->getValue('tag');
		$tree_id = $this->director->tree->getCurrentId();

		// get settings
		$settings = $this->plugin->getSettings($tree_id, $tag);

		$mailfrom = $settings['mailfrom'];

		// array with form element objects
		$objects = array();

		$searchcriteria = array('tree_id' 	=> $tree_id, 
														'tag' 			=> $tag, 
														'no_type'		=> 'InputDescription',
														'active' 		=> true);

		$element = $this->plugin->getObject(Form::TYPE_ELEMENT);

		try 
		{
			$id = session_id();

			$list = $element->getList($searchcriteria, 0, 1, SqlParser::ORDER_ASC);

			foreach($list['data'] as $item)
			{
				$obj = $element->getObject($item['type'], $item);
				$obj->setId($element->getTypeId($item['id']));
				$obj->setWeight($item['weight']);
				$obj->handlePostRequest();

				// validate field
				$obj->validate();

				// check if field class = sender
				if(get_class($obj) == "InputEmailSender" && $obj->getValue()) $mailfrom = $obj->getValue();
				$objects[] = $obj;
			}

			// store form
			$formRecordValues = array();
			$formRecordValues['tree_id'] = $tree_id;
			$formRecordValues['tag'] = $tag;
			$formRecordValues['ip'] = $request->getValue('REMOTE_ADDR', Request::SERVER);
			$formRecordValues['host'] = gethostbyaddr($formRecordValues['ip']);
			$formRecordValues['client'] = $request->getValue('HTTP_USER_AGENT', Request::SERVER);
			$formRecordValues['optin'] = ($settings['action'] == Form::ACTION_OPTIN) ? md5($id.time()) : '';

			$formRecord = $this->plugin->getObject(Form::TYPE_RECORD);
			$recordId = $formRecord->insert($formRecordValues);

			$recordFields = array();
			$formRecordItem = $this->plugin->getObject(Form::TYPE_RECORD_ITEM);
			foreach($objects as $object)
			{
				$formRecordItemValues = array();
				$formRecordItemValues['rcd_id'] = $recordId['id'];
				$formRecordItemValues['elm_id'] = $object->getId();
				$formRecordItemValues['classname'] = get_class($object);
				$formRecordItemValues['weight'] = $object->getWeight();
				$formRecordItemValues['name'] = $object->getName();
				$formRecordItemValues['value'] = (string) $object;

				$formRecordItem->insert($formRecordItemValues);
				$recordFields[] = $formRecordItemValues;
			}

			$settings['optin_key'] = $formRecordValues['optin'];

			$tplContent = $this->composeMail($settings, $recordFields);

			// If type is optin, email will be sent when user confirms request
			if($settings['action'] != Form::ACTION_OPTIN)
				$this->sendMail($settings['mailto'], $mailfrom, $settings['subject'], $tplContent->fetch());

			if($settings['action'])
			{
				//add intro / activation text to email
				$tplText = $this->composeText($settings);
				$tplContent->setVariable('text',  $tplText, false);

				$this->sendMail($mailfrom, $mailfrom, $settings['subject'], $tplContent->fetch());
			}

			$location = $settings['ref_tree_id'] ? $this->director->tree->getPath($settings['ref_tree_id']) : '/';
			header("Location: $location");
			exit;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('formError',  $e->getMessage(), false);

			$this->handleOverviewGet();
		}
	}
	 //}}}

/*------- tree new request {{{ -------*/
	/**
	 * handle overview request
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

		// get settings
		$settings = $this->plugin->getSettings($tree_id, $tag);

		$templateItem = new TemplateEngine($this->getPath()."templates/formoverviewitem.tpl");

		$template->setPostfix($tag);
		$template->setCacheable(true);

		// check if template is in cache
		if(!$template->isCached())
		{
			$templateItem->setVariable('settings',  $settings);

			$searchcriteria = array('tree_id' 	=> $tree_id, 
															'tag' 			=> $tag, 
															'active' 		=> true);

			$element = $this->plugin->getObject(Form::TYPE_ELEMENT);
			$list = $element->getList($searchcriteria, 0, 1, SqlParser::ORDER_ASC );
			foreach($list['data'] as &$item)
			{
				$obj = $element->getObject($item['type'], $item);
				$obj->setId($element->getTypeId($item['id']));

				$request->getRequestType() == Request::POST ? $obj->handlePostRequest() : $obj->handleGetRequest();
				$item['html'] = $obj->getHtml();
				$item['class'] = get_class($obj);
			}

			$templateItem->setVariable('fields',  $list);

			$template->setVariable('tag',  $tag);
			$template->setVariable('settings',  $settings);
			$template->setVariable('tpl_element_item',  $templateItem);
		}

		$this->handleTreeSettings($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeNewPost()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		// get settings
		$settings = $this->plugin->getSettings($tree_id, $tag);

		// array with form element objects
		$objects = array();

		$searchcriteria = array('tree_id' 	=> $tree_id, 
														'tag' 			=> $tag, 
														'no_type'		=> 'InputDescription',
														'active' 		=> true);

		$element = $this->plugin->getObject(Form::TYPE_ELEMENT);

		try 
		{
			$list = $element->getList($searchcriteria, 0, 1, SqlParser::ORDER_ASC);

			foreach($list['data'] as $item)
			{
				$obj = $element->getObject($item['type'], $item);
				$obj->setId($element->getTypeId($item['id']));
				$obj->setWeight($item['weight']);
				$obj->handlePostRequest();

				// validate field
				$obj->validate();

				$objects[] = $obj;
			}

			// store form
			$formRecordValues = array();
			$formRecordValues['tree_id'] = $tree_id;
			$formRecordValues['tag'] = $tag;
			$formRecordValues['ip'] = $request->getValue('REMOTE_ADDR', Request::SERVER);
			$formRecordValues['host'] = gethostbyaddr($formRecordValues['ip']);
			$formRecordValues['client'] = $request->getValue('HTTP_USER_AGENT', Request::SERVER);
			$formRecordValues['optin'] = '';

			$formRecord = $this->plugin->getObject(Form::TYPE_RECORD);
			$recordId = $formRecord->insert($formRecordValues);

			$recordFields = array();
			$formRecordItem = $this->plugin->getObject(Form::TYPE_RECORD_ITEM);
			foreach($objects as $object)
			{
				$formRecordItemValues = array();
				$formRecordItemValues['rcd_id'] = $recordId['id'];
				$formRecordItemValues['elm_id'] = $object->getId();
				$formRecordItemValues['classname'] = get_class($object);
				$formRecordItemValues['weight'] = $object->getWeight();
				$formRecordItemValues['name'] = $object->getName();
				$formRecordItemValues['value'] = (string) $object;

				$formRecordItem->insert($formRecordItemValues);
				$recordFields[] = $formRecordItemValues;
			}

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleTreeOverview();
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
	 * handle edit request
	*/
	private function handleTreeEditGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(ViewManager::TREE_EDIT);
		$element = $this->plugin->getObject(Form::TYPE_ELEMENT);
		$record = $this->plugin->getObject(Form::TYPE_RECORD);
		$recordItem = $this->plugin->getObject(Form::TYPE_RECORD_ITEM);

		if(!$request->exists('rcd_id')) throw new Exception('Record id is missing.');
		$rcd_id = intval($request->getValue('rcd_id'));

		$key = array('id' => $rcd_id);
		if(!$record->exists($key)) throw new Exception('Record does not exist.');

		$recordDetail = $record->getDetail($key);
		$tree_id = $recordDetail['tree_id'];
		$tag = $recordDetail['tag'];

		// get record items
		if($request->getRequestType() == Request::GET)
			$recordItemList = $recordItem->getRecordElementList($rcd_id);
		else
			$recordItemList = array();

		// get settings
		$settings = $this->plugin->getSettings($tree_id, $tag);

		$templateItem = new TemplateEngine($this->getPath()."templates/formoverviewitem.tpl");

		$template->setPostfix($tag);
		$template->setCacheable(true);

		$templateItem->setVariable('settings',  $settings);

		$searchcriteria = array('tree_id' 	=> $tree_id, 
														'tag' 			=> $tag, 
														'active' 		=> true);

		$list = $element->getList($searchcriteria, 0, 1, SqlParser::ORDER_ASC );
		foreach($list['data'] as &$item)
		{
			$obj = $element->getObject($item['type'], $item);
			$obj->setId($element->getTypeId($item['id']));

			if($request->getRequestType() == Request::POST)
				$obj->handlePostRequest();
			elseif(array_key_exists($obj->getId(), $recordItemList))
				$obj->handleSetValue($recordItemList[$obj->getId()]['value']);

			$item['html'] = $obj->getHtml();
			$item['class'] = get_class($obj);
		}

		$templateItem->setVariable('fields',  $list);

		$template->setVariable('rcd_id', $rcd_id);
		$template->setVariable('tag',  $tag);
		$template->setVariable('settings',  $settings);
		$template->setVariable('tpl_element_item',  $templateItem);

		$this->handleTreeSettings($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeEditPost()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		// array with form element objects
		$objects = array();

		$searchcriteria = array('tree_id' 	=> $tree_id, 
														'tag' 			=> $tag, 
														'no_type'		=> 'InputDescription',
														'active' 		=> true);

		$element = $this->plugin->getObject(Form::TYPE_ELEMENT);
		$record = $this->plugin->getObject(Form::TYPE_RECORD);
		$recordItem = $this->plugin->getObject(Form::TYPE_RECORD_ITEM);

		if(!$request->exists('rcd_id')) throw new Exception('Record id is missing.');
		$rcd_id = intval($request->getValue('rcd_id'));

		$key = array('id' => $rcd_id);
		if(!$record->exists($key)) throw new Exception('Record does not exist.');

		// get record items
		$recordItemList = $recordItem->getRecordElementList($rcd_id);

		try 
		{
			$list = $element->getList($searchcriteria, 0, 1, SqlParser::ORDER_ASC);

			foreach($list['data'] as $item)
			{
				if($item['type'] == 'InputLogin') continue; // don't change original login username 

				$obj = $element->getObject($item['type'], $item);
				$obj->setId($element->getTypeId($item['id']));
				$obj->setWeight($item['weight']);
				$obj->handlePostRequest();

				// validate field
				$obj->validate();

				$objects[] = $obj;
			}

			foreach($objects as $object)
			{
				$formRecordItemValues = array();
				$formRecordItemValues['rcd_id'] = $rcd_id;
				$formRecordItemValues['elm_id'] = $object->getId();
				$formRecordItemValues['classname'] = get_class($object);
				$formRecordItemValues['weight'] = $object->getWeight();
				$formRecordItemValues['name'] = $object->getName();
				$formRecordItemValues['value'] = (string) $object;

				if(array_key_exists($object->getId(), $recordItemList))
				{
					// item already exists. update data
					$recordItem->update(array('id' => $recordItemList[$object->getId()]['id']), $formRecordItemValues);
				}
				else
				{
					// item is new. insert data
					$recordItem->insert($formRecordItemValues);
				}
			}

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleTreeOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			$this->handleTreeNewGet();
		}
	}
	 //}}}

/*------- tree edit old {{{ -------*/
	/**
	 * handle tree edit
	*/
	private function handleeditget($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(ViewManager::TREE_EDIT);

		if(!$request->exists('id')) throw new Exception('Nieuwsbericht ontbreekt.');
		$id = intval($request->getValue('id'));

		$key = array('id' => $id);
		$template->setVariable($key);

		if($retrieveFields)
		{
			$fields = $this->getDetail($key);
			$fields['online'] = $fields['online'] ? strftime('%Y-%m-%d', $fields['online']) : '';
			$fields['offline'] = $fields['offline'] ? strftime('%Y-%m-%d', $fields['offline']) : '';
			$fields['date'] = $fields['date'] ? strftime('%Y-%m-%d', $fields['date']) : '';
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
			$detail = $this->getDetail($key);
			$fields['image'] = $detail['image'];
		}

		$this->setFields($fields);

		if($fields['image'])
		{
			$img = new Image($fields['image'], $this->plugin->getContentPath(true));
			$fields['image'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
		}

		$template->setVariable($fields);

		$this->handleTreeSettings($template);
		$template->setVariable('fckBox',  $this->getEditor($fields['text']));

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
		$datefields[] = array('dateField' => 'date', 'triggerElement' => 'date');
		Utils::getDatePicker($this->director->theme, $datefields);

		// get crop settings
		$settings = $this->plugin->getSettings();

		//only crop if both width and height defaults are set
		if($fields['image'] && $settings['image_width'] && $settings['image_height'] && ($fields['image']['width'] > $settings['image_width'] || $fields['image']['height'] > $settings['image_height']))
		{
			$theme = $this->director->theme;

			$parseFile = new ParseFile();
			$parseFile->setVariable($fields);
			$parseFile->setVariable('imgTag',  'imgsrc');
			$parseFile->setVariable($settings);
			$parseFile->setSource($this->plugin->getHtdocsPath(true)."js/cropinit.js.in");
			//$parseFile->setDestination($this->plugin->getCachePath(true)."cropinit_tpl_content.js");
			//$parseFile->save();
			$theme->addJavascript($parseFile->fetch());

			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');
			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/cropper.js"></script>');
			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/mint.js"></script>');
			//$theme->addHeader('<script type="text/javascript" src="'.$this->plugin->getCachePath().'cropinit_tpl_content.js"></script>');
		}

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleeditpost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			if(!$request->exists('id')) throw new Exception('Nieuwsbericht ontbreekt.');
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

		if(!$request->exists('id')) throw new Exception('Record is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		$record = $this->plugin->getObject(Form::TYPE_RECORD);
		$template->setVariable($record->getDetail(array('id' => $id)), NULL, false);
		$this->handleTreeSettings($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeDeletePost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Record is missing.');
			$ids = $request->getValue('id');
			if(!is_array($ids)) $ids = array($ids);

			$record = $this->plugin->getObject(Form::TYPE_RECORD);
			$recordItem = $this->plugin->getObject(Form::TYPE_RECORD_ITEM);
			$record->delete(array('id' => $ids));
			$recordItem->delete(array('rcd_id' => $ids));

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleTreeOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleTreeDeleteGet();
		}
	} 
//}}}

/*------- record export all request {{{ -------*/
	/**
	 * handle record export all
	*/
	private function handleRecordExport()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

		$cache = Cache::getInstance();
		$filename = $this->plugin->getObject(Form::TYPE_SETTINGS)->getName($key).".csv";
		$csvContent = '';

		if(!$cache->isCached($filename))
		{
			require_once(DIF_ROOT."utils/CsvFile.php");

			$record = $this->plugin->getObject(Form::TYPE_RECORD);
			$recordItem = $this->plugin->getObject(Form::TYPE_RECORD_ITEM);

			// get columns and create template column list because not every record may have all the columns.
			$recordTemplate = array();
			$columns = $recordItem->getColumns($tree_id, $tag);
			foreach($columns as $column)
			{
				$recordTemplate[$column] = '';
			}

			// retrieve all records within this tree node
			$recsearch = $key;
			$recsearch['optin'] = '';

			$recordlist = array();
			$records = $record->getList($recsearch);
			foreach($records['data'] as $item)
			{
				$recordlist[$item['id']] = $item['createdate'];
			}

			// search for all form elements within the specified records
			$exportList = array();
			$elemsearch = array('rcd_id' => array_keys($recordlist));

			// get all items sorted by record
			$recordItemList = $recordItem->getItems($elemsearch);
			foreach($recordItemList as $rcd_id=>$recordElement)
			{
				// set values to all the columns
				$element = $recordTemplate;
				foreach($recordElement as $recordColumn)
				{
					// columnames are forced to lowercase, do the same for this result to merge case sensitive versions of columns
					$element[strtolower($recordColumn['name'])] = $recordColumn['value'];
				}
				// add timestamp 
				$element['timestamp'] = strftime('%m/%d/%Y %R', $recordlist[$rcd_id]);
				$exportlist[] = $element;
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

/*------- record delete all request {{{ -------*/
	/**
	 * handle tree delete all
	*/
	private function handleRecordDeleteAllGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(Form::VIEW_RECORD_DEL_ALL);

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id', $tree_id);
		$template->setVariable('tag', $tag);

		$this->handleTreeSettings($template);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleRecordDeleteAllPost()
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

			$record = $this->plugin->getObject(Form::TYPE_RECORD);
			$recordItem = $this->plugin->getObject(Form::TYPE_RECORD_ITEM);
	
			$record->delete($key);
			$recordItem->delete($key);

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage', $e->getMessage(), false);
			$this->handleRecordDeleteAllGet();
		}
	} 
//}}}

/*------- Optin confirm request {{{ -------*/
	/**
	 * handle optin confirm
	*/
	private function handleOptin()
	{
		$taglist = $this->plugin->getTagList();
		if(!$taglist) return;

		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$recordElement = $this->plugin->getObject(Form::TYPE_ELEMENT);
		$record = $this->plugin->getObject(Form::TYPE_RECORD);
		$recordItem = $this->plugin->getObject(Form::TYPE_RECORD_ITEM);

		if(!$request->exists('key')) throw new Exception('Parameter does not exist.');
		$keyValue = $request->getValue('key');
		if(!$keyValue)throw new Exception('Parameter is empty.');

		// get request
		$key = array('optin' => $keyValue);
		$recordDetail = $record->getDetail($key);

		$itemSearch = array('rcd_id' => $recordDetail['id']);
		$recordItemList = $recordItem->getList($itemSearch);
		foreach($recordItemList['data'] as $item)
		{
			if($item['classname'] == 'InputEmailSender')
			{
				$mailfrom = $item['value'];
				break;
			}
		}

		// enable request
		$success = $record->enable($key);

		// retrieve settings to get redirect location
		$settings = $this->plugin->getSettings($recordDetail['tree_id'], $recordDetail['tag']);

		// send email
		if($success)
		{
			if(!$mailfrom) $mailfrom = $settings['mailto'];
			$tplContent = $this->composeMail($settings, $recordItemList['data']);
			$this->sendMail($settings['mailto'], $mailfrom, $settings['subject'], $tplContent->fetch());
		}

		$location = $settings['optin_tree_id'] ? $this->director->tree->getPath($settings['optin_tree_id']) : '/';
		header("Location: $location");
		exit;
	}

//}}}

/*------- handle object requests {{{ -------*/

	/**
	 * handle object get request
	*/
	private function handleObjectGet($objectType)
	{
		// add object to renderlist
		$this->plugin->addRenderList($objectType);

		$obj = $this->plugin->getObject($objectType);
		$obj->handleHttpGetRequest();
	}

	/**
	 * handle object post request
	*/
	private function handleObjectPost($objectType)
	{
		// add object to renderlist
		$this->plugin->addRenderList($objectType);

		$obj = $this->plugin->getObject($objectType);
		$obj->handleHttpPostRequest();
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

		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addJavascript("
function toggleCheckBoxes(formName)   {
	var form = formName;
	var i=form.getElements('checkbox');
	i.each(
	function(item)
	{
			if (item.checked)
			{
			item.checked=false;
		}
		else 
		{
			item.checked=true;
		}
	}
	);
}");


		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
