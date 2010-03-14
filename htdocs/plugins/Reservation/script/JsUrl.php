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
class JsUrl extends Url 
{
	
	/**
	 * constructor
	 * @param string 	$url 			the optional URL (a string) to base the Url on
	 * @param string 	$parameters the optional URL (a string) with parameters only
	 * @return void
	 */
	public function __construct($useCurrent=false)
	{
		$this->parameters 		= array();
	}
	
	
	/**
	 * Update the value of a parameter
	 * @param string	$parameter  	name of the parameter
	 * @param mixed		$value  			value of the parameter
	 * @return void
	 */
	public function setParameter($parameter, $value, $urlencode=true) 
	{
		$this->parameters[$parameter] = $value;
	}
	
	/**
	 * Set the base/page name for the Url
	 * @param string	$path  	a string representing the new path for the Url
	 * @return void
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}
	
	 
	/**
	 * Return a string representation of the URL
	 * @param boolean	$htmlentities  	true if the returning url have to be xhtml compatible
	 * @return string
	 */
	public function getUrl($htmlentities=false) 
	{
		$retval     = $this->path;
		$parameters = join(',', $this->parameters);

		return "$retval($parameters)";
	}
}
?>
