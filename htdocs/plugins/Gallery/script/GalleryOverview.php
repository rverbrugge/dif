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
class GalleryOverview extends Observer
{

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	/**
	 * refernce objects (attachment)
	 */
	private $reference;

	/**
	 * plugin settings of parent class
	 */
	private $settings = array();

	/**
	 * pointer to global plugin plugin
	 * @var Gallery
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
		$this->templateFile = "galleryoverview.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";
		$this->reference = array();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('gallery', 'a');
		$this->sqlParser->addField(new SqlField('a', 'gal_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_weight', 'weight', 'Gewicht', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'gal_active', 'activated', 'Geactiveerd', SqlParser::getTypeSelect(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'gal_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_description', 'description', 'Introductie', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_thumbnail', 'thumbnail', 'Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_image', 'image', 'Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'gal_img_x', 'img_x', 'Offset x', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_img_y', 'img_y', 'Offset y', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_img_width', 'img_width', 'Offset width', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_img_height', 'img_height', 'Offset height', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'gal_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'gal_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

																	
		$this->orderStatement = array(Gallery::ORDER_LINEAR	=> 'order by a.gal_weight asc, a.gal_create asc',
																	Gallery::ORDER_RANDOM => "order by rand()",
																	Gallery::ORDER_PREVIOUS => 'order by a.gal_weight desc, a.gal_create asc');
	}

/*-------- helper functions {{{----------*/
	private function getPath()
	{
		return $this->basePath;
	}

	private function getSettings($tree_id, $tag)
	{
		$key = $tree_id.$tag;
		if(array_key_exists($key, $this->settings)) return $this->settings[$key];

		$settingsObj = $this->plugin->getObject(Gallery::TYPE_SETTINGS);
		$this->settings[$key] = array_merge($this->plugin->getSettings(), $settingsObj->getSettings($tree_id, $tag));

		return $this->settings[$key];
	}

	private function getId($key)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($key);
		$this->parseCriteria($sqlParser, $key);
		$sqlParser->setOrderby($this->getOrder(array_key_exists('next', $key) ? Gallery::ORDER_LINEAR: Gallery::ORDER_PREVIOUS));

		$db = $this->getDb();
		$db->setLimit(1);
		$query = $sqlParser->getSql(SqlParser::PKEY);
		//if(array_key_exists('previous', $key)) echo $query;

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		return $res->fetchOne();
	}

	private function handleDisplaySettings($display)
	{
		$theme = $this->director->theme;

		switch($display)
		{
			case Gallery::DISP_LIGHTBOX :
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/lightbox.js"></script>');
				$theme->addHeader('<link rel="stylesheet" href="'.DIF_VIRTUAL_WEB_ROOT.'css/lightbox.css" type="text/css" media="screen" />');
			/*
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/mootools.v1.11.js"></script>');
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/slimbox.js"></script>');
				$theme->addHeader('<link rel="stylesheet" href="'.DIF_VIRTUAL_WEB_ROOT.'css/slimbox.css" type="text/css" media="screen" />');
				*/
				break;
			case Gallery::DISP_SLIDESHOW :
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/mootools.v1.11.js"></script>');
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/BackgroundSlider.js"></script>');
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/slideshow.js"></script>');
				$theme->addHeader('<link rel="stylesheet" href="'.DIF_VIRTUAL_WEB_ROOT.'css/slideshow.css" type="text/css" media="screen" />');
				break;
		}
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
			$treeRef = new GalleryTreeRef();
			$treeRef->updateRefTreeId($tree_id, $new_tree_id);
		}

		// update settings
		$settings = $this->plugin->getObject(Gallery::TYPE_SETTINGS);
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
		$treeRef = new GalleryTreeRef();
		$treeRef->updateRefTreeId($sourceNodeId, $destinationNodeId);

		// update settings
		$settings = $this->plugin->getObject(Gallery::TYPE_SETTINGS);
		$settings->updateTreeId($tag, $tree_id, $newTag);
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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.gal_id', $value, '<>')); break;
				case 'previous' : 
					$SqlParser->addFrom('left join gallery as b on b.gal_tree_id = a.gal_tree_id and b.gal_tag = a.gal_tag');
					$SqlParser->addCriteria(new SqlCriteria('b.gal_id', $value));
					$SqlParser->addCriteria(new SqlCriteria('a.gal_weight', 'b.gal_weight', '<', true)); break;
				case 'next' : 
					$SqlParser->addFrom('left join gallery as b on b.gal_tree_id = a.gal_tree_id and b.gal_tag = a.gal_tag');
					$SqlParser->addCriteria(new SqlCriteria('b.gal_id', $value));
					$SqlParser->addCriteria(new SqlCriteria('a.gal_weight', 'b.gal_weight', '>', true)); break;
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
			case 'weight' : 
				$request = Request::getInstance();
				return $this->getNextWeight($request->getValue('tree_id'), $request->getValue('tag')); 
				break;
		}
	}

	/**
	 * filters field values like checkbox resize and date resize
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

		$img = $values['image'];
		if(!$this->importing && (!is_array($img) ||  !$img['tmp_name'])) return;

		$settings = $this->getSettings($values['tree_id'], $values['tag']);

		$destWidth = $settings['image_width'];
		$destHeight = $settings['image_height'];
		$maxWidth = $settings['image_max_width'];
		$newWidth			= $destWidth;
		$newHeight		= $destHeight;

		$image = new Image($img);
		$imgWidth = $image->getWidth();
		$imgHeight = $image->getHeight();
		$ext = Utils::getExtension(is_array($img) ? $img['name'] : $img);

		$path 			= $this->plugin->getContentPath(true);
		$filename 	= strtolower($this->getClassName())."_".$id['id'];
		$imgFile 		= $filename.".$ext";
		$thumbFile	= $filename."_thumb.$ext";
		$imgX				= 0;
		$imgY				= 0;

		// delete current image if filename new filename is different
		$detail = $this->getDetail($id);
		if($detail['image'] && $detail['image'] != $imgFile)
			$this->deleteImage($detail);

		// only crop if both width and height settings are set and image is big enough, else do a resize

		if($destWidth && $destHeight && $imgWidth > $destWidth && $imgHeight > $destHeight)
		{
			// resize image 
			if($imgWidth > $maxWidth && $maxWidth > 0) $image->resize($maxWidth);
			$image->save($path.$imgFile);

			// crop thumbnail
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

			$image->crop($imgX, $imgY, $newWidth, $newHeight, $destWidth, $destHeight);
			$image->save($path.$thumbFile);
		}
		else
		{
			// save image
			$image->save($path.$imgFile);

			// resize thumbnail
			$image->resize($destWidth, $destHeight);
			$image->save($path.$thumbFile);
		}

		$db = $this->getDb();
		$query = sprintf("update gallery set gal_image= '%s', gal_thumbnail = '%s', gal_img_x = %d, gal_img_y = %d, gal_img_width = %d, gal_img_height = %d where gal_id = %d", 
										addslashes($imgFile), 
										addslashes($thumbFile), 
										$imgX,
										$imgY,
										$newWidth,
										$newHeight,
										$id['id']);

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	protected function cropImage($id, $values)
	{
		// no crop if image is uploaded (it's being cropped by insertimage)
		$img = $values['image'];
		// RETURN IF IMAGE IS ARRAY!!!!!
		if(is_array($img) &&  $img['tmp_name']) return;

		$settings = $this->getSettings($values['tree_id'], $values['tag']);
		$detail = $this->getDetail($id);
		if(!$detail['image']) return;

		$destWidth = $settings['image_width'];
		$destHeight = $settings['image_height'];

		$image = new Image($detail['image'], $this->plugin->getContentPath(true));
		$imgWidth = $image->getWidth();
		$imgHeight = $image->getHeight();
		$ext = Utils::getExtension($detail['image']);

		// only crop if both width and height settings are set and image is big enough
		if(!($destWidth && $destHeight && $imgWidth > $destWidth && $imgHeight > $destHeight)) return;

		$path 			= $this->plugin->getContentPath(true);
		$filename 	= strtolower($this->getClassName())."_".$id['id'];
		$thumbFile	= $filename."_thumb.$ext";

		// crop thumbnail
		$imgX = $detail['img_x'];
		$imgY = $detail['img_y'];
		$imgWidth = $detail['img_width'];
		$imgHeight = $detail['img_height'];

		$image->crop($imgX, $imgY, $imgWidth, $imgHeight, $destWidth, $destHeight);
		$image->save($path.$thumbFile);

		$db = $this->getDb();
		$query = sprintf("update gallery set gal_thumbnail = '%s' where gal_id = %d", addslashes($thumbFile), $id['id']);
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
		$query = "update gallery set gal_image = '', gal_thumbnail = '' where gal_id = {$id['id']}";
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	protected function handlePostUpdate($id, $values)
	{
		$this->insertImage($id, $values);
		$this->cropImage($id, $values);
	}

	protected function handlePreDelete($id, $values)
	{
		$search = array('gal_id' => $id['id']);
		$comment = $this->plugin->getObject(Gallery::TYPE_COMMENT);
		$comment->delete($search);
	}


	protected function handlePostDelete($id, $values)
	{
		$this->deleteImage($values);
	}

	private function getNextWeight($tree_id, $tag)
	{
		$retval = 0;
		$offset = 10;
		$query = sprintf("select max(gal_weight) from gallery where gal_tree_id = %d and gal_tag = '%s'", $tree_id, addslashes($tag));

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = $res->fetchOne();
		return $retval + $offset;
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

		switch($viewManager->getType())
		{
			case Gallery::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW : $this->handleTreeNewGet(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditGet(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeleteGet(); break;
			case Gallery::VIEW_IMPORT : $this->handleImportGet(); break;
			case Gallery::VIEW_RESIZE : $this->handleResizeGet(); break;
			case Gallery::VIEW_COMMENT_OVERVIEW : 
			case Gallery::VIEW_COMMENT_EDIT : 
			case Gallery::VIEW_COMMENT_DELETE : $this->handleObjectGet(Gallery::TYPE_COMMENT); break;
			case Gallery::VIEW_CONFIG : $this->handleObjectGet(Gallery::TYPE_SETTINGS); break;
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
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW :  $this->handleTreeNewPost(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditPost(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeletePost(); break;
			case Gallery::VIEW_IMPORT : $this->handleImportPost(); break;
			case Gallery::VIEW_RESIZE : $this->handleResizePost(); break;
			case Gallery::VIEW_COMMENT_OVERVIEW : 
			case Gallery::VIEW_COMMENT_EDIT : 
			case Gallery::VIEW_COMMENT_DELETE : $this->handleObjectPost(Gallery::TYPE_COMMENT); break;
			case Gallery::VIEW_CONFIG : $this->handleObjectPost(Gallery::TYPE_SETTINGS); break;
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
		$force = $cliServer->parameterExists('force');
		if(!$cliServer->parameterExists('tree_id') && !$force) throw new Exception("Parameter tree_id not set. Use --force to force resize without tree\n");
		if(!$cliServer->parameterExists('tag') && !$force) throw new Exception("Parameter tag not set. Use --force to force resize without tag\n");
		if(!$cliServer->parameterExists('task')) throw new Exception("Parameter task not set.\n");

		$tree_id = $cliServer->getParameter('tree_id');
		$tag = $cliServer->getParameter('tag');

		if(!$tree_id && !$force) throw new Exception("Parameter tree_id not set.\n");
		if(!$tag && !$force) throw new Exception("Parameter tag not set.\n");

		switch($cliServer->getParameter('task'))
		{
			case 'import' :
				$this->import($tree_id, $tag, true);
				break;
			case 'resize' :
				$this->resize($tree_id, $tag, true);
				break;
			default :
			 throw new Exception("Unknown task. Use 'import' for image import or 'resize' for file resize.\n");
		}
	}

	private function resize($tree_id=NULL, $tag=NULL, $stdout=true)
	{
		// enable processing of local files in insertimage
		$this->importing = true;
		$retval = array();

		$debug = array();
		$debug[] = "Starting resize at ".date('d-m-Y H:i:s');
		$debug[] = "Tree: $tree_id";
		$debug[] = "Tag: $tag";
		$debug[] = "----------------------------------\n";
		$retval = array_merge($retval, $debug);
		if($stdout) echo join("\n", $debug)."\n";
		$debug = array();

		if(isset($tree_id))
		{
			$searchcriteria = array('tree_id'	=> $tree_id,
															'tag'			=> $tag);
		}

		$path       = $this->plugin->getContentPath(true);
		$list = $this->getList($searchcriteria);
		foreach($list['data'] as $item)
		{
			$item['image'] = $path.$item['image'];
			$this->insertImage($item, $item);

			$debug[] = "Processing {$item['image']}";
			$retval = array_merge($retval, $debug);
			if($stdout) echo join("\n", $debug)."\n";
			$debug = array();
		}
		$debug[] = "----------------------------------";
		$debug[] = "Conversion finished at ".date('d-m-Y H:i:s');

		$retval = array_merge($retval, $debug);
		if($stdout) echo join("\n", $debug)."\n";
		$debug = array();

		return $retval;
	} 

	private function import($tree_id, $tag, $stdout=true)
	{
		// enable processing of local files in insertimage
		$this->importing = true;

		$debug = array();
		$retval = array();

		$importPath = $this->director->getImportPath()."/";
		$values = array('tree_id'	=> $tree_id,
										'tag'			=> $tag,
										'active'	=> 1);

		if(!is_dir($importPath)) throw new Exception("Import path $importPath does not exist. Create it and fill it with images first\n");

		$debug[] = "Starting import at ".date('d-m-Y H:i:s');
		$debug[] = "Path: $importPath";
		$debug[] = "Tree: $tree_id";
		$debug[] = "Tag: $tag";
		$debug[] = "----------------------------------\n";
		$retval = array_merge($retval, $debug);
		if($stdout) echo join("\n", $debug)."\n";
		$debug = array();

		$files = array();
		$dh = dir($importPath);
		while(FALSE !== ($entry = $dh->read()))
		{
			$file = $importPath.$entry;
			if(!Image::isImage($file)) continue;
			$files[] = $file;
		}
		// sort alphabetical
		sort($files);

		foreach($files as $file)
		{
			$name = basename($file, ".".Utils::getExtension($file));

			$values['name'] = $name;
			$values['weight']	= $this->getNextWeight($tree_id, $tag);
			$values['image'] = $file;

			$debug[] = "Processing $name";
			$id = $this->insert($values);

			$retval = array_merge($retval, $debug);
			if($stdout) echo join("\n", $debug)."\n";
			$debug = array();

			unlink($file);
		}
		$debug[] = "----------------------------------";
		$debug[] = "Import finished at ".date('d-m-Y H:i:s');

		$retval = array_merge($retval, $debug);
		if($stdout) echo join("\n", $debug)."\n";
		$debug = array();

		return $retval;
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
		$taglist = $this->plugin->getTagList(array('plugin_type' => Gallery::TYPE_DEFAULT));
		if(!$taglist) return;

		$url = new Url(true); 
		$url->setParameter($view->getUrlId(), Gallery::VIEW_DETAIL);

		$htdocsPathAbs 	= $this->plugin->getContentPath(true);
		$htdocsPath			= $this->plugin->getContentPath(false);

		foreach($taglist as $tag)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$template->setPostfix($tag['tag']);
			$template->setCacheable(true);

			// get settings
			$settings = $this->getSettings($tag['tree_id'], $tag['tag']);
			$this->handleDisplaySettings($settings['display']);
			$template->setVariable('settings', $settings);

			// check if template is in cache
			if(!$template->isCached())
			{

				$pagesize = $settings['rows'];

				$searchcriteria = array('tree_id' 	=> $tag['tree_id'], 
																'tag' 			=> $tag['tag'], 
																'activated' 		=> true);

				$imageSelect = array();

				$list = $this->getList($searchcriteria, $pagesize, $page);
				foreach($list['data'] as &$item)
				{
					$imageSelect[] = $item['id'];
					$url->setParameter('id', $item['id']);

					$item['href_detail'] = $url->getUrl(true);
					if($item['image'])
					{
						$img = new Image($item['image'], $htdocsPathAbs);
						$item['image'] = array('src' => $htdocsPath.$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
					}
					if($item['thumbnail'])
					{
						$img = new Image($item['thumbnail'], $htdocsPathAbs);
						$item['thumbnail'] = array('src' => $htdocsPath.$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
					}
				}

				if($settings['display'] == Gallery::DISP_LIGHTBOX)
				{
					// retrieve all images for lightbox
					$all = $this->getList($searchcriteria);
					$pregallery =  array();
					$postgallery = array();
					$pre = true;
					foreach($all['data'] as $allImg)
					{
						if(in_array($allImg['id'], $imageSelect))
						{
							// image is in selection, skip image and change array to post
							$pre = false;
							continue;
						}
						if($allImg['image'])
						{
							$img = new Image($allImg['image'], $htdocsPathAbs);
							$allImg['image'] = array('src' => $htdocsPath.$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
						}
						// add to right array
						$pre ? $pregallery[] = $allImg : $postgallery[] = $allImg;
					}
					$template->setVariable('pregallery',  $pregallery);
					$template->setVariable('postgallery',  $postgallery);
					
				}

				$template->setVariable('gallery',  $list);
				$template->setVariable('display',  $settings['display'], false);
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

		// clear subtitle
		$view->setName('');

		if(!$request->exists('id')) throw new Exception('Galerij ontbreekt.');
		$id = intval($request->getValue('id'));
		$key = array('id' => $id, 'activated' => true);

		if(!$this->exists($key)) throw new HttpException('404');
		$detail = $this->getDetail($key);
		// check if tree node of gallery item is accessable
		$tree = $this->director->tree;
		if(!$tree->exists($detail['tree_id'])) throw new HttpException('404');

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setCacheable(true);

		// overwrite default naming
		$template->setVariable('pageTitle',  $detail['name'], false);

		// add breadcrumb item
		$url = new Url(true);
		$url->clearParameter('id');

		$breadcrumb = array('name' => $detail['name'], 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		// check if template is in cache
		if(!$template->isCached())
		{
			// add image
			if($detail['image'])
			{
				$img = new Image($detail['image'], $this->plugin->getContentPath(true));
				$detail['image'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
			}

			$template->setVariable('gallery',  $detail, false);

			// previous and next
			$url = new Url(true);
			$search = array('activated' => true, 'previous' => $detail['id']);
			$prev_id = $this->getId($search);
			if($prev_id)
			{
				$url->setParameter('id', $prev_id);
				$template->setVariable('href_previous',  $url->getUrl(true), false);
			}

			$search = array('activated' => true, 'next' => $detail['id']);
			$next_id = $this->getId($search);
			if($next_id)
			{
				$url->setParameter('id', $next_id);
				$template->setVariable('href_next',  $url->getUrl(true), false);
			}

			// comment
			$settings = $this->getSettings($detail['tree_id'], $detail['tag']);
			$template->setVariable('gallerysettings',  $settings);

			// get settings
			if($settings['comment'])
			{
				// process comments
				$comment = $this->plugin->getObject(Gallery::TYPE_COMMENT);
				$comment->setSettings($settings);
				$comment->handleHttpGetRequest();
			}

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

		// add breadcrumb item
		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::TREE_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_import = clone $url;
		$url_import->setParameter($view->getUrlId(), Gallery::VIEW_IMPORT);
		$template->setVariable('href_import',  $url_import->getUrl(true), false);

		$url_resize = clone $url;
		$url_resize->setParameter($view->getUrlId(), Gallery::VIEW_RESIZE);
		$template->setVariable('href_resize',  $url_resize->getUrl(true), false);

		$url_conf = clone $url;
		$url_conf->setParameter($view->getUrlId(), Gallery::VIEW_CONFIG);
		$template->setVariable('href_conf',  $url_conf->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::TREE_DELETE);

		$url_com = clone $url;
		$url_com->setParameter($view->getUrlId(), Gallery::VIEW_COMMENT_OVERVIEW);

		$list = $this->getList($key, $pagesize, $page, null, clone $url);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);
			$url_com->setParameter('gal_id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
			$item['href_com'] = $url_com->getUrl(true);

			if($item['thumbnail'])
			{
				$img = new Image($item['thumbnail'], $this->plugin->getContentPath(true));
				$item['thumbnail'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
			}
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
		$fields['image'] = '';

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

		if(!$request->exists('id')) throw new Exception('Galerij ontbreekt.');
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

		// get crop settings
		$settings = $this->getSettings($fields['tree_id'], $fields['tag']);

		//only crop if both width and height defaults are set
		if($fields['image'] && $settings['image_width'] && $settings['image_height'] && $fields['image']['width'] > $settings['image_width'] && $fields['image']['height'] > $settings['image_height'])
		{
			$theme = $this->director->theme;

			$parseFile = new ParseFile();
			$parseFile->setVariable($fields);
			$parseFile->setVariable('imgTag',  'imgsrc', false);
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
			if(!$request->exists('id')) throw new Exception('Galerij ontbreekt.');
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

		if(!$request->exists('id')) throw new Exception('Galerij ontbreekt.');
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
			if(!$request->exists('id')) throw new Exception('Galerij ontbreekt.');
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

/*------- import request {{{ -------*/
	/**
	 * handle import
	*/
	private function handleImportGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(Gallery::VIEW_IMPORT);
		$auth = Authentication::getInstance();

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('importPath',  $this->director->getImportPath(), false);
		$template->setVariable('username',  $auth->getUsername(), false);

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
//}}}

/*------- resize request {{{ -------*/
	/**
	 * handle resize
	*/
	private function handleResizeGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(Gallery::VIEW_RESIZE);
		$auth = Authentication::getInstance();

		$template->setVariable('username',  $auth->getUsername(), false);
		$this->handleTreeSettings($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleResizePost()
	{
		$request = Request::getInstance();
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$this->handleTreeSettings($template);

		try 
		{
			$debug = $this->resize($tree_id, $tag, false);
			$template->setVariable('debug',  $debug, false);
			$this->template[$this->director->theme->getConfig()->main_tag] = $template;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);

			$this->handleResizeGet();
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

		if(!$request->exists('gal_id')) return;

		$gal_id = $request->getValue('gal_id');
		$galName = $this->getName(array('id' => $gal_id));
		$template->setVariable('pageTitle', $galName, false);

		$tree_id = $request->getValue('tree_id');
		$tag = $request->getValue('tag');
		$template->setVariable('tree_id', $tree_id, false);
		$template->setVariable('tag', $tag, false);
		$template->setVariable('gal_id', $gal_id, false);
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
		$theme->addFileVar('gallery_htdocs_path', $this->plugin->getHtdocsPath());
		$rpcfile_src = $this->plugin->getHtdocsPath(true)."js/rpc.js.in";
		$theme->addJavascript($theme->fetchFile($rpcfile_src));

		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_lib.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_wrappers.js"></script>');

		// render comments
		$objComment = $this->plugin->getObject(Gallery::TYPE_COMMENT);
		$objComment->renderForm($theme);


		if($this->reference)
		{
			foreach($this->reference as $object)
			{
				$object->renderForm($theme);
			}
		}

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
