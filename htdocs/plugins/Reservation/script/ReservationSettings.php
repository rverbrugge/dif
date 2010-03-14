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

require_once(DIF_ROOT."utils/ParseFile.php");

/**
 * Main configuration 
 * @package Common
 */
class ReservationSettings extends Observer
{
	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	/**
	 * pointer to global plugin plugin
	 * @var Reservation
	 */
	private $plugin;

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct($plugin)
	{
		parent::__construct();
		$this->plugin = $plugin;
		
		$this->template = array();
		$this->templateFile = "reservationsettings.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('reservation_overview_settings', 'a');
		$this->sqlParser->addField(new SqlField('a', 'set_tree_id', 'tree_id', 'Node', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_tag', 'tag', 'Tag', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'set_max_subscribe', 'max_subscribe', 'Maximun subscriptions per person', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_slots', 'slots', 'Maximum subscriptions per time', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_vip_slots', 'vip_slots', 'Maximum VIP subscriptions per time', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_schedule', 'schedule', 'Schedule', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'set_cap_subscribe', 'cap_subscribe', 'Caption subscribe link', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'set_cap_unsubscribe', 'cap_unsubscribe', 'Caption unsubscribe link', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'set_vip_grp_id', 'vip_grp_id', 'VIP group id', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'set_usr_id', 'usr_id', 'User', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_own_id', 'own_id', 'Owner', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_create', 'createdate', 'Created', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'set_ts', 'ts', 'Modified', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
	}

/*-------- Helper functions {{{------------*/
	public function getSettings($tree_id, $tag)
	{
		$searchcriteria = array('tag' => $tag, 'tree_id' => $tree_id);

		$settings = $this->getDetail($searchcriteria);
		if(!$settings) $settings = $this->getFields(SqlParser::MOD_INSERT);

		return $settings;
	}

	private function getPath()
	{
		return $this->basePath;
	}

	public function updateTag($tree_id, $tag, $new_tree_id, $new_tag)
	{
		$searchcriteria = array('tag' => $tag, 'tree_id' => $tree_id);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria, false);
		$this->parseCriteria($sqlParser, $searchcriteria);
		$sqlParser->setFieldValue('tag', $new_tag);
		$sqlParser->setFieldValue('tree_id', $new_tree_id);

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	public function updateTreeId($sourceNodeId, $destinationNodeId)
	{
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
//}}}

/*-------- DbConnector insert function {{{------------*/

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	protected function parseCriteria($SqlParser, $searchcriteria)
	{
		if(!$searchcriteria || !is_array($searchcriteria)) return;
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
			case 'display' : 
				$settings = $this->plugin->getSettings();
				return $settings['display']; 
				break;
			case 'max_subscribe' : 
				$settings = $this->plugin->getSettings();
				return $settings['max_subscribe']; 
				break;
			case 'cap_back' : return 'back'; break;
			case 'cap_submit' : return 'Submit'; break;
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$fields['usr_id'] = $userId['id'];

		$fields['schedule'] = serialize(str_replace("'",'',$fields['schedule_times']));

		return $fields;
	}

	protected function handlePostGetList($values)
	{
		$values['schedule_times'] = unserialize($values['schedule']);
		return $values;
	}

	protected function handlePostGetDetail($values)
	{
		$values['schedule_times'] = unserialize($values['schedule']);
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
		$authentication = Authentication::getInstance();
		$userId = $authentication->getUserId();
		$this->sqlParser->setFieldValue('own_id', $userId['id']);

		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));
	}
	//}}}

/*----- handle http requests {{{ -------*/
	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case Reservation::VIEW_CONFIG : $this->handleConfigPost(); break;
		}

	} 

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case Reservation::VIEW_CONFIG : $this->handleConfigGet(); break;
		}
	}
//}}}

/*------- tree edit request {{{ -------*/
	/**
	 * handle tree edit
	*/
	private function handleConfigGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$view->setType(Reservation::VIEW_CONFIG);

		$key = $this->getKey();

		$fields = array();
		if($retrieveFields)
		{
			$key_exists = $this->exists($key);
 			$fields = ($key_exists) ? $this->getDetail($key) : $this->getFields(SqlParser::MOD_INSERT);

			if(!$key_exists)
			{
				$settings = $this->plugin->getSettings();
				$fields['schedule_times'] = $settings['schedule_times'];
			}
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		$this->setFields($fields);
		$template->setVariable($fields);
		$template->setVariable($key);

		$schedule = $this->plugin->getTimes($fields['schedule_times']);
		$template->setVariable('schedule_times', $schedule);

		$template->setVariable('cbo_display', Utils::getHtmlCombo(Reservation::getDisplayTypeList(), $fields['display']));

		$usergroup = $this->director->systemUser->getUserGroupObject();
		$usergrouplist = $usergroup->getList();
		$template->setVariable('cbo_vip_grp', Utils::getHtmlCombo($usergrouplist['data'], $fields['vip_grp_id']));

		// add breadcrumb item
		$this->director->theme->handleAdminLinks($template);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleConfigPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			$key = $this->getKey();

			if($this->exists($key))
			{
				$this->update($key, $values);
			}
			else
			{
				$this->insert($values);
			}

			viewManager::getInstance()->setType(ViewManager::TREE_OVERVIEW);
			$this->plugin->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage', $e->getMessage(), false);
			$this->handleConfigGet(false);
		}

	} 
//}}}

	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 * @see GuiProvider::renderForm
	 */
	public function renderForm($theme)
	{

		$view = ViewManager::getInstance();

		$template = $theme->getTemplate();
		$template->setVariable($view->getUrlId(), $view->getName());

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key, $value);
		}
	}
}

?>
