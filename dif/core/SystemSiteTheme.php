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
class SystemSiteTheme extends Observer 
{

	public function __construct()
	{
		parent::__construct();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('sitetheme', 'a');
		$this->sqlParser->addField(new SqlField('a', 'tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'theme_id', 'theme_id', 'Thema', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('b', 'theme_name', 'name', 'Thema', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('b', 'theme_classname', 'classname', 'Klasse', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('b', 'theme_active', 'active', 'Active state', SqlParser::getTypeSelect(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('b', 'theme_dif_version', 'dif_version', 'DIF Version', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));

		$this->sqlParser->addFrom("left join theme as b on b.theme_id = a.theme_id");
	}

	/**
	 * @see DbConnector::getDefaultValue
	 */
	protected function parseCriteria($sqlParser, $searchcriteria)
	{
		if(!$searchcriteria || !is_array($searchcriteria)) return;
		return;

/*
		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
			}
		}
		*/
	}

	protected function handlePostGetList($values)
	{
		// check if plugin is active
		$values['activated'] = ($values['active'] && $values['dif_version'] >= $this->director->getRequiredDifVersion()) ;
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$values['activated'] = ($values['active'] && $values['dif_version'] >= $this->director->getRequiredDifVersion()) ;
		return $values;
	}


	/**
	 * Change the tree node id of a theme.
	 * The change will not proceed if the tree node id is shared with other sitegroups.
	 * This because the theme will than be 'stolen' from the other site group.
	 *
	 * This function will throw an error if the destination tree id already exists.
	 * This because tree id must be unique.
	 */
	public function changeNode($sourceNodeId, $destinationNodeId)
	{
		// check if we have a theme for this node
		$uniqueKey = array('tree_id' => $sourceNodeId);
		if(!$this->exists($uniqueKey)) return;

		// check if destination is available
		$uniqueKey = array('tree_id' => $destinationNodeId);
		if($this->exists($uniqueKey)) throw new Exception("Destination node contains a theme. Delete one of the themes fisrt.");

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
