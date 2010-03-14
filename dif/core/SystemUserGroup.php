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
class SystemUserGroup extends Observer 
{
	public function __construct()
	{
		parent::__construct();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('groups', 'a');
		$this->sqlParser->addField(new SqlField('a', 'grp_id', 'id', 'Identifier', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'grp_name', 'name', 'AchterNaam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));

		$this->orderStatement = array('order by a.grp_name asc');
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
				case 'no_id' : $sqlParser->addCriteria(new SqlCriteria('a.grp_id', $value, '<>')); break;
			}
		}
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
		$values['createdate'] = date('Y-m-d');

		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('grp_name', $values['name']));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('gebruikersgroep bestaat reeds.');
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
		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('grp_name', $values['name']));
		$sqlParser->addCriteria(new SqlCriteria('grp_id', $id['id'], '<>'));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('gebruikersgroep bestaat reeds.');
	}

	protected function handlePreDelete($id, $values)
	{
		// remove acls
		$acl = new Acl();
		$searchcriteria = array('grp_id' => $id['id']);
		$acl->delete($searchcriteria);

		// remove users
		$this->removeUser($id);
	}

	/**
	 * remove user from group
   *
	 * @param array key of user
	 * @return string name of user
	 * @see AuthenticationUser::getUserName
	 */
	public function removeUser($groupId)
	{
		$query = sprintf("delete from  usergroup where grp_id = %d", $groupId['id']);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

}

?>
