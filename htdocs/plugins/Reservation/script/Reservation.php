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

require_once('ReservationUserLink.php');
require_once('JsUrl.php');

/**
 * Main configuration 
 * @package Common
 */
class Reservation extends Plugin implements RpcProvider, GuiProvider
{
	const TYPE_DEFAULT = 1;
	const TYPE_HEADLINES = 2;
	const TYPE_BLOCK_PERIOD = 4;
	const TYPE_USER_GROUP = 5;
	const TYPE_SETTINGS = 6;
	const TYPE_LIST = 8;

	const DISP_BRIEF 	= 1;
	const DISP_FULL		= 4;

	const VIEW_DETAIL 			= "res";
	const VIEW_CONFIG 	= "cfg";

	const VIEW_BLOCK_PERIOD_OVERVIEW 	= "blk1";
	const VIEW_BLOCK_PERIOD_NEW 	= "blk2";
	const VIEW_BLOCK_PERIOD_EDIT 	= "blk3";
	const VIEW_BLOCK_PERIOD_DELETE 	= "blk4";

	const VIEW_USER_GROUP_OVERVIEW 	= "ugrp1";
	const VIEW_USER_GROUP_NEW 	= "ugrp2";
	const VIEW_USER_GROUP_EDIT 	= "ugrp3";
	const VIEW_USER_GROUP_DELETE 	= "ugrp4";

	static public $displaytypes 	= array(self::DISP_BRIEF 	=> 'Name',
																				self::DISP_FULL		=> 'Full');
	static private $displaytypelist;

	protected	$types = array(self::TYPE_DEFAULT => 'Overview',
														self::TYPE_LIST => 'User reservation list');
														//self::TYPE_HEADLINES => 'Headlines');

	/**
	 * plugin settings of parent class
	 * @var array
	 */
	private $settings;


	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $templateFile;
	private $templateTimeFile;
	private $templateReservationFile;

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

		$this->configFile = strtolower(__CLASS__.".ini");

		$this->template = array();
		$this->templateFile = "reservation.tpl";
		$this->templateTimeFile = "reservationtime.tpl";
		$this->templateReservationFile = "reservationlist.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('reservation_settings', 'a');
		$this->sqlParser->addField(new SqlField('a', 'set_id', 'id', 'id', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'set_display', 'display', 'Display', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_max_subscribe', 'max_subscribe', 'Maximun subscriptions per person', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_slots', 'slots', 'Maximum subscriptions per time', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_schedule', 'schedule', 'Schedule', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'set_usr_id', 'usr_id', 'Gebruiker', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_own_id', 'own_id', 'Eigenaar', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'set_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE, true));
		$this->sqlParser->addField(new SqlField('a', 'set_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_DETAIL, 'Details');
		$view->insert(self::VIEW_CONFIG, 'Configuration');
		$view->insert(self::VIEW_BLOCK_PERIOD_OVERVIEW, 'Block period overview');
		$view->insert(self::VIEW_BLOCK_PERIOD_NEW, 'Block period new');
		$view->insert(self::VIEW_BLOCK_PERIOD_EDIT, 'Block period edit');
		$view->insert(self::VIEW_BLOCK_PERIOD_DELETE, 'Block period delete');
		$view->insert(self::VIEW_USER_GROUP_OVERVIEW, 'User group overview');
		$view->insert(self::VIEW_USER_GROUP_NEW, 'User group new');
		$view->insert(self::VIEW_USER_GROUP_EDIT, 'User group edit');
		$view->insert(self::VIEW_USER_GROUP_DELETE, 'User group delete');
	}

/*-------- Helper functions {{{------------*/
	public function getSettings()
	{
		if($this->settings) return $this->settings;

		$this->settings = $this->getDetail(array());
		if(!$this->settings) $this->settings = $this->getFields(SqlParser::MOD_INSERT);

		return $this->settings;
	}

/*
	public function getFilePath()
	{
		return DIF_SYSTEM_ROOT.$this->director->getConfig()->file_path."/".strtolower($this->getClassName()).'/';
	}
	*/

	static public function getDisplayTypeList()
	{
		if(isset(self::$displaytypelist)) return self::$displaytypelist;

		self::$displaytypelist = array();
		foreach(self::$displaytypes as $key=>$value)
		{
			self::$displaytypelist[$key] = array('id' => $key, 'name' => $value);
		}
		return self::$displaytypelist;
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
			case 'rows' : return 20; break;
			case 'max_subscribe' : return 1; break;
			case 'display' : return self::DISP_FULL; break;
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
	}
	//}}}

/*----- handle http requests {{{ -------*/
	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$typelist = array();
		if($this->director->isAdminSection())
		{
			$request = Request::getInstance();
			$view = ViewManager::getInstance();

			if($view->isType(ViewManager::OVERVIEW)) $view->setType(ViewManager::ADMIN_OVERVIEW);

			switch($view->getType())
			{
				case ViewManager::CONF_OVERVIEW : 
				case ViewManager::CONF_NEW : 
				case ViewManager::CONF_DELETE : 
				case ViewManager::CONF_EDIT : $this->handleConfEditPost(); break;
				default : if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
									$typelist = $this->getReferenceTypeList(intval($request->getValue('tree_id')), $request->getValue('tag'));
			}
		}
		else
			$typelist = $this->getReferenceTypeList();

		foreach($typelist as $type)
		{
			$this->addRenderList($type);
			$reference = $this->getObject($type);
			$reference->handleHttpPostRequest();
		}
	}

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$typelist = array();
		$view = ViewManager::getInstance();
		if($this->director->isAdminSection())
		{
			$request = Request::getInstance();

			if($view->isType(ViewManager::OVERVIEW)) $view->setType(ViewManager::ADMIN_OVERVIEW);

			switch($view->getType())
			{
				case ViewManager::CONF_OVERVIEW : 
				case ViewManager::CONF_NEW : 
				case ViewManager::CONF_DELETE : 
				case ViewManager::CONF_EDIT : $this->handleConfEditGet(); break;
				default : if(!$request->exists('tree_id')) throw new Exception('Node ontbreekt.');
									$typelist = $this->getReferenceTypeList(intval($request->getValue('tree_id')), $request->getValue('tag'));
			}
		}
		else
		{
			switch($view->getType())
			{
				default : $typelist = $this->getReferenceTypeList();
			}
		}

		foreach($typelist as $type)
		{
			$this->addRenderList($type);
			$reference = $this->getObject($type);
			$reference->handleHttpGetRequest();
		}
	}
//}}}

/*------- conf edit request {{{ -------*/
	/**
	 * handle conf edit
	*/
	private function handleConfEditGet($retrieveFields=true)
	{
		viewManager::getInstance()->setType(ViewManager::CONF_EDIT);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('pageTitle', $this->description);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$detail = $this->getDetail(array());

		$fields = array();
		if($retrieveFields)
		{
 			$fields = ($this->exists(array())) ? $detail : $this->getFields(SqlParser::MOD_INSERT);
		}
		else
		{
			$fields = $this->getFields(SqlParser::MOD_UPDATE);
		}

		$this->setFields($fields);
		$template->setVariable($this->getFields(SqlParser::MOD_UPDATE));

		$schedule = $this->getTimes($fields['schedule_times']);
		$template->setVariable('schedule_times', $schedule);

		$template->setVariable('id', ($detail) ? $detail['id'] : '');
		$template->setVariable('cbo_display', Utils::getHtmlCombo($this->getDisplayTypeList(), $fields['display']));

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	public function getTimes($schedule)
	{
		$days = array();
		$days[1] = array('id' => 1, 'desc' => 'Monday', 'start' => '', 'stop' => '');
		$days[2] = array('id' => 2, 'desc' => 'Tuesday', 'start' => '', 'stop' => '');
		$days[3] = array('id' => 3, 'desc' => 'Wednesday', 'start' => '', 'stop' => '');
		$days[4] = array('id' => 4, 'desc' => 'Thursday', 'start' => '', 'stop' => '');
		$days[5] = array('id' => 5, 'desc' => 'Friday', 'start' => '', 'stop' => '');
		$days[6] = array('id' => 6, 'desc' => 'Saturday', 'start' => '', 'stop' => '');
		$days[7] = array('id' => 7, 'desc' => 'Sunday', 'start' => '', 'stop' => '');

		if(is_array($schedule))
		{
			foreach($days as &$day)
			{
				if(!array_key_exists($day['id'], $schedule)) continue;
				$day['start'] = $schedule[$day['id']]['start'];
				$day['stop'] = $schedule[$day['id']]['stop'];
			}
		}
		return $days;
	}

	private function handleConfEditPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if($this->exists(array()))
			{
				$this->update($this->getKey($values), $values);
			}
			else
			{
				$this->insert($values);
			}

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->referer->handleHttpGetRequest();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage', $e->getMessage(), false);
			$this->handleConfEditGet(false);
		}
	} 
//}}}

/*----- handle plugin requests {{{ -------*/
	public function getObject($type)
	{
		if(isset($this->reference[$type])) return $this->reference[$type];

		switch($type)
		{
			case self::TYPE_DEFAULT : 
				require_once('ReservationOverview.php');
				$this->reference[$type] = new ReservationOverview($this);
				break;
			/*
			case self::TYPE_HEADLINES :
				require_once("ReservationHeadlines.php");
				$this->reference[$type] = new ReservationHeadlines($this);
				break;
				*/
			case self::TYPE_SETTINGS :
				require_once("ReservationSettings.php");
				$this->reference[$type] = new ReservationSettings($this);
				break;
			case self::TYPE_LIST :
				require_once("ReservationList.php");
				$this->reference[$type] = new ReservationList($this);
				break;
			case self::TYPE_BLOCK_PERIOD :
				require_once("ReservationBlockPeriod.php");
				$this->reference[$type] = new ReservationBlockPeriod($this);
				break;
			case self::TYPE_USER_GROUP :
				require_once("ReservationUserGroup.php");
				$this->reference[$type] = new ReservationUserGroup($this);
				break;
			default :
				throw new Exception("Type {$type} not defined in {$this->getClassName()}.");
		}
		return $this->reference[$type];
	}

	public function getPluginList($tag, $tree_id, $plugin_type)
	{
		$ref = $this->getObject($plugin_type); 
		$searchcriteria = array('tag' => $tag, 'tree_id' => $tree_id);

		return $ref->getList($searchcriteria);
	}

	public function updateTag($tree_id, $tag, $new_tree_id, $new_tag, $plugin_type)
	{
		$ref = $this->getObject($plugin_type); 
		return $ref->updateTag($tree_id, $tag, $new_tree_id, $new_tag);
	}

	public function updateTreeId($sourceNodeId, $destinationNodeId, $pluginType)
	{
		$ref = $this->getObject($pluginType); 
		return $ref->updateTreeId($sourceNodeId, $destinationNodeId);
	}

 /**
	 * delete a plugin item
	 *
	 * @param array whith id [fieldname => value]
	 * @param string name of the tag that is being deleted
	 * @param integer id of the tree 
	 * @return void
	 */
	public function deletePlugin($values, $plugin_type)
	{
		$ref = $this->getObject($plugin_type); 
		
		switch($plugin_type)
		{
			case self::TYPE_DEFAULT :
				// delete reference ids
				/*
				require_once('ReservationTreeRef.php');
				$treeref = new ReservationTreeRef();
				$key = array('ref_tree_id' => $values['tree_id']);
				$treeref->delete($key);
				*/

				$key = array('tree_id' => $values['tree_id'], 'tag' => $values['tag']);

				// delete settings
				$settings = $this->getObject(self::TYPE_SETTINGS);
				$settings->delete($key);

				// delete block periods
				$period = $this->getObject(self::TYPE_BLOCK_PERIOD);
				$period->delete($key);

				// delete user group
				$userGroup = $this->getObject(self::TYPE_USER_GROUP);
				$userLink = new ReservationUserLink();
				$userLink->delete($key);

				$userGroup->delete($key);

				// delete reservations
				$ref->delete($key);
				break;
			default :
				$key = $ref->getKey($values);
				$ref->delete($key);
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
		// parse stylesheet to set variables
		$theme->addFileVar('plugin_path', $this->getHtdocsPath(false));

		$stylesheet_src = $this->getHtdocsPath(true)."css/style.css.in";
		$theme->addStylesheet($theme->fetchFile($stylesheet_src));

		$renderList = $this->getRenderList();

		if($renderList)
		{
			foreach($renderList as $type)
			{
				$object = $this->getObject($type);
				$object->renderForm($theme);
			}
		}
		else
		{
			$template = $theme->getTemplate();

			foreach($this->template as $key => $value)
			{
				$template->setVariable($key, $value);
			}
		}
	}

/*----- handle rpc requests {{{ -------*/
	private function isVipUser($usr_id, $vip_grp_id)
	{
		/**
		 * search for users with is that are not in vip user group
		 * Result are all users that are not in Vip group
		 * Therefore, if result is zero, all users are in Vip group
		 */
		$search = array('usr_id' => $usr_id, 'no_grp_id' => $vip_grp_id);
		//$userCount = (is_array($usr_id)) ? sizeof($usr_id) : 1;

		// check if user is vip user
		$systemUser = $this->director->systemUser;
		$list = $systemUser->getList($search);
		return ($list['totalItems'] == 0);

	}

	private function getReservationTemplate($tree_id, $tag, $usr_id)
	{
		$subObj = $this->getObject(self::TYPE_DEFAULT);

		$subSearch = array('tree_id' 						=> $tree_id,
												'tag' 							=> $tag,
												'activated' 				=> true,
												'usr_id'						=> $usr_id);

		$subscriptionList = $subObj->getList($subSearch, 0,1,ReservationOverview::ORDER_TIME_ASC|SqlParser::ORDER_ASC);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateReservationFile);
		$template->setVariable('schedule', $subscriptionList);
		return $template;
	}

	private function getTimeTemplate($tree_id, $tag, $date, $usr_id, $settings)
	{
		$blockObj = $this->getObject(self::TYPE_BLOCK_PERIOD);
		$subObj = $this->getObject(self::TYPE_DEFAULT);
		$times = $this->getTimes($settings['schedule_times']);

		$canEdit = $settings['canEdit'];

		$now = time();
		$ts = $date ? strtotime($date) : $now;
		$weekday = strftime('%u', $ts);
		if(!array_key_exists($weekday, $times)) throw new Exception("Weekday '$weekday' not available");

		$subSearch = array('tree_id' 						=> $tree_id,
												'tag' 							=> $tag,
												'reservation_date' 	=> strftime('%Y-%m-%d', $ts));

		$subscriptionList = $subObj->getList($subSearch);
		$subscription = array();
		foreach($subscriptionList['data'] as $item)
		{
			$key = $item['reservation_time'];
			if(!array_key_exists($key, $subscription)) $subscription[$key] = array();
			$subscription[$item['reservation_time']][] = $item;
		}

		$blockSearch = array('tree_id' 					=> $tree_id,
												'tag' 							=> $tag,
												'period' 						=> $ts);

		// get linked users
		$userLink = new ReservationUserLink();
		$list = $userLink->getList(array('own_id' => $usr_id));
		$userList = array($usr_id);

		foreach($list['data'] as $item)
		{
			$userList[] = $item['usr_id'];
		}

		//$vipUser = $this->isVipUser($userList, $settings['vip_grp_id']);

		// check if user is able to enter a reservation
		$legitimateUserList = $subObj->getLegitimateUserList($userList, $settings['max_subscribe']);
		$addSubscriptionLink = ($legitimateUserList && !$blockObj->exists($blockSearch));

		$startList = explode(':',$times[$weekday]['start']);
		$stopList = explode(':',$times[$weekday]['stop']);
		$schedule = array();

		for($i = $startList[0]; $i < $stopList[0]; $i++)
		{
			$hour = str_pad($i, 2, '0', STR_PAD_LEFT);
			$row = array('id' => $hour, 'url' => '', 'users' => array());

			// create date from hour
			$datetime = mktime($hour,null,null,date('m',$ts),date('d',$ts),date('Y',$ts));

			// pretend there are no subscriptions fro this time
			$slots = 0;
			$vip_slots = 0;

			if(array_key_exists($i, $subscription))
			{
				$users = $subscription[$i];
				$slots = sizeof($users);

				foreach($users as &$user)
				{
					// count vip subscriptions
					if($user['vip']) $vip_slots++;
					// determine if this user is in the group list of the requesting user
					$ownGroup = $canEdit || in_array($user['usr_id'], $userList);
					$user['ownGroup'] = $ownGroup;

					// add unsubscribe link if user is owner of the subscription 
					if($datetime > $now && $ownGroup)
						$user['url'] = sprintf("unsubscribe(%d)", $user['id']);
					else
						$user['url'] = '';
				}

				$row['users'] = $users;
			}

			// vip check is handled by userlist && (!$vipUser || $vip_slots < $settings['vip_slots']))
			if(($canEdit || ($addSubscriptionLink && $datetime > $now)) && $slots < $settings['slots'])
			{
				$row['url'] = sprintf("getUserList('%s',%d)", date('Y-m-d', $ts), $i);
			}
				//$row['url'] = (sizeof($legitimateUserList) > 1) ? sprintf("getUserList('%s',%d)", date('Y-m-d', $ts), $i) : sprintf("subscribe('%s',%d,%d)", date('Y-m-d', $ts), $i,current($legitimateUserList));

			$schedule[] = $row;
		}

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateTimeFile);
		$template->setVariable('schedule', $schedule);
		$template->setVariable('timestamp', $ts);
		$template->setVariable('htdocs_path', $this->getHtdocsPath(false));

		return $template;
	}

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function getTimeList($search)
	{
		try
		{
			if(!array_key_exists('tree_id', $search)) throw new Exception('Tree node not set');
			if(!array_key_exists('tag', $search)) throw new Exception('Template tag not set');
			if(!array_key_exists('date', $search)) throw new Exception('Date not set');


			$authentication = Authentication::getInstance();
			$owner = $authentication->getUserId();
			$own_id = $owner['id'];
			$usr_id = $own_id;

      $canEdit = $authentication->canEdit($search['tree_id']);
      $canView = $authentication->canView($search['tree_id']);
			if(!$canEdit && !$canView) throw new Exception('Access denied');
			
			$settingObj = $this->getObject(self::TYPE_SETTINGS);
			$settings = $settingObj->getSettings($search['tree_id'], $search['tag']);
			$settings['canEdit'] = $canEdit;

			$template = $this->getTimeTemplate($search['tree_id'], $search['tag'], $search['date'], $usr_id, $settings);
			return $template->fetch();
		}
		catch(Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function getReservationList($search)
	{
		try
		{
			if(!array_key_exists('tree_id', $search)) throw new Exception('Tree node not set');
			if(!array_key_exists('tag', $search)) throw new Exception('Template tag not set');

			$authentication = Authentication::getInstance();
			$user = $authentication->getUserId();
			$usr_id = $user['id'];

			$userLink = new ReservationUserLink();
			$userList = $userLink->getList(array('own_id' => $usr_id));
			$users = array($usr_id);
			foreach($userList['data'] as $item)
			{
				$users[] = $item['usr_id'];
			}
			
			// check if user is authorized to view tree node
			if(!$this->director->tree->exists($search['tree_id'])) throw new Exception("Access denied");

			$template = $this->getReservationTemplate($search['tree_id'], $search['tag'], $users);
			return $template->fetch();
		}
		catch(Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function subscribe($search)
	{
		try
		{
			if(!array_key_exists('tree_id', $search)) throw new Exception('Tree node not set');
			if(!array_key_exists('tag', $search)) throw new Exception('Template tag not set');
			if(!array_key_exists('date', $search)) throw new Exception('Date not set');
			if(!array_key_exists('hour', $search)) throw new Exception('Hour not set');


			$hour = $search['hour'];
			if(!$search['date']) $search['date'] = date('Y-m-d');


			$authentication = Authentication::getInstance();
			$owner = $authentication->getUserId();
			$own_id = $owner['id'];
			
      $canEdit = $authentication->canEdit($search['tree_id']);
      $canView = $authentication->canView($search['tree_id']);
			if(!$canEdit && !$canView) throw new Exception('Access denied');

			$usr_id = (array_key_exists('usr_id', $search) && $search['usr_id']) ? $search['usr_id'] : $own_id;

			$subObj = $this->getObject(self::TYPE_DEFAULT);
			$settingObj = $this->getObject(self::TYPE_SETTINGS);
			$settings = $settingObj->getSettings($search['tree_id'], $search['tag']);
			$settings['canEdit'] = $canEdit;

			// check if date is valid and occurs in future
			$ts = strtotime($search['date']);
			$datetime = mktime($hour,null,null,date('m',$ts),date('d',$ts),date('Y',$ts));
			if(!$canEdit && $datetime < time()) throw new Exception("Date {$search['date']} occurs in the past");

			// check if period is blocked
			$blockSearch = array('tree_id' 					=> $search['tree_id'],
													'tag' 							=> $search['tag'],
													'period' 						=> $ts);

			$blockObj = $this->getObject(self::TYPE_BLOCK_PERIOD);
			if($blockObj->exists($blockSearch)) throw new Exception("Period {$search['date']} is blocked");


			// check if user is able to enter a reservation
			if(!$canEdit)
			{
				$countsearch = array('tree_id' => $search['tree_id'], 'tag' => $search['tag'], 'usr_id' => $usr_id, 'activated' => true);
				if($subObj->getCount($countsearch) >= $settings['max_subscribe']) throw new Exception("Maximum reservations user reached");
			}

			// check if period is available for reservation
			$countsearch = array('tree_id' => $search['tree_id'], 'tag' => $search['tag'], 'reservation_date' => $ts, 'hour' => $search['hour']);
			if($subObj->getCount($countsearch) >= $settings['slots']) throw new Exception("Maximum reservations for period reached");

			// check if period is available for VIP reservation
			//$vipUser = $this->isVipUser($usr_id, $settings['vip_grp_id']);
			$vipUser = (array_key_exists('vip', $search) && $search['vip']);//$this->isVipUser($usr_id, $settings['vip_grp_id']);
			if($vipUser)
			{
				$countsearch = array('tree_id' => $search['tree_id'], 'tag' => $search['tag'], 'reservation_date' => $ts, 'hour' => $search['hour'], 'vip' => true);
				if($subObj->getCount($countsearch) >= $settings['vip_slots']) throw new Exception("Maximum VIP reservations for period reached");
			}

			$times = $this->getTimes($settings['schedule_times']);

			$weekday = strftime('%u', $ts);
			if(!array_key_exists($weekday, $times)) throw new Exception("Weekday '$weekday' not available");

			$startList = explode(':',$times[$weekday]['start']);
			$stopList = explode(':',$times[$weekday]['stop']);
			$start = $startList[0];
			$stop = $stopList[0];

			if(!is_int($hour) || $hour < $start || $hour >= $stop) throw new Exception("Hour out of range");

			$fields = $subObj->getFields(SqlParser::MOD_INSERT);
			$fields['tree_id'] = $search['tree_id'];
			$fields['tag'] = $search['tag'];
			$fields['active'] = 1;
			$fields['usr_id'] = $usr_id;
			$fields['own_id'] = $own_id;
			$fields['reservation_date'] = strftime('%Y-%m-%d', $ts);
			$fields['reservation_time'] = $hour;
			$fields['vip'] = $vipUser;
			$subObj->insert($fields);

			$template = $this->getTimeTemplate($search['tree_id'], $search['tag'], $search['date'], $own_id, $settings);
			//$template = $this->getTimeTemplate($search['tree_id'], $search['tag'], $search['date'], ($canEdit) ? $usr_id : $own_id, $settings);
			return $template->fetch();
		}
		catch(Exception $e)
		{
			return $e->getMessage();
		}
	}


	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function unsubscribe($search)
	{
		try
		{
			if(!array_key_exists('id', $search)) throw new Exception('Id not set');
			$key = array('id' => $search['id'], 'activated' => true);


			$authentication = Authentication::getInstance();
			$owner = $authentication->getUserId();
			$own_id = $owner['id'];
			
			$subObj = $this->getObject(self::TYPE_DEFAULT);
			if(!$subObj->exists($key)) throw new Exception("Reservation {$search['id']} does not exist.");

			$detail = $subObj->getDetail($key);
      $canEdit = $authentication->canEdit($detail['tree_id']);
      $canView = $authentication->canView($detail['tree_id']);
			if(!$canEdit && !$canView) throw new Exception('Access denied');

			// get linked users if user is not super user
			if(!$canEdit)
			{
				$userLink = new ReservationUserLink();
				$list = $userLink->getList(array('own_id' => $own_id));
				$userList = array($own_id);
				foreach($list['data'] as $item)
				{
					$userList[] = $item['usr_id'];
				}

				// user can only delete subscriptions of himself and people in his group
				$key['usr_id'] = $userList;
			}

			$settingObj = $this->getObject(self::TYPE_SETTINGS);
			$settings = $settingObj->getSettings($detail['tree_id'], $detail['tag']);
			$settings['canEdit'] = $canEdit;

			$subObj->delete($key);

			$template = $this->getTimeTemplate($detail['tree_id'], $detail['tag'], strftime('%Y-%m-%d', $detail['reservation_date']), $own_id, $settings);
			return $template->fetch();
		}
		catch(Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function getUserList($search)
	{
		try
		{
			if(!array_key_exists('tree_id', $search)) throw new Exception('Tree node not set');
			if(!array_key_exists('tag', $search)) throw new Exception('Template tag not set');
			if(!array_key_exists('date', $search)) throw new Exception('Date not set');
			if(!array_key_exists('hour', $search)) throw new Exception('Hour not set');

			$maxpage = 10;
			$page = array_key_exists('page', $search) ? intval($search['page']) : 0;

			$hour = $search['hour'];
			$date = $search['date'] ? $search['date'] : date('Y-m-d');

			$authentication = Authentication::getInstance();
			$user = $authentication->getUserId();
			$usr_id = $user['id'];
			
      $canEdit = $authentication->canEdit($search['tree_id']);
      $canView = $authentication->canView($search['tree_id']);
			if(!$canEdit && !$canView) throw new Exception('Access denied');

			$subObj = $this->getObject(self::TYPE_DEFAULT);
			$settingObj = $this->getObject(self::TYPE_SETTINGS);
			$settings = $settingObj->getSettings($search['tree_id'], $search['tag']);

			// get linked users
			$userLink = new ReservationUserLink();
			$list = $userLink->getList(array('own_id' => $usr_id));
			$userList = array($usr_id);
			foreach($list['data'] as $item)
			{
				$userList[] = $item['usr_id'];
			}

			$vipCount = $subObj->getVipCount($search);
			$legitimateUserList = $canEdit ? array() : $subObj->getLegitimateUserList($userList, $settings['max_subscribe']);

			// check if there are any users that are allowed to make a reservation
			if(!$legitimateUserList && !$canEdit) throw new Exception("Reservation count exceeded.");

			// user is super user, let him be able to make reservations for his self
			//if(!$legitimateUserList) $legitimateUserList = array($usr_id); // COMMENTED OUT BECAUSE SUPER USER CAN MAKE RESERVATIONS FOR ALL MEMBERS

			$userSearch = array('id' => $legitimateUserList);
			//if($vipCount >= $settings['vip_slots']) $userSearch['no_grp_id'] = $settings['vip_grp_id']; // vip_grp_id is not used anymore

			// add search string for user
			if(array_key_exists('user', $search) && $search['user']) $userSearch['search'] = $search['user'];

			$userObj = $this->director->systemUser;
			// backup pager url
			$tmppagerUrl = $userObj->getPagerUrl();
			$tmppagerKey = $userObj->getPagerKey();
			$url = new JsUrl();
			$url->setPath('javascript:userSearch');
			$url->setParameter('date', "'$date'");
			$url->setParameter('hour', $hour);
			$url->setParameter('user', "'{$search['user']}'");

			$userObj->setPagerUrl($url);

			$users = $userObj->getList($userSearch, $maxpage, $page);

			// restore pager url
			$userObj->setPagerUrl($tmppagerUrl);
			$userObj->setPagerKey($tmppagerKey);

			$template = new TemplateEngine($this->getPath()."templates/reservationuserselect.tpl");
			$template->setVariable('users', $users);
			$template->setVariable('date', $date);
			$template->setVariable('hour', $hour);
			$template->setVariable('htdocs_path', $this->getHtdocsPath(false));
			$template->setVariable('include_vip', ($vipCount < $settings['vip_slots']));
			$template->setVariable('usersearch', (array_key_exists('user', $search)) ? $search['user'] : '');

			return $template->fetch();
		}
		catch(Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function getUserSelection($search)
	{
		try
		{
			if(!array_key_exists('tree_id', $search)) throw new Exception('tree_id not set');
			if(!array_key_exists('usr_id', $search)) throw new Exception('usr_id not set');

			// get linked users
			$userGroup = $this->getObject(self::TYPE_USER_GROUP);
			$template = $userGroup->getUserSelection($search['tree_id'], $search['usr_id']);
			return $template->fetch();
		}
		catch(Exception $e)
		{
			return $e->getMessage();
		}
	}

	public function handleRpcRequest($method_name, $params, $app_data)
	{
		list($class,$method) = explode('.',$method_name);

		return $this->$method($params[0]);
	}


	/**
	 * registers xml rpc functions
	 * @see RpcProvider::registerRpcMethods
	 */
	public function registerRpcMethods(RpcServer $rpcServer)
	{
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getTimeList", array(&$this,'handleRpcRequest'));
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getUserList", array(&$this,'handleRpcRequest'));
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getReservationList", array(&$this,'handleRpcRequest'));
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".subscribe", array(&$this,'handleRpcRequest'));
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".unsubscribe", array(&$this,'handleRpcRequest'));
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getUserSelection", array(&$this,'handleRpcRequest'));
	}
//}}}

}

?>
