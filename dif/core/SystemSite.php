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

require_once('SiteTree.php');
require_once('SystemSiteTag.php');
require_once('SystemSiteTheme.php');
require_once('SystemSitePlugin.php');
require_once('Acl.php');

/**
 * Main configuration 
 * @package Common
 */
class SystemSite extends Observer 
{

	const HIDE_LOGIN  = 1;
	const HIDE_LOGOUT = 2;

	/**
	 * Offset to calculate next weight (index) value of tree node
	 * @var integer
	 */
	const WEIGHT_OFFSET = 10;

	protected $tree;

	/**
	 * helper objects
	 * @var SitePlugin
	 */
	private $sitePlugin;

	/**
	 * helper objects
	 * @var SiteTheme
	 */
	private $siteTheme;

	/**
	 * helper objects
	 * @var SiteTag
	 */
	private $siteTag;

	/**
	 * helper objects
	 * @var SiteTag
	 */
	private $siteGroup;

	/**
	 * Array with tree node id as key and theme information as value
	 * Holds the used theme at the specific tree node id
	 * @var array
	 */
	private $theme = array();

	private $taglist = array();

	/**
	 * Array with visibility types
	 * @var array
	 */
	private $hideList = array(self::HIDE_LOGIN 	=> 'if logged in',
														self::HIDE_LOGOUT => 'if not logged in');


	/**
	 * temporary ref to parent id. it is used when a new item is beeing created and some info related to the parent has to be fetched
	 * @var integer
	 */
	protected $parentId;
	
	public function __construct()
	{
		parent::__construct();

		$this->tree = new SiteTree($this);

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('sitetree', 'a');
		$this->sqlParser->addField(new SqlField('a', 'tree_id', 'id', 'Identifier', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'tree_parent_id', 'parent', 'Parent node', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'tree_sitegrp_id', 'sitegroup_id', 'Site group', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'tree_weight', 'weight', 'Gewicht', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'tree_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'tree_visible', 'visible', 'Zichtbaar status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'tree_hide', 'hide', 'Verberg mode', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'tree_startpage', 'startpage', 'Startpagina', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'tree_online', 'online', 'Online datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'tree_offline', 'offline', 'Offline datum', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'tree_name', 'name', 'Naam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'tree_title', 'title', 'Titel', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'tree_url', 'url', 'Url', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'tree_external', 'external', 'Externe url', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'tree_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'tree_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'tree_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'tree_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//$this->sqlParser->addFrom("left join groups as d on d.grp_id = a.tree_grp_id");

		$this->orderStatement = array('order by a.tree_parent_id asc, a.tree_weight asc, a.tree_name asc');
	}

	public function getTree()
	{
		return $this->tree;
	}

/*-------- DbConnector insert function {{{------------*/
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
				case 'no_id' : $sqlParser->addCriteria(new SqlCriteria('a.tree_id', $value, '<>')); break;
				/*
				case 'activated' : 
					// hide page if logged in or not
					$authentication = Authentication::getInstance();
					$sqlParser->addCriteria(new SqlCriteria('a.tree_hide', ($authentication->isLogin()) ? self::HIDE_LOGIN : self::HIDE_LOGOUT, '<>'));

					// only active pages
					$sqlParser->addCriteria(new SqlCriteria('a.tree_active', 1));

					// only pages that are online
					$online = new SqlCriteria('a.tree_online', 'now()', '>=');
					$online->addCriteria(new SqlCriteria('unix_timestamp(a.tree_online)', 0, '='), SqlCriteria::REL_OR);

					$offline = new SqlCriteria('a.tree_offline', 'now()', '<');
					$offline->addCriteria(new SqlCriteria('unix_timestamp(a.tree_offline)', 0, '='), SqlCriteria::REL_OR);

					$sqlParser->addCriteria($offline); 
					$sqlParser->addCriteria($online); 
					break;       
			*/
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
			case 'active' : return 0; break;
			case 'visible' : return 1; break;
			case 'weight' : return $this->getNextWeight($this->getParentId()); break;
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
		$fields['active'] = (array_key_exists('active', $fields) && $fields['active']);
		$fields['visible'] = (array_key_exists('visible', $fields) && $fields['visible']);
		$fields['startpage'] = (array_key_exists('startpage', $fields) && $fields['startpage']);
		$fields['url'] = array_key_exists('url', $fields) ? strtolower($fields['url']) : '';
		$fields['external'] = Utils::isUrl($fields['url']) ? true : false;

		//$fields['online'] = (array_key_exists('online', $fields) && $fields['online']) ? Utils::convertDate($fields['online']) : '';
		//$fields['offline'] = (array_key_exists('offline', $fields) && $fields['offline']) ? Utils::convertDate($fields['offline']) : '';

		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$fields['usr_id'] = $userId['id'];

		return $fields;
	}

	private function deselect($sitegroupId)
	{
		$query = sprintf("update sitetree set tree_startpage = 0 where tree_sitegrp_id = %d", $sitegroupId);
		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}


	protected function handlePostGetList($values)
	{
		// hide page if logged in or not
		$activated = 1;
		$date = mktime(0,0,0);

		if(!$this->director->isAdminSection())
		{
			$authentication = Authentication::getInstance();
			$login = $authentication->isLogin();

			if(($login && $values['hide'] == self::HIDE_LOGIN) || (!$login && $values['hide'] == self::HIDE_LOGOUT)) $activated = 0;
		}

		if(!$values['active']) 
			$activated = 0;
		elseif($values['online'] > 0 && $values['online'] > $date)
			$activated = 0;
		elseif($values['offline'] > 0 && $values['offline'] <= $date)
			$activated = 0;

		$values['activated'] = $activated;
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$values['hide_name'] = $this->getHideDescr($values['hide']);
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
		// retrieve responsible user
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$usr_id = $userId['id'];
		$this->sqlParser->setFieldValue('own_id', $usr_id);

		// create audit entries
		$sitegroupId = $this->getSiteGroup()->getCurrentId();
		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));
		$this->sqlParser->setFieldValue('sitegroup_id', $sitegroupId);

		// check if url already exists
		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('tree_parent_id', $values['parent']));
		$sqlParser->addCriteria(new SqlCriteria('tree_url', $values['url']));
		$sqlParser->addCriteria(new SqlCriteria('tree_sitegrp_id', $sitegroupId));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('url bestaat reeds.');

		// if this is the startpage, deselect the rest because a startpage is unique
		if($values['startpage']) $this->deselect($sitegroupId);

		// check if index is unique. if not, reindex nodes
		$searchcriteria = array('weight' => $values['weight'],
														'parent'	=> $values['parent']);
		if($this->exists($searchcriteria))
		{
			$this->increaseWeight($values['parent'], $values['weight']);
		}

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
		
		$sitegroupId = $this->getSiteGroup()->getCurrentId();

		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('tree_parent_id', $values['parent']));
		$sqlParser->addCriteria(new SqlCriteria('tree_url', $values['url']));
		$sqlParser->addCriteria(new SqlCriteria('tree_sitegrp_id', $sitegroupId));
		$sqlParser->addCriteria(new SqlCriteria('tree_id', $id['id'], '<>'));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('url bestaat reeds.');

		// only 1 default selected theme can exist
		if(array_key_exists('startpage', $values) && $values['startpage']) $this->deselect($sitegroupId);

		// check if index is unique. if not, reindex nodes
		$searchcriteria = array('no_id'		=> $values['id'],
														'weight' => $values['weight'],
														'parent'	=> $values['parent']);
		if($this->exists($searchcriteria))
		{
			$this->increaseWeight($values['parent'], $values['weight']);
		}

	}

	private function getNextWeight($parentId)
	{
		if(!$parentId) $parentId = $this->tree->getRootId();
		$sitegroupId = $this->getSiteGroup()->getCurrentId();

		$retval = 0;
		$query = sprintf("select max(tree_weight) from sitetree where tree_parent_id = %d and tree_sitegrp_id = %d", $parentId, $sitegroupId);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = $res->fetchOne();
		return $retval + self::WEIGHT_OFFSET;
	}

	private function getParentId()
	{
		if(!$this->parentId) $this->parentId = $this->tree->getRootId();
		return $this->parentId;
	}

	protected function handlePreDelete($id, $values)
	{
		$this->handleDeleteObjects($id);

		$parentId = $this->tree->getParentId($id['id']);
		// move child nodes to parent
		$query = sprintf("update %s set tree_parent_id = %d where tree_parent_id = %d", $this->sqlParser->getTable(), $parentId, $id['id']);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		// reset the tree
		$this->tree->resetTree();
	}

	public function handleDeleteObjects($id)
	{
		// remove all reference objects
		$sitePlugin = $this->getSitePlugin();
		$siteTag = $this->getSiteTag();
		$siteTheme = $this->getSiteTheme();
		$acl = new Acl();

		$searchKey = array('tree_id' => $id['id']);
		$this->handleDeleteObject($searchKey, $sitePlugin);
		//$this->handleDeleteObject($searchKey, $siteTag);
		//$this->handleDeleteObject($searchKey, $siteTheme);

		$siteTag->delete($searchKey);
		$siteTheme->delete($searchKey);
		$acl->delete($searchKey);

	}

	private function handleDeleteObject($id, $obj)
	{
		$list = $obj->getList($id);
		foreach($list['data'] as $item)
		{
			$obj->delete($obj->getKey($item));
		}
	}

//}}}

/*-------- Helper functions {{{------------*/
	/**
	 * Retrieves a list of tags that are used in the specified tree id
	 * This list consists of the tags that are specified in the theme
	 * and user defined tag that overrides the theme tags.
	 * 
	 * @param array searchcriteria including tree_id: identifier of the tree node
	 * @return array key is tagname, result is array with information and classname of plugin
	 */
	public function getTagList($searchcriteria)
	{
		//if(isset($this->taglist[$tree_id])) return $this->taglist[$tree_id];
		if(!array_key_exists('tree_id', $searchcriteria)) throw new Exception("Tree node id not in searchcriteria in ".__CLASS__);

		// cache result
		$searchkey = serialize($searchcriteria);
		if(array_key_exists($searchkey, $this->taglist)) return $this->taglist[$searchkey];

		$siteTag = $this->getSiteTag();
		$themeDetail = $this->getTheme($searchcriteria);
		$themeManager = $this->director->themeManager;

		$search = array('classname' => $themeDetail['classname'], 'active' => 1, 'compatible' => $this->director->getRequiredDifVersion());
		$themeClass = $themeManager->exists($search) ? $themeDetail['classname'] : $this->director->getConfig()->admin_theme;
		$themeObject = $themeManager->getTheme($themeClass, true);

		$taglist = $themeObject->getTagList();
		$usertaglist = $siteTag->getTagList($searchcriteria);
		foreach($usertaglist as $key=>$item)
		{
			unset($taglist[$key]);


			foreach($item as $newtag)
			{
				$taglist[$newtag] = array('id' => $newtag, 'name' => $newtag);
			}
		}
		//$this->taglist[$tree_id] = $taglist;
		$this->taglist[$searchkey] = $taglist;

		return $taglist;
	}

	public function movetoPreceding($id)
	{
		$previous = $this->tree->getPrecedingSiblingNode($id['id']);
		if(!$previous) return;

		$current = $this->tree->getNode($id['id']);

		if($previous['weight'] == $current['weight'])
		{
			$this->decreaseWeight($current['parent'], 0, $current['weight']);
			$previous['weight'] = $previous['weight'] - self::WEIGHT_OFFSET;
		}

		$this->updateWeight($current['id'], $previous['weight']);
		$this->updateWeight($previous['id'], $current['weight']);

		// clear cache
		$cache = Cache::getInstance();
		$cache->disableCache();
		$cache->clear();
		$this->tree->resetTree();
	}

	public function movetoFollowing($id)
	{
		$next = $this->tree->getFollowingSiblingNode($id['id']);
		if(!$next) return;

		$current = $this->tree->getNode($id['id']);

		if($next['weight'] == $current['weight'])
		{
			$this->increaseWeight($current['parent'], $current['weight']);
			$next['weight'] = $next['weight'] + self::WEIGHT_OFFSET;
		}

		$this->updateWeight($current['id'], $next['weight']);
		$this->updateWeight($next['id'], $current['weight']);

		// clear cache
		$cache = Cache::getInstance();
		$cache->disableCache();
		$cache->clear();
		$this->tree->resetTree();
	}

	public function movetoParent($id)
	{
		$parent_id = $this->tree->getParentId($id['id']);
		if($parent_id == $this->tree->getRootId()) throw new Exception("Cannot move node {$id['id']} above root node");

		$current = $this->tree->getNode($id['id']);
		$parent = $this->tree->getNode($parent_id);

		$previous = $this->tree->getPrecedingSiblingNode($id['id']);
		if($previous['weight'] < $current['weight'] - self::WEIGHT_OFFSET)
			$this->decreaseWeight($current['parent'], $current['weight']);

		$this->increaseWeight($parent['parent'], $parent['weight']);
		$this->updateWeight($current['id'], $parent['weight']);
		$this->moveNode($current['id'], $parent['parent']);

		// clear cache
		$cache = Cache::getInstance();
		$cache->disableCache();
		$cache->clear();
		$this->tree->resetTree();
	}

	public function movetoChild($id)
	{
		$new_parent_id = $this->tree->getFollowingSiblingId($id['id']);
		if($new_parent_id == $this->tree->getRootId()) throw new Exception("Cannot move node {$id['id']} down to nothing");

		$current = $this->tree->getNode($id['id']);
		$previous = $this->tree->getPrecedingSiblingNode($id['id']);

		if($previous['weight'] < $current['weight'] - self::WEIGHT_OFFSET)
			$this->decreaseWeight($current['parent'], $current['weight']);

		$this->increaseWeight($new_parent_id, 0);
		$this->updateWeight($current['id'], self::WEIGHT_OFFSET);
		$this->moveNode($current['id'], $new_parent_id);

		// clear cache
		$cache = Cache::getInstance();
		$cache->disableCache();
		$cache->clear();
		$this->tree->resetTree();
	}

	private function updateWeight($tree_id, $weight)
	{
		$query = sprintf("update sitetree set tree_weight = %d where tree_id = %d", $weight, $tree_id);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * increase weight with self::weight_offset for child nodes of parent id that have at least min_weight and maximun max_weight
	 */
	private function increaseWeight($parent_id, $min_weight=0, $max_weight=0)
	{
		$query = sprintf("update sitetree set tree_weight = tree_weight + %d where tree_parent_id = %d", self::WEIGHT_OFFSET, $parent_id);
		if($min_weight) $query .= sprintf(" and tree_weight >= %d", $min_weight);
		if($max_weight) $query .= sprintf(" and tree_weight <= %d", $max_weight);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * decrease weight with self::weight_offset for child nodes of parent id that have at least min_weight and maximun max_weight
	 */
	private function decreaseWeight($parent_id, $min_weight=0, $max_weight=0)
	{
		$query = sprintf("update sitetree set tree_weight = tree_weight - %d where tree_parent_id = %d", self::WEIGHT_OFFSET, $parent_id);
		if($min_weight) $query .= sprintf(" and tree_weight >= %d", $min_weight);
		if($max_weight) $query .= sprintf(" and tree_weight <= %d", $max_weight);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * Move node to other parent
	 */
	private function moveNode($tree_id, $parent_id)
	{
		$query = sprintf("update sitetree set tree_parent_id = %d where tree_id = %d", $parent_id, $tree_id);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	public function moveToNode($sourceNodeId, $sitegroupId, $destinationNodeId)
	{
		// move tree to new parent
		$searchcriteria = array('parent' => $sourceNodeId, 'sitegroup_id' => $sitegroupId);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria, false);
		$this->parseCriteria($sqlParser, $searchcriteria);
		$this->setField('parent', $destinationNodeId);

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	public function getHideDescr($hide)
	{
		if(array_key_exists($hide, $this->hideList)) return $this->hideList[$hide];
	}

	public function getHideList()
	{
		$retval = array();
		foreach($this->hideList as $key=>$value)
		{
			$retval[] = array('id' => $key, 'name' => $value);
		}
		return $retval;
	}

	/**
	 * Retrieves the theme information linked to the specified tree id node
	 * It will return the default theme or the user defined theme.
	 * If no theme is available, NULL is returned
	 *
	 * @param integer identifier of the tree node
	 * @return array information of theme
	 */
	public function getTheme($searchcriteria)
	{
		$retval = NULL;
		if(!array_key_exists('tree_id', $searchcriteria)) throw new Exception("tree_id is missing in search array");
		$tree_id = $searchcriteria['tree_id'];

		if(array_key_exists($tree_id, $this->theme)) return $this->theme[$tree_id];
		
		$siteTheme = $this->getSiteTheme();
		$rootNodeId = $this->tree->getRootId();

		$searchcriteria['tree_id'] = array($tree_id, $rootNodeId);

		$list = $siteTheme->getList($searchcriteria);
		$specific = (sizeof($list['data']) > 1);

		foreach($list['data'] as $theme)
		{
			// only fetch specific if there is one
			if($specific && $theme['tree_id'] == $rootNodeId) continue;

			$retval = $theme;
			$this->theme[$tree_id] = $theme;
		}
		return $retval;
	}

	public function getDefaultTheme()
	{
		$rootNodeId = $this->tree->getRootId();
		return $this->getTheme(array('tree_id' => $rootNodeId));
	}

	protected function clearThemeBuffer()
	{
		$this->theme = array();
	}

//}}}

	/*-------- get objects {{{-------*/
	public function getSiteGroup()
	{
		return $this->director->siteGroup;
	}

	protected function getSiteTag()
	{
		if(!isset($this->siteTag))
			$this->siteTag = new SystemSiteTag();
		return $this->siteTag;
	}

	protected function getSiteTheme()
	{
		if(!isset($this->siteTheme))
			$this->siteTheme = new SystemSiteTheme();
		return $this->siteTheme;
	}

	public function getSitePlugin()
	{
		if(!isset($this->sitePlugin))
			$this->sitePlugin = new SystemSitePlugin();
		return $this->sitePlugin;
	}
	//}}}
}

?>
