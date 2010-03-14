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

require_once(DIF_ROOT.'utils/TemplateEngine.php');
require_once(DIF_ROOT.'utils/ParseFile.php');

/**
 * Main configuration 
 * @package Common
 */
abstract class Theme
{

	/**
	 * list of default images. each image item is an array with form: [src,width,height]
	 * @var array
	 */
	private $images;

	/**
	 * list of tags that will be pre processes by a defined template
	 * @var array
	 */
	private $tagTemplate;

	/**
	 * description of plugin
	 * @var string
	 */
	protected $name;
	protected $description;

	/**
	 * list of header definitions like stylesheet and javascript include statements
	 * @var array
	 */
	private $headers;
	private $cssFiles;
	private $jsFiles;


	/**
	 * list of variables to parse .in files with template engine
	 * @var array
	 */
	protected $parseFile;

	/**
	 * list of available tags that plugins can link to
	 * @var array
	 */
	private $tags = array();

	/**
	 * configuration object that holds configuration options
	 * @var Config
	 */
	protected $config;

	/**
	 * configuration file
	 * @var string
	 */
	protected $configFile;

	/**
	 * path to configuration file
	 * @var string
	 */
	protected $basePath;

	/**
	 * path to template file
	 * @var string
	 */
	protected $templatePath;

	/**
	 * Template object
	 * @var TemplateEngine object
	 */
	protected $template;

	/**
	 * Tree object for parsing the menu
	 * @var Menu object
	 */
	protected $tree;

	/**
	 * The current selected node
	 * @var string path to current node
	 */
	protected $currentNodePath;

	/**
	 * The breadcrumb path
	 * @var array path to current node
	 */
	protected $breadcrumb;

	protected $templateVars = array('currentView',
												'currentViewName',
												'href_up',
												'htdocsPath',
												'htmlheaders',
												'pageName',
												'pageTitle',
												'siteTitle',
												'domain',
												'tpl_breadcrumb',
												'urlPath');

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct()
	{
		$this->headers = array();
		$this->initialize();
	}

	private function initialize()
	{
		$this->parseFile = new ParseFile();
		$this->parseFile->setVariable('path', $this->getHtdocsPath());

		$invars = $this->getConfig()->in_vars;
		if(!$invars) return;
		
		$this->parseFile->setVariable($invars);

		$this->loadTags();
		$this->loadImages();
		$this->loadTagTemplates();
	}

	public function getConfig()
	{
		if($this->config) return $this->config;

		$configPath = $this->getPath().'script/';
		$configFile = $configPath.$this->configFile;
		$configFileDist = $configFile.'-dist';

		// check if config file is available. If not try to copy the distribution file
		if(!file_exists($configFile) && file_exists($configFileDist))
		{
			copy($configFileDist, $configFile);
			chmod($configFile, 0664);
		}

		$this->config = new Config($configPath, $this->configFile);

		return $this->config;
	}

	public function getPluginName()
	{
		return $this->name;
	}

	public function setPluginName($name)
	{
		$this->name = $name;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * returns base path of the theme
	 * @return string name of theme
	 */
	public function getPath()
	{
		return $this->basePath;
	}

	public function getTagTemplate($tag)
	{
		if(array_key_exists($tag, $this->tagTemplate)) return $this->tagTemplate[$tag];
	}

	public function replaceTag($parent_tag, $tags, $template_data, $remove_parent=true)
	{
		$container = $this->getTagTemplate($parent_tag);
		$template = $this->getTemplate();

		if($container)
		{
			foreach($tags as $item)
			{
				if(!$item['inherit_container']) continue;
				$childTag = $item['tag'];

				$data = $template->getVariable($childTag);
				if(!$data) continue;

				$tagTemplate = new TemplateEngine($container['file']);
				$tagTemplate->setVariable($container['tag'], $data);
				$template->setVariable($childTag, $tagTemplate, false);
			}
		}

		$substitute = new TemplateEngine($template_data, false);
		$template->setVariable($parent_tag, $substitute, false, true);

		if($remove_parent) unset($this->tagTemplate[$parent_tag]);
	}

	/**
	 * returns the absolute htdocs path 
	 * absolute parameter specifies which path has to be retrieved:
	 * if absolute is true, it returns the absolute system path. eg. /var/www/site/themes/foobar/htdocs/
	 * if false it returns the absolute web path eg. /themes/foobar/htdocs/
	 *
	 * @param boolean specifies which path has to be retrieved, default false
	 * @return string path to htdocs directory
	 */
	public function getHtdocsPath($absolute=false)
	{
		return ($absolute) ? $this->getPath()."htdocs/" : Director::getWebRoot()."themes/{$this->getClassName()}/htdocs/";
	}

	/**
	 * returns the absolute htdocs path 
	 * absolute parameter specifies which path has to be retrieved:
	 * if absolute is true, it returns the absolute system path. eg. /var/www/site/themes/foobar/htdocs/
	 * if false it returns the absolute web path eg. /themes/foobar/htdocs/
	 *
	 * @param boolean specifies which path has to be retrieved, default false
	 * @return string path to htdocs directory
	 */
	public function getContentPath($absolute=false)
	{
		$director = Director::getInstance();
		$path =  $director->getContentPath($absolute).strtolower($this->getClassName())."/";
		if($absolute && !is_dir($path))
		{
			if(!mkdir($path, 0755)) throw new Exception("Error creating direcotry ".$path);
			chmod($path, 0755);
		}
		return $path;
	}

	/**
	 * returns the absolute htdocs path 
	 * absolute parameter specifies which path has to be retrieved:
	 * if absolute is true, it returns the absolute system path. eg. /var/www/site/themes/foobar/htdocs/
	 * if false it returns the absolute web path eg. /themes/foobar/htdocs/
	 *
	 * @param boolean specifies which path has to be retrieved, default false
	 * @return string path to htdocs directory
	 */
	public function getCachePath($absolute=false)
	{
		$director = Director::getInstance();
		$path =  $director->getCachePath($absolute).strtolower($this->getClassName())."/";
		if($absolute && !is_dir($path))
		{
			if(!mkdir($path, 0755)) throw new Exception("Error creating direcotry ".$path);
			chmod($path, 0755);
		}
		return $path;
	}

	/**
	 * returns classname of the theme
	 * @return string classname of theme
	 */
	public function getClassName()
	{
		return get_class($this);
	}

	public function getConfigFile()
	{
		return $this->getPath().'/script/'.$this->configFile;
	}

	public function getStyleSheetFile()
	{
		return $this->getHtdocsPath(true).$this->getConfig()->stylesheet.".in";
	}

	/**
	 * returns template filename
	 * @return string template file name
	 */
	public function getTemplateFile()
	{
		return $this->templatePath.$this->getConfig()->template;
	}

	/**
	 * returns template of the theme
	 * @return string template of theme
	 */
	public function getTemplate()
	{
		if(isset($this->template)) return $this->template;

		$this->template = new TemplateEngine($this->getTemplateFile());
		$this->template->setCacheable(false);

		return $this->template;
	}

	public function getTemplateVars()
	{
		return $this->templateVars;
	}

	/**
	 * assings tree object for menu parsing
	 * @param Tree object
	 */
	public function setTree($obj)
	{
		$this->tree = $obj;
	}

	/**
	 * returns the tree
	 * @return Tree object
	 */
	public function getTree()
	{
		return $this->tree;
	}

	/**
	 * load default images from configuration file
	 */
	private function loadImages()
	{
		$this->images = array();

		$images = $this->getConfig()->images;
		if(!$images) return;

		$imagePath = $this->getHtdocsPath()."images/";

		foreach($images as $key=>$value)
		{
			$img = explode(',',$value);
			if(!$img || sizeof($img) != 3) continue;

			list($src, $width, $height) = $img;
			$image =  array('src' => $imagePath.$src, 'width' => $width, 'height' => $height);
			$this->images[$key] = $image;
			$this->parseFile->setVariable($key, $image);
		}
	}

	public function getImage($name)
	{
		if(array_key_exists($name, $this->images)) return $this->images[$name];
		return NULL;
	}

	/**
	 * load default tags from configuration file
	 */
	private function loadTags()
	{
		$this->tags = array();
		$tags = $this->getConfig()->tags;
		foreach($tags as $key=>$value)
		{
			$this->tags[$key] = array('id' => $key, 'name' => $value);
		}
	}

	/**
	 * load tags that require template preprocessing
	 */
	private function loadTagTemplates()
	{
		$this->tagTemplate = array();
		$tags = $this->getConfig()->tagtemplate;
		if(!$tags) return;

		foreach($tags as $key=>$value)
		{
			$tmp = explode(',',$value);
			if(sizeof($tmp) != 2) throw new Exception(__CLASS__."->".__FUNCTION__.": invalid format for tagtemplate variable. should be [template file],[tagname]");

			$this->tagTemplate[$key] = array('file' => $this->templatePath.trim($tmp[0]), 'tag' => trim($tmp[1]));
		}
	}

	public function getTagList()
	{
		return $this->tags;
	}

	public function getTagPluginList()
	{
		$retval = array();
		$list = $this->getConfig()->tagplugin;
		foreach($list as $key=>$item)
		{
			$tmp = explode(',',$item);
			$retval[$key] = array('classname' => $tmp[0], 'type' => isset($tmp[1]) ? $tmp[1] : 1);
		}
		return $retval;
	}

	public function getPreview()
	{
		$img = explode(',',$this->getConfig()->preview_image);
		if(sizeof($img) < 3) return;

		$imagePath = $this->getHtdocsPath()."images/";

		list($src, $width, $height) = $img;
		return array('src' => $imagePath.$src, 'width' => $width, 'height' => $height);
	}

	/**
	 * renders breadcrumb into menu template
	 * @return  object
	 */
	public function renderBreadcrumb()
	{
		if(!$this->getConfig()->template_breadcrumb || !$this->breadcrumb) return;


		$template = new TemplateEngine($this->templatePath.$this->getConfig()->template_breadcrumb);

		$last = array_pop($this->breadcrumb);
		$template->setVariable('breadcrumb', $this->breadcrumb, false);
		$template->setVariable('breadcrumb_last', $last['name'], false);
		return $template;
	}

	public function handleInitPreProcessing()
	{
	}

	/**
	 * add breadcrumb array (or node) 
	 * @param array breadcrumb array[path, name]
	 */
	public function getBreadcrumb()
	{
		return $this->breadcrumb;
	}

	/**
	 * add breadcrumb array (or node) 
	 * @param array breadcrumb array[path, name]
	 */
	public function addBreadcrumb($breadcrumb)
	{
		if(!array_key_exists('name', $breadcrumb) || !array_key_exists('path', $breadcrumb)) return; 
		if(!$this->breadcrumb) $this->breadcrumb = array();
		$this->breadcrumb[] = $breadcrumb;
	}

	/**
	 * merge breadcrumb array (or node) 
	 * @param array breadcrumb array[path, name]
	 */
	public function mergeBreadcrumb($breadcrumb)
	{
		$this->breadcrumb = array_merge($this->breadcrumb, $breadcrumb);
	}

	/**
	 * returns name of the theme
	 * @return string name of theme
	 */
	public function handlePreProcessing()
	{
		$this->handleInitPreProcessing();
		$request = Request::getInstance();
		$director = Director::getInstance();

		$template = $this->getTemplate();
		$template->setVariable('urlPath', $request->getPath(), false);
		$template->setVariable('domain', $request->getDomain(), false);
		$template->setVariable('adminSection', $director->isAdminSection(), false);

		// load theme htdocs path
		$template->setVariable('htdocsPath', $this->getHtdocsPath(), false);

		// load images
		$template->setVariable($this->images, NULL, false);

		//load default javascript functions
		//$this->addHeader('<script type="text/javascript" src="/js/functions.js"></script>');
		$this->addHeader('<meta name="generator" content="DIF Content Management Framework - http://www.difsystems.nl" />');


		$template->setVariable('siteTitle', 'Website', false);
		if($this->tree)
		{
			$node = $this->tree->getNode($this->tree->getCurrentId());
			//FIXME : clean up
			if(!$node) $node = array('name' => '');
			$template->setVariable('pageName', $node['name'], false);
			$template->setVariable('pageTitle', array_key_exists('title', $node) ? $node['title'] : $node['name'], false);

			$template->setVariable('href_up', 	$this->tree->getPath($this->tree->getParentId($this->tree->getCurrentId())), false);
			$template->setVariable('siteTitle', $this->tree->getTreeTitle(), false);
			if($this->tree->getTreeDescription()) $this->addHeader('<meta name="description" content="'.$this->tree->getTreeDescription().'" />');
			if($this->tree->getKeywords()) $this->addHeader('<meta name="keywords" content="'.$this->tree->getKeywords().'" />');

			$template->setVariable('keywords', $this->tree->getKeywords(), false);

			$this->breadcrumb = $this->tree->getAncestorList($this->tree->getCurrentId(), true);
			foreach($this->breadcrumb as &$item)
			{
				$item['path'] = $this->tree->getPath($item['id']);
			}
		}
	}

	/**
	 * creates a file with the specified content and returns the absolute web path to the file 
	 *
	 * @param string filename to write the content to
	 * @param string content of file
	 * @return string absolute web path to file
	 */
	public function createFile($filename, $content)
	{
		$this->parseFile->setSource($content);
		$this->parseFile->setDestination($this->getCachePath(true).$filename);
		$this->parseFile->save();

		return $this->getCachePath().$filename;
	}

	/**
	 * parse a file with vars and write it to destination
	 *
	 * @param string source filename with vars
	 * @param string destination filename
	 * @return string name of theme
	 */
	public function parseFile($file, $destination)
	{
		$this->parseFile->setSource($file);
		$this->parseFile->setDestination($destination);
		$this->parseFile->save();
	}

	/**
	 * fetch file content with vars and return
	 *
	 * @param string source filename with vars
	 * @return string content of parsed file
	 */
	public function fetchFile($file)
	{
		$this->parseFile->setSource($file);
		return $this->parseFile->fetch();
	}

	public function addFileVars($vars)
	{
		$this->parseFile->setVariable($vars, NULL);
	}

	public function addFileVar($key, $value)
	{
		$this->parseFile->setVariable($key, $value);
	}

	public function getFileVars()
	{
		return $this->parseFile->getVariables();
	}

	public function handleInitPostProcessing()
	{
	}

	public function handlePostProcessing()
	{
		$this->handleInitPostProcessing();

		$template = $this->getTemplate();

		// set view type in template
		$view = ViewManager::getInstance();
		$template->setVariable('currentView', $view->getType(), false);
		$template->setVariable('currentViewName', $view->getName(), false);
	}

	private function handleInitFetchTheme()
	{
		// skip tree stuff if no tree is set
		if(!$this->tree) return;

		$template = $this->getTemplate();

		$template->setVariable('tpl_breadcrumb', $this->renderBreadcrumb(), false);
	}

	public function addHeader($header)
	{
		$this->headers[] = $header;
	}

	public function addStylesheet($content)
	{
		$this->cssFiles[] = $content;
	}

	public function addJavascript($content)
	{
		$this->jsFiles[] = $content;
	}

	/**
	 * Write array with strings to the specified file
	 * This function is being used to generate 1 single javascript and css file
	 *
	 * @see addStylesheet
	 * @param array list of text lines to write to the file
	 * @param string full path of file to write to
	 */
	private function writeToFile($content, $filename)
	{
		if(!$content) return;
		if(!is_array($content)) throw new Exception("content is not an array in".__FUNCTION__." ".__CLASS__);

		if(!($fh = fopen($filename, "w"))) throw new Exception("could not open file {$filename} for writing. in ".__CLASS__);
		foreach($content as $item)
		{
			fputs($fh, $item);
		}
		fclose($fh);
		chmod($filename, 0644);
	}


	public function fetchTheme()
	{
		$this->handleInitFetchTheme();

		$cache = Cache::getInstance();

		$template = $this->getTemplate();

		// handle tag preprocessing
		foreach($this->tagTemplate as $key=>$value)
		{
			$data = trim($template->getVariable($key));
			if(!$data) continue;

			$tagTemplate = new TemplateEngine($value['file']);
			$tagTemplate->setVariable($value['tag'], $data);
			$template->setVariable($key, $tagTemplate, true, true);
		}

		// create unique filename for javascript and css files
		$request = Request::getInstance();
		$director = Director::getInstance();
		$siteGroup = $director->siteGroup;
		//FIXME remove user specific javascript
		$fileprefix = $request->getUrl().$siteGroup->getCurrentId().$this->getClassName();
		$stylesheet = md5($fileprefix.'stylesheet').".css";
		$javascript = md5($fileprefix.'javascript').".js";

		// write content to file
		if(!$cache->isCached($stylesheet));
		{
			if($this->getConfig()->stylesheet)
				$this->addStylesheet($this->fetchFile($this->getStyleSheetFile()));

			$stylesheetDestFile = $this->getCachePath(true).$stylesheet;
			if($cache->isCacheEnabled())
			{
				$stylesheetCacheFile = $cache->save($this->cssFiles, $stylesheet);
				copy($stylesheetCacheFile, $stylesheetDestFile);
				chmod($stylesheetDestFile, 0644);
			}
			else
			{
				$this->writeToFile($this->cssFiles, $stylesheetDestFile);
			}
		}
		$this->addHeader('<link href="'.$this->getCachePath().$stylesheet.'" rel="stylesheet" type="text/css" media="screen" />');

		if(!$cache->isCached($javascript))
		{
			$javascriptDestFile = $this->getCachePath(true).$javascript;
			if($cache->isCacheEnabled())
			{
				$javascriptCacheFile = $cache->save($this->jsFiles, $javascript);
				copy($javascriptCacheFile, $javascriptDestFile);
				chmod($javascriptDestFile, 0644);
			}
			else
			{
				$this->writeToFile($this->cssFiles, $stylesheetDestFile);
			}
		}
		$this->addHeader('<script type="text/javascript" src="'.$this->getCachePath().$javascript.'"></script>');

		// make sure no duplicate javascript files are included because functions are already defined than.
		$headers = array_unique($this->headers);

		$template->setVariable('htmlheaders', join("\n", $headers), false);

		// process system messages
		
		$template->setVariable('htmlheaders', join("\n", $headers), false);
		return $template->fetch();
	}

	public function handleAdminLinks(TemplateEngine $template, $name=NULL, Url $url=NULL)
	{
		$view = ViewManager::getInstance();
		if(!isset($name)) $name = $view->getName();

		// up link
		$crumb = end($this->getBreadcrumb());
		$template->setVariable('href_up', $crumb['path'], false);
		$template->setVariable('href_back', $crumb['path'], false);
		
		// breadbrumb
		if(!isset($url)) $url = new Url(true);
		$breadcrumb = array('name' => $name, 'path' => $url->getUrl(true));
		$this->addBreadcrumb($breadcrumb);
	}


	/**
	 * handle navigation for sub classes / pages
	*/
	public function handleAdminSubLinks($keyName, $title, $addBreadcrumb=false)
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

		$this->addBreadcrumb($breadcrumb);
	}

}

?>
