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

require_once("NewsLetterPlugin.php");

/**
 * Main configuration 
 * @package Common
 */
class NewsLetterOverview extends Observer
{

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	/**
	 * pointer to global plugin plugin
	 * @var NewsLetter
	 */
	private $plugin;

	/**
	 * temporary location of dynamic templates for code tags
	 * these templates are used by newsletter generation 
	 * they contain for example user specific information
	 * @var array
	 */
	private $newsLetterTemplate = array();

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
		$this->templateFile = "newsletteroverview.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('newsletter', 'a');
		$this->sqlParser->addField(new SqlField('a', 'nl_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'nl_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'nl_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'nl_theme_id', 'theme_id', 'Theme', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'nl_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'nl_online', 'online', 'Online datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'nl_offline', 'offline', 'Offline datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'nl_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'nl_intro', 'intro', 'Introductie', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'nl_thumbnail', 'thumbnail', 'Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'nl_image', 'image', 'Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'nl_img_x', 'img_x', 'Offset x', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'nl_img_y', 'img_y', 'Offset y', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'nl_img_width', 'img_width', 'Offset width', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'nl_img_height', 'img_height', 'Offset height', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'nl_count', 'count', 'Visits', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'nl_send_count', 'send_count', 'Send count', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'nl_send_date', 'send_date', 'Send date', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'nl_usr_id', 'usr_id', 'User', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'nl_own_id', 'own_id', 'Owner', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'nl_create', 'createdate', 'Created', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'nl_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->orderStatement = array('order by a.nl_online desc, a.nl_id desc');
	}

/*-------- Helper functions {{{------------*/
	private function getPath()
	{
		return $this->basePath;
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
		
		// update settings
		$settings = $this->plugin->getObject(NewsLetter::TYPE_SETTINGS);
		$settings->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
		
		// update settings
		$obj = $this->plugin->getNewsLetterUser();
		$obj->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
		
		// update treeref
		if($tree_id != $new_tree_id)
		{
			$treeRef = new NewsLetterTreeRef();
			$treeRef->updateRefTreeId($tree_id, $new_tree_id);
		}

		// update settings
		$obj = $this->plugin->getNewsLetterGroup();
		$obj->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
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
		$treeRef = new NewsLetterTreeRef();
		$treeRef->updateRefTreeId($sourceNodeId, $destinationNodeId);

		// update settings
		$settings = $this->plugin->getObject(NewsLetter::TYPE_SETTINGS);
		$settings->updateTreeId($tag, $tree_id, $newTag);

		// update settings
		$obj = $this->plugin->getNewsLetterUser();
		$obj->updateTreeId($tag, $tree_id, $newTag);

		// update settings
		$obj = $this->plugin->getNewsLetterGroup();
		$obj->updateTreeId($tag, $tree_id, $newTag);
	}


	public function updateCount($key, $count)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($key, false);
		$this->parseCriteria($sqlParser, $key, false);
		$sqlParser->setFieldValue('count', $count);
		$sqlParser->setFieldValue('send_date', 'now()', false);

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}
	/*
	public function updateCount($key)
	{
		$request = Request::getInstance();
		$count = $request->exists('nlcount', Request::SESSION) ? $request->getValue('nlcount', Request::SESSION) : array();
		
		// check if already counted
		if(in_array($key['id'], $count)) return;

		$query = sprintf("update newsletter set nl_count = nl_count+1 where nl_id = %d", $key['id']);
		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$count[] = $key['id'];
		$request->setValue('nlcount', $count);
	}
	*/

//}}}

/*-------- DbConnector insert function {{{------------*/

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	protected function parseCriteria($sqlParser, $searchcriteria, $prefix=true)
	{
		if(!$searchcriteria || !is_array($searchcriteria)) return;

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'no_id' : $sqlParser->addCriteria(new SqlCriteria($sqlParser->getFieldByName('id')->getField($prefix), $value, '<>')); break;
				case 'archiveonline' : 
					$sqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.nl_online)', $value, '>'));
					break;
				case 'archiveoffline' : 
					$sqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.nl_offline)', $value, '<='));
					$sqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.nl_offline)', 0, '>'));
					break;
				case 'activated' : 
					// only active pages
					$sqlParser->addCriteria(new SqlCriteria($sqlParser->getFieldByName('active')->getField($prefix), 1));

					// only pages that are online
					$sqlParser->addCriteria(new SqlCriteria($sqlParser->getFieldByName('online')->getField($prefix), 'now()', '<='));

					$offlineField = $sqlParser->getFieldByName('offline')->getField($prefix);
					$offline = new SqlCriteria($offlineField, 'now()', '>');
					$offline->addCriteria(new SqlCriteria($offlineField, 'null', '='), SqlCriteria::REL_OR);
					$offline->addCriteria(new SqlCriteria($offlineField, 0, '='), SqlCriteria::REL_OR);
					$sqlParser->addCriteria($offline); 
					break;
				case 'search' : 
					$search = new SqlCriteria('a.nl_name', "%$value%", 'like'); 
					$search->addCriteria(new SqlCriteria('a.nl_intro', "%$value%", 'like'), SqlCriteria::REL_OR); 
					$sqlParser->addCriteria($search);
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
			case 'offline' : return strftime('%Y-%m-%d', mktime(0,0,0,date('m')+2)); break;
			case 'theme_id' : 
				$settings = $this->plugin->getSettings();
				return $settings['theme_id']; 
				break;
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

		$fields['active'] = (array_key_exists('active', $fields) && $fields['active']);
		$fields['online'] = (array_key_exists('online', $fields) && $fields['online']) ? strftime('%Y-%m-%d',strtotime($fields['online'])) : '';
		$fields['offline'] = (array_key_exists('offline', $fields) && $fields['offline']) ? strftime('%Y-%m-%d',strtotime($fields['offline'])) : '';

		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$date = mktime(0,0,0);

		$activated = 1;

		// hide newlettersitem if not active
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
		$this->insertImage($id, $values);
	}

	protected function insertImage($id, $values)
	{
		$request = Request::getInstance();

		// get settings
		$settings = $this->plugin->getSettings();

		$destWidth = $settings['image_width'];
		$destHeight = $settings['image_height'];
		$maxWidth = $settings['image_max_width'];
		$imgX = $values['img_x'];
		$imgY = $values['img_y'];

		$path 				= $this->plugin->getContentPath(true);
		$filename 		= strtolower($this->getClassName())."_".$id['id'];

		// check if image is uploaded
		$img = $values['image'];
		$upload = (is_array($img) &&  $img['tmp_name']);
		if($upload)
		{
			// image is new uploaded image
			$image = new Image($img);
			$imgWidth = $image->getWidth();
			$imgHeight = $image->getHeight();
			$ext = Utils::getExtension($img['name']);

			$originalFile = "$filename.$ext";
			$croppedFile	= $filename."_thumb.$ext";

			// delete current image if filename new filename is different
			$detail = $this->getDetail($id);
			if($detail['image'] && $detail['image'] != $originalFile)
				$this->deleteImage($detail);

			// resize original image. (increase size if necessary)
			if($maxWidth > 0 && $imgWidth > $maxWidth)
				$image->resize($maxWidth);
			elseif($imgWidth < $destWidth || $imgHeight < $destHeight)
			{
				if($image->getWidth() < $destWidth) $image->resize($destWidth, 0, true);
				if($image->getHeight() < $destHeight) $image->resize(0, $destHeight, true);
			}

			$image->save($path.$originalFile);
		}
		else
		{
			// no image provided. check if one exists
			$detail = $this->getDetail($id);
			if(!$detail['image']) return;

			// get original image
			$image = new Image($detail['image'], $this->plugin->getContentPath(true));
			$ext = Utils::getExtension($detail['image']);

			$originalFile = "$filename.$ext";
			$croppedFile	= $filename."_thumb.$ext";
		}

		// only crop if both width and height settings are set and image is big enough, else do a resize
		if($destWidth && $destHeight)
		{
			// crop image
			if($upload)
			{
				// calculate area of crop field
				// first assume width is smalles side
				$newWidth = $image->getWidth();
				$newHeight = ($newWidth / $destWidth) * $destHeight;
				if($newHeight > $image->getHeight())
				{
					// width was larger than height, so use height as smallest side
					$newHeight = $image->getHeight();
					$newWidth = ($newHeight / $destHeight) * $destWidth;
				}
				// center crop area
				$imgX = intval(($image->getWidth() / 2) - ($newWidth / 2));
				$imgY = intval(($image->getHeight() / 2) - ($newHeight / 2));
			}
			else
			{
				$newWidth = $values['img_width'];
				$newHeight = $values['img_height'];
			}

			// crop image
			$image->crop($imgX, $imgY, $newWidth, $newHeight, $destWidth, $destHeight);

			// save cropped and overlayed image
			$image->save($path.$croppedFile);
		}
		else
		{
			// resize image
			$image->resize($destWidth, $destHeight);
			$newWidth = $image->getWidth();
			$newHeight = $image->getHeight();
			$image->save($path.$croppedFile);
		}

		$db = $this->getDb();
		$query = sprintf("update newsletter set nl_image= '%s', nl_thumbnail = '%s', nl_img_x = %d, nl_img_y = %d, nl_img_width = %d, nl_img_height = %d where nl_id = %d", 
										addslashes($originalFile), 
										addslashes($croppedFile), 
										$imgX,
										$imgY,
										$newWidth,
										$newHeight,
										$id['id']);

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	private function deleteImage($values)
	{
		$retval = false;

		if($values['thumbnail']) 
		{
			$image = new Image($values['thumbnail'], $this->plugin->getContentPath(true));
			$image->delete();
			$retval = true;
		}

		if($values['image']) 
		{
			$image = new Image($values['image'], $this->plugin->getContentPath(true));
			$image->delete();
			$retval = true;
		}
		return $retval;
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
		// only process if delete request
		if(!isset($values['thumbnail_delete'])) return;

		$detail = $this->getDetail($id);
		if(!$this->deleteImage($detail)) return;
		
		$db = $this->getDb();
		$query = "update newsletter set nl_image = '', nl_thumbnail = '' where nl_id = {$id['id']}";
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	protected function handlePostUpdate($id, $values)
	{
		$this->insertImage($id, $values);
	}

	protected function handlePreDelete($id, $values)
	{
		$attachment = $this->plugin->getObject(NewsLetter::TYPE_ATTACHMENT);
		$search = array('nl_id' => $id['id']);
		$list = $attachment->getList($search);
		foreach($list['data'] as $item)
		{
			$attachment->delete(array('id' => $item['id']));
		}

		$comment = $this->getComment();
		$comment->delete($search);
	}

	protected function handlePostDelete($id, $values)
	{
		$this->deleteImage($values);
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
		$request = Request::getInstance();

		// check if settings defined
		if($viewManager->isType(ViewManager::TREE_OVERVIEW))
		{
			$newsLetterSettings = $this->plugin->getObject(NewsLetter::TYPE_SETTINGS);
			$key = array('tree_id' => intval($request->getValue('tree_id')), 'tag' => $request->getValue('tag'));
			if(!$newsLetterSettings->exists($key)) $viewManager->setType(NewsLetter::VIEW_CONFIG);
		}

		switch($viewManager->getType())
		{
			case NewsLetter::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW : $this->handleTreeNewGet(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditGet(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeleteGet(); break;
			case NewsLetter::VIEW_FILE_OVERVIEW : 
			case NewsLetter::VIEW_FILE_NEW : 
			case NewsLetter::VIEW_FILE_EDIT : 
			case NewsLetter::VIEW_FILE_IMPORT : 
			case NewsLetter::VIEW_FILE_DELETE : $this->handleObjectGet(NewsLetter::TYPE_ATTACHMENT); break;
			case NewsLetter::VIEW_GROUP_OVERVIEW : 
			case NewsLetter::VIEW_GROUP_NEW : 
			case NewsLetter::VIEW_GROUP_EDIT : 
			case NewsLetter::VIEW_GROUP_USER : 
			case NewsLetter::VIEW_GROUP_DELETE : $this->handleObjectGet(NewsLetter::TYPE_GROUP); break;
			case NewsLetter::VIEW_USER_OVERVIEW : 
			case NewsLetter::VIEW_USER_NEW : 
			case NewsLetter::VIEW_USER_EDIT : 
			case NewsLetter::VIEW_USER_UNSUBSCRIBE : 
			case NewsLetter::VIEW_USER_DELETE : $this->handleObjectGet(NewsLetter::TYPE_USER); break;
			case NewsLetter::VIEW_TAG_EDIT : 
			case NewsLetter::VIEW_TAG_DELETE : $this->handleObjectGet(NewsLetter::TYPE_TAG); break;
			case NewsLetter::VIEW_PLUGIN_OVERVIEW : 
			case NewsLetter::VIEW_PLUGIN_CONFIG : 
			case NewsLetter::VIEW_PLUGIN_EDIT : 
			case NewsLetter::VIEW_PLUGIN_MOVE : 
			case NewsLetter::VIEW_PLUGIN_DELETE : $this->handleObjectGet(NewsLetter::TYPE_PLUGIN); break;
			case NewsLetter::VIEW_PREVIEW : $this->handlePreviewGet(); break;
			case NewsLetter::VIEW_SEND : $this->handleSendGet(); break;
			case NewsLetter::VIEW_CONFIG : $this->handleObjectGet(NewsLetter::TYPE_SETTINGS); break;
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
			case NewsLetter::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW :  $this->handleTreeNewPost(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditPost(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeletePost(); break;
			case NewsLetter::VIEW_FILE_OVERVIEW : 
			case NewsLetter::VIEW_FILE_NEW : 
			case NewsLetter::VIEW_FILE_EDIT : 
			case NewsLetter::VIEW_FILE_IMPORT : 
			case NewsLetter::VIEW_FILE_DELETE : $this->handleObjectPost(NewsLetter::TYPE_ATTACHMENT); break;
			case NewsLetter::VIEW_GROUP_OVERVIEW : 
			case NewsLetter::VIEW_GROUP_NEW : 
			case NewsLetter::VIEW_GROUP_EDIT : 
			case NewsLetter::VIEW_GROUP_USER : 
			case NewsLetter::VIEW_GROUP_DELETE : $this->handleObjectPost(NewsLetter::TYPE_GROUP); break;
			case NewsLetter::VIEW_USER_OVERVIEW : 
			case NewsLetter::VIEW_USER_NEW : 
			case NewsLetter::VIEW_USER_EDIT : 
			case NewsLetter::VIEW_USER_UNSUBSCRIBE : 
			case NewsLetter::VIEW_USER_DELETE : $this->handleObjectPost(NewsLetter::TYPE_USER); break;
			case NewsLetter::VIEW_TAG_EDIT : 
			case NewsLetter::VIEW_TAG_DELETE : $this->handleObjectPost(NewsLetter::TYPE_TAG); break;
			case NewsLetter::VIEW_PLUGIN_OVERVIEW : 
			case NewsLetter::VIEW_PLUGIN_CONFIG : 
			case NewsLetter::VIEW_PLUGIN_EDIT : 
			case NewsLetter::VIEW_PLUGIN_MOVE : 
			case NewsLetter::VIEW_PLUGIN_DELETE : $this->handleObjectPost(NewsLetter::TYPE_PLUGIN); break;
			case NewsLetter::VIEW_PREVIEW : $this->handlePreviewPost(); break;
			case NewsLetter::VIEW_SEND : $this->handleSendPost(); break;
			case NewsLetter::VIEW_CONFIG : $this->handleObjectPost(NewsLetter::TYPE_SETTINGS); break;
			default : $this->handleOverviewPost(); break;
		}

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

		$values = $request->getRequest(Request::POST);

		// retrieve tags that are linked to this plugin
		$taglist = $this->plugin->getTagList(array('plugin_type' => NewsLetter::TYPE_DEFAULT));
		if(!$taglist) return;

		$objSettings = $this->plugin->getObject(NewsLetter::TYPE_SETTINGS);
		$objUser = $this->plugin->getObject(NewsLetter::TYPE_USER);
		$objGroup = $this->plugin->getObject(NewsLetter::TYPE_GROUP);

		$group = is_array($request->getValue('group')) ? array_keys($request->getValue('group')) : array();


		foreach($taglist as $tag)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$template->setPostfix($tag['tag']);
			$template->setCacheable(true);

			// check if template is in cache
			if(!$template->isCached())
			{
				// get settings
				$globalSettings = $this->plugin->getSettings();

				$settings = array_merge($globalSettings, $objSettings->getSettings($tag['tree_id'], $tag['tag']));
				$template->setVariable('settings',  $settings);

				$searchcriteria = array('tree_id' => $tag['tree_id'], 'tag' => $tag['tag']);
				$groupList = $objGroup->getList($searchcriteria);
				$template->setVariable('groupList',  $groupList);

				$template->setVariable('cbo_gender',  $groupList);
				$template->setVariable('cbo_gender', Utils::getHtmlCombo($objUser->getGenderTypeList(), array_key_exists('gender', $values) ? $values['gender'] : ''));
				$template->setVariable('name', array_key_exists('name', $values) ? $values['name'] : '');
				$template->setVariable('email', array_key_exists('email', $values) ? $values['email'] : '');
				$template->setVariable($searchcriteria);
			}
			$template->setVariable('group',  $group);
			$this->template[$tag['tag']] = $template;
		}
	}

	/**
	 * handle overview request
	*/
	private function handleOverviewPost()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		// retrieve tags that are linked to this plugin
		if(!$request->exists('tag')) throw new Exception('Tag not defined');
		$tag = $request->getValue('tag');
		$tree_id = $this->director->tree->getCurrentId();

		// get objects
		$objSettings = $this->plugin->getObject(NewsLetter::TYPE_SETTINGS);
		$objUser = $this->plugin->getObject(NewsLetter::TYPE_USER);
		$objGroup = $this->plugin->getObject(NewsLetter::TYPE_GROUP);

		// get settings
		$globalSettings = $this->plugin->getSettings();
		$settings = array_merge($globalSettings, $objSettings->getSettings($tree_id, $tag));

		//$mailfrom = $settings['mailfrom'];

		try 
		{
			$values = $request->getRequest(Request::POST);
			$values['tree_id'] = $tree_id;
			$values['tag'] = $tag;

			$ip = $request->getValue('REMOTE_ADDR', Request::SERVER);
			$values['active']	= true;
			$values['ip']			= $ip;
			$values['host']   = gethostbyaddr($ip);
			$values['client'] = $request->getValue('HTTP_USER_AGENT', Request::SERVER);
			$values['optin'] = ($settings['action'] == NewsLetterSettings::ACTION_OPTIN) ? md5(session_id().time()) : '';
			
			$group = is_array($request->getValue('group')) ? array_keys($request->getValue('group')) : array();

			$id = $objUser->insert($values);
			foreach($group as $item)
			{
				$objUser->addGroup($id, array('id' => $item));
			}

			$settings['optin_key'] = $values['optin'];

			if($settings['action'])
			{
				$info = array_merge($settings, $values);
				//add intro / activation text to email
				$tplContent = $this->composeText($info);

				$this->sendMail($values['email'], $this->director->getConfig()->email_address, $settings['subject'], $tplContent->fetch(), NULL, false);
			}

			$location = $settings['ref_tree_id'] ? $this->director->tree->getPath($settings['ref_tree_id']) : '/';
			header("Location: $location");
			exit;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('newsLetterErrorMessage',  $e->getMessage(), false);

			$this->handleOverviewGet();
		}
	}
	//}}}

/*------- detail request {{{ -------*/
	/**
	 * handle detail request
	*/
	private function handleDetail()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		// process attachments
		$attachment = $this->plugin->getObject(NewsLetter::TYPE_ATTACHMENT);
		$attachment->handleHttpGetRequest();

		// clear subtitle
		$view->setName('');

		// check security
		if(!$request->exists('id')) throw new Exception('NewsLetter item is missing.');
		$id = intval($request->getValue('id'));
		$key = array('id' => $id, 'activated' => true);

		if(!$this->exists($key)) throw new HttpException('404');
		$detail = $this->getDetail($key);


		// check if tree node of news item is accessable
		$tree = $this->director->tree;
		if(!$tree->exists($detail['tree_id'])) throw new HttpException('404');

		// process request
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setPostfix($detail['tag']);
		// disable cache because we want to count visits
		$template->setCacheable(false);
		Cache::disableCache();

		// update view counter
		$this->updateCount($key);

		// overwrite default naming
		$template->setVariable('pageTitle',  $detail['name'], false);

		// add breadcrumb item
		$url = new Url(true);
		$url->clearParameter('id');

		$breadcrumb = array('name' => $detail['name'], 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		// check if template is in cache
		/*
		if(!$template->isCached())
		{
			$template->setVariable('news',  $detail, false);
		}
		*/
		$template->setVariable('news',  $detail, false);

		$settings = $this->plugin->getObject(NewsLetter::TYPE_SETTINGS);
		$treeSettings = $settings->getSettings($detail['tree_id'], $detail['tag']);
		$template->setVariable('newssettings',  $treeSettings, false);

		// get settings
		if($treeSettings['comment'])
		{
			// process comments
			$comment = $this->getComment();
			$comment->setSettings($treeSettings);
			$comment->handleHttpGetRequest();
		}


		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter($view->getUrlId(), ViewManager::OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$detail['tag']] = $template;

	} 
//}}}

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

		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);

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

		$url = new Url(true);
		$url->clearParameter('id');
		$url->clearParameter('nl_id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);


		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::TREE_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_conf = clone $url;
		$url_conf->setParameter($view->getUrlId(), NewsLetter::VIEW_CONFIG);
		$template->setVariable('href_conf',  $url_conf->getUrl(true), false);

		$url_user = clone $url;
		$url_user->setParameter($view->getUrlId(), NewsLetter::VIEW_USER_OVERVIEW);
		$template->setVariable('href_user',  $url_user->getUrl(true), false);

		$url_group = clone $url;
		$url_group->setParameter($view->getUrlId(), NewsLetter::VIEW_GROUP_OVERVIEW);
		$template->setVariable('href_group',  $url_group->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::TREE_DELETE);

		$url_att = clone $url;
		$url_att->setParameter($view->getUrlId(), NewsLetter::VIEW_FILE_OVERVIEW);

		$url_preview = clone $url;
		$url_preview->setParameter($view->getUrlId(), NewsLetter::VIEW_PREVIEW);

		$url_send = clone $url;
		$url_send->setParameter($view->getUrlId(), NewsLetter::VIEW_SEND);

		$url_plugin = clone $url;
		$url_plugin->setParameter($view->getUrlId(), NewsLetter::VIEW_PLUGIN_OVERVIEW);

		$list = $this->getList($searchcriteria, $pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);
			$url_att->setParameter('nl_id', $item['id']);
			$url_plugin->setParameter('nl_id', $item['id']);
			$url_preview->setParameter('nl_id', $item['id']);
			$url_send->setParameter('nl_id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
			$item['href_att'] = $url_att->getUrl(true);
			$item['href_plugin'] = $url_plugin->getUrl(true);
			$item['href_preview'] = $url_preview->getUrl(true);
			$item['href_send'] = $url_send->getUrl(true);

			if($item['thumbnail'])
			{
				$img = new Image($item['thumbnail'], $this->plugin->getContentPath(true));
				$item['thumbnail'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
			}
		}
		$template->setVariable('list',  $list, false);
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

		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

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
		$fields['image'] = '';

		$this->handleTreeSettings($template);

		$template->setVariable($fields);
		$template->clearVariable('id');

		// get theme list
		$themeManager = new ThemeManager();
		$searchcriteria = array('active' => true);
		$themelist = $themeManager->getList($searchcriteria);
		$template->setVariable('cbo_theme', Utils::getHtmlCombo($themelist['data'], $fields['theme_id']));

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
			$this->handleTreeOverview();
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

		if(!$request->exists('id')) throw new Exception('Newsletter is missing.');
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
			$fields['image'] = $detail['image'];
		}

		$this->setFields($fields);


		if($fields['image'])
		{
			$img = new Image($fields['image'], $this->plugin->getContentPath(true));
			$fields['image'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
		}

		$template->setVariable($fields);

		// get theme list
		$themeManager = new ThemeManager();
		$searchcriteria = array('active' => true);
		$themelist = $themeManager->getList($searchcriteria);
		$template->setVariable('cbo_theme', Utils::getHtmlCombo($themelist['data'], $fields['theme_id']));

		$this->handleTreeSettings($template);

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
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
		}

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleTreeEditPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			if(!$request->exists('id')) throw new Exception('Newsletter is missing.');
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

		if(!$request->exists('id')) throw new Exception('Newsletter is missing.');
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
			if(!$request->exists('id')) throw new Exception('Newsletter is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->delete($key);

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

/*------- handle navigation for sub classes / pages {{{ -------*/
	/**
	 * handle navigation for sub classes / pages
	*/
	private function handleSubNavigation($addBreadcrumb=true)
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$template =  new TemplateEngine();

		if(!$request->exists('nl_id')) return;

		$nl_id = $request->getValue('nl_id');
		$newsLetterName = $this->getName(array('id' => $nl_id));
		$template->setVariable('pageTitle', $newsLetterName, false);

		$tree_id = $request->getValue('tree_id');
		$tag = $request->getValue('tag');
		$template->setVariable('tree_id', $tree_id, false);
		$template->setVariable('tag', $tag, false);
		$template->setVariable('nl_id', $nl_id, false);

		if(!$addBreadcrumb) return;

		$url = new Url(true);
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);
		$url->setParameter('id', $nl_id);
		$url->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);
		$breadcrumb = array('name' => $newsLetterName, 'path' => $url->getUrl(true));

		$this->director->theme->addBreadcrumb($breadcrumb);
	}
//}}}

/*------- handle object requests {{{ -------*/

	/**
	 * handle object get request
	*/
	private function handleObjectGet($objectType)
	{
		$this->handleSubNavigation(false);
		$obj = $this->plugin->getObject($objectType);
		$obj->handleHttpGetRequest();
	}

	/**
	 * handle object post request
	*/
	private function handleObjectPost($objectType)
	{
		$this->handleSubNavigation(false);
		$obj = $this->plugin->getObject($objectType);
		$obj->handleHttpPostRequest();
	}

//}}}

/*------- newsletter send / view requests {{{ -------*/

	/**
	 * handle file 
	*/
	private function handlePreviewGet()
	{
		$request = Request::getInstance();
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$this->handleSubNavigation(false);

		if(!$request->exists('nl_id')) throw new Exception(__CLASS__."::".__FUNCTION__." : nl_id parameter is missing");
		$key = array('id' => intval($request->getValue('nl_id')));

		if(!$this->exists($key)) throw new Exception("Newsletter does not exist");
		$detail = $this->getDetail($key);

		$template->setVariable('email', $request->getValue('email'));

		$this->director->theme->handleAdminLinks($template, $detail['name']);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handlePreviewPost()
	{
		$request = Request::getInstance();

		try
		{
			if(!$request->exists('nl_id')) throw new Exception(__CLASS__."::".__FUNCTION__." : nl_id parameter is missing");
			$key = array('id' => $request->getValue('nl_id'));

			$sendmail = $request->exists('send');

			$email = $request->getValue('email');
			if($sendmail && !Utils::isEmail($email)) throw new Exception("Invalid email address specified");

			if(!$this->exists($key)) throw new HttpException('404');
			$detail = $this->getDetail($key);

			$user = array('gender_description' => NewsLetterUser::getGenderTypeDesc(NewsLetterUser::GENDER_MALE),
										'name'							=> 'Test Recipient',
										'email' 						=> $email ?  $email : $this->director->getConfig()->email_address);

			$systemSite = new SystemSite();
			$tree = $systemSite->getTree();

			$url_unsubscribe = new Url();
			$url_unsubscribe->setPath($request->getProtocol().$request->getDomain().$tree->getPath($detail['tree_id']));
			$url_unsubscribe->setParameter(ViewManager::URL_KEY_DEFAULT, NewsLetter::VIEW_USER_UNSUBSCRIBE);
			$url_unsubscribe->setParameter(NewsLetterUser::KEY_UNSUBSCRIBE, $user['email']);

			$parameters = array('user' => $user,
													'href_unsubscribe' => $url_unsubscribe->getUrl(true));

			$key = array('nl_id' => $key['id'], 'activated' => true);
			$theme =  $this->getNewsLetterTheme($key, $detail);
			$this->handleThemePostProcessing($theme, $parameters);

			$content = $theme->fetchTheme();

			if($sendmail) 
			{
				$objAttachment = $this->plugin->getObject(NewsLetter::TYPE_ATTACHMENT);
				$attachments = $objAttachment->getAttachmentList($key);
				$this->sendMail(array($email), $this->director->getConfig()->email_address, $detail['name'], $content, $attachments);
				// reset theme settings
				$this->director->theme->handlePreProcessing($this->director);

				$view = ViewManager::getInstance();
				$view->setType(ViewManager::TREE_OVERVIEW);
				$this->handleHttpGetRequest();
			}
			else
			{
				echo $content;
				exit;
			}
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handlePreviewGet();
		}
	}

	/**
	 * handle file 
	*/
	private function handleSendGet()
	{
		$request = Request::getInstance();
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$this->handleSubNavigation(false);

		if(!$request->exists('nl_id')) throw new Exception(__CLASS__."::".__FUNCTION__." : nl_id parameter is missing");
		$key = array('id' => intval($request->getValue('nl_id')));

		$groups = is_array($request->getValue('groups')) ? array_keys($request->getValue('groups')) : array();

		// retrieve newsletter
		if(!$this->exists($key)) throw new HttpException('404');
		$detail = $this->getDetail($key);
		$template->setVariable($detail);

		// retrieve user groups
		$key = array('tree_id' => $request->getValue('tree_id'), 'tag' => $request->getValue('tag'));
		$objGroup = $this->plugin->getObject(NewsLetter::TYPE_GROUP);
		$grouplist = $objGroup->getList($key);
		foreach($grouplist['data'] as &$item)
		{
			$item['selected'] = in_array($item['id'], $groups);
		}

		$template->setVariable('grouplist', $grouplist);

		$this->director->theme->handleAdminLinks($template, $detail['name']);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleSendPost()
	{
		$request = Request::getInstance();

		try
		{
			if(!$request->exists('nl_id')) throw new Exception(__CLASS__."::".__FUNCTION__." : nl_id parameter is missing");
			$nl_id = intval($request->getValue('nl_id'));
			$key = array('id' => $nl_id, 'activated' => true);

			$groups = is_array($request->getValue('groups')) ? array_keys($request->getValue('groups')) : array();

			if(!$this->exists($key)) throw new HttpException('404');
			$detail = $this->getDetail($key);

			// retrieve users
			$userKey = array('tree_id' => intval($request->getValue('tree_id')), 'tag' => $request->getValue('tag'), 'activated' => true);
			if($groups) $userKey['grp_id'] = $groups;

			$objUser = $this->plugin->getObject(NewsLetter::TYPE_USER);
			$userlist = $objUser->getList($userKey);

			$refKey = array('nl_id' => $nl_id, 'activated' => true);

			// retrieve attachments
			$objAttachment = $this->plugin->getObject(NewsLetter::TYPE_ATTACHMENT);
			$attachments = $objAttachment->getAttachmentList($refKey);

			// prepare newsletter
			$theme =  $this->getNewsLetterTheme($refKey, $detail);

			$mailfrom = $this->director->getConfig()->email_address;
			foreach($userlist['data'] as $item)
			{
				// add user data to newsletter
				$parameters = array('user' => $item);

				// generate newlsetter for each recipient
				$this->handleThemePostProcessing($theme, $parameters);

				// send newsletter
				$content = $theme->fetchTheme();
				$this->sendMail(array($item['email']), $mailfrom,  $detail['name'], $content, $attachments);
			}

			// TODO update counters
			$objGroup = $this->plugin->getObject(NewsLetter::TYPE_GROUP);
			$objUser->updateCount($userKey);
			$objGroup->updateCount($userKey);

			$userCount = $objUser->getCount($userKey);
			$this->updateCount($key, $userCount);

			// display success page
			$view = ViewManager::getInstance();
			$view->setType(NewsLetter::VIEW_SEND_SUCCESS);

			// reset theme settings
			$this->director->theme->handlePreProcessing($this->director);

			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$this->director->theme->handleAdminLinks($template, $detail['name']);
			$template->setVariable($detail);
			$this->handleSubNavigation(false);
			$this->template[$this->director->theme->getConfig()->main_tag] = $template;

		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleSendGet();
		}
	}

	private function handleThemePostProcessing($theme, $parameters=NULL)
	{
		$template = $theme->getTemplate();
		$template->setVariable($parameters, NULL, false);

		foreach($this->newsLetterTemplate as $key=>$value)
		{
			$template->setVariable($key, $value, false); 
		}
		$theme->handlePostProcessing();
	}

	private function getNewsLetterTheme($key, $newsLetter, $parameters=NULL)
	{
		$objTag = $this->plugin->getObject(NewsLetter::TYPE_TAG);
		$objPlugin = $this->plugin->getObject(NewsLetter::TYPE_PLUGIN);
		$objAttachment = $this->plugin->getObject(NewsLetter::TYPE_ATTACHMENT);
		$pluginManager = $this->director->pluginManager;

		// use fully qualified paths
		Director::setAppendDomain(true);

		$theme = $this->director->themeManager->getThemeFromId(array('id' => $newsLetter['theme_id']));
		if(!$theme) throw new Exception("Theme does not exists. Select theme in Config section first.");
		$theme->handlePreProcessing($this->director);

		$template = $theme->getTemplate();
		$template->setVariable($parameters, NULL, false);

		$pluginlist= $objPlugin->getList($key);
		foreach($pluginlist['data'] as $item)
		{
			switch($item['type'])
			{
				case NewsLetterPlugin::TYPE_CODE : 
				case NewsLetterPlugin::TYPE_CODE_HEADER : 
				case NewsLetterPlugin::TYPE_CODE_FOOTER : 
					$code = new TemplateEngine($item['text'], false); 
					// buffer dynamic template so variable can be replaced for each user
					$this->newsLetterTemplate[$item['nl_tag']] = $code;
					break;
				case NewsLetterPlugin::TYPE_TEXT : $template->setVariable($item['nl_tag'], $item['text'], false); break;
				case NewsLetterPlugin::TYPE_PLUGIN : 
					if(!$item['plugin_keys']) break;
					$tmp = $pluginManager->getPluginFromId(array('id' => $item['plugin_id']));
					$parameters = array('pluginType' => $item['plugin_type'],
															'requestType'	=> PluginProvider::TYPE_LIST,
															'tag'					=> $item['nl_tag'],
															'parameters'	=> array('keys' => unserialize($item['plugin_keys'])));
					$this->director->callObjectsImplementing('PluginProvider', 'handlePluginRequest', $parameters);
					break;
			}
		}
		$this->director->callObjectsImplementing('PluginProvider', 'renderPluginRequest', array($theme));

		// buffer replacement templates
		$this->newsLetterTemplate = array_merge($this->newsLetterTemplate, $objTag->getReplacementTemplates($key, $theme));

		return $theme;
	}

	private function sendMail($mailto, $mailfrom, $subject, $message, $attachments=NULL, $htmlMessage=true)
	{
		$mail = new PHPMailer();
		$mail->IsHTML($htmlMessage);
		$mail->From = $mailfrom;
		$mail->FromName = $mailfrom;

		if(is_array($mailto))
		{
			foreach($mailto as $email)
			{
				$mail->AddAddress(trim($email));
			}
		}
		else
			$mail->AddAddress(trim($mailto));

		$mail->WordWrap = 80;
		$mail->Subject = $subject;
		$mail->Body = $message;

		if(isset($attachments) && is_array($attachments))
		{
			foreach($attachments as $item)
			{
				$mail->AddAttachment($item['file'], $item['name'], 'base64', $item['mime']);
			}
		}

		if(!$mail->Send()) throw new Exception("Error sending message: ".$mail->ErrorInfo);
		//if(!$this->log->isInfoEnabled()) return;
		//$this->log->info("notification '$subject' sent to $recepients");

	}

	private function composeText($settings)
	{
		$template = new TemplateEngine($settings['mailtext'], false);
		$template->setVariable($settings);

		$view = ViewManager::getInstance();
		$request = Request::getInstance();
		// if multiple sites are active, refer to the current site. otherwise link can fail if other site is default
		$siteGroup = $this->director->siteManager->systemSite->getSiteGroup();

		// parse introduction text
		$url = new Url(true);
		$url->setParameter($view->getUrlId(), NewsLetter::VIEW_OPTIN);
		$url->setParameter('key', $settings['optin_key']);
		$url->setParameter(SystemSiteGroup::CURRENT_ID_KEY, $siteGroup->getCurrentId());

		$template->setVariable('optin_url',  $request->getProtocol().$request->getDomain().$url->getUrl(false));
		return $template;
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

		// parse rpc javascript to set variables
		$rpcfile_src = $this->plugin->getHtdocsPath(true)."js/rpc.js.in";
		$rpcfile_dest = $this->plugin->getCachePath(true)."rpc.js";
		$theme->parseFile($rpcfile_src, $rpcfile_dest);

		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_lib.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_wrappers.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.$this->plugin->getCachePath().'rpc.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
