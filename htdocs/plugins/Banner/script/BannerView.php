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

/**
 * Main configuration 
 * @package Common
 */
class BannerView extends Observer
{

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	/**
	* images to parse in javascript file
	*/
	private $fileVars;
	private $fileParse;
	
	/**
	 * pointer to global plugin plugin
	 * @var Attachment
	 */
	private $plugin;

	/**
	 * special view type
	 */
	const VIEW_BANNER = 'vbnr';

	/**
	 * special order type
	 */
	const ORDER_ADMIN = 4;
	const ORDER_RANDOM = 8;

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
		$this->templateFile = "bannerview.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->fileVars = array();
		$this->fileParse = array();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('banner', 'a');
		$this->sqlParser->addField(new SqlField('a', 'bnr_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_weight', 'weight', 'Index', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'bnr_online', 'online', 'Online datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_offline', 'offline', 'Offline datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'bnr_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_url', 'url', 'Url', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_URL));
		$this->sqlParser->addField(new SqlField('a', 'bnr_image_temp', 'image_temp', 'Temp Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'bnr_image', 'image', 'Afbeelding', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'bnr_img_x', 'img_x', 'Offset x', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_img_y', 'img_y', 'Offset y', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_img_width', 'img_width', 'Offset width', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_img_height', 'img_height', 'Offset height', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_views', 'views', 'Aantal weergaves', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_clicks', 'clicks', 'Aantal clicks', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'bnr_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'bnr_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('b', 'bnr_transition_speed', 'transition_speed', 'Wissel snelheid', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('b', 'bnr_display', 'display', 'Weergave', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('b', 'bnr_display_order', 'display_order', 'Volgorde', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));

		$this->sqlParser->addFrom('inner join banner_settings as b on b.bnr_tree_id = a.bnr_tree_id and b.bnr_tag = a.bnr_tag');

		$this->orderStatement = array(self::ORDER_ADMIN => 'order by a.bnr_weight asc, a.bnr_name asc',
																	self::ORDER_RANDOM => "order by rand()");
	}

	private function getPath()
	{
		return $this->basePath;
	}

	private function getSettings($key)
	{
		$settings = $this->plugin->getDetail($key);
		if(!$settings) $settings = $this->plugin->getFields(SqlParser::MOD_INSERT);

		return $settings;
	}

	private function getDetailUrl($id, $tree_id, $tag, $htmlentities=true)
	{
		$view = ViewManager::getInstance();
		$request = Request::getInstance();
		$tree = $this->director->tree;

		$url_banner = new Url();
		//$url_banner->useCurrent(false);
		$url_banner->setPath($tree->getPath($tree_id));
		$url_banner->setParameter($view->getUrlId(), self::VIEW_BANNER);
		$url_banner->setParameter('id', $id);
		$url_banner->setParameter('tag', $tag);
		$url_banner->setParameter('tree_id', $tree_id);

		return $url_banner->getUrl($htmlentities);
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
				case 'no_id' : $SqlParser->addCriteria(new SqlCriteria('a.bnr_id', $value, '<>')); break;
				case 'archiveonline' : 
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.bnr_online)', $value, '>'));
					break;
				case 'archiveoffline' : 
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.bnr_offline)', $value, '<='));
					$SqlParser->addCriteria(new SqlCriteria('unix_timestamp(a.bnr_offline)', 0, '>'));
					break;
				case 'activated' : 
					// only active pages
					$SqlParser->addCriteria(new SqlCriteria('a.bnr_active', 1));

					// only pages that are online
					$SqlParser->addCriteria(new SqlCriteria('a.bnr_online', 'now()', '<='));

					$offline = new SqlCriteria('a.bnr_offline', 'now()', '>');
					$offline->addCriteria(new SqlCriteria('unix_timestamp(a.bnr_offline)', 0, '='), SqlCriteria::REL_OR);
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
			//case 'offline' : return date('d-m-Y', mktime(0,0,0,date('m')+2)); break;
			case 'weight' : return $this->getNextWeight(); break;
		}
	}

	private function getNextWeight()
	{
		$request = Request::getInstance();
		$tree_id = intval($request->getValue('tree_id'));
		$tag = $request->getValue('tag');

		$retval = 0;
		$offset = 10;
		$query = sprintf("select max(bnr_weight) from banner where bnr_tree_id = %d and bnr_tag = '%s'", $tree_id, $tag);

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

		// hide banneritem if not active
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

		// hide banneritem if not active
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
		$searchcriteria = array('tree_id' => $values['tree_id'], 'tag'	=> $values['tag']);
		$settings = $this->getSettings($searchcriteria);

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

			$originalFile = $filename."_tmp.$ext";
			$croppedFile	= "$filename.$ext";

			// delete current image if filename new filename is different
			$detail = $this->getDetail($id);
			if($detail['image_temp'] && $detail['image_temp'] != $originalFile)
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
			if(!$detail['image_temp']) return;

			// get original image
			$image = new Image($detail['image_temp'], $this->plugin->getContentPath(true));
			$ext = Utils::getExtension($detail['image']);

			$originalFile = $filename."_tmp.$ext";
			$croppedFile	= "$filename.$ext";
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

			// add overlay to  image
			if($settings['image'])
			{
				$layerImg = new Image($settings['image'], $this->plugin->getContentPath(true));
				$image->overlay($layerImg);
			}

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
		$query = sprintf("update banner set bnr_image= '%s', bnr_image_temp = '%s', bnr_img_x = %d, bnr_img_y = %d, bnr_img_width = %d, bnr_img_height = %d where bnr_id = %d", 
										addslashes($croppedFile), 
										addslashes($originalFile), 
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

		if($values['image_temp']) 
		{
			$image = new Image($values['image_temp'], $this->plugin->getContentPath(true));
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
		$query = "update banner set bnr_image = '', bnr_image_temp = '', bnr_img_x = 0, bnr_img_y = 0, bnr_img_width = 0, bnr_img_height = 0  where bnr_id = {$id['id']}";
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

	private function addView($id)
	{
		$db = $this->getDb();
		$query = sprintf("update banner set bnr_views = bnr_views + 1 where bnr_id = %d", $id['id']);
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	private function addClick($id)
	{
		$db = $this->getDb();
		$query = sprintf("update banner set bnr_clicks = bnr_clicks + 1 where bnr_id = %d", $id['id']);
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
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
			case self::VIEW_BANNER : $this->handleBanner(); break;
			case ViewManager::TREE_OVERVIEW : $this->handleTreeOverview(); break;
			case ViewManager::TREE_NEW : $this->handleTreeNewGet(); break;
			case ViewManager::TREE_EDIT : $this->handleTreeEditGet(); break;
			case ViewManager::TREE_DELETE : $this->handleTreeDeleteGet(); break;
			default : $this->handleOverview(); break;
		}
	}
//}}}

/*------- banner link request {{{ -------*/
	private function handleBanner()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		//FIXME add check if referer is the same as the current site

		if(!$request->exists('id')) throw new Exception('Id ontbreekt.');
		$id = intval($request->getValue('id'));
		$key = array('id' => $id, 'activated' => true);

		if(!$this->exists($key)) throw new HttpException('404');

		// check if file is set
		$detail = $this->getDetail($key);
		if(!$detail['url']) throw new HttpException('404');

		// check if tree node of news item is accessable
		$tree = $this->director->tree;
		if(!$tree->exists($detail['tree_id'])) throw new HttpException('404');

		// add click and check if user hasnt clicked already during this session
		$clicks = $request->getValue($this->getClassName(), Request::SESSION);
		if(!$clicks) $clicks = array();
		if(!in_array($id, $clicks))
		{
			$this->addClick($key);
			$clicks[] = $id;
		}
		$request->setValue($this->getClassName(), $clicks); 
		
		header("Location: {$detail['url']}");
		exit;
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

		// retrieve tags that are linked to this plugin
		$taglist = $this->plugin->getTagList();
		if(!$taglist) return;

		foreach($taglist as $tag)
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

			$searchcriteria = array('tree_id' 	=> $tag['tree_id'], 
															'tag' 			=> $tag['tag'], 
															'current' 	=> $request->exists("bnr{$tag['tag']}", Request::SESSION) ? $request->getValue("bnr{$tag['tag']}", Request::SESSION) : 0,
															'activated' 		=> true);

			// skip if no images available
			if(!$this->exists($searchcriteria)) continue;

			Cache::disableCache();

			// get settings
			$settings = $this->getSettings($searchcriteria);
			$template->setVariable('settings',  $settings, false);


			switch($settings['display_order'])
			{
				case Banner::DISP_ORDER_LINEAR :
					$banner = $this->getLinear($searchcriteria);
					break;
				default :
					$banner = $this->getRandom($searchcriteria);
			}
			
			$template->setVariable('banner',  $banner);

			// save current id in session for next banner retrieval
			$request->setValue("bnr{$banner['tag']}", $banner['id']);

			// register view
			$this->addView($banner);

			$theme = $this->director->theme;
			// add javascript start script 
			// skip if transition speed is insane fast
			if($settings['display'] != Banner::DISP_SINGLE && $banner['transition_speed'] > 1)
			{
				$theme->addJavascript(sprintf('setTimeout("getNextBanner(%d, %d, \'%s\', %d)", %d*1000);', 
																				$banner['id'], 
																				$banner['tree_id'], 
																				$banner['tag'], 
																				$banner['display_order'], 
																				$banner['transition_speed']));
			}

			// parse unique stylesheet
			// retrieve tag to postfix scripts and stylesheets for uniqueness (there can be more than 1 banner in a single page)
			$parseFile = new ParseFile();
			$parseFile->setVariable('banner',  $banner, false);
			$parseFile->setSource($this->plugin->getHtdocsPath(true)."css/banner.css.in");
			//$parseFile->setDestination($this->plugin->getCachePath(true)."banner_{$tag['tree_id']}{$tag['tag']}.css");
			//$parseFile->save();
			$theme->addStylesheet($parseFile->fetch());

			$this->template[$tag['tag']] = $template;
		}
	} 

	private function handleDisplayType($type, $searchcriteria, $template, $parseFile=null)
	{
		$request = Request::getInstance();
		$banner = array();

		switch($type)
		{
			case Banner::DISP_ORDER_LINEAR :
				$banner = $this->getLinear($searchcriteria);
				break;
			default :
				$banner = $this->getRandom($searchcriteria);
		}
		
		$template->setVariable('banner',  $banner);

		// save current id in session for next banner retrieval
		$request->setValue("bnr{$banner['tag']}", $banner['id']);

		// register view
		$this->addView($banner);

		if($parseFile)
		{
			$theme = $this->director->theme;
			$theme->addJavascript(sprintf('setTimeout("getNextBanner(%d, \'%s\', %d)", %d*1000);', 
																			$banner['id'], 
																			$banner['tag'], 
																			$banner['display_order'], 
																			$banner['transition_speed']));
			$parseFile->setVariable('banner',  $banner, false);
		}
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

		$url_conf = clone $url;
		$url_conf->setParameter($view->getUrlId(), Banner::VIEW_SETTINGS);
		$template->setVariable('href_conf',  $url_conf->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::TREE_EDIT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::TREE_DELETE);

		$list = $this->getList($key, $pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);

			if($item['image'])
			{
				$img = new Image($item['image'], $this->plugin->getContentPath(true));
				$item['image'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
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

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
		Utils::getDatePicker($this->director->theme, $datefields);

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
			$fields['image'] = $detail['image'];
		}

		// user original image
		if($fields['image_temp'])
		{
			$img = new Image($fields['image_temp'], $this->plugin->getContentPath(true));
			$fields['image'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
		}

		$this->setFields($fields);

		$datefields = array();
		$datefields[] = array('dateField' => 'online', 'triggerElement' => 'online');
		$datefields[] = array('dateField' => 'offline', 'triggerElement' => 'offline');
		Utils::getDatePicker($this->director->theme, $datefields);

		// get settings
		$searchcriteria = array('tree_id' => $fields['tree_id'], 'tag'	=> $fields['tag']);
		$settings = $this->getSettings($searchcriteria);

		//only crop if both width and height defaults are set
		if($fields['image'] && $settings['image_width'] && $settings['image_height'] && ($fields['image']['width'] > $settings['image_width'] || $fields['image']['height'] > $settings['image_height']))
		{
			$theme = $this->director->theme;

			$parseFile = new ParseFile();
			$parseFile->setVariable($fields, NULL, false);
			$parseFile->setVariable('imgTag',  'imgsrc', false);
			$parseFile->setVariable($settings, NULL, false);
			$parseFile->setSource($this->plugin->getHtdocsPath(true)."js/cropinit.js.in");
			//$parseFile->setDestination($this->plugin->getCachePath(true)."cropinit_tpl_content.js");
			//$parseFile->save();
			$theme->addJavascript($parseFile->fetch());

			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
			//$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');
			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');
			$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/cropper.js"></script>');
		}

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

		if(!$request->exists('id')) throw new Exception('Banner id is missing.');
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

	public function getLinear($search)
	{
		if(!array_key_exists('tree_id', $search)) throw new Exception('Tree node not set');
		if(!array_key_exists('tag', $search)) throw new Exception('Template tag not set');
		if(!array_key_exists('current', $search)) throw new Exception('Current item not set');

		$search['activated'] = true;

		$detail = array();

		if($search['current'])
		{
			$list = $this->getList($search);
			$found = false;
			foreach($list['data'] as $item)
			{
				if($found)
				{
					$detail = $item;
					if($detail['image'])
					{
						$img = new Image($detail['image'], $this->plugin->getContentPath(true));
						$detail['image'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
					}
					break;
				}

				if($item['id'] == $search['current']) $found = true;
			}
		}

		if(!$detail)
		{
			$detail = $this->getDetail($search, self::ORDER_ADMIN);
			if($detail['image'])
			{
				$img = new Image($detail['image'], $this->plugin->getContentPath(true));
				$detail['image'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
			}
		}

		$detail['url'] = $detail['url'] ? $this->getDetailUrl($detail['id'], $detail['tree_id'], $detail['tag'], false) : '';

		// register view
		$this->addView($detail);

		return $detail;
	}

	public function getRandom($search)
	{
		if(!array_key_exists('tree_id', $search)) throw new Exception('Tree node not set');
		if(!array_key_exists('tag', $search)) throw new Exception('Template tag not set');
		if(!array_key_exists('current', $search)) throw new Exception('Current item not set');

		$search['activated'] = true;
		$search['no_id'] = $search['current'];

		// fix searchcriteria if only 1 image is in the list
		if(!$this->exists($search)) unset($search['no_id']);

		$detail = $this->getDetail($search, BannerView::ORDER_RANDOM);

		if($detail['image'])
		{
			$img = new Image($detail['image'], $this->plugin->getContentPath(true));
			$detail['image'] = array('src' => $this->plugin->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
		}
		$detail['url'] = $detail['url'] ? $this->getDetailUrl($detail['id'], $detail['tree_id'], $detail['tag'], false) : '';

		// register view
		$this->addView($detail);

		return $detail;
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
	}

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
		$theme->addJavascript($theme->fetchFile($rpcfile_src));

		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_lib.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/jsxmlrpc/lib/xmlrpc_wrappers.js"></script>');

		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/scriptaculous.js"></script>');
		$theme->addJavascript(file_get_contents($this->plugin->getHtdocsPath(true).'js/fade.js'));

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
