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
class Acl extends Observer 
{
	const VIEW = 1;
	const READ = 2;
	const EDIT = 4;
	const CREATE = 8;
	const MODIFY = 16;
	const DELETE = 32;

	private $rightsList = array(self::VIEW => 'View',
															self::READ => 'Read',
															self::EDIT => 'Edit',
															self::CREATE => 'Create',
															self::MODIFY => 'Modify',
															self::DELETE => 'Delete');

	private $rightsDescriptionList = array(self::VIEW => 'Frontend user can access this page.',
															self::READ => 'Backend user can view this page in order to access to child pages.',
															self::EDIT => 'Backend user can edit tags.',
															self::CREATE => 'Backend user can create child pages',
															self::MODIFY => 'Backend user can modify settings of the page',
															self::DELETE => 'Backend user can delete the page');

	public function __construct()
	{
		parent::__construct();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('acl', 'a');
		$this->sqlParser->addField(new SqlField('a', 'acl_tree_id', 'tree_id', 'Tree id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'acl_grp_id', 'grp_id', 'Group id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'acl_rights', 'rights', 'Rights', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		//$this->sqlParser->addField(new SqlField('b', 'tree_sitegrp_id', 'sitegroup_id', 'Site group', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('c', 'grp_name', 'name', 'Group name', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'acl_create', 'createdate', 'Created', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'acl_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->sqlParser->addFrom("inner join groups as c on c.grp_id = a.acl_grp_id");

		$this->orderStatement = array('order by a.acl_tree_id asc');
	}

	public function getRightsList()
	{
		$retval = array();
		foreach($this->rightsList as $key=>$value)
		{
			$retval[] = array('id' => $key, 'name' => $value, 'description' => $this->rightsDescriptionList[$key]);
		}
		return $retval;
	}

	/**
	 * @see DbConnector::getDefaultValue
	 */
	protected function parseCriteria($sqlParser, $searchcriteria)
	{
		if(!$searchcriteria || !is_array($searchcriteria)) return;

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'usr_id'	:
					$sqlParser->addFrom("inner join usergroup as c on c.grp_id = a.acl_grp_id");
					$sqlParser->addCriteria(new SqlCriteria('c.usr_id', $value)); 
					break;
				case 'sitegroup_id'	:
					$sqlParser->addFrom("left join sitetree as b on b.tree_id = a.acl_tree_id");
					$sqlParser->addCriteria(new SqlCriteria('b.tree_sitegrp_id', $value)); 
					break;
			}
		}
	}

	protected function handlePostGetList($values)
	{
		$desc = array();
		if(($values['rights'] & self::VIEW) == self::VIEW) $desc[] = $this->rightsList[self::VIEW];
		if(($values['rights'] & self::READ) == self::READ) $desc[] = $this->rightsList[self::READ];
		if(($values['rights'] & self::EDIT) == self::EDIT) $desc[] = $this->rightsList[self::EDIT];
		if(($values['rights'] & self::CREATE) == self::CREATE) $desc[] = $this->rightsList[self::CREATE];
		if(($values['rights'] & self::MODIFY) == self::MODIFY) $desc[] = $this->rightsList[self::MODIFY];
		if(($values['rights'] & self::DELETE) == self::DELETE) $desc[] = $this->rightsList[self::DELETE];

		$values['description'] = join(", ", $desc);
		return $values;
	}

	/**
	 * handle pre insert checks and additions 
	 * eg. check for uniqueness of set default values
   *
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePreInsert
	 */
	protected function handlePreInsert($values)
	{
		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));

		$searchcriteria = array('tree_id' => $values['tree_id'], 'grp_id' => $values['grp_id']);
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria);
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('acl already exists.');
	}

	/**
	 * handle pre update checks and additions 
	 * eg. check for uniqueness of set default values
   *
	 * @param array filtered values for insertion
	 * @param array filtered values for insertion
	 * @return void
	 * @see DbConnector::handlePreInsert
	 */
	protected function handlePreUpdate($id, $values)
	{
		/*
		$searchcriteria = array('tree_id' => $values['tree_id'], 'grp_id' => $values['grp_id']);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria);
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('acl already exists.');
		*/
	}

	public function getAclList($searchcriteria)
	{
		$retval = array();
		$list = $this->getList($searchcriteria);
		foreach($list['data'] as $item)
		{
			$tree_id = $item['tree_id'];
			// create new entry
			if(!array_key_exists($tree_id, $retval)) $retval[$tree_id] = array();

			$retval[$tree_id][] = $item;
		}
		return $retval;
	}

	public function getAclGroupList($tree_id)
	{
		$retval = array();
		$searchcriteria = array('tree_id' => $tree_id);

		$list = $this->getList($searchcriteria);
		foreach($list['data'] as $item)
		{
			$grp_id = $item['grp_id'];
			// create new entry
			if(!array_key_exists($grp_id, $retval)) $retval[$grp_id] = array();
			if(($item['rights'] & self::VIEW) == self::VIEW) $retval[$grp_id][] =  self::VIEW;
			if(($item['rights'] & self::READ) == self::READ) $retval[$grp_id][] =  self::READ;
			if(($item['rights'] & self::EDIT) == self::EDIT) $retval[$grp_id][] =  self::EDIT;
			if(($item['rights'] & self::CREATE) == self::CREATE) $retval[$grp_id][] = self::CREATE;
			if(($item['rights'] & self::MODIFY) == self::MODIFY) $retval[$grp_id][] = self::MODIFY;
			if(($item['rights'] & self::DELETE) == self::DELETE) $retval[$grp_id][] = self::DELETE;
		}
		return $retval;
	}

	/**
	 * Change the tree node id of a tag.
	 * The change will not proceed if the tree node id is shared with other sitegroups.
	 * This because the tag will than be 'stolen' from the other site group.
	 *
	 * This function will throw an error if the destination tree id already exists.
	 * This because tree id must be unique.
	 */
	public function changeNode($sourceNodeId, $destinationNodeId)
	{
		// check if we have an acl for this node
		$uniqueKey = array('tree_id' => $sourceNodeId);
		if(!$this->exists($uniqueKey)) return;

		// check if destination is available
		$uniqueKey = array('tree_id' => $destinationNodeId);
		if($this->exists($uniqueKey)) throw new Exception("Destination node contains acl's. Delete one of the alc's fisrt.");

		// move to new tree node
		$searchcriteria = array('tree_id' => $sourceNodeId);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria, false);
		$this->parseCriteria($sqlParser, $searchcriteria);
		$sqlParser->setFieldValue('tree_id', $destinationNodeId);

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}
}

?>
