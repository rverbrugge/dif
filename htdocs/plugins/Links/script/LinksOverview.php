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
class LinksOverview extends Observer
{

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	/**
	 * List of tags to which this plugin want to write content to but is not part of this plugin
	 */
	private $overrideTag = array();

	/**
	 * pointer to global plugin plugin
	 * @var Links
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
		$this->templateFile = "linksoverview.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('links', 'a');
		$this->sqlParser->addField(new SqlField('a', 'lnk_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'lnk_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'lnk_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'lnk_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'lnk_active', 'activated', 'Activated status', SqlParser::getTypeSelect(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'lnk_weight', 'weight', 'Reference Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'lnk_ref_tree_id', 'ref_tree_id', 'Reference Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'lnk_url', 'url', 'Url', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'lnk_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'lnk_intro', 'intro', 'Introductie', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'lnk_text', 'text', 'Inhoud', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'lnk_thumbnail', 'thumbnail', 'Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'lnk_image', 'image', 'Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'lnk_img_x', 'img_x', 'Offset x', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'lnk_img_y', 'img_y', 'Offset y', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'lnk_img_width', 'img_width', 'Offset width', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'lnk_img_height', 'img_height', 'Offset height', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'lnk_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'lnk_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'lnk_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'lnk_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->orderStatement = array('order by a.lnk_weight asc, a.lnk_name asc');
	}

/*-------- Helper functions {{{------------*/
	private function getPath()
	{
		return $this->basePath;
	}

	private function getHtdocsPath($absolute=false)
	{
		return $this->plugin->getHtdocsPath($absolute);
	}

	private function getContentPath($absolute=false)
	{
		return $this->plugin->getContentPath($absolute);
	}

	private function getCachePath($absolute=false)
	{
		return $this->plugin->getCachePath($absolute);
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
		$settings = $this->plugin->getObject(Links::TYPE_SETTINGS);
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

		// update settings
		$settings = $this->plugin->getObject(Links::TYPE_SETTINGS);
		$settings->updateTreeId($tag, $tree_id, $newTag);
	}

//}}}

/*-------- Weight functions {{{------------*/
	private function getNextWeight()
	{
		$request = Request::getInstance();
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$retval = 0;
		$offset = 10;
		$query = sprintf("select max(lnk_weight) from links where lnk_tree_id = %d and lnk_tag = '%s'", $tree_id, addslashes($tag));

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = $res->fetchOne();
		return $retval + $offset;
	}

	public function movetoPreceding($id)
	{
		$current = $this->getDetail($id);
		if(!$current) return;

		$searchcriteria = array('tree_id' => $current['tree_id'],
														'tag'			=> $current['tag'],
														'next_id'	=> $current['id']);

		$previous = $this->getDetail($searchcriteria, SqlParser::ORDER_DESC);
		if(!$previous) return;

		if($previous['weight'] == $current['weight'])
		{
			$this->decreaseWeight($current['tree_id'], $current['tag'], 0, $current['weight']);
			$previous['weight'] = $previous['weight'] - self::WEIGHT_OFFSET;
		}

		$this->updateWeight($current['id'], $previous['weight']);
		$this->updateWeight($previous['id'], $current['weight']);

		// clear cache
		$cache = Cache::getInstance();
		$cache->disableCache();
		$cache->clear();
	}

	public function movetoFollowing($id)
	{
		$current = $this->getDetail($id);
		if(!$current) return;

		$searchcriteria = array('tree_id' => $current['tree_id'],
														'tag'			=> $current['tag'],
														'previous_id'	=> $current['id']);

		$next = $this->getDetail($searchcriteria, SqlParser::ORDER_ASC);
		if(!$next) return;

		if($next['weight'] == $current['weight'])
		{
			$this->increaseWeight($current['tree_id'], $current['tag'], $current['weight']);
			$next['weight'] = $next['weight'] + self::WEIGHT_OFFSET;
		}

		$this->updateWeight($current['id'], $next['weight']);
		$this->updateWeight($next['id'], $current['weight']);

		// clear cache
		$cache = Cache::getInstance();
		$cache->disableCache();
		$cache->clear();
	}

	private function updateWeight($tree_id, $weight)
	{
		$query = sprintf("update links set lnk_weight = %d where lnk_id = %d", $weight, $tree_id);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * increase weight with self::weight_offset for child nodes of parent id that have at least min_weight and maximun max_weight
	 */
	private function increaseWeight($tree_id, $tag, $min_weight=0, $max_weight=0)
	{
		$query = sprintf("update links set lnk_weight = lnk_weight + %d where lnk_tree_id = %d and lnk_tag = '%s'", self::WEIGHT_OFFSET, $tree_id, $tag);
		if($min_weight) $query .= sprintf(" and lnk_weight >= %d", $min_weight);
		if($max_weight) $query .= sprintf(" and lnk_weight <= %d", $max_weight);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * decrease weight with self::weight_offset for child nodes of parent id that have at least min_weight and maximun max_weight
	 */
	private function decreaseWeight($tree_id, $tag, $min_weight=0, $max_weight=0)
	{
		$query = sprintf("update links set lnk_weight = lnk_weight - %d where lnk_tree_id = %d and lnk_tag = '%s'", self::WEIGHT_OFFSET, $tree_id, $tag);
		if($min_weight) $query .= sprintf(" and lnk_weight >= %d", $min_weight);
		if($max_weight) $query .= sprintf(" and lnk_weight <= %d", $max_weight);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.lnk_id', $value, '<>')); break;
				case 'previous_id' : 
					$SqlParser->addFrom("left join links as b on b.lnk_tree_id = a.lnk_tree_id and b.lnk_tag = a.lnk_tag");
					$SqlParser->addCriteria(new SqlCriteria('b.lnk_id', $value, '=')); 
					$SqlParser->addCriteria(new SqlCriteria('a.lnk_weight', 'b.lnk_weight', '>', true)); 
					break;
				case 'next_id' : 
					$SqlParser->addFrom("left join links as b on b.lnk_tree_id = a.lnk_tree_id and b.lnk_tag = a.lnk_tag");
					$SqlParser->addCriteria(new SqlCriteria('b.lnk_id', $value, '=')); 
					$SqlParser->addCriteria(new SqlCriteria('a.lnk_weight', 'b.lnk_weight', '<', true)); 
					break;
				case 'search' : 
					$search = new SqlCriteria('a.lnk_name', "%$value%", 'like'); 
					$search->addCriteria(new SqlCriteria('a.lnk_intro', "%$value%", 'like'), SqlCriteria::REL_OR); 
					$search->addCriteria(new SqlCriteria('a.lnk_text', "%$value%", 'like'), SqlCriteria::REL_OR); 
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
			case 'weight' : return $this->getNextWeight(); break;
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

		// check if index is unique. if not, reindex nodes
		$searchcriteria = array('weight' => $values['weight'],
														'tree_id'	=> $values['tree_id'],
														'tag'	=> $values['tag']);
		if($this->exists($searchcriteria))
		{
			$this->increaseWeight($values['tree_id'], $values['tag'], $values['weight']);
		}

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
		$searchcriteria = array('tree_id' => $values['tree_id'], 'tag'	=> $values['tag']);
		$settings = $this->plugin->getObject(Links::TYPE_SETTINGS)->getSettings($values['tag'], $values['tree_id']);

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
			$image = new Image($detail['image'], $this->getContentPath(true));
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
		$query = sprintf("update links set lnk_image= '%s', lnk_thumbnail = '%s', lnk_img_x = %d, lnk_img_y = %d, lnk_img_width = %d, lnk_img_height = %d where lnk_id = %d", 
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
			$image = new Image($values['thumbnail'], $this->getContentPath(true));
			$image->delete();
			$retval = true;
		}

		if($values['image']) 
		{
			$image = new Image($values['image'], $this->getContentPath(true));
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

		// check if index is unique. if not, reindex nodes
		$searchcriteria = array('no_id'		=> $values['id'],
														'weight' => $values['weight'],
														'tree_id'	=> $values['tree_id'],
														'tag'	=> $values['tag']);
		if($this->exists($searchcriteria))
		{
			$this->increaseWeight($values['tree_id'], $values['tag'], $values['weight']);
		}

		$detail = $this->getDetail($id);
		if(!$this->deleteImage($detail)) return;
		
		$db = $this->getDb();
		$query = "update links set lnk_image = '', lnk_thumbnail = '' where lnk_id = {$id['id']}";
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	protected function handlePostUpdate($id, $values)
	{
		$this->insertImage($id, $values);
	}

	protected function handlePreDelete($id, $values)
	{
	}

	protected function handlePostDelete($id, $values)
	{
		$this->deleteImage($values);
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
			case Links::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW :  $this->handleTreeNewPost(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditPost(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeletePost(); break;
			case Links::VIEW_IMPORT : $this->handleImportPost(); break;
			case Links::VIEW_RESIZE : $this->handleResizePost(); break;
			case Links::VIEW_CONFIG : $this->handleObjectPost(Links::TYPE_SETTINGS); break;
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
		// check if settings defined
		if($viewManager->isType(ViewManager::TREE_OVERVIEW))
		{
			$request = Request::getInstance();
			$linksSettings = $this->plugin->getObject(Links::TYPE_SETTINGS);
			$key = array('tree_id' => intval($request->getValue('tree_id')), 'tag' => $request->getValue('tag'));
			if(!$linksSettings->exists($key)) $viewManager->setType(Links::VIEW_CONFIG);
		}


		switch($viewManager->getType())
		{
			case Links::VIEW_DETAIL : $this->handleDetail(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW : $this->handleTreeNewGet(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditGet(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeleteGet(); break;
			case Links::VIEW_IMPORT : $this->handleImportGet(); break;
			case Links::VIEW_RESIZE : $this->handleResizeGet(); break;
			case Links::VIEW_CONFIG : $this->handleObjectGet(Links::TYPE_SETTINGS); break;
			case Links::VIEW_MV_FOL : 
			case Links::VIEW_MV_PREC : $this->handleMove(); break;
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
	private function handleOverview($tree_id=NULL, $tag=NULL)
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$page = $this->getPage();
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		$active_id = NULL;

		// retrieve tags that are linked to this plugin
		if(!isset($tree_id) && !isset($tag))
		{
			$taglist = $this->plugin->getTagList();
			if(!$taglist) return;
		}
		else
		{
			$taglist = array(array('tree_id' => $tree_id, 'tag' => $tag));
			$active_id = intval($request->getValue('id'));
		}

		$url = new Url(true); 
		$url->setParameter($view->getUrlId(), Links::VIEW_DETAIL);

		foreach($taglist as $tag)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
			$template->setPostfix($tag['tag']);
			$template->setCacheable(true);
			$template->setVariable('active_id', $active_id);
			if(isset($active_id)) $template->setVariable('currentView', ViewManager::OVERVIEW);

			// check if template is in cache
			if(!$template->isCached())
			{
				// get settings
				$settings = $this->plugin->getObject(Links::TYPE_SETTINGS)->getSettings($tag['tag'], $tag['tree_id']);

				$pagesize = $settings['rows'];

				$searchcriteria = array('tree_id' 	=> $tag['tree_id'], 
																'tag' 			=> $tag['tag'], 
																'activated' 		=> true);

				$list = $this->getList($searchcriteria, $pagesize, $page);
				foreach($list['data'] as &$item)
				{
					if($item['url'])
					{
						$item['href_detail'] = $item['url'];
					}
					elseif($item['ref_tree_id'])
					{
						$item['href_detail'] = $this->director->tree->getPath($item['ref_tree_id']);
					}
					else
					{
						$url->setParameter('id', $item['id']);
						$item['href_detail'] = $url->getUrl(true);
					}

					if($item['thumbnail'])
					{
						$img = new Image($item['thumbnail'], $this->getContentPath(true));
						$item['thumbnail'] = array('src' => $this->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
					}
				}

				$template->setVariable('links',  $list);
				$template->setVariable('settings',  $settings);
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

		// check security
		if(!$request->exists('id')) throw new Exception('Link is missing.');
		$id = intval($request->getValue('id'));
		$key = array('id' => $id, 'activated' => true);

		if(!$this->exists($key)) throw new HttpException('404');
		$detail = $this->getDetail($key);

		// check if tree node of thumb item is accessable
		$tree = $this->director->tree;
		if(!$tree->exists($detail['tree_id'])) throw new HttpException('404');

		// process request
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setPostfix($detail['tag']);

		// overwrite default naming
		$template->setVariable('pageTitle',  $detail['name'], false);

		// add breadcrumb item
		$url = new Url(true);
		$breadcrumb = array('name' => $detail['name'], 'path' => $url->getUrl(true));
		$this->director->theme->addBreadcrumb($breadcrumb);

		$template->setVariable('links',  $detail, false);

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter($view->getUrlId(), ViewManager::OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$settings = $this->plugin->getObject(Links::TYPE_SETTINGS)->getSettings($detail['tag'], $detail['tree_id']);
		$tag = $detail['tag'];
		if($settings['target'])
		{
			$tag = $settings['target'];
			$this->overrideTag[] = $tag;
			$this->handleOverview($detail['tree_id'], $detail['tag']);
		}

		$this->template[$tag] = $template;

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

		// add breadcrumb item
		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::TREE_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_conf = clone $url;
		$url_conf->setParameter($view->getUrlId(), Links::VIEW_CONFIG);
		$template->setVariable('href_conf',  $url_conf->getUrl(true), false);

		$url_import = clone $url;
		$url_import->setParameter($view->getUrlId(), Links::VIEW_IMPORT);
		$template->setVariable('href_import',  $url_import->getUrl(true), false);

		$url_resize = clone $url;
		$url_resize->setParameter($view->getUrlId(), Links::VIEW_RESIZE);
		$template->setVariable('href_resize',  $url_resize->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);

		$url_mv_prev = clone $url;
		$url_mv_prev->setParameter($view->getUrlId(), Links::VIEW_MV_PREC);

		$url_mv_next = clone $url;
		$url_mv_next->setParameter($view->getUrlId(), Links::VIEW_MV_FOL);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::TREE_DELETE);

		$counter = 0;
		$list = $this->getList($searchcriteria, $pagesize, $page);
		$maxcount = $list['totalItems'];
		foreach($list['data'] as &$item)
		{
			$counter++;

			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);
			$url_mv_prev->setParameter('id', $item['id']);
			$url_mv_next->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);
			if($counter > 1) $item['href_mv_prev'] = $url_mv_prev->getUrl(true);
			if($counter < $maxcount) $item['href_mv_next'] = $url_mv_next->getUrl(true);

			if($item['url'])
				$item['link'] = $item['url'];
			elseif($item['ref_tree_id'])
				$item['link'] = $this->director->tree->getPath($item['ref_tree_id']);
			else
				$item['link'] = "link detailed page";

			if($item['thumbnail'])
			{
				$img = new Image($item['thumbnail'], $this->getContentPath(true));
				$item['thumbnail'] = array('src' => $this->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
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

	private function getEditor($text)
	{
		// include fck editor
		require_once(DIF_WEB_ROOT."fckeditor/fckeditor.php");
		$oFCKeditor = new FCKeditor('text');
		$oFCKeditor->BasePath = DIF_VIRTUAL_WEB_ROOT.'fckeditor/';
		$oFCKeditor->Value = $text;
		$oFCKeditor->Width  = '700' ;
		$oFCKeditor->Height = '500' ;
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

		// get all tree nodes which have plugin modules
		$site 			= new SystemSite();
		$tree = $site->getTree();

		$treelist = $tree->getList();
		foreach($treelist as &$item)
		{
			$item['name'] = $tree->toString($item['id'], '/', 'name');
		}
		$template->setVariable('cbo_tree_id', Utils::getHtmlCombo($treelist, $fields['ref_tree_id'],'...'));

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

		if(!$request->exists('id')) throw new Exception('Link is missing.');
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
			$img = new Image($fields['image'], $this->getContentPath(true));
			$fields['image'] = array('src' => $this->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
		}

		$template->setVariable($fields, NULL, false);

		$this->handleTreeSettings($template);
		$template->setVariable('fckBox',  $this->getEditor($fields['text']), false);

		// get all tree nodes which have plugin modules
		$site 			= new SystemSite();
		$tree = $site->getTree();

		$treelist = $tree->getList();
		foreach($treelist as &$item)
		{
			$item['name'] = $tree->toString($item['id'], '/', 'name');
		}
		$template->setVariable('cbo_tree_id', Utils::getHtmlCombo($treelist, $fields['ref_tree_id'],'...'));

		// get crop settings
		$settings = $this->plugin->getObject(Links::TYPE_SETTINGS)->getSettings($fields['tag'], $fields['tree_id']);

		//only crop if both width and height defaults are set
		if($fields['image'] && $settings['image_width'] && $settings['image_height'] && ($fields['image']['width'] > $settings['image_width'] || $fields['image']['height'] > $settings['image_height']))
		{
			$theme = $this->director->theme;

			$parseFile = new ParseFile();
			$parseFile->setVariable($fields, NULL, false);
			$parseFile->setVariable('imgTag',  'imgsrc', false);
			$parseFile->setVariable($settings, NULL, false);
			$parseFile->setSource($this->getHtdocsPath(true)."js/cropinit.js.in");
			//$parseFile->setDestination($this->getCachePath(true)."cropinit_tpl_content.js");
			//$parseFile->save();
			$theme->addJavascript($parseFile->fetch());
			//$this->headers[] = '<script type="text/javascript" src="'.$this->getCachePath().'cropinit_tpl_content.js"></script>';

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
			if(!$request->exists('id')) throw new Exception('Link is missing.');
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

		if(!$request->exists('id')) throw new Exception('Thumbnail is missing.');
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
			if(!$request->exists('id')) throw new Exception('Thumbnail is missing.');
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

/*------- import request {{{ -------*/
	/**
	 * handle import
	*/
	private function handleImportGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(Links::VIEW_IMPORT);
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
		$view->setType(Links::VIEW_RESIZE);
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

/*------- handle move request {{{ -------*/
	/**
	 * handle Move request
	*/
	private function handleMove()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		if(!$request->exists('id')) throw new Exception(__FUNCTION__.' Element is missing.');
		$form_id = intval($request->getValue('id'));

		// check if node exists
		if(!$this->exists($form_id)) throw new HttpException('404');

		$key = array('id' => $form_id);

		try 
		{
			switch($view->getType())
			{
				case Links::VIEW_MV_PREC : $this->movetoPreceding($key); break;
				case Links::VIEW_MV_FOL : $this->movetoFollowing($key); break;
			}
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
		}

		viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
		$this->handleTreeOverview();
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

		if(!$request->exists('id')) return;

		$id = $request->getValue('id');
		$template->setVariable('pageTitle', $this->getName(array('id' => $id)), false);

		$tree_id = $request->getValue('tree_id');
		$tag = $request->getValue('tag');
		$template->setVariable('tree_id', $tree_id, false);
		$template->setVariable('tag', $tag, false);
		$template->setVariable('id', $id, false);

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

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
			if(in_array($key, $this->overrideTag)) $template->lockVariable($key);
		}
	}
}

?>
