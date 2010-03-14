<?php
/**
 * Object to manage sql fields 
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

/*
if (!defined('FCMS_HOME')) 
	define('FCMS_HOME', realpath(dirname(__FILE__).'/..') . '/');

require_once(FCMS_HOME.'utils/Utils.php');
*/

/**
 * Main configuration 
 * @package Database
 */
class SqlField
{

	const TYPE_STRING = 1;
	const TYPE_INTEGER = 2;
	const TYPE_BOOLEAN = 3;
	const TYPE_DATE = 4;
	const TYPE_PHONE = 5;
	const TYPE_EMAIL = 6;
	const TYPE_URL = 7;
	const TYPE_FILE = 8;

	/**
	 * array with from statements
	 * @var array
	 */
	private $field;
	private $prefix;
	private $name;
	private $title;
	private $type;
	private $querytype;
	private $mandatory;
	private $value;

	/**
	 * specify if value of field needs to be escaped with addslashes and quoted
	 */
	private $escape;


	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct($prefix, $field, $name, $title, $querytype, $type=self::TYPE_STRING, $mandatory=false)
	{
		$this->field			= $field;
		$this->name			= $name;
		$this->prefix			= $prefix;
		$this->title 			= $title;
		$this->querytype 	= $querytype;
		$this->type 			= $type;
		$this->mandatory 	= $mandatory;
		$this->escape = true;
	}

	/**
	 * set value for item
	 * @param value string
	 * @return void
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * set value for item
	 * @param value string
	 * @return void
	 */
	public function setEscape($value)
	{
		return $this->escape = $value;
	}

	/**
	 * return query type
	 * @return integer
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * return query type
	 * @return integer
	 */
	public function getSafeValue()
	{
		if(is_int($this->value) || !$this->escape) 
			return $this->value;
		else
			return "'".addslashes($this->value)."'";
	}

	/**
	 * return query type
	 * @return integer
	 */
	public function hasValue()
	{
		return isset($this->value);
	}

	public function getType()
	{
		return $this->type;
	}

	/**
	 * return query type
	 * @return integer
	 */
	public function getField($prefix=false)
	{
		return ($prefix) ? "{$this->prefix}.{$this->field}" : $this->field;
	}

	/**
	 * return query type
	 * @return integer
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * return query type
	 * @return integer
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * return query type
	 * @return integer
	 */
	public function getQueryType()
	{
		return $this->querytype;
	}

	/**
	 * check if field is part of given type
	 * @param query type constant
	 * @return boolean
	 */
	public function isQueryType($type)
	{
		return (($this->querytype & $type) == $type);
	}

	/**
	 * retrieve where statement
	 * @return string
	 */
	public function toString()
	{
		$field = ($this->prefix) ? $this->prefix.".".$this->field : $this->field;

		switch($this->type)
		{
			case self::TYPE_DATE : $field = "unix_timestamp($field)"; break;
		}

		return "$field as {$this->name}";
	}

	public function validate()
	{
		$retval = '';

		// check if field is empty and mandatory
		if($this->mandatory && !(isset($this->value) && $this->value !== '')) throw new Exception($this->title." is missing.");

		if(!$this->value) return;

		switch($this->type)
		{
			case self::TYPE_INTEGER : if(!is_numeric($this->value)) $retval = $this->title." is not a number."; break;
			//case self::TYPE_STRING : if(!is_string($this->value)) $retval = $this->title." heeft ongeldige tekenreeks."; break;
			case self::TYPE_STRING : if(!$this->value) $retval = $this->title." has illegal characters."; break;
			case self::TYPE_BOOLEAN : if(!is_bool($this->value)) $retval = $this->title." is not a boolean value."; break;
			case self::TYPE_DATE : if(!Utils::isDate($this->value)) $retval = $this->title." is not formatted as a date."; break;
			case self::TYPE_PHONE : if(!Utils::isPhone($this->value)) $retval = $this->title." is not a phone number."; break;
			case self::TYPE_EMAIL : if(!Utils::isEmail($this->value)) $retval = $this->title." is not an email address."; break;
			case self::TYPE_URL : if(!Utils::isUrl($this->value)) $retval = $this->title." is not an url."; break;
			case self::TYPE_FILE : if(!Utils::isUpload($this->value)) $retval = $this->title." is not a valid uploaded file."; break;
		}
		if($retval) throw new Exception($retval);
	}
}

?>
