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
abstract class Extension extends Observer
{

	/**
	 * configuration object that holds configuration options
	 * @var Config
	 */
	private $config;

	/**
	 * description of extension
	 * @var string
	 */
	protected $name;
	protected $description;
	protected $version;

	/**
	 * configuration file
	 * @var string
	 */
	protected $configFile;

	protected $basePath;

	/**
	 * referer Tree with GuiProvider Object. It is the object that requested the extension to handle requests
	 * @var GuiProvider Object
	 */
	protected $referer;

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
	}

	public function getConfig()
	{
		if($this->config) return $this->config;

		$this->config = new Config(Director::$configPath, $this->configFile);
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

	public function getVersion()
	{
		return $this->version;
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
		return ($absolute) ? $this->getPath()."htdocs/" : Director::getWebRoot()."extensions/{$this->getClassName()}/htdocs/";
	}

	public function setReferer($obj)
	{
		$this->referer = $obj;
	}

	public function getReferer()
	{
		return $this->referer;
	}

}
?>
