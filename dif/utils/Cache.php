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
class Cache extends Observer
{
	private static $instance;

	/**
	 * specifies if page as whole is cacheable
	 *
	 * @var boolean
	 */
	static private $cacheable;
	static private $cacheEnabled;

	/**
	 * path to cache files
	 *
	 * @var string
	 */
	private $path;

	/**
	 * url of current page
	 *
	 * @var string
	 */
	private $url;

	/**
	 * expirationtime of cache file in minutes
	 *
	 * @var integer
	 */
	private $expiration;

	public function __construct()
	{
		parent::__construct();
		//$this->log =& LoggerManager::getLogger(get_class($this));
		$this->setCacheable(true);
		self::$cacheEnabled = true;
		$this->initialize();
	}

	public static function getInstance()
	{
		if(!isset(self::$instance))
			self::$instance = new Cache();

		return self::$instance;
	}

	private function initialize()
	{
		$request = Request::getInstance();
		$siteGroup = $this->director->siteGroup;

		// disable caching if disabled or type is not get
		if(!$this->director->getConfig()->enable_caching || $request->getRequestType() != Request::GET) 
		{
			$this->setCacheable(false);
			self::$cacheEnabled = false;
		}

		$authentication = Authentication::getInstance();
		$this->path = realpath(DIF_SYSTEM_ROOT.$this->director->getConfig()->path).'/';
		$userId = $authentication->getUserId();
		$this->url = $request->getUrl().$userId['id'].$siteGroup->getCurrentId();
		$this->expiration = $this->director->getConfig()->expiration;
	}

	static public function isCacheEnabled()
	{
		return self::$cacheEnabled;
	}

	/**
	 * Disables caching of total page request.
	 * When disabled the merge of all templates that make the page is not cached.
	 * This function must be called by a plugin that cannot be cached (eg random data)
	 * 
	 * This function does not disable caching of specific templates.
	 * If you want a template not to be cached, use the disableCache function of the template class.
	 *
	 * Usualy you disable both caching of total page (this function) and caching of the specific template (or *not* check if template is cached).
	 * This because if the template cannot be cached, the page cannot be cached as a whole.
	 *
	 * With the next request the system sees the page is not cached and calls all plugins. 
	 * Each plugin returns its cache because its caching was enabled in the template.
	 * Only the specific template that was not cached will process and return fresh content. 
	 *
	 */
	static public function disableCache()
	{
		self::$cacheable = false;
	}

	public function setCacheable($cacheable)
	{
		self::$cacheable = $cacheable;
	}

	public function isCacheable()
	{
		return self::$cacheable;
	}

	public function isCached($postfix='')
	{
		//if(!$this->isCacheable()) return false;
		if(!$this->isCacheEnabled()) return;
		$view = ViewManager::getInstance();

		$filename = $this->path.md5($this->url.$postfix.$view->getType());
		$expire = time() - $this->expiration * 60;
		return (is_file($filename) && filemtime($filename) > $expire);
	}

	public function getCache($postfix='')
	{
		$view = ViewManager::getInstance();
		$url = md5($this->url.$postfix.$view->getType());
		$filename = $this->path.$url;
		if(!is_file($filename)) throw new Exception('file $filename does not exists in '.__CLASS__." ".__FUNCTION__);

		return file_get_contents($filename);
	}

	public function save($content, $postfix='')
	{
		// only save if caching is enabled in config file
		if(!$this->isCacheEnabled()) return;
		$view = ViewManager::getInstance();

		$url = md5($this->url.$postfix.$view->getType());
		$filename = $this->path.$url;
		if(!($fh = fopen($filename, "w"))) throw new Exception("could not open file $filename for writing. in ".__CLASS__." ".__FUNCTION__);

		fputs($fh, $content);
		fclose($fh);
		chmod($filename, 0644);

		return $filename;
	}

	public function clear()
	{
		if(!$this->path) throw new Exception("path to cache directory is not set ".__CLASS__." ".__FUNCTION__);

		$dh = opendir(realpath($this->path));
		if(!$dh) throw new Exception("could not open directory {$this->path} for writing. in ".__CLASS__." ".__FUNCTION__);

		while(FALSE !== ($file = readdir($dh)))
		{
			$filename = $this->path.$file;
			if(!is_file($filename)) continue;
			unlink($filename);
		}
		closedir($dh);
	}

	public function onEvent($obj, $id, $type)
	{
		$this->clear();
	}

public function getPath()
{
	return $this->path;
}
}

?>
