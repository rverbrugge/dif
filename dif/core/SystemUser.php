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

require_once('SystemUserGroup.php');
require_once('AuthenticationUser.php');
require_once('phpmailer/class.phpmailer.php');

/**
 * Main configuration 
 * @package Common
 */
class SystemUser extends Observer implements AuthenticationUser
{

	const ROLE_ADMIN=1;
	const ROLE_FRONTEND=2;
	const ROLE_BACKEND=3;
	const ROLE_UTIL=4;

	static public $roleList = array(self::ROLE_FRONTEND => 'frontend', 
														self::ROLE_BACKEND => 'backend', 
														self::ROLE_UTIL => 'utility', 
														self::ROLE_ADMIN => 'admin');
	protected $usergroup;

	public function __construct()
	{
		parent::__construct();

		$this->usergroup = new SystemUserGroup();

		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('users', 'a');
		$this->sqlParser->addField(new SqlField('a', 'usr_id', 'id', 'Identifier', SqlParser::getTypeSelect()|SqlParser::PKEY, SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'usr_active', 'active', 'Actieve status', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'usr_notify', 'notify', 'Notify actions', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_BOOLEAN));
		$this->sqlParser->addField(new SqlField('a', 'usr_role', 'role', 'User Role', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_name', 'name', 'AchterNaam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_firstname', 'firstname', 'Voornaam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::NAME, SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'usr_address', 'address', 'Adres', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'usr_address_nr', 'address_nr', 'Huisnummer', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'usr_zipcode', 'zipcode', 'Postcode', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'usr_city', 'city', 'Plaats', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'usr_country', 'country', 'Land', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_STRING));
		$this->sqlParser->addField(new SqlField('a', 'usr_phone', 'phone', 'Telefoon', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_PHONE));
		$this->sqlParser->addField(new SqlField('a', 'usr_mobile', 'mobile', 'Mobiel nummer', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_PHONE));
		$this->sqlParser->addField(new SqlField('a', 'usr_email', 'email', 'Email', SqlParser::getTypeSelect()|SqlParser::getTypeModify(), SqlField::TYPE_EMAIL, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_username', 'username', 'Gebruikersnaam', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::SEL_USERNAME, SqlField::TYPE_STRING, true));
		$this->sqlParser->addField(new SqlField('a', 'usr_password', 'password', 'Wachtwoord', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT|SqlParser::SEL_PASSWORD, SqlField::TYPE_STRING, false));
		$this->sqlParser->addField(new SqlField('a', 'usr_logincount', 'logincount', 'Aantal logins', SqlParser::getTypeSelect(), SqlField::TYPE_INTEGER));
		$this->sqlParser->addField(new SqlField('a', 'usr_logindate', 'logindate', 'Laatste login', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
		$this->sqlParser->addField(new SqlField('a', 'usr_create', 'createdate', 'Creatie', SqlParser::getTypeSelect()|SqlParser::MOD_INSERT, SqlField::TYPE_DATE));

		$this->orderStatement = array('order by a.usr_name asc, a.usr_firstname asc');
	}

	public function getUserGroupObject()
	{
		return $this->usergroup;
	}

	/**
	 * returns a list  of the group types that the user is part of
	 * @return array
	 */
	 static public function getRoleDesc($id)
	 {
		 if(array_key_exists($id, self::$roleList)) return self::$roleList[$id];
	 }

	/**
	 * returns a list  of the group types that the user is part of
	 * @return array
	 */
	 static public function getRoleId($desc)
	 {
		 return array_search($desc, self::$roleList);
	 }

	/**
	 * returns a list  of the group types that the user is part of
	 * @return array
	 */
	 public function getRoleList()
	 {
		 $retval = array();
		 foreach(self::$roleList as $key=>$value)
		 {
			 $retval[] = array('id' => $key, 'name' => $value);
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
				case 'usr_id' : $sqlParser->addCriteria(new SqlCriteria('a.usr_id', $value)); break;
				case 'no_id' : $sqlParser->addCriteria(new SqlCriteria('a.usr_id', $value, '<>')); break;
				case 'grp_id'	:
					$sqlParser->addFrom('left join usergroup as b on b.usr_id = a.usr_id');
					$sqlParser->addCriteria(new SqlCriteria('b.grp_id', $value, '=')); 
					break;
				case 'no_grp_id'	:
					//$sqlParser->addFrom('left join usergroup as b on b.usr_id = a.usr_id');
					//$sqlParser->addCriteria(new SqlCriteria('b.grp_id', $value, '<>')); 
					$parser = new SqlParser();
					$parser->setSelect('select');
					$parser->setTable('usergroup', 'b');
					$parser->addField(new SqlField('b', 'grp_id', 'grp_id', 'grp_id', SqlParser::SEL_LIST, SqlField::TYPE_INTEGER));
					$parser->parseCriteria(array('grp_id' => $value));
					$parser->addCriteria(new SqlCriteria('b.usr_id', 'a.usr_id', '=', true)); 
					$subquery = $parser->getSql(SqlParser::SEL_LIST);

					$sqlParser->addCriteria(new SqlCriteria(NULL, "($subquery)", 'not exists', true)); 

					break;
				case 'tree_access'	:
					$sqlParser->addFrom('left join usergroup as b on b.usr_id = a.usr_id');

					$search = new SqlCriteria('a.usr_role', self::ROLE_ADMIN, '='); 
					$searchGroup = new SqlCriteria('a.usr_role', self::ROLE_FRONTEND, '<>'); 
					$searchGroup->addCriteria(new SqlCriteria('b.grp_id', $group, '='), SqlCriteria::REL_AND); 
					$search->addCriteria($searchGroup, SqlCriteria::REL_OR);
					$sqlParser->addCriteria($search);
					break;
				case 'search' : 
					//$search = new SqlCriteria('a.usr_name', $value, 'regexp'); 
					$value = "%$value%";
					$search = new SqlCriteria('a.usr_name', $value, 'like'); 
					$search->addCriteria(new SqlCriteria('a.usr_firstname', $value, 'like'), SqlCriteria::REL_OR); 
					$search->addCriteria(new SqlCriteria('a.usr_username', $value, 'like'), SqlCriteria::REL_OR); 
					$search->addCriteria(new SqlCriteria('a.usr_email', $value, 'like'), SqlCriteria::REL_OR); 
					$search->addCriteria(new SqlCriteria('a.usr_city', $value, 'like'), SqlCriteria::REL_OR); 
					$search->addCriteria(new SqlCriteria('a.usr_country', $value, 'like'), SqlCriteria::REL_OR); 
					//$search->addCriteria(new SqlCriteria('c.grp_name', $value, 'like'), SqlCriteria::REL_OR); 
					$sqlParser->addCriteria($search);
					/*
					$key = "concat(a.usr_name, a.usr_firstname, a.usr_username, a.usr_email, a.usr_city, a.usr_country)";
					$sqlParser->addCriteria(new SqlCriteria($key, $value, 'regexp')); 
					*/
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
			case 'active' : return 1; break;
			case 'notify' : return 0; break;
			case 'password' : return Utils::generatePassword(); break;
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
		$fields['notify'] = (array_key_exists('notify', $fields) && $fields['notify']);
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
		$this->sqlParser->setFieldValue('createdate', date('Y-m-d H:i:s'));
		
		$search = new SqlCriteria('usr_username', $values['username']);
		// EMAIL is not unique anymore
		//$search->addCriteria(new SqlCriteria('usr_email', $values['email']), SqlCriteria::REL_OR);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria($search);
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('user name already exists.');
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
		$search = new SqlCriteria('usr_username', $values['username']);
		// email is not unique anymore
		//$search->addCriteria(new SqlCriteria('usr_email', $values['email']), SqlCriteria::REL_OR);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria($search);
		$sqlParser->addCriteria(new SqlCriteria('usr_id', $id['id'], '<>'));
		$query = $sqlParser->getSql(SqlParser::PKEY);

		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		if($res->numRows() > 0)  throw new Exception('user name already exists.');
	}

	protected function handlePreDelete($id, $values)
	{
		// remove users
		$this->removeGroup($id);
	}

	/**
	 * Add user to group
   *
	 * @param array key of user
	 * @return string name of user
	 * @see AuthenticationUser::getUserName
	 */
	public function addGroup($userId, $groupId)
	{
		$query = sprintf("insert into usergroup (usr_id, grp_id) values(%d, %d)", $userId['id'], $groupId['id']);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	/**
	 * Add user to group
   *
	 * @param array key of user
	 * @return string name of user
	 * @see AuthenticationUser::getUserName
	 */
	public function removeGroup($userId)
	{
		$query = sprintf("delete from  usergroup where usr_id = %d", $userId['id']);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	public function setPassword($id, $password)
	{
		/*
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($id, false);
		$this->setField('password', crypt($password));

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);
		*/
		$query = sprintf("update users set usr_password = '%s' where usr_id = %d", crypt($password), $id['id']);

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
		$this->log->info("Password changed for ".$this->getName($id));
	}

	/**
	 * returns the username of the user
   *
	 * @param array key of user
	 * @return string name of user
	 * @see AuthenticationUser::getUserName
	 */
	public function getUserName($id)
	{
		return $this->getName($id);
	}

	/**
	 * returns the id of the user
   *
	 * @param array key of user
	 * @return string name of user
	 * @see AuthenticationUser::getUserId
	 */
	public function getUserId($username)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->addCriteria(new SqlCriteria('usr_username', $username));

		$db = $this->getDb();

		$query = $sqlParser->getSql(SqlParser::PKEY);

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		//return $res->fetchOne();
		return $res->fetchRow(MDB2_FETCHMODE_ASSOC);
	}

	/**
	 * returns true if given password matches password of user
   *
	 * @param integer key of user
	 * @return string password
	 * @see AuthenticationUser::getPassword
	 */
	public function checkPassword($id, $password)
	{
		$pass = $this->getPassword($id);
		return (crypt($password, $pass) == $pass);
	}

	public function getPassword($id)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($id);

		$db = $this->getDb();

		$query = $sqlParser->getSql(SqlParser::SEL_PASSWORD);

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		return $res->fetchOne();
	}
	/**
	 * disables a user
   *
	 * @param integer key of user
	 * @return void
	 * @see AuthenticationUser::disable
	 */
	public function disable($id)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($id);
		//$sqlParser->addCriteria(new SqlCriteria('usr_id', $id));
		$sqlParser->setFieldValue('active', 0);

		$db = $this->getDb();

		$query = $sqlParser->getSql(SqlParser::MOD_UPDATE_FIELDS);

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		return true;
	}

	/**
	 * checks if user is disabled
   *
	 * @param integer key of user
	 * @return boolean disabled state
	 * @see AuthenticationUser::isDisabled
	 */
	public function isEnabled($id)
	{
		if(!$id) return;
		$id['active'] = 1;

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($id);
		//$sqlParser->addCriteria(new SqlCriteria('usr_id', $id));
		//$sqlParser->addCriteria(new SqlCriteria('usr_active', 1));

		$db = $this->getDb();

		$query = $sqlParser->getSql(SqlParser::PKEY);

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		return $res->numRows();
	}

	public function onLogin($id)
	{
		if(!$id) return;

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($id, false);
		//$sqlParser->addCriteria(new SqlCriteria('usr_id', $id));
		$query = sprintf("update %s set usr_logincount = usr_logincount +1, usr_logindate = now() %s", $sqlParser->getTable(), $sqlParser->getSqlWhere());

		$db = $this->getDb();

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());
	}

	public function onLogout($id)
	{
	}

	public function isAdmin($id)
	{
	}

	public function isType($id)
	{
	}

	/**
	* returns a list  of the group types that the user is part of
	* @return array
	*/
	public function getRole($id)
	{
		$detail = $this->getDetail($id);
		if(!$detail) return SystemUser::ROLE_ADMIN; // user does not exist so it is the backdoor user
		return $detail['role'];
	}

	/**
	 * returns a list  of the groups that the user is part of
	 * @param array identifier (key = name of identifier, valeu = value)
	 * @return array
	 */
	public function getGroup($searchcriteria)
	{
		$sqlParser = new SqlParser();
		$sqlParser->setSelect('select distinct');
		$sqlParser->setTable('usergroup', 'a');
		$sqlParser->addField(new SqlField('a', 'usr_id', 'usr_id', 'id', SqlParser::SEL_DETAIL, SqlField::TYPE_INTEGER));
		$sqlParser->addField(new SqlField('a', 'grp_id', 'grp_id', 'id', SqlParser::SEL_LIST, SqlField::TYPE_INTEGER));
		$sqlParser->parseCriteria($searchcriteria);
		$query = $sqlParser->getSql(SqlParser::SEL_LIST);

		//$query = sprintf("select distinct a.grp_id as grp_id from usergroup as a where a.usr_id %s", $users);
		$db = $this->getDb();
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$retval = array();
		while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC))
		{
			$retval[] = $row['grp_id'];
		}
		return $retval;
	}


	/**
	 * notifies users able to edit node id and with notify option enabled
	 * only users that are not frontend type are notified
	 *
	 * @param integer $tree_id node id that requests notification. Users with rights for this node will be notified
	 * @param string $subject subject of email message
	 * @param string $message content of notification email 
	 */
	public function notify($tree_id=NULL, $subject, $message)
	{
		$searchcriteria = 	array('notify' 			=> true);

		$acl = new Acl();
		$grouplist = $acl->getAclGroupList($tree_id);
		$group = array();
		foreach($grouplist as $grp_id=>$item)
		{
			if(in_array(Acl::EDIT, $item) || in_array(Acl::CREATE, $item) || in_array(Acl::MODIFY, $item) || in_array(Acl::DELETE, $item))
			$group[] = $grp_id;
		}

		if($group)
			$searchcriteria['tree_access'] = $group;
		else
			$searchcriteria['role'] = self::ROLE_ADMIN;


		 $userlist = $this->getList($searchcriteria);
		 $mailto = array();
		 foreach($userlist['data'] as $item)
		 {
			 $mailto[] = $item['email'];
		 }

		 if(!$mailto) return;
		 $this->sendMail($mailto, $this->director->getConfig()->email_address, $subject, $message);
	 }

	private function sendMail($mailto, $mailfrom, $subject, $message)
	{
		$recepients = join(",", $mailto);

		$mail = new PHPMailer();
		$mail->From = $mailfrom;
		$mail->FromName = $mailfrom;

		foreach($mailto as $email)
		{
			$mail->AddAddress(trim($email));
		}

		$mail->WordWrap = 80;
		$mail->Subject = $subject;
		$mail->Body = html_entity_decode($message);

		if(!$mail->Send()) throw new Exception("Error sending message: ".$mail->ErrorInfo);
		if(!$this->log->isInfoEnabled()) return;
		$this->log->info("notification '$subject' sent to $recepients");

	}


}

?>
