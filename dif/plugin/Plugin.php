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
abstract class Plugin extends Observer
{

	/**
	 * configuration object that holds configuration options
	 * @var Config
	 */
	private $config;

	/**
	 * id assigned by PluginManager
	 * @var integer
	 */
	private $id;

	/**
	 * description of plugin
	 * @var string
	 */
	protected $name;
	protected $description;

	/**
	 * configuration file
	 * @var string
	 */
	protected $configFile;

	protected $basePath;

	/**
	 * different plugin types
	 * @var array
	 */
	protected $types;
	private $typelist;

	/**
	 * Object linked to the type of plugin
	 * @var Plugin
	 */
	protected $reference = array();
	private $referenceType;

	/**
	 * list of objects that need to be rendered
	 * @var array
	 */
	private $renderList = array();

	/**
	 * referer Tree with GuiProvider Object. It is the object that requested the plugin to handle requests
	 * @var GuiProvider Object
	 */
	protected $referer;

	/**
	 * list of tags that the plugin is related to
	 * @var array
	 */
	protected $tagList;

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct()
	{
		parent::__construct();
		$this->tagList = array();
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
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

	public function getTypeDesc($type)
	{
		if(array_key_exists($type, $this->types)) return $this->types[$type];
	}

	protected function getPath()
	{
		return $this->basePath;
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
		return ($absolute) ? $this->getPath()."htdocs/" : Director::getWebRoot()."plugins/{$this->getClassName()}/htdocs/";
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
		$path =  $this->director->getContentPath($absolute).strtolower($this->getClassName())."/";
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
		$path =  $this->director->getCachePath($absolute).strtolower($this->getClassName())."/";
		if($absolute && !is_dir($path))
		{
			if(!mkdir($path, 0755)) throw new Exception("Error creating direcotry ".$path);
			chmod($path, 0755);
		}
		return $path;
	}

	public function getTypeList()
	{
		if(!isset($this->types)) return array();
		if(isset($this->typelist)) return $this->typelist;

		$this->typelist = array();
		foreach($this->types as $key=>$value)
		{
			//TODO check if typelist must contain $key. if so, PluginHandler xmlrpc will fail because it treats array as struct...
			//$this->typelist[$key] = array('id' => $key, 'name' => $value);
			$this->typelist[] = array('id' => $key, 'name' => $value);
		}
		return $this->typelist;
	}

	public function setReferer($obj)
	{
		$this->referer = $obj;
	}

	public function getReferer()
	{
		return $this->referer;
	}

	public function getTagList($searchcriteria=NULL)
	{
		//if(isset($this->tagList)) return $this->tagList;
		if(!$searchcriteria) $searchcriteria = array();
		$key = serialize($searchcriteria);
		if(array_key_exists($key, $this->tagList)) return $this->tagList[$key];

		if(!array_key_exists('tree_id', $searchcriteria) || !$searchcriteria['tree_id']) $searchcriteria['tree_id'] = $this->director->tree->getCurrentId();
		$searchcriteria['classname'] = $this->getClassName();

		$sitePlugin = $this->director->siteManager->systemSite->getSitePlugin();
		$this->tagList[$key] =  $sitePlugin->getTagList($searchcriteria);
		return $this->tagList[$key];
	}

	protected function getReferenceTypeList($tree_id=NULL, $tag=NULL)
	{
		if($this->referenceType) return $this->referenceType;

		$searchcriteria = array();
		if(isset($tree_id)) $searchcriteria['tree_id'] = $tree_id;
		if(isset($tag)) $searchcriteria['tag'] = $tag;

		$taglist = $this->getTagList($searchcriteria);

		$this->referenceType = array();
		foreach($taglist as $item)
		{
			$this->referenceType[$item['tag']] = $item['plugin_type'];
		}

		return $this->referenceType;
	}

	protected function getReferenceType($tag, $tree_id=NULL)
	{
		$typelist = $this->getReferenceTypeList($tree_id);

		if(!array_key_exists($tag, $typelist)) throw new Exception("$tag is not in reference type list in ".$this->getClassName());
		return $typelist[$tag];
	}

 /**
	 * retrieve a list of items to be deleted as a plugin delete
	 *
	 * @param array whith id [fieldname => value]
	 * @param string name of the tag that is being deleted
	 * @param integer id of the tree 
	 * @return void
	 */
	public function getPluginList($tag, $tree_id, $plugin_type)
	{
		$searchcriteria = array('tag' => $tag, 'tree_id' => $tree_id);

		return $this->getList($searchcriteria);
	}

 /**
	 * delete a plugin item
	 *
	 * @param array whith id [fieldname => value]
	 * @param string name of the tag that is being deleted
	 * @param integer id of the tree 
	 * @return void
	 */
	public function deletePlugin($values, $plugin_type)
	{
		$key = $this->getKey($values);
		$this->delete($key);
	}

	public function updateTag($tree_id, $tag, $new_tree_id, $new_tag, $plugin_type)
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

	public function updateTreeId($sourceNodeId, $destinationNodeId, $plugin_type)
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
	 * add plugin type to render list
	 * renderList is used by renderTheme to render specific classes in the plugin
	 */
	public function addRenderList($type)
	{
		$this->renderList[$type] = $type;
	}

	public function getRenderList()
	{
		return $this->renderList;
	}

}
?>
