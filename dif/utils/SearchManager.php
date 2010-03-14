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
 * Create urls
 * @package utils
 */
class SearchManager 
{
	
	const SESSION_KEY = 'searchmanager';

	/**
	 * array of URL parameters
	 *
	 * @private array
	 */
	private $parameters;
	private $exclude;
	private $url;
	private $request;
	private $sessionParameters;


	
	/**
	 * constructor
	 * @param string 	$url 			the optional URL (a string) to base the Url on
	 * @param string 	$parameters the optional URL (a string) with parameters only
	 * @return void
	 */
	public function __construct()
	{
		$this->parameters 		= array();
		$this->exclude 		= array();
		$this->url = new Url(true);
		$this->request = Request::getInstance();
		$this->sessionParameters = array();

		if($this->request->getValue('REQUEST_METHOD', Request::SERVER) == 'POST' && $this->request->exists(self::SESSION_KEY, Request::SESSION))
			$this->sessionParameters = $this->request->getValue(self::SESSION_KEY, Request::SESSION);
	}
	
	public function setUrl($url)
	{
		$this->url = $url;
	}


	public function addParameters($parameters)
	{
		foreach($parameters as $value)
		{
			$this->setParameter($value);
		}
	}
	
	
	/**
	 * Get the value of the specified Url parameter
	 * @param string	$name  	name of the parameter
	 * @return mixed
	 */
	public function getParameter($name)
	{
		if(key_exists($name, $this->parameters)) return $this->parameters[$name];
	}
	
	/**
	 * Returns all parameters as array[<name>]=<value>
	 * @return array
	 */
	public function getParameterList()
	{
		return $this->parameters;
	}
	
	/**
	 * Returns all parameters as array[<name>]=<value>
	 * @return array
	 */
	public function getExcludeList()
	{
		return $this->exclude;
	}
	
	/**
	 * Update the value of a parameter
	 * @param string	$parameter  	name of the parameter
	 * @param mixed		$value  			value of the parameter
	 * @return void
	 */
	public function setParameter($parameter)
	{
		$this->parameters[$parameter] = $parameter;
	}
	
	 /**
	 * clears a parameter
	 * @param string	$parameter  	name of the parameter
	 * @return void
	 */
	public function clearParameter($parameter) 
	{
	  if(array_key_exists($parameter, $this->parameters)) unset($this->parameters[$parameter]);
	}

	public function setExclude($parameter)
	{
		$this->exclude[$parameter] = $parameter;
	}

	public function getSearchParameterList()
	{
		$retval = array();

		foreach($this->parameters as $item)
		{
			$value = $this->getValue($item);
			if(!$value) continue;

			$retval[$item] = $value;
		}
		return $retval;
	}

	private function getValue($parameter)
	{
		$retval = null;

		if($this->request->exists($parameter))
			$retval = $this->request->getValue($parameter);
		elseif(array_key_exists($parameter, $this->sessionParameters))
			$retval = $this->sessionParameters[$parameter];

		return $retval;
	}

	public function getMandatoryParameterList()
	{
		$list = $this->url->getParameterList();
		$searchparam = $this->getParameterList();
		$exclude = $this->getExcludeList();
		return array_diff_key($list, $searchparam, $exclude);
	}

	public function saveList()
	{
		$list = array_merge($this->getMandatoryParameterList(), $this->getSearchParameterList());
		$this->request->setValue(self::SESSION_KEY, $list);
	}
	
}
?>
