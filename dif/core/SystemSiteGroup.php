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

//require_once('SiteTree.php');
require_once(DIF_ROOT.'core/Observer.php');

/**
 * Main configuration 
 * @package Common
 */
class SystemSiteGroup extends Observer 
{

	const CURRENT_ID_KEY = __CLASS__;

	private $buffer;
	private $existbuffer;

	protected $language = array('nl' => 'Nederlands',
															'be' => 'Vlaams',
															'en' => 'English',
															'us' => 'American',
															'fr' => 'Francais',
															'de' => 'Deutch',
															'es' => 'Espanol');
	
	private $localeLanguage = array('nl' 	=> 'dutch',
																	'be' 	=> 'dutch',
																	'en'	=> 'english',
																	'us'	=> 'english',
																	'fr'	=> 'french',
																	'de'	=> 'german',
																	'es'	=> 'spanish');
	public function __construct()
	{
		parent::__construct();
		$this->buffer = array();
		$this->existbuffer = array();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('sitegroup', 'a');
		$this->sqlParser->addField(new SqlField('a', 'grp_id', 'id', 'Identifier', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'grp_active', 'active', 'Active state', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'grp_startpage', 'startpage', 'Start page', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'grp_name', 'name', 'Name', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_title', 'title', 'Title', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_description', 'description', 'Description', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'grp_keywords', 'keywords', 'Keywords', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'grp_language', 'language', 'Language', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_tree_root_id', 'tree_root_id', 'Root node id', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_usr_id', 'usr_id', 'User', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_own_id', 'own_id', 'Owner', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_create', 'createdate', 'Created', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'grp_ts', 'ts', 'modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		$this->orderStatement = array('order by a.grp_startpage desc, a.grp_name asc, a.grp_language asc');
	}

	public function hasCurrentId()
	{
		$request = Request::getInstance();
		return $request->exists(self::CURRENT_ID_KEY);
	}

	public function getDetail($id)
	{
		if(array_key_exists('id', $id) && array_key_exists($id['id'], $this->buffer)) return $this->buffer[$id['id']];
		$retval = parent::getDetail($id);
		$this->buffer[$retval['id']] = $retval;
		return $retval;
	}

	public function exists($id)
	{
		if(!$id) $id = array();
		if(array_key_exists('id', $id))
		{
			if(array_key_exists($id['id'], $this->buffer)) return true;
			if(array_key_exists($id['id'], $this->existbuffer)) return $this->existbuffer[$id['id']];
		}

		$retval = parent::exists($id);
		if(array_key_exists('id', $id)) $this->existbuffer[$id['id']] = $retval;
		return $retval;
	}

	public function getCurrentId()
	{
		$request = Request::getInstance();
		$group = $request->getValue(self::CURRENT_ID_KEY, Request::SESSION);

		try 
		{
			if(!$group || !is_array($group) || !$this->exists(array('id' => $group['id'])))
			{
					$group = $this->getDetail(array('startpage' => true));
					if(!$group) $group = $this->getDetail(array());

				$this->setCurrentId($group['id']);
			}
		}
		catch(Exception $e)
		{
			$group = NULL; // reset if error
		}

		$this->setLocaleLanguage($group);
		return $group['id'];
	}

	public function setCurrentId($id)
	{
		if(!$this->exists(array('id' => $id))) throw new Exception("Sitegroup $id does not exist");
		$detail = $this->getDetail(array('id' => $id));

		$request = Request::getInstance();
		$request->setValue(self::CURRENT_ID_KEY, $detail); 
	}

	public function setLocaleLanguage($group)
	{
		if(!$group) return;
		try
		{
			/* Set locale language */
			$locLng = array_key_exists($group['language'], $this->localeLanguage) ? $this->localeLanguage[$group['language']] : 'english';
			setlocale(LC_ALL, $locLng);
		}
		catch(Exception $e)
		{
		}
	}

	public function getLanguage($id)
	{
		$detail = $this->getDetail(array('id' => $id));
		if(!$detail) throw new Exception("Sitegroup $id does not exist");

		return $detail['language'];
	}

	public function getLanguageList()
	{
		$retval = array();
		foreach($this->language as $key=>$value)
		{
			$retval[] = array('id' => $key, 'name' => $value);
		}
		return $retval;
	}

	/**
	 * returns a list  of the group types that the user is part of
	 * @return array
	 */
	 public function getLanguageDesc($id)
	 {
		 if(array_key_exists($id, $this->language)) return $this->language[$id];
	 }

	private function deselect()
	{
		$query = "update sitegroup set grp_startpage = 0";
		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
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
	 * returns default value of a field
	 * @return mixed
	 * @see DbConnector::getDefaultValue
	 */
	public function getDefaultValue($fieldname)
	{
		switch($fieldname)
		{
			case 'active' : return 1; break;
			case 'startpage' : return 0; break;
			case 'tree_root_id' : return 0; break;
			case 'language' : return 1; break;
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
		$fields['startpage'] = (array_key_exists('startpage', $fields) && $fields['startpage']);

		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$fields['usr_id'] = $userId['id'];

		return $fields;
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
		if($values['tree_root_id'] > 0) throw new Exception("Root node moet kleiner of gelijk zijn aan 0");

		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$usr_id = $userId['id'];
		$this->sqlParser->setFieldValue('own_id', $usr_id);

		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));

		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('grp_name', $values['name']));
		$sqlParser->addCriteria(new SqlCriteria('grp_language', $values['language']));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('website already exists.');
		if($values['startpage']) $this->deselect();
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
		if($values['tree_root_id'] > 0) throw new Exception("Root node must be smaller than 1");

		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('grp_name', $values['name']));
		$sqlParser->addCriteria(new SqlCriteria('grp_language', $values['language']));
		$sqlParser->addCriteria(new SqlCriteria('grp_id', $id['id'], '<>'));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('Website already exists.');

		// only 1 default selected theme can exist
		if(array_key_exists('startpage', $values) && $values['startpage']) $this->deselect();

		// rearrange tree
		$current = $this->getDetail($id);
		if($current['tree_root_id'] != $values['tree_root_id'])
		{

			// check if current id is shared with others. if so, skip theme, tag and plugin move
			$sqlParser = clone $this->sqlParser;
			$sqlParser->addCriteria(new SqlCriteria('grp_tree_root_id', $current['tree_root_id']));
			$sqlParser->addCriteria(new SqlCriteria('grp_id', $id['id'], '<>'));
			$query = $sqlParser->getSql(SqlParser::PKEY);

			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			if($res->numRows() < 1)
			{
				// move theme to new parent
				$siteTheme = new SystemSiteTheme();
				$siteTheme->changeNode($current['tree_root_id'], $values['tree_root_id']);

				// move tag to new parent
				$siteTag = new SystemSiteTag();
				$siteTag->changeNode($current['tree_root_id'], $values['tree_root_id']);

				// move plugins to new parent
				$sitePlugin = $this->director->siteManager->systemSite->getSitePlugin();
				$sitePlugin->changeNode($current['tree_root_id'], $values['tree_root_id']);

				// move acl to new parent
				$acl = new Acl();
				$acl->changeNode($current['tree_root_id'], $values['tree_root_id']);
			}

			// move child nodes
			$site = new SystemSite();
			$site->moveToNode($current['tree_root_id'], $id['id'], $values['tree_root_id']);

			// clear cache
			$cache = Cache::getInstance();
			$cache->clear();
		}
	}

	protected function handlePreDelete($id, $values)
	{
		// remove all reference objects
		$site = new SystemSite();

		$searchKey = array('sitegroup_id' => $id['id']);
		if($site->exists($searchKey)) throw new Exception('Tree nodes exist in group.');

		// delete related objects like themes, tags and plugins
		// only delete related objects if no other sitegroup uses them (same tree_root_id)
		$list = $this->getList(array('tree_root_id' => $values['tree_root_id']));
		if(sizeof($list['data']) == 1) 
		{
			$site->handleDeleteObjects(array('id' => $values['tree_root_id']));
		}
	}


}

?>
