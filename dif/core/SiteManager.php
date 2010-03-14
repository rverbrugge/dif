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
class SiteManager
{

	//private $config;
	//private $configPath;
	//private $configfile;
	private $director;
	public $tree;
	public $systemSite;

	/**
	 * array with admin objects of the current location
	 * @var array
	 */
	private $objects;

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct()
	{
		$this->director = Director::getInstance();
	}

	public function initialize()
	{
		$request = Request::getInstance();

		// create tree object
		$this->systemSite = new SystemSite();
		$this->tree = $this->systemSite->getTree();

		// check if path is set. is not, get the startpage path
		$path = $request->getPath();
		$currentId = ($path) ? $this->tree->getIdFromPath($path) : $this->tree->getStartNodeId();

		// current id does not exist. try to search login pages
		if(!$currentId && $this->tree->pathExists($path)) 
			$this->tree->setCurrentIdExists($this->tree->getIdFromPath($path, Tree::TREE_ORIGINAL));

		$this->tree->setCurrentId($currentId);
	}

	public function getTree()
	{
		return $this->tree;
	}

	public function getThemeClassName()
	{
		$tree_id = $this->tree->getCurrentId();
		$detail = $this->systemSite->getTheme(array('tree_id' => $tree_id));
		if($detail) 
		{
			$retval = $detail['classname'];
		}
		else
		{
			Logger::getInstance()->warn('No theme specified for tree id '.$tree_id);
			$retval = $this->director->getConfig()->admin_theme;
		}
		return $retval;
	}

	public function handlePostProcessing($theme)
	{
		// process user defined templates and their stylesheets
		$template = $theme->getTemplate();

		$siteTag = new SystemSiteTag();
		$search = array('tree_id' => $this->tree->getCurrentId());
		$list = $siteTag->getList($search);
		foreach($list['data'] as $item)
		{
			$theme->replaceTag($item['parent_tag'], $item['child_tags'], $item['template'], $item['remove_container']);

			// skip the rest if no stylesheet is set
			if(!$item['stylesheet']) continue;

			// parse stylesheet to set variables
			$theme->addStylesheet($theme->fetchFile($item['stylesheet']));

			// save stylesheet to a file and then inlcude it in the theme headers
			/*$filename = strtolower($siteTag->getClassname()."_{$item['tree_id']}_{$item['parent_tag']}.css");
			$stylesheet = $theme->createFile($filename, $item['stylesheet']);
			$theme->addHeader('<link href="'.$stylesheet.'" rel="stylesheet" type="text/css" media="screen" />');*/
		}
	}


	public function loadPlugins()
	{
		$request = Request::getInstance();
		

		if($this->tree->getCurrentId())
		{
			// everything is ok
			$sitePlugin = $this->director->siteManager->systemSite->getSitePlugin();
			$pluginManager = $this->director->pluginManager;
			$logger = Logger::getInstance();

			$search = array('tree_id' => $this->tree->getCurrentId());
			$taglist  = $sitePlugin->getTagList($search);

			foreach($taglist as $item)
			{
				//try to load the plugin
				try
				{
					$plugin = $pluginManager->getPlugin($item['classname']);
				}
				catch(Exception $err)
				{
					// plugin load failed. Add message and continue
					$logger->error($err->getMessage(), get_class($pluginManager), 'getPlugin'); 
				}
			}
		}
		elseif($this->tree->currentIdExists())
		{
			// path not avialable, redirect to login page
			try
			{
				$plugin = $pluginManager->loadPlugin($this->director->getConfig()->login_plugin);
			} 
			catch(Exception $err) 
			{ 
				$logger->error($err->getMessage(), get_class($pluginManager), 'getPlugin'); 
			}
		}
	}
}

?>
