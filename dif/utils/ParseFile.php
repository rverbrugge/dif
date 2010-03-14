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
 * ParseFile class
 * @package Utils
 */
class ParseFile
{

  /**
   * array of variables
   * @var array
   */
  private $vars = array();

	/**
	 * template file
	 * @var string
	 */
	private $source;
	private $destination;

	/**
	 * Constructor
	 *
	 * @param string filename of template content
	 * @param string include file (if it is a filename) or evaluate file (if it is the content itself)
	 */
	public function __construct()
	{
	}

	public function setSource($source)
	{
		$this->source = $source;
	}

	public function getSource()
	{
		return $this->source;
	}

	public function setDestination($destination)
	{
		$this->destination = $destination;
	}

	public function getDestination()
	{
		return $this->destination;
	}

	public function isCached()
	{
		$cache = Cache::getInstance();
		return ($this->isCacheable() && $cache->isCached($this->getDestination()));
	}

	/**
	 * checks if a value exists
	 */
	public function exists($key)
	{
		return array_key_exists($key, $this->vars);
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
				$this->vars[$key] = null;
			}
		}
		else
		{
				$this->vars[$name] = null;
		}
	}

	public function getVariable($key)
	{
		if(array_key_exists($key, $this->vars)) return $this->vars[$key];
	}

	public function getVariables()
	{
			return $this->vars;
	}

	/**
	 * Set a template variable
	 *   
	 * @param mixed $name 
 	 * @param mixed $value 
	 * @param boolean $append
	 * @return void
	 * @access public
	 */
	function setVariable($name, $value=null)
	{
		if(!$name) return;

		if(is_array($name))
			$this->vars = array_merge($this->vars, $name);
		elseif($value)
			$this->vars[$name] = $value;
	}

	/**
	 * Open, parse, and return the template file.
	 *
	 * @param string optional php script instead of file
	 */
	function save()
	{
		$includeFile = is_file($this->getSource());

		$template = new TemplateEngine($this->getSource(), $includeFile);

		//$template->setVariable($this->vars,  null);
		$template->setVariable($this->vars,  null, false);
		if(!($fh = fopen($this->getDestination(), "w"))) throw new Exception("could not open file {$this->getDestination()} for writing. in ".__CLASS__);

		fputs($fh, $template->fetch());
		fclose($fh);
		chmod($this->getDestination(), 0644);
	}

	public function fetch()
	{
		$includeFile = is_file($this->getSource());

		$template = new TemplateEngine($this->getSource(), $includeFile);

		$template->setVariable($this->vars,  null, false);
		return $template->fetch();
	}
}
