<?php
/**
 * Object to parse sql where statements
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
 * @package Database
 */
class SqlCriteria
{

	/**
	 * type of queries (also used by SqlFields)
	 */
	const REL_AND = 'and'; 
	const REL_OR 	= 'or'; 

	/**
	 * Array with SqlCriteria objects, which holds all related where statements 
	 * @var array
	 */
	protected $criteria;

	/**
	 * array with from statements
	 * @var array
	 */
	private $fieldname;
	private $value;
	private $relation;
	private $noquote;

	/**
	 * array with relation translation from variable to array type
	 * eg. = translates to in
	 */
	private $relationArray;
	private $relationNull;

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct($field=NULL, $value, $relation='=', $noquote=false)
	{
		$this->criteria = array();
		$this->fieldname = $field;
		$this->relation = $relation;
		$this->value = $value;
		$this->noquote = $noquote;
		$this->relationArray = array('=' 	=> 'in',
																'<>'	=> 'not in');

		$this->relationNull = array('=' 	=> 'is',
																'<>'	=> 'is not');
	}

	/**
	 * add SqlCriteria object
	 * @param SqlCriteria object
	 * @return void
	 */
	public function addCriteria($obj, $relation=SqlCriteria::REL_AND) 
	{
		$this->criteria[] = array('object' => $obj,'relation' => $relation);
	}


	/**
	 * retrieve where statement
	 * @return string
	 */
	public function toString()
	{
		$retval = $prefix = $postfix = '';

		if($this->criteria)
		{
			$prefix 	= "(";
			$postfix 	= ")";
		}

		$value = $this->getValue($this->value, $this->relation);

		$retval = isset($this->fieldname) ? "{$this->fieldname} $value" : $value;

		foreach($this->criteria as $item)
		{
			$obj 			= $item['object'];
			$relation = $item['relation'];

			$retval .= " ".$relation.$obj->toString();
		}
		return " $prefix$retval$postfix ";
	}

	private function getArrayRelation($type)
	{
		if(array_key_exists($type, $this->relationArray)) return $this->relationArray[$type];
		return $type;
	}

	private function getNullRelation($type)
	{
		if(array_key_exists($type, $this->relationNull)) return $this->relationNull[$type];
		return $type;
	}

	private function getValue($value, $type)
	{
		$retval = $prefix = $postfix = '';

		if(is_numeric($value))
		{
				$retval = "$type $value";
		}
		elseif(is_array($value))
		{
			if(sizeof($value) == 0) return;

			$type = $this->getArrayRelation($type);
			$prefix = "$type (";
			$postfix = ")";

			$test = current($value);
			if(is_numeric($test))
			{
				$retval .= join(",",$value);
			}
			else
			{
				foreach($value as $item)
				{
					$tmpval = '';
					if(is_array($item))
					{
						if(!array_key_exists('id', $item)) break;
						$tmpval = $item['id'];
					}
					else
						$tmpval = $item;

					if($retval) $retval .= ",";
					$retval .= (is_numeric($tmpval)) ? $tmpval : "'".addslashes($tmpval)."'";
				}
			}
		}
		else
		{
			switch(strtolower($value))
			{
				case 'null' : $retval = $this->getNullRelation($type)." ".$value; break;
				case 'now()' : $retval = "$type ".addslashes($value); break;
				default : $retval = $this->noquote ? "$type ".addslashes($value) : "$type '".addslashes($value)."'"; break;
			}
		}
		return $prefix.$retval.$postfix;
	}

}

?>
