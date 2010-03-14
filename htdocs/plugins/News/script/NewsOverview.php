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
require_once('NewsTreeRef.php');

/**
 * Main configuration 
 * @package Common
 */
class NewsOverview extends Observer
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
	 * @var News
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
		$this->templateFile = "newsoverview.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('news', 'a');
		$this->sqlParser->addField(new SqlField('a', 'news_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'news_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'news_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'news_online', 'online', 'Online datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'news_offline', 'offline', 'Offline datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'news_date', 'date', 'Date', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'news_name', 'name', 'Name', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'news_intro', 'intro', 'Introduction', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'news_text', 'text', 'Content', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'news_thumbnail', 'thumbnail', 'Thumbnail', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'news_image', 'image', 'Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'news_img_x', 'img_x', 'Offset x', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_img_y', 'img_y', 'Offset y', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_img_width', 'img_width', 'Offset width', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_img_height', 'img_height', 'Offset height', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_count', 'count', 'Visits', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'news_usr_id', 'usr_id', 'User', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'news_own_id', 'own_id', 'Owner', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'news_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'news_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->orderStatement = array('order by a.news_online desc, a.news_id desc');
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
		
		// update treeref
		if($tree_id != $new_tree_id)
		{
			$treeRef = new NewsTreeRef();
			$treeRef->updateRefTreeId($tree_id, $new_tree_id);
		}

		// update settings
		$settings = $this->plugin->getObject(News::TYPE_SETTINGS);
		$settings->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
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
		$treeRef = new NewsTreeRef();
		$treeRef->updateRefTreeId($sourceNodeId, $destinationNodeId);

		// update settings
		$settings = $this->plugin->getObject(News::TYPE_SETTINGS);
		$settings->updateTreeId($tag, $tree_id, $newTag);
	}

	public function updateCount($key)
	{
		$request = Request::getInstance();
		$count = $request->exists('newscount', Request::SESSION) ? $request->getValue('newscount', Request::SESSION) : array();
		
		// check if already counted
		if(in_array($key['id'], $count)) return;

		$query = sprintf("update news set news_count = news_count+1 where news_id = %d", $key['id']);
		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$count[] = $key['id'];
		$request->setValue('newscount', $count);
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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.news_id', $value, '<>')); break;
				case 'archiveonline' : 
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.news_online)', $value, '>'));
					break;
				case 'archiveoffline' : 
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.news_offline)', $value, '<='));
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.news_offline)', 0, '>'));
					break;
				case 'activated' : 
					// only active pages
					$SqlParser->addCriteria(new SqlCriteria('a.news_active', 1));

					// only pages that are online
					$SqlParser->addCriteria(new SqlCriteria('a.news_online', 'now()', '<='));

					$offline = new SqlCriteria('a.news_offline', 'now()', '>');
					$offline->addCriteria(new SqlCriteria('unix_timestamp(a.news_offline)', 0, '='), SqlCriteria::REL_OR);
					$SqlParser->addCriteria($offline); 
					break;
				case 'search' : 
					$search = new SqlCriteria('a.news_name', "%$value%", 'like'); 
					$search->addCriteria(new SqlCriteria('a.news_intro', "%$value%", 'like'), SqlCriteria::REL_OR); 
					$search->addCriteria(new SqlCriteria('a.news_text', "%$value%", 'like'), SqlCriteria::REL_OR); 
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
			case 'online' : return strftime('%Y-%m-%d'); break;
			case 'date' : return strftime('%Y-%m-%d'); break;
			case 'offline' : return strftime('%Y-%m-%d', mktime(0,0,0,date('m')+2)); break;
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
		$fields['date'] = (array_key_exists('date', $fields) && $fields['date']) ? strftime('%Y-%m-%d',strtotime($fields['date'])) : '';

		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$date = mktime(0,0,0);

		$activated = 1;

		// hide newsitem if not active
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
		$newsSettings = $this->plugin->getObject(News::TYPE_SETTINGS);
		$globalSettings = $this->plugin->getSettings();
		$settings = array_merge($globalSettings, $newsSettings->getSettings($values['tree_id'], $values['tag']));

		$destWidth = $settings['image_width'];
		$destHeight = $settings['image_height'];
		$maxWidth = $settings['image_max_width'];

		// user global if tree values are not set
		if(!$destWidth) $destWidth = $globalSettings['image_width'];
		if(!$destHeight) $destHeight = $globalSettings['image_height'];
		if(!$maxWidth) $maxWidth = $globalSettings['image_max_width'];

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
		$query = sprintf("update news set news_image= '%s', news_thumbnail = '%s', news_img_x = %d, news_img_y = %d, news_img_width = %d, news_img_height = %d where news_id = %d", 
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
		$query = "update news set news_image = '', news_thumbnail = '' where news_id = {$id['id']}";
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	protected function handlePostUpdate($id, $values)
	{
		$this->insertImage($id, $values);
	}

	protected function handlePreDelete($id, $values)
	{
		$attachment = $this->plugin->getObject(News::TYPE_ATTACHMENT);
		$search = array('news_id' => $id['id']);
		$list = $attachment->getList($search);
		foreach($list['data'] as $item)
		{
			$attachment->delete(array('id' => $item['id']));
		}

		$image = $this->plugin->getObject(News::TYPE_IMAGE);
		$search = array('news_id' => $id['id']);
		$list = $image->getList($search);
		foreach($list['data'] as $item)
		{
			$image->delete(array('id' => $item['id']));
		}

		$comment = $this->plugin->getObject(News::TYPE_COMMENT);
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

		// check if settings defined
		if($viewManager->isType(ViewManager::TREE_OVERVIEW))
		{
			$request = Request::getInstance();
			$settings = $this->plugin->getObject(News::TYPE_SETTINGS);
			$key = array('tree_id' => intval($request->getValue('tree_id')), 'tag' => $request->getValue('tag'));
			if(!$settings->exists($key)) $viewManager->setType(News::VIEW_CONFIG);
		}

		switch($viewManager->getType())
		{
			case News::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW : $this->handleTreeNewGet(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditGet(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeleteGet(); break;
			case News::VIEW_FILE_OVERVIEW : 
			case News::VIEW_FILE_NEW : 
			case News::VIEW_FILE_EDIT : 
			case News::VIEW_FILE_IMPORT : 
			case News::VIEW_FILE_DELETE : $this->handleObjectGet(News::TYPE_ATTACHMENT); break;
			case News::VIEW_IMAGE_OVERVIEW : 
			case News::VIEW_IMAGE_NEW : 
			case News::VIEW_IMAGE_EDIT : 
			case News::VIEW_IMAGE_IMPORT : 
			case News::VIEW_IMAGE_RESIZE : 
			case News::VIEW_IMAGE_DELETE : $this->handleObjectGet(News::TYPE_IMAGE); break;
			case News::VIEW_COMMENT_OVERVIEW : 
			case News::VIEW_COMMENT_EDIT : 
			case News::VIEW_COMMENT_DELETE : $this->handleObjectGet(News::TYPE_COMMENT); break;
			case News::VIEW_CONFIG : $this->handleObjectGet(News::TYPE_SETTINGS); break;
			default : $this->handleOverview(); break;
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
			case News::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW :  $this->handleTreeNewPost(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditPost(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeletePost(); break;
			case News::VIEW_FILE_OVERVIEW : 
			case News::VIEW_FILE_NEW : 
			case News::VIEW_FILE_EDIT : 
			case News::VIEW_FILE_IMPORT : 
			case News::VIEW_FILE_DELETE : $this->handleObjectPost(News::TYPE_ATTACHMENT); break;
			case News::VIEW_IMAGE_OVERVIEW : 
			case News::VIEW_IMAGE_NEW : 
			case News::VIEW_IMAGE_EDIT : 
			case News::VIEW_IMAGE_IMPORT : 
			case News::VIEW_IMAGE_RESIZE : 
			case News::VIEW_IMAGE_DELETE : $this->handleObjectPost(News::TYPE_IMAGE); break;
			case News::VIEW_COMMENT_OVERVIEW : 
			case News::VIEW_COMMENT_EDIT : 
			case News::VIEW_COMMENT_DELETE : $this->handleObjectPost(News::TYPE_COMMENT); break;
			case News::VIEW_CONFIG : $this->handleObjectPost(News::TYPE_SETTINGS); break;
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
		$obj = $this->plugin->getObject(News::TYPE_IMAGE);
		$obj->handleCliRequest($cliServer);
	}

//}}}

/*------- handle plugin request {{{ -------*/
	/**
	 * handle plugin request
	*/
	public function handlePluginRequest($requestType, $templateTag, $parameters=NULL)
	{
		switch($requestType)
		{
			case PluginProvider::TYPE_SELECT : $this->handlePluginSelect($templateTag, $parameters); break;
			case PluginProvider::TYPE_LIST : $this->handlePluginList($templateTag, $parameters); break;
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
		$taglist = $this->plugin->getTagList(array('plugin_type' => News::TYPE_DEFAULT));
		if(!$taglist) return;

		$url = new Url(true); 
		$url->setParameter($view->getUrlId(), News::VIEW_DETAIL);

		$newsSettings = $this->plugin->getObject(News::TYPE_SETTINGS);

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

				$settings = array_merge($globalSettings, $newsSettings->getSettings($tag['tree_id'], $tag['tag']));
				$template->setVariable('settings',  $settings);

				// parse stylesheet
				if($settings['template'])
				{
					$template->setFile($settings['template']);
					$template->setIncludeFile(false);
				}

				$pagesize = ($settings['rows']) ? $settings['rows'] : $globalSettings['rows'];

				$searchcriteria = array('tree_id' 	=> $tag['tree_id'], 
																'tag' 			=> $tag['tag'], 
																'activated' 		=> true);

				$list = $this->getList($searchcriteria, $pagesize, $page);
				if(!$list['data']) continue;
				foreach($list['data'] as &$item)
				{
					$url->setParameter('id', $item['id']);

					$item['href_detail'] = $url->getUrl(true);
					if($item['thumbnail'])
					{
						$img = new Image($item['thumbnail'], $this->plugin->getContentPath(true));
						$item['thumbnail'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
					}

					// retrieve attachments
					if($settings['display'] == News::DISP_FULL)
					{
						$attachmentTag = 'newsattachment';
						$attachment = $this->plugin->getObject(News::TYPE_ATTACHMENT);
						$item[$attachmentTag] = $attachment->getAttachmentTemplate($item['id'], $attachmentTag);

						$imageTag = 'template_newsimage';
						$image = $this->plugin->getObject(News::TYPE_IMAGE);
						$item[$imageTag] = $image->getImageTemplate($item['id'], $imageTag);
					}
				}
				$template->setVariable('news',  $list);
			}
			$this->template[$tag['tag']] = $template;
		}
	} //}}}

/*------- detail request {{{ -------*/
	/**
	 * handle detail request
	*/
	private function handleDetail()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		// process attachments
		$attachment = $this->plugin->getObject(News::TYPE_ATTACHMENT);
		$attachment->handleHttpGetRequest();

		// process images
		$image = $this->plugin->getObject(News::TYPE_IMAGE);
		$image->handleHttpGetRequest();

		// clear subtitle
		$view->setName('');

		// check security
		if(!$request->exists('id')) throw new Exception('News id is missing.');
		$id = intval($request->getValue('id'));
		$key = array('id' => $id, 'activated' => true);

		if(!$this->exists($key)) throw new HttpException('404');
		$detail = $this->getDetail($key);


		// check if tree node of news item is accessable
		$tree = $this->director->tree;
		if(!$tree->exists($detail['tree_id'])) throw new HttpException('404');

		if($detail['thumbnail'])
		{
			$img = new Image($detail['thumbnail'], $this->plugin->getContentPath(true));
			$detail['thumbnail'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
		}

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
		$template->setVariable('news',  $detail, false);

		$objSettings = $this->plugin->getObject(News::TYPE_SETTINGS);
		$settings = $objSettings->getSettings($detail['tree_id'], $detail['tag']);
		$template->setVariable('newssettings',  $settings, false);

		// get settings
		if($settings['comment'])
		{
			// process comments
			$comment = $this->plugin->getObject(News::TYPE_COMMENT);
			$comment->setSettings($settings);
			$comment->handleHttpGetRequest();
		}

		if($settings['stylesheet'])
		{
			$theme = $this->director->theme;
			$theme->addStylesheet($theme->fetchFile($settings['stylesheet']));
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

		if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
		if(!$request->exists('tag')) throw new Exception('Tag ontbreekt.');

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('tree_id' => $tree_id, 'tag' => $tag);
		$template->setVariable($key);

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
		$url->clearParameter('news_id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);


		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::TREE_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_conf = clone $url;
		$url_conf->setParameter($view->getUrlId(), News::VIEW_CONFIG);
		$template->setVariable('href_conf',  $url_conf->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::TREE_DELETE);

		$url_att = clone $url;
		$url_att->setParameter($view->getUrlId(), News::VIEW_FILE_OVERVIEW);

		$url_com = clone $url;
		$url_com->setParameter($view->getUrlId(), News::VIEW_COMMENT_OVERVIEW);

		$url_img = clone $url;
		$url_img->setParameter($view->getUrlId(), News::VIEW_IMAGE_OVERVIEW);

		$list = $this->getList($searchcriteria, $pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);
			$url_att->setParameter('news_id', $item['id']);
			$url_com->setParameter('news_id', $item['id']);
			$url_img->setParameter('news_id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
			$item['href_att'] = $url_att->getUrl(true);
			$item['href_com'] = $url_com->getUrl(true);
			$item['href_img'] = $url_img->getUrl(true);

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
		$template->setVariable('fckBox',  $this->getEditor($fields['text']), false);

		$template->setVariable($fields, NULL, false);
		$template->clearVariable('id');

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
		$datefields[] = array('dateField' => 'date', 'triggerElement' => 'date');
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

		if(!$request->exists('id')) throw new Exception('News id is missing.');
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

	private function handleTreeEditPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			if(!$request->exists('id')) throw new Exception('News id is missing.');
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

		if(!$request->exists('id')) throw new Exception('News id is missing.');
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
			if(!$request->exists('id')) throw new Exception('News id is missing.');
			$ids = $request->getValue('id');
			if(!is_array($ids)) $ids = array($ids);

			foreach($ids as $id)
			{
				$key = array('id' => $id);
				$this->delete($key);
			}

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
	 * handle attachment request
	*/
	private function handleSubNavigation()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$template =  new TemplateEngine();

		if(!$request->exists('news_id')) return;

		$news_id = $request->getValue('news_id');
		$newsName = $this->getName(array('id' => $news_id));
		$template->setVariable('pageTitle', $newsName, false);

		$tree_id = $request->getValue('tree_id');
		$tag = $request->getValue('tag');
		$template->setVariable('tree_id', $tree_id, false);
		$template->setVariable('tag', $tag, false);
		$template->setVariable('news_id', $news_id, false);
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

		$this->handleSubNavigation();
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

		$this->handleSubNavigation();
		$obj = $this->plugin->getObject($objectType);
		$obj->handleHttpPostRequest();
	}

//}}}

/*------- handle plugin select {{{ -------*/
	private function handlePluginSelect($tag, $parameters)
	{
		$pagesize = 20;
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$page = $this->getPage();

		$searchcriteria = isset($parameters) && array_key_exists('searchcriteria', $parameters) ? $parameters['searchcriteria'] : array();
		$keys = isset($parameters) && array_key_exists('keys', $parameters) ? $parameters['keys'] : array();

		$this->pagerUrl->addParameters($searchcriteria);
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		// handle searchcriteria
		$search = new SearchManager();
		$search->setUrl($this->pagerUrl);
		$search->setExclude($this->pagerKey);
		$search->setParameter('search');
		$search->saveList();
		$searchcriteria = $search->getSearchParameterList();
		//$searchcriteria = array_merge($searchcriteria, $key);

		$rangeKeys = array();

		$list = $this->getList($searchcriteria, $pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$rangeKeys[] = $item['id'];
			$item['selected'] = in_array($item['id'], $keys);

			if($item['thumbnail'])
			{
				$img = new Image($item['thumbnail'], $this->plugin->getContentPath(true));
				$item['thumbnail'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
			}
		}
		$template->setVariable('list',  $list);
		$template->setVariable('searchparam',  $search->getMandatoryParameterList());
		$template->setVariable('searchcriteria',  $searchcriteria);
		$template->setVariable('rangeKeys',  $rangeKeys);

		$this->template[$tag] = $template;
	}
//}}}

/*------- handle plugin list {{{ -------*/
	private function handlePluginList($tag, $parameters)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$searchcriteria = isset($parameters) && array_key_exists('searchcriteria', $parameters) ? $parameters['searchcriteria'] : array();
		$keys = isset($parameters) && array_key_exists('keys', $parameters) ? $parameters['keys'] : array();
		$searchcriteria['id'] = $keys;

		$settings = $this->plugin->getSettings();
		$template->setVariable('settings', $settings);

		$systemSite = new SystemSite();
		$tree = $systemSite->getTree();

		$list = $this->getList($searchcriteria, $pagesize, $page);
		foreach($list['data'] as &$item)
		{
			//TODO get url from caller plugin (newsletter) to track visits
			$url = new Url();
			$url->setPath($request->getProtocol().$request->getDomain().$tree->getPath($item['tree_id']));
			$url->setParameter('id', $item['id']);
			$url->setParameter(ViewManager::URL_KEY_DEFAULT, News::VIEW_DETAIL);
			$item['href_detail'] = $url->getUrl(true);

			if($item['thumbnail'])
			{
				$img = new Image($item['thumbnail'], $this->plugin->getContentPath(true));
				$item['thumbnail'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
			}
		}
		$template->setVariable('news',  $list);

		$this->template[$tag] = $template;
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
		$theme->addFileVar('news_htdocs_path', $this->plugin->getHtdocsPath());
		$rpcfile_src = $this->plugin->getHtdocsPath(true)."js/rpc.js.in";
		$theme->addJavascript($theme->fetchFile($rpcfile_src));

		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_lib.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_wrappers.js"></script>');

		// render comments
		$objComment = $this->plugin->getObject(News::TYPE_COMMENT);
		$objComment->renderForm($theme);

		$objAttachment = $this->plugin->getObject(News::TYPE_ATTACHMENT);
		$objAttachment->renderForm($theme);

		$objImage = $this->plugin->getObject(News::TYPE_IMAGE);
		$objImage->renderForm($theme);

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
