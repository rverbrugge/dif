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
class FormRecordItem extends DbConnector
{
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
		
		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('form_record_item', 'a');
		$this->sqlParser->addField(new SqlField('a', 'item_id', 'id', 'Id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'item_rcd_id', 'rcd_id', 'Record id', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'item_elm_id', 'elm_id', 'Element id', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'item_classname', 'classname', 'Class name', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'item_weight', 'weight', 'Index', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'item_name', 'name', 'Name', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'item_value', 'value', 'Waarde', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, false));

		$this->sqlParser->addFrom("inner join form_record as b on b.rcd_id = a.item_rcd_id");
	}

/*-------- DbConnector insert function {{{------------*/

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	protected function parseCriteria($sqlParser, $searchcriteria)
	{
		if(!$searchcriteria || !is_array($searchcriteria)) return;

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'tree_id' : $sqlParser->addCriteria(new SqlCriteria('b.rcd_tree_id', $value)); break;
				case 'tag' : $sqlParser->addCriteria(new SqlCriteria('b.rcd_tag', $value)); break;
			}
		}
	}

	/**
	 * filters field values like checkbox conversion and date conversion
	 *
	 * @param array unfiltered values
	 * @return array filtered values
	 * @see DbConnector::filterFields
	 */
	public function filterFields($fields)
	{
		$fields['value'] = strip_tags($fields['value']);
		
		return $fields;
	}

	//}}}

	public function updateName($tree_id, $tag, $oldName, $newName)
	{
		$search = array('tree_id' => $tree_id, 'tag' => $tag, 'name' => $oldName);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($search, false);
		$this->parseCriteria($sqlParser, $search);
		$sqlParser->setFieldValue('name', $newName);

		$db = $this->getDb();

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	public function getColumns($tree_id, $tag)
	{
		$retval = array();
		$db = $this->getDb();
		$query = sprintf("select distinct a.item_name as name from form_record_item as a inner join 
											form_record as b on b.rcd_id = a.item_rcd_id 
											where b.rcd_tree_id = %d 
											and b.rcd_tag = '%s' 
											group by a.item_name
											order by a.item_weight asc", $tree_id, $tag);

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) 
		{
			// lowercase columnnames to get a unique index 
			$retval[] = strtolower($row['name']);
		}
		return $retval;
	}

	public function getItems($searchcriteria)
	{
		//$this->orderStatement = array('order by a.item_weight asc');
		$this->orderStatement = array('order by b.rcd_create desc');

		$sqlParser = clone $this->sqlParser;

		$sqlParser->parseCriteria($searchcriteria);
		$this->parseCriteria($sqlParser, $searchcriteria);

		$sqlParser->setOrderby($this->getOrder(''));

		$query = $sqlParser->getSql(SqlParser::SEL_LIST);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = array();
		while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) 
		{
			$rcd_id = $row['rcd_id'];
			if(!array_key_exists($rcd_id, $retval)) $retval[$rcd_id] = array();
			$retval[$rcd_id][] = $row;
		}
		return $retval;
	}

	public function getRecordElementList($rcd_id)
	{
		$sqlParser = clone $this->sqlParser;

		$searchcriteria = array('rcd_id' => $rcd_id);
		$sqlParser->parseCriteria($searchcriteria);
		$this->parseCriteria($sqlParser, $searchcriteria);

		$query = $sqlParser->getSql(SqlParser::SEL_LIST);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = array();
		while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) 
		{
			$retval[$row['elm_id']] = $row;
		}
		return $retval;
	}
}

?>
