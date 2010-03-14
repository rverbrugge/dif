<?php
/**
 * Object to parse sql statements
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

require_once('SqlCriteria.php');
require_once('SqlField.php');

/**
 * Main configuration 
 * @package Common
 */
class SqlParser
{

	/**
	 * type of queries (also used by SqlFields)
	 */
	const PKEY	= 1; 
	const NAME = 2; 
	const SEL_LIST = 4; 
	const SEL_DETAIL = 8; 
	const SEL_COUNT = 16; 
	const MOD_INSERT = 32; 
	const MOD_UPDATE = 64;
	const MOD_UPDATE_FIELDS = 128;
	const MOD_DELETE = 256;
	const SEL_USERNAME = 512; 
	const SEL_PASSWORD = 1024; 

	const ORDER_ASC = 1;
	const ORDER_DESC = 2;

	public static function getTypeSelect()
	{
		return self::SEL_LIST|self::SEL_DETAIL;
	}

	public static function getTypeModify()
	{
		return self::MOD_INSERT|self::MOD_UPDATE;
	}

	/**
	 * Array with SqlFieds objects, which holds all fields of table
	 * @var array
	 */
	protected $fields;

	/**
	 * SqlFieds object, which holds all fields of table
	 * @var array
	 */
	protected $criteria;

	/**
	 * array with from statements
	 * @var array
	 */
	protected $select;
	protected $from, $table, $tableAlias;
	protected $orderby;
	protected $groupby;

	/**
	 * Static singleton reference
	 * @var Director
	 */
	 static private $instance;

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct()
	{
		$this->criteria = $this->fields = $this->from = array();
	}

	/**
	 * insert field from top
	 * @param SqlFields object
	 * @return void
	 */
	public function getFieldNames()
	{
		$retval = array();
		foreach($this->fields as $field)
		{
			$retval[$field->getField()] = $field->getName();
		}
		return $retval;
	}

	/**
	 * insert field from top
	 * @param SqlFields object
	 * @return void
	 */
	public function unshiftField($obj) 
	{
		$field = array($obj->getName() => $obj);
		$this->fields = array_merge($field, $this->fields);
	}

	/**
	 * set fields object
	 * @param SqlFields object
	 * @return void
	 */
	public function addField($obj) 
	{
		$this->fields[$obj->getName()] = $obj;
	}

	/**
	 * get field object by name
	 * @param string field name
	 * @return SqlField object
	 */
	public function getFieldByName($name) 
	{
		if(!array_key_exists($name, $this->fields)) return NULL;

		return $this->fields[$name];
	}

	/**
	 * get field object by type
	 * @param string type name
	 * @return array of SqlField objects
	 */
	public function getFieldByType($type) 
	{
		if(!$type) return $this->fields;
		$retval = array();

		foreach($this->fields as $item)
		{
			if($item->isQueryType($type))
				$retval[] = $item;
		}
		return $retval;
	}

	/**
	 * set the value for a field
	 * @param string fieldname
	 * @param mixed value
	 * @return void
	 */
	public function setFieldValue($name, $value, $escape=true)
	{
		if(!array_key_exists($name, $this->fields)) return NULL;
		$field = $this->fields[$name];
		$field->setValue($value);
		$field->setEscape($escape);
	}

	/**
	 * retrieves values from object en assigns them to the fields of the same name
	 * @param array values
	 * @return void
	 */
	public function setFieldValues($values)
	{
		//$request = Request::getInstance();
		foreach($this->fields as &$item)
		{
			if(!array_key_exists($item->getName(), $values)) continue;
			$item->setValue($values[$item->getName()]);
		}
	}

	/**
	 * parse an array with fieldname => value 
	 * @param array searchcriteria supplied criteria
	 * @param boolean if true, a prefix will be added to the column name
	 * @return void
	 */
	public function parseCriteria($values, $prefix=true)
	{
		if(!$values || !is_array($values)) return; //throw new Exception('Nothing to parse. no values specified.');

		foreach($values as $key=>$value)
		{
			$field = $this->getFieldByName($key);
			if(!$field) continue;
			$this->addCriteria(new SqlCriteria($field->getField($prefix), $value));
		}
	}

	/**
	 * retrieves values from object en assigns them to the fields of the same name
	 * @param array values
	 * @return void
	 */
	public function validate($type)
	{
		$fields = $this->getFieldByType($type);
		foreach($fields as $item)
		{
			$item->validate();
		}
	}

	/**
	 * remove fields object
	 * @param SqlFields object
	 * @return void
	 */
	public function removeField($fieldname) 
	{
		unset($this->fields[$fieldname]);
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
	 * return primary table
	 * @return string table name
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * define primary table
	 * @param string tablename
	 * @param string alias to tablename
	 * @return void
	 */
	public function setTable($value, $alias='') 
	{
		$this->table = $value;
		$this->tableAlias = $alias;
	}

	/**
	 * set orderby clause
	 * @param string
	 * @return void
	 */
	public function addFrom($value) 
	{
		$this->from[] = $value;
	}

	/**
	 * set orderby clause
	 * @param string
	 * @return void
	 */
	public function setOrderby($value) 
	{
		$this->orderby = $value;
	}

	/**
	 * set groupby clause
	 * @param string
	 * @return void
	 */
	public function setGroupby($value) 
	{
		$this->groupby = $value;
	}

	public function setSelect($value)
	{
		$this->select = $value;
	}

	/**
	 * returns sql statement
	 * @param string
	 * @return void
	 */
	public function getSql($type='')
	{
		$retval = '';
		switch($type)
		{
			case self::SEL_COUNT :
				$retval = "{$this->select} count('x') from {$this->getFrom()} ".$this->getSqlWhere()." {$this->groupby}";
				break;
			case self::MOD_INSERT :
				$retval = "insert into {$this->table}(".$this->getSqlFieldNames($type).") values(".$this->getSqlFieldValues($type).")";
				break;
			case self::MOD_UPDATE :
				$retval = "update {$this->getFrom()} set ".$this->getSqlFieldAssignments($type)." ".$this->getSqlWhere();
				break;
			case self::MOD_UPDATE_FIELDS :
				$retval = "update {$this->getFrom()} set ".$this->getSqlFieldAssignments($type)." ".$this->getSqlWhere();
				break;
			case self::MOD_DELETE :
				$retval = "delete {$this->tableAlias} from {$this->getFrom()} ".$this->getSqlWhere();
				break;
			case self::SEL_LIST :
			case self::SEL_DETAIL :
				$retval = "{$this->select} ".$this->getSqlFields($type)." from {$this->getFrom()} ".$this->getSqlWhere()." {$this->groupby} {$this->orderby}";
				break;
			default : 
				$retval = "{$this->select} ".$this->getSqlFields($type)." from {$this->getFrom()} ".$this->getSqlWhere()." {$this->groupby} {$this->orderby}";
				break;
		}
		//Utils::debug($retval, 'query.log');
		return $retval;
	}


	public function getSqlWhere()
	{
		if(!$this->criteria) return;

		$retval = '';
		foreach($this->criteria as $item)
		{
			$obj 			= $item['object'];
			$relation = $item['relation'];

			$link = ($retval) ? $relation : 'where' ;
			$retval .= $link.$obj->toString();
		}
		return $retval;
	}

	private function getSqlFields($type)
	{
		$retval = array();
		foreach($this->fields as $obj)
		{
			if(!$obj->isQueryType($type)) continue;
			$retval[] = $obj->toString();
		}
		return join(',',$retval);
	}

	private function getSqlFieldAssignments($type)
	{
		$retval = array();
		switch($type)
		{
			case self::MOD_UPDATE_FIELDS :
				foreach($this->fields as $obj)
				{
					if(!$obj->hasValue()) continue;
					$retval[] = $obj->getField(true)." = ".$obj->getSafeValue();
				}
				break;
			default:
				foreach($this->fields as $obj)
				{
					if(!$obj->isQueryType($type)) continue;
					$retval[] = $obj->getField(true)." = ".$obj->getSafeValue();
				}
		}

		return join(',',$retval);
	}

	private function getSqlFieldNames($type)
	{
		$retval = array();
		foreach($this->fields as $obj)
		{
			if(!$obj->isQueryType($type)) continue;
			$retval[] = $obj->getField();
		}
		return join(',',$retval);
	}

	private function getSqlFieldValues($type)
	{
		$retval = array();
		foreach($this->fields as $obj)
		{
			if(!$obj->isQueryType($type)) continue;
			$retval[] = $obj->getSafeValue();
		}
		return join(',',$retval);
	}

	private function getFrom($addalias=true)
	{
		$retval = "{$this->table} ";
		if($addalias && $this->tableAlias) $retval .= "as {$this->tableAlias} ";

		$retval .= join(" ", $this->from);
		return $retval;
	}
}

?>
