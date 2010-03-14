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
class SystemSitePlugin extends Observer 
{

	private $tagList;

	// view type constant
	// specifies on which view to load a plugin
	const VIEW_ALL = 0;
	const VIEW_OVERVIEW = 1;
	const VIEW_NONE = 2;

	const TYPE_NORMAL = 1;
	const TYPE_VIRTUAL = 2;
	const TYPE_VIRTUAL_OVERRIDE = 3;
	const TYPE_VIRTUAL_OVERRIDE_SETTINGS = 4;

	public $viewtypes 	= array(self::VIEW_ALL 	=> 'Always show plugin',
															self::VIEW_OVERVIEW	=> 'Only show plugin in overview',
															self::VIEW_NONE	=> 'Hide plugin');
	private $viewtypelist;


	public function __construct()
	{
		parent::__construct();
		$this->tagList = array();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('siteplugin', 'a');
		$this->sqlParser->addField(new SqlField('a', 'tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME|SqlParser::PKEY, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'plug_id', 'plugin_id', 'Plugin', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'plug_type', 'plugin_type', 'Plugin type', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'plug_view', 'plugin_view', 'Plugin view', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, false));
		$this->sqlParser->addField(new SqlField('a', 'plug_recursive', 'recursive', 'Recursive', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN, false));
		$this->sqlParser->addField(new SqlField('a', 'usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'createdate', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('c', 'plug_classname', 'classname', 'Naam klasse', SqlParser::getTypeSelect()|SqlParser::NAME, SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('c', 'plug_name', 'plugin_name', 'Plugin Name', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('c', 'plug_active', 'active', 'Active state', SqlParser::getTypeSelect(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('c', 'plug_dif_version', 'dif_version', 'DIF Version', SqlParser::getTypeSelect(), SqlField::TYPE_STRING));

		$this->sqlParser->addFrom("left join plugin as c on c.plug_id = a.plug_id");
	}

	public function getViewTypeList()
	{
		if(isset($this->viewtypelist)) return $this->viewtypelist;

		$this->viewtypelist = array();
		foreach($this->viewtypes as $key=>$value)
		{
			$this->viewtypelist[$key] = array('id' => $key, 'name' => $value);
		}
		return $this->viewtypelist;
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
				case 'no_tag' : $sqlParser->addCriteria(new SqlCriteria('a.tag', $value, '<>')); break;
				case 'no_plugin_view' : $sqlParser->addCriteria(new SqlCriteria('a.plug_view', $value, '<>')); break;
				case 'recursive_tree_id' : 
					$self = $value['self'];
					$recursive = $value['recursive'];

					$search = new SqlCriteria('a.tree_id', $self); 
					if($recursive)
					{
						$recsearch = new SqlCriteria('a.tree_id', $recursive); 
						$recsearch->addCriteria(new SqlCriteria('a.plug_recursive', 1), SqlCriteria::REL_AND); 
						$search->addCriteria($recsearch, SqlCriteria::REL_OR); 
					}

					$sqlParser->addCriteria($search);
					break;
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
			case 'recursive' : return 0; break;
		}
	}

	/**
	 * filters field value like checkbox conversion and date conversion
	 *
	 * @param key 	name of field
	 * @param value 	value to be filtered
	 * @return  filtered value
	 */
	protected function filterFields($fields)
	{
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$fields['usr_id'] = $userId['id'];
		$fields['recursive'] = (array_key_exists('recursive', $fields) && $fields['recursive']);
		return $fields;
	}

	protected function handlePostGetList($values)
	{
		// check if plugin is active
		$values['activated'] = (!$values['classname'] || ($values['active'] && $values['dif_version'] >= $this->director->getRequiredDifVersion()));
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$values['activated'] = ($values['active'] && $values['dif_version'] >= $this->director->getRequiredDifVersion()) ;
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$usr_id = $userId['id'];
		$this->sqlParser->setFieldValue('usr_id', $usr_id);
	}

	protected function handlePreDelete($id, $values)
	{
		$classname = $values['classname'];
		if(!$classname) return;

		$plugin = $this->director->pluginManager->getPlugin($classname);
		$plugin->deletePlugin($values, $values['plugin_type']);

/*
		$pluginlist = $plugin->getPluginList($values['tag'], $values['tree_id'], $values['plugin_type']);
		foreach($pluginlist['data'] as $item)
		{
			$plugin->deletePlugin($item, $values['plugin_type']);
		}
		*/
	}

	public function flatten($var)
	{
		$retval = array();
		foreach($var as $key=>$value)
		{
			if(is_array($value))
				$retval[$key] = $this->flatten($value);
			else
				$retval[$key] = $value;
		}
		return $retval;
	}

	private function filterList($list, $key, $value)
	{
		$retval = array();
		foreach($list as $tmpkey=>$tmpval)
		{
			if($tmpval[$key] == $value) 
				$retval[$tmpkey] = $tmpval;
		}
		return $retval;
	}

	private function getFilteredTagList($searchcriteria)
	{
		if(!array_key_exists($searchcriteria['tree_id'], $this->tagList)) return array();

		$retval = $this->tagList[$searchcriteria['tree_id']];

		if(array_key_exists('classname', $searchcriteria))
			$retval = $this->filterList($retval, 'classname', $searchcriteria['classname']);

		if(array_key_exists('plugin_type', $searchcriteria))
			$retval = $this->filterList($retval, 'plugin_type', $searchcriteria['plugin_type']);

		if(array_key_exists('tag', $searchcriteria))
			$retval = $this->filterList($retval, 'tag', $searchcriteria['tag']);

		return $retval;
	}

	/**
	 * Retrieves a list of tags that exists within the searchcriteria (usualy the search on a tree_id)
	 * Each item has the tag name as key. The result includes a classname field which can be used to create the related plugin. 
	 * 
	 * @param array searchcriteria (only the tree_id criteria makes sense)
	 * @return array key is tag name, result is siteplugin query result including classname of plugin
	 */
	public function getTagList($searchcriteria)
	{
		if(!$searchcriteria) return array();
		if(!array_key_exists('tree_id', $searchcriteria)) throw new Exception("tree_id not set in searchcriteria :".__FUNCTION__);

		// try to get the buffered result
		$retval = $this->getFilteredTagList($searchcriteria);
		if($retval) return $retval;

		// result is new, fetch it
		// get both the plugins connected to the tree node and the plugins connected to te root of the tree (default plugins)
		$tree = $this->director->siteTree;
		$tree_id =  $searchcriteria['tree_id'];
		$root_id = $tree->getRootId();
		$treesearch['recursive_tree_id'] = array('self' => array($tree_id, $root_id), 'recursive' => $tree->getAncestorList($tree_id));

		// if viewtype is not overview, skip plugins that are not allowed 
		$view = ViewManager::getInstance();

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($treesearch);
		$this->parseCriteria($sqlParser, $treesearch);

		$query = $sqlParser->getSql(SqlParser::SEL_LIST);
		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$result = array();
		$tmpresult= array();

		while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC))
		{
			// handle post getlist
			$row = $this->handlePostGetList($row);
			if($row['tree_id'] != $tree_id)
				$row['virtual_type'] = self::TYPE_VIRTUAL; // virtual indicates the plugin originates from a parent and is *not* edited by the current tree id
			elseif(!$row['plugin_id'])
				$row['virtual_type'] = self::TYPE_VIRTUAL_OVERRIDE_SETTINGS; // virtual override settings indicates if settings have been changed of a virtual plugin
			else
				$row['virtual_type'] = self::TYPE_NORMAL;


			// last tree id is where the settings of the plugin have been changed, not necessarily where the plugin has been changed
			$row['last_tree_id'] = $row['tree_id'];
			// reset the tree is if it only contains settings and not a plugin
			if($row['virtual_type'] == self::TYPE_VIRTUAL_OVERRIDE_SETTINGS) $row['tree_id'] = 0;

			// multiple virtual plugins can exist in the tree, order the plugins by tree depth
			$tmpresult[$row['tag']][$tree->getDepth($row['last_tree_id'])] = $row;
		}

		// purge multiple plugins per tag (virtual plugins) to get the shortes parent tree id (or self tree id)
		foreach($tmpresult as $tag => $plugin_list)
		{
			$item = array();
			ksort($plugin_list);
			foreach($plugin_list as $plugin)
			{
				// copy data if it exists (to merge different settings from each parent tree node)
				foreach($plugin as $key=>$value)
				{
					if($value) $item[$key] = $value;
				}
			}
			// virtual indicates the plugin originates from a parent node
			if(sizeof($plugin_list) > 1 && $item['virtual_type'] == self::TYPE_NORMAL)
				$item['virtual_type'] = self::TYPE_VIRTUAL_OVERRIDE;

			// skip if not in admin mode and special display types are defined
			if(!$this->director->isAdminSection())
			{
				if($item['plugin_view'] == self::VIEW_NONE) continue;
				if(!$view->isType(ViewManager::OVERVIEW) && $item['plugin_view'] != self::VIEW_ALL)  continue;
			}
			$result[$tag] = $item;
		}
		
		$this->tagList[$searchcriteria['tree_id']] = $result;
		return $this->getFilteredTagList($searchcriteria);
	}

/*
	private function handleGetTagList($searchcriteria, $root_id)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria);
		$this->parseCriteria($sqlParser, $searchcriteria);

		$query = $sqlParser->getSql(SqlParser::SEL_LIST);
		//if(!$virtual) echo $query;

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = array();
		while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC))
		{
			$row['virtual'] = ($row['tree_id'] == $root_id);
			//$row['virtual'] = $virtual;
			$retval[$row['tag']] = $row;
		}
		return $retval;
	}
	*/

	public function updateTag($tree_id, $tag, $new_tree_id, $new_tag, $viewtype=NULL, $recursive)
	{
		$key = array('tree_id' => $tree_id, 'tag' => $tag);
		$new_key = array('tree_id' => $new_tree_id, 'tag' => $new_tag);

		$detail = $this->getDetail($key);
		$this->infoLog(__FUNCTION__, $detail);

		// default values
		if(!$new_tag) $new_tag = $tag;
		if(!$new_tree_id) $new_tree_id = $tree_id;

		if($tag != $new_tag || $tree_id != $new_tree_id)
		{
			// check if destination is already in use
			if($this->exists($new_key)) throw new Exception('Destination node and tag already in use. Select a different node or tag');

			$plugin = $this->director->pluginManager->getPlugin($detail['classname']);
			$plugin->updateTag($tree_id, $tag, $new_tree_id, $new_tag, $detail['plugin_type']);
		}

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($key, false);
		$this->parseCriteria($sqlParser, $key);

		$sqlParser->setFieldValue('tag', $new_tag);
		$sqlParser->setFieldValue('tree_id', $new_tree_id);
		$sqlParser->setFieldValue('plugin_view', $viewtype);
		$sqlParser->setFieldValue('recursive', $recursive ? 1 : 0);

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
		$this->director->notify($this, $key, Director::UPDATE);

	}

	private function updateTreeId($sourceNodeId, $destinationNodeId)
	{
		// move to new tree node
		$searchcriteria = array('tree_id' => $sourceNodeId);

		$pluginlist = $this->getList($searchcriteria);
		foreach($pluginlist['data'] as $item)
		{
			$this->infoLog(__FUNCTION__, $item);

			$plugin = $this->director->pluginManager->getPlugin($item['classname']);
			$plugin->updateTreeId($sourceNodeId, $destinationNodeId, $item['plugin_type']);
		}

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
		$query = sprintf("select a.tree_id from siteplugin as a inner join siteplugin as b on b.tag = a.tag where a.tree_id = %d and b.tree_id = %d", $destinationNodeId, $sourceNodeId);
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
		if($res->numRows() > 0) throw new Exception("Plugins connected to same tag names in destination site group. Remove either one of them first.");

		// notify and update plugins
		$this->updateTreeId($sourceNodeId, $destinationNodeId);

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
