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
class CalendarImage extends Observer
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
	 * @var Image
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
		$this->templateFile = "calendarimage.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('calendar_image', 'a');
		$this->sqlParser->addField(new SqlField('a', 'img_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'img_cal_id', 'cal_id', 'Calendar', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('b', 'cal_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER, false));
		$this->sqlParser->addField(new SqlField('b', 'cal_tag', 'tag', 'Tag', SqlParser::getTypeSelect(), SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('b', 'cal_active', 'cal_active', 'Actieve status', SqlParser::getTypeSelect(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'img_weight', 'weight', 'Index', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'img_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'img_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'img_thumbnail', 'thumbnail', 'Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'img_image', 'image', 'Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'img_img_x', 'img_x', 'Offset x', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'img_img_y', 'img_y', 'Offset y', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'img_img_width', 'img_width', 'Offset width', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'img_img_height', 'img_height', 'Offset height', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'img_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'img_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'img_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'img_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->addFrom("inner join calendar as b on b.cal_id = a.img_cal_id");

		$this->orderStatement = array('order by a.img_weight asc, a.img_name asc');
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

	private function getSettings()
	{
		if($this->settings) return $this->settings;

		$this->settings = $this->plugin->getDetail(array());
		if(!$this->settings) $this->settings = $this->plugin->getFields(SqlParser::MOD_INSERT);

		return $this->settings;
	}

	private function handleDisplaySettings($display)
	{
		$theme = $this->director->theme;

		switch($display)
		{
			case self::DISP_LIGHTBOX :
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
			case self::DISP_SLIDESHOW :
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/mootools.v1.11.js"></script>');
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/BackgroundSlider.js"></script>');
				$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/slideshow.js"></script>');
				$theme->addHeader('<link rel="stylesheet" href="'.DIF_VIRTUAL_WEB_ROOT.'css/slideshow.css" type="text/css" media="screen" />');
				break;
		}
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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.img_id', $value, '<>')); break;
				case 'cal_active' : $SqlParser->addCriteria(new SqlCriteria('b.cal_active', $value)); break;
				case 'activated' : 
					// only active pages
					$SqlParser->addCriteria(new SqlCriteria('a.img_active', 1));
					$SqlParser->addCriteria(new SqlCriteria('b.cal_active', 1));
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
		$cal_id = intval($request->getValue('cal_id'));

		$retval = 0;
		$offset = 10;
		$query = sprintf("select max(img_weight) from calendar_image where img_cal_id = %d", $cal_id);

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

		// hide imageitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif(!$values['cal_active']) 
			$activated = 0;

		$values['activated'] = $activated;
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$date = mktime(0,0,0);

		$activated = 1;

		// hide imageitem if not active
		if(!$values['active']) 
			$activated = 0;
		elseif(!$values['cal_active']) 
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
		if($upload || $this->importing)
		{
			// image is new uploaded image
			$image = new Image($img);
			$imgWidth = $image->getWidth();
			$imgHeight = $image->getHeight();
			$ext = Utils::getExtension(is_array($img) ? $img['name'] : $img);

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
			if($upload || $this->importing)
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
		$query = sprintf("update calendar_image set img_image= '%s', img_thumbnail = '%s', img_img_x = %d, img_img_y = %d, img_img_width = %d, img_img_height = %d where img_id = %d", 
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
		$query = "update calendar_image set img_image = '', img_thumbnail = '' where img_id = {$id['id']}";
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	protected function handlePostUpdate($id, $values)
	{
		$this->insertImage($id, $values);
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
			case Calendar::VIEW_IMAGE_OVERVIEW : $this->handleTreeOverview(); break;
			case Calendar::VIEW_IMAGE_NEW :  $this->handleTreeNewPost(); break;
			case Calendar::VIEW_IMAGE_EDIT : $this->handleTreeEditPost(); break;
			case Calendar::VIEW_IMAGE_DELETE : $this->handleTreeDeletePost(); break;
			case Calendar::VIEW_IMAGE_IMPORT : $this->handleImportPost(); break;
			case Calendar::VIEW_IMAGE_RESIZE : $this->handleResizePost(); break;
			case Calendar::VIEW_DETAIL : $this->handleOverview(); break;
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
			case Calendar::VIEW_IMAGE_OVERVIEW : $this->handleTreeOverview(); break;
			case Calendar::VIEW_IMAGE_NEW : $this->handleTreeNewGet(); break;
			case Calendar::VIEW_IMAGE_EDIT : $this->handleTreeEditGet(); break;
			case Calendar::VIEW_IMAGE_DELETE : $this->handleTreeDeleteGet(); break;
			case Calendar::VIEW_IMAGE_IMPORT : $this->handleImportGet(); break;
			case Calendar::VIEW_IMAGE_RESIZE : $this->handleResizeGet(); break;
			case Calendar::VIEW_DETAIL : $this->handleOverview(); break;
			default : $this->handleOverview();
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
		if(!$cliServer->parameterExists('cal_id') && !$force) throw new Exception("Parameter cal_id not set. Use --force to force resize without calendar\n");
		if(!$cliServer->parameterExists('tag') && !$force) throw new Exception("Parameter tag not set. Use --force to force resize without tag\n");
		if(!$cliServer->parameterExists('task')) throw new Exception("Parameter task not set.\n");

		$tree_id = $cliServer->getParameter('tree_id');
		$cal_id = $cliServer->getParameter('cal_id');
		$tag = $cliServer->getParameter('tag');

		if(!$tree_id && !$force) throw new Exception("Parameter tree_id not set.\n");
		if(!$cal_id && !$force) throw new Exception("Parameter cal_id not set.\n");
		if(!$tag && !$force) throw new Exception("Parameter tag not set.\n");

		switch($cliServer->getParameter('task'))
		{
			case 'import' :
				$this->import($tree_id, $tag, $cal_id, true);
				break;
			case 'resize' :
				$this->resize($tree_id, $tag, true);
				break;
			default :
			 throw new Exception("Unknown task. Use 'import' for image import or 'resize' for file resize.\n");
		}
	}

	private function resize($tree_id=NULL, $tag=NULL, $cal_id=NULL, $stdout=true)
	{
		// enable processing of local files in insertimage
		$this->importing = true;
		$retval = array();

		$debug = array();
		$debug[] = "Starting resize at ".date('d-m-Y H:i:s');
		$debug[] = "Tree: $tree_id";
		$debug[] = "Calendar: $cal_id";
		$debug[] = "Tag: $tag";
		$debug[] = "----------------------------------\n";
		$retval = array_merge($retval, $debug);
		if($stdout) echo join("\n", $debug)."\n";
		$debug = array();

		$searchcriteria = array();
		if(isset($tree_id))
		{
			$searchcriteria = array('tree_id'	=> $tree_id,
															'tag'			=> $tag);
		}

		if(isset($cal_id)) $searchcriteria['cal_id']	= $cal_id;

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

	private function import($tree_id, $tag, $cal_id, $stdout=true)
	{
		// enable processing of local files in insertimage
		$this->importing = true;

		$debug = array();
		$retval = array();

		$importPath = $this->director->getImportPath()."/";
		$values = array('tree_id'	=> $tree_id,
										'tag'			=> $tag,
										'cal_id'	=> $cal_id,
										'active'	=> 1);

		if(!is_dir($importPath)) throw new Exception("Import path $importPath does not exist. Create it and fill it with images first\n");

		$debug[] = "Starting import at ".date('d-m-Y H:i:s');
		$debug[] = "Path: $importPath";
		$debug[] = "Tree: $tree_id";
		$debug[] = "Calendar: $cal_id";
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
		$tag = 'template_calimage';

		if(!$request->exists('id')) throw new Exception('Calendar is missing.');
		$cal_id = intval($request->getValue('id'));

		$template = $this->getImageTemplate($cal_id, $tag);
		$this->template[$tag] = $template;
	}

	public function getImageTemplate($cal_id, $tag)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setPostfix($tag.$cal_id);
		$template->setCacheable(true);

		// check if template is in cache
		if(!$template->isCached())
		{
			$searchcriteria = array('cal_id' 	=> $cal_id, 
												'activated' 		=> true);

			$imageSelect = array();

			$htdocsPathAbs 	= $this->plugin->getContentPath(true);
			$htdocsPath			= $this->plugin->getContentPath(false);

			$list = $this->getList($searchcriteria);
			foreach($list['data'] as &$item)
			{
				$imageSelect[] = $item['id'];

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

			$template->setVariable('calendarimage',  $list);
		}

		$theme = $this->director->theme;
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/lightbox.js"></script>');
		$theme->addHeader('<link rel="stylesheet" href="'.DIF_VIRTUAL_WEB_ROOT.'css/lightbox.css" type="text/css" media="screen" />');

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
		$view->setType(Calendar::VIEW_IMAGE_OVERVIEW);

		if(!$request->exists('cal_id')) throw new Exception('Calendar is missing.');
		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$cal_id = intval($request->getValue('cal_id'));
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$key = array('cal_id' => $cal_id);

		$url = new Url(true);
		$url->clearParameter('id');
		$url->setParameter('cal_id', $cal_id);
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('tag', $tag);

		// add breadcrumb item
		$this->director->theme->handleAdminLinks($template);

		// create urls
		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), Calendar::VIEW_IMAGE_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_import = clone $url;
		$url_import->setParameter($view->getUrlId(), Calendar::VIEW_IMAGE_IMPORT);
		$template->setVariable('href_import',  $url_import->getUrl(true), false);

		$url_resize = clone $url;
		$url_resize->setParameter($view->getUrlId(), Calendar::VIEW_IMAGE_RESIZE);
		$template->setVariable('href_resize',  $url_resize->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), Calendar::VIEW_IMAGE_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), Calendar::VIEW_IMAGE_DELETE);

		$list = $this->getList($key);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);

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

		if(!$request->exists('tree_id')) throw new Exception('Node is missing.');
		if(!$request->exists('cal_id')) throw new Exception('Calendar is missing.');
		if(!$request->exists('tag')) throw new Exception('Tag is missing.');

		$tree_id = intval($request->getValue('tree_id'));
		$cal_id = intval($request->getValue('cal_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		// create href back url
		$url = new Url(true); 
		$url->setParameter('tree_id', $tree_id);
		$url->setParameter('cal_id', $cal_id);
		$url->setParameter('id', $cal_id);
		$url->setParameter('tag', $tag);
		$url->setParameter($view->getUrlId(), Calendar::VIEW_IMAGE_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		// add breadcrumb item
		$breadcrumb = array('name' => $view->getName(Calendar::VIEW_IMAGE_OVERVIEW), 'path' => $url->getUrl(true));
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
		$view->setType(Calendar::VIEW_IMAGE_NEW);

		$cal_id = intval($request->getValue('cal_id'));

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		$key = array('cal_id' => $cal_id);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$fields['cal_id'] 	= $cal_id;
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

			viewManager::getInstance()->setType(Calendar::VIEW_IMAGE_OVERVIEW);
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
		$view->setType(Calendar::VIEW_IMAGE_EDIT);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		if(!$request->exists('id')) throw new Exception('Bestand is missing.');
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

		if($fields['image'])
		{
			$img = new Image($fields['image'], $this->plugin->getContentPath(true));
			$fields['image'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
		}

		$template->setVariable($fields);

		$this->handleTreeSettings($template);

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
			if(!$request->exists('id')) throw new Exception('Bestand is missing.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$this->update($key, $values);

			viewManager::getInstance()->setType(Calendar::VIEW_IMAGE_OVERVIEW);
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
		$view->setType(Calendar::VIEW_IMAGE_DELETE);

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$template->setVariable('tree_id',  $tree_id, false);
		$template->setVariable('tag',  $tag, false);

		if(!$request->exists('id')) throw new Exception('Bestand is missing.');
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
			if(!$request->exists('id')) throw new Exception('Bestand is missing.');
			$ids = $request->getValue('id');

			if(!is_array($ids)) $ids = array($ids);
			foreach($ids as $id)
			{
				$key = array('id' => $id);
				$this->delete($key);
			}

			viewManager::getInstance()->setType(Calendar::VIEW_IMAGE_OVERVIEW);
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
		$view->setType(Calendar::VIEW_IMAGE_IMPORT);
		$auth = Authentication::getInstance();

		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');
		$cal_id = intval($request->getValue('cal_id'));

		$template->setVariable('cal_id',  $cal_id, false);
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
		$cal_id = intval($request->getValue('cal_id'));
		$tag = $request->getValue('tag');

		$this->handleTreeSettings($template);

		try 
		{
			$debug = $this->import($tree_id, $tag, $cal_id, false);
			$template->setVariable('debug',  $debug);
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
		$view->setType(Calendar::VIEW_IMAGE_RESIZE);
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
		$news_id = intval($request->getValue('news_id'));
		$tag = $request->getValue('tag');

		$this->handleTreeSettings($template);

		try 
		{
			$debug = $this->resize($tree_id, $tag, $news_id, false);
			$template->setVariable('debug',  $debug);
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
