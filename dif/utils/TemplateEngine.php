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
 * @package Utils
 */

/**
 * Template class
 * @package Utils
 */
class TemplateEngine
{

  /**
   * append postfix to cache filename
   * @var string
   */
  private $postfix;

  /**
   * Whether to include of evaluate the file
   * @var boolean
   */
  private $includeFile;

  /**
   * array of variables
   * @var array
   */
  static private $vars = array();

  /**
   * array of local variables scope is current object
   * @var array
   */
  private $localVars = array();

	/**
	 * List of variables that are not allowed to be overridden
	 */
	private $lockedVars = array();

	/**
	 * template file
	 * @var string
	 */
	private $file;

	/**
	 * specifies if template can be cached
	 * @var boolean
	 */
	private $cacheable;

	/**
	 * Constructor
	 *
	 * @param string filename of template content
	 * @param string include file (if it is a filename) or evaluate file (if it is the content itself)
	 */
	public function __construct($file=null, $includeFile=true)
	{
		$this->setFile($file);
		$this->includeFile = $includeFile;
		$this->cacheable = false;
	}

	public function setFile($file)
	{
		$this->file = $file;
	}

	public function setIncludeFile($include=true)
	{
		$this->includeFile = $include;
	}

	public function getFile()
	{
		return $this->file;
	}

	public function setPostfix($postfix)
	{
		$this->postfix = $postfix;
	}

	public function getPostfix()
	{
		return $this->postfix;
	}

	public function setCacheable($bool)
	{
		$this->cacheable = $bool;
	}

	public function isCacheable()
	{
		return $this->cacheable;
	}

	public function isCached()
	{
		$cache = Cache::getInstance();
		return ($this->isCacheable() && $cache->isCached($this->getFile().$this->postfix));
	}

	/**
	 * checks if a value exists
	 */
	public function exists($key)
	{
		return array_key_exists($key, self::$vars);
	}

	/**
	 * remove a value from the variable list
	 */
	public function clearVariable($name)
	{
		if(is_array($name))
		{
			foreach(array_keys($name) as $key)
			{
				self::$vars[$key] = null;
			}
		}
		else
		{
				self::$vars[$name] = null;
		}
	}

	public function lockVariable($name)
	{
		$this->lockedVars[] = $name;
	}

	public function getVariable($key=null)
	{
		if(isset($key))
		{
			//if(array_key_exists($key, $this->localVars)) return $this->localVars[$key];
			if(array_key_exists($key, self::$vars)) return self::$vars[$key];
		}
		else
		{
			return self::$vars;
		}
	}

	public function getLocalVariable($key=null)
	{
		if(isset($key))
		{
			if(array_key_exists($key, $this->localVars)) return $this->localVars[$key];
		}
		else
		{
			return $this->localVars;
		}
	}

	/**
	 * Set a template variable
	 *   
	 * @param string|array $name name of the variable
 	 * @param string|integer|array|TemplateEngine  $value value of variable
	 * @param boolean $local specify if value is in local scope or static 
	 * @param boolean $force  force to set a variable even it is locked
	 * @return void
	 * @access public
	 */
	public function setVariable($name, $value=null, $local=true, $force=false)
	{
		// do not allow to replace locekd variables
		if(!$force && in_array($name, $this->lockedVars)) return;
		//TODO remove debuggin
		if(false && $name == 'tpl_content')
		{
			$trace=debug_backtrace();
			$caller=array_shift($trace);
			echo 'called by '.$caller['function'];
			if (isset($caller['class'])) echo 'in '.$caller['class'];
			echo "\n<br />";
		}


		if(is_array($name))
		{
			if($local) 
				$this->localVars = array_merge($this->localVars, $name);
			else
				self::$vars = array_merge(self::$vars, $name);
		}
		else
		{
			$key = $name;

			if(is_object($value))
			{
				// value is an object, check if it is a template object
				if(__CLASS__ != get_class($value)) return;

				// generate text from template
				$value = $value->fetch();
			}
			
			if($local)
				$this->localVars[$name] = $value;
			else
				self::$vars[$name] = $value;
		}
	}

	/**
	 * Open, parse, and return the template file.
	 *
	 * @param string optional php script instead of file
	 */
	public function fetch()
	{
		$cache = Cache::getInstance();

		// return cached page if it exists
		if($this->isCached()) return $cache->getCache($this->getFile().$this->postfix);

		extract(self::$vars);        // Extract the vars to local namespace
		extract($this->localVars);        // Extract the vars to local namespace
		ob_start();                    // Start output buffering

		if($this->includeFile)
			include($this->getFile());   // Include the file
		else
			eval("?>".$this->getFile()."<?");

		$contents = ob_get_contents(); // Get the contents of the buffer
		ob_end_clean();                // End buffering and discard

		// save to cache
		if($this->isCacheable()) $cache->save($contents, $this->getFile().$this->postfix);

		return $contents;              // Return the contents
	}

	public function __toString()
	{
		return $this->fetch();
	}
}
