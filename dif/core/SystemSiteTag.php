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
class SystemSiteTag extends Observer 
{

	public function __construct()
	{
		parent::__construct();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('sitetag', 'a');
		$this->sqlParser->addField(new SqlField('a', 'tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'parent_tag', 'parent_tag', 'Parent tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'tags', 'tags', 'Tags', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'remove_container', 'remove_container', 'Remove parent', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'template', 'template', 'Template', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'stylesheet', 'stylesheet', 'Stylesheet', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
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
				case 'no_parent_tag' : $sqlParser->addCriteria(new SqlCriteria('a.parent_tag', $value, '<>')); break;
			}
		}
	}

	/**
	 * returns default value of a field
	 * @return mixed
	 * @see DbConnector::getDefaultValue
	 */
	public function getDefaultValue($fieldname)
	{
		switch($fieldname)
		{
			case 'remove_container' : return 1; break;
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
		// convert posted checkbox value to boolean
		if(array_key_exists('stylesheet', $fields)) $fields['stylesheet'] = trim($fields['stylesheet']);
		$fields['tags'] = trim($fields['tags'], "\r\n\t ");

		$fields['remove_container'] = (array_key_exists('remove_container', $fields) && $fields['remove_container']);

		return $fields;
	}

	private function splitTags($tags)
	{
		$result = array();
		foreach(preg_split("/\s+/", $tags, -1, PREG_SPLIT_NO_EMPTY) as $item)
		{
			$result[] = array('tag' 								=> trim($item, ":"),
												'inherit_container' => !(substr($item, -1) == ':'));
		}
		return $result;
	}

	protected function handlePostGetList($values)
	{
		$values['child_tags'] = $this->splitTags($values['tags']);
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$values['child_tags'] = $this->splitTags($values['tags']);
		return $values;
	}


	public function getTagList($searchcriteria)
	{
		if(!$searchcriteria) return array();

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria);
		$this->parseCriteria($sqlParser, $searchcriteria);

		$query = $sqlParser->getSql(SqlParser::SEL_LIST);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = array();
		while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC))
		{
			$retval[$row['parent_tag']] = preg_split("/\s+/", str_replace(':','',$row['tags']), -1, PREG_SPLIT_NO_EMPTY);
		}
		return $retval;
	}

	protected function handlePreDelete($id, $values)
	{
		/*
		$sitePlugin = $this->director->siteManager->systemSite->getSitePlugin();
		$tagList = $this->getTagList($id);
		$tags = array();
		foreach($tagList as $item)
		{
			$tags = array_merge($tags, $item);
		}
		$search = array('tag' => $tags);
		if($sitePlugin->exists($search)) throw new Exception("There is a plugin linked to one of the following tags: ".join(', ',$tags));
		*/
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
		// check if we have a theme for this node
		$uniqueKey = array('tree_id' => $sourceNodeId);
		if(!$this->exists($uniqueKey)) return;

		$db = $this->getDb();

		// check if we have conflicting tag names
		$query = sprintf("select a.tree_id from sitetag as a inner join sitetag as b on b.parent_tag = a.parent_tag where a.tree_id = %d and b.tree_id = %d", $destinationNodeId, $sourceNodeId);
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
		if($res->numRows() > 0) throw new Exception("Conflicting user defined tags in destination site group. Remove either one of them first.");

		// move to new tree node
		$searchcriteria = array('tree_id' => $sourceNodeId);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria, false);
		$this->parseCriteria($sqlParser, $searchcriteria);
		$sqlParser->setFieldValue('tree_id', $destinationNodeId);

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);


		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

}

?>
