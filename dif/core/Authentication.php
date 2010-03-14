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
class Authentication
{
	private static $instance;

	private $session;
	private static $authenticationUser;

	private $user_info = array();
	private $userId;
	private $name = '';
	private $username = '';
	private $password = '';
	private $aclList = array();

	/**
	 * @var Logger
	 */
	protected $log;

	/**
	 * list of groups the user is connected to
	 * @var array
	 */
	private $group;

	/**
	 * group types the user is connected to
	 * @var integer
	 */
	private $role;
	private $acl;

	private function __construct()
	{
		$this->log = Logger::getInstance();
		$this->acl = new Acl();
	}

	public static function getInstance()
	{
		if(!isset(self::$instance))
		{
			if(!isset(self::$authenticationUser))  throw new Exception("AuthenticationUser object is not initialized.");
			self::$instance = new Authentication();
		}

		return self::$instance;
	}

	public static function registerUser(AuthenticationUser $object)
	{
		if(isset(self::$instance)) throw new Exception(__CLASS__." is already initialized.");
		if(!isset(self::$authenticationUser))
			self::$authenticationUser = $object;
	}

	public function getAuthenticationUser()
	{
		return self::$authenticationUser;
	}

	public function getSession()
	{
		return $this->session;
	}

	public function setSession($session)
	{
		$this->session = $session;
	}

	protected function handlePreInitialize()
	{
	}

	protected function handlePostInitialize()
	{
	}

	public function initialize()
	{
		$this->handlePreInitialize();

		$director = Director::getInstance();
		$this->setSession($director->getConfig()->session_name);

		$request = Request::getInstance();

		$userinfo = $request->getValue($this->getSession(), Request::SESSION);

		if(is_array($userinfo) && array_key_exists('username', $userinfo) && array_key_exists('password', $userinfo) && array_key_exists('id', $userinfo))
		{
			$this->user_info = $userinfo;
			$this->handlePostInitialize();
		}
		/*----------- very insecure! webserver logging! 
		TODO implement other auto login procedure
		elseif($request->exists('username', Request::GET) && $request->exists('password', Request::GET))
		{
			try
			{
				$this->login($request->getValue('username', Request::GET), $request->getValue('password', Request::GET), true);
			}
			catch(Exception $err)
			{
				return;
			}
			$this->handlePostInitialize();
		}
		*/
	}

	public function getUserId()
	{
		if(array_key_exists('id', $this->user_info)) return array('id' => $this->user_info['id']);
	}

	public function getUserName()
	{
		if(array_key_exists('username', $this->user_info)) return $this->user_info['username'];
	}

	public function getName()
	{
		if(array_key_exists('name', $this->user_info)) return $this->user_info['name'];
	}

	public function login($username, $password)
	{
		if(!$username || !$password) throw new Exception("user name and / or password is missing.");
		$request = Request::getInstance();

		$director = Director::getInstance();

		if($this->isBackdoorUser($username, $password))
		{
			$this->user_info = array('id' => -1,
																'name' => $this->getBackdoorName(),
																'username' => $username,
																'password' => $password,
																'backdoor' => true);
		}
		else
		{
			$user = $this->getAuthenticationUser();
			$id = $director->hasDb() ? $user->getUserId($username) : false;
			if(!$id) 
			{
				$this->log->info("Failed login for $username (unknown user)");
				throw new Exception("Unknown user.");
			}

			if(!$user->isEnabled($id)) 
			{
				$this->log->info("$username login failed (account disabled)");
				throw new Exception("User account is disabled.");
			}

			if(!$user->checkPassword($id, $password))
			{
				$this->log->info("Failed login for $username (wrong password)");
				throw new Exception("Unknown user.");
			}

			$this->user_info = array('id' => $id['id'],
																'name' => $user->getName($id),
																'username' => $username,
																'password' => $password);

			$user->onLogin($this->userId);
		}

		// check if admin users are allowed to login
		$director = Director::getInstance();
		$ip_allow = $director->getConfig()->admin_login_ip_allow;
		if($ip_allow && $this->isRole(SystemUser::ROLE_ADMIN))
		{
			$ips = explode(",",$ip_allow);
			if(!in_array($request->getValue('REMOTE_ADDR', Request::SERVER), $ips)) 
			{
				$this->log->info("Failed login for $username (administrator login not allowed from this ip)");
				$this->user_info = array();
				throw new Exception('Access denied');
			}
		}

		$this->saveSession();
		$this->handlePostLogin();

		$this->log->info("login");

		return;
	}

	private function saveSession()
	{
		$request = Request::getInstance();
		$request->setValue($this->getSession(), $this->user_info, Request::SESSION);
	}

	private function isBackdoorUser($username, $password)
	{
		$director = Director::getInstance();
		if(!$director->getConfig()->login_enable) return false;

		$backdoor_username = $director->getConfig()->login_username;
		$backdoor_password = $director->getConfig()->login_password;
		if(!$backdoor_username || !$backdoor_password) return;

		return ($username == $backdoor_username && crypt($password, $backdoor_password) == $backdoor_password);
	}

	private function getBackdoorName()
	{
		$director = Director::getInstance();
		return $director->getConfig()->login_name;
	}

	public function logout()
	{
		$request = Request::getInstance();
		$this->log->info("logout");

		$this->handlePreLogout();

		$request = Request::getInstance();
		$request->setValue($this->getSession(), '', Request::SESSION);

		self::$authenticationUser->onLogout($this->user_info);
		$this->handlePostLogout();
		$this->user_info = array();
	}

	public function handlePostLogin()
	{
	}

	public function handlePreLogout()
	{
	}

	public function handlePostLogout()
	{
	}


	/**
	 * returns default value of a field
	 * @return mixed
	 */
	 public function isLogin()
	 {
		 return isset($this->user_info['id']);
	 }

	/**
	 * returns true if user is a backdoor user
	 * @return boolean
	 */
	 public function isBackdoor()
	 {
		 return array_key_exists('backdoor', $this->user_info);;
	 }

	/**
	 * returns default value of a field
	 * @return mixed
	 */
	 public function isRole($key)
	 {
		 if($this->isBackdoor()) return true;

		 $role = $this->getRole();
		 switch($role)
		 {
			 case SystemUser::ROLE_ADMIN: return true; break;
			 case SystemUser::ROLE_UTIL: return ($key == $role || $key == SystemUser::ROLE_BACKEND || $key == SystemUser::ROLE_FRONTEND); break;
			 case SystemUser::ROLE_BACKEND: return ($key == $role || $key == SystemUser::ROLE_FRONTEND); break;
			 default: return ($key == $role);
		 }
	 }

	/**
	 * returns a list  of the group types that the user is part of
	 * @return array
	 */
	 public function getRole()
	 {
		 if(!$this->isLogin()) return;
		 if(isset($this->role)) return $this->role;
		 $this->role = self::$authenticationUser->getRole($this->userId);
		 return $this->role;
	 }

	/**
	 * returns a list  of the groups that the user is part of
	 * @return array
	 */
	 public function getGroup()
	 {
		 if(!$this->isLogin()) return array();
		 if(isset($this->group)) return $this->group;
		 return self::$authenticationUser->getGroup(array('usr_id' => $this->user_info['id']));
	 }

	 private function getAcl($tree_id)
	 {
		 if(!array_key_exists($tree_id, $this->aclList)) 
		 { 
			 $tmplist = $this->acl->getList(array('tree_id' => $tree_id));
			 $this->aclList[$tree_id] = $tmplist['data'];
		 }
		 return $this->aclList[$tree_id];
	 }

	public function canView($tree_id)
	{
		if($this->isRole(SystemUser::ROLE_ADMIN)) return true;

		$acl = $this->getAcl($tree_id);
		if(!$acl) return true; // no acl so you can do everything

		$retval = false;
		$group = $this->getGroup();
		foreach($acl as $item)
		{
			if(in_array($item['grp_id'], $group))
			{
				if(($item['rights'] & Acl::VIEW) == Acl::VIEW) 
        {
          $retval = true;
          break;
        }
			}
		}
		return $retval;
	}

	public function canRead($tree_id)
	{
		if($this->isRole(SystemUser::ROLE_ADMIN)) return true;

		$acl = $this->getAcl($tree_id);
		if(!$acl) return true; // no acl so you can do everything

		$retval = false;
		$group = $this->getGroup();
		foreach($acl as $item)
		{
			if(in_array($item['grp_id'], $group))
			{
				$retval = true;
				break;
			}
		}
		return $retval;
	}

	public function canEdit($tree_id)
	{
		if($this->isRole(SystemUser::ROLE_ADMIN)) return true;

		// retrieve all acls connected to the tree
		$acl = $this->getAcl($tree_id);
		if(!$acl) return false; // no acl so you can do everything

		// retrieve groups to which the user is a member
		$group = $this->getGroup();
		$retval = false;
		foreach($acl as $item)
		{
			// user is member of the group
			if(in_array($item['grp_id'], $group))
			{
				// group has edit access so user has edit access
				if(($item['rights'] & Acl::EDIT) == Acl::EDIT) 
        {
          $retval = true;
          break;
        }
				elseif(($item['rights'] & Acl::CREATE) == Acl::CREATE) 
				{
          $retval = true;
          break;
				}
				elseif(($item['rights'] & Acl::MODIFY) == Acl::MODIFY) 
				{
          $retval = true;
          break;
				}
				elseif(($item['rights'] & Acl::DELETE) == Acl::DELETE) 
				{
          $retval = true;
          break;
				}
			}
		}
		return $retval;
	}

	public function canCreate($tree_id)
	{
		if($this->isRole(SystemUser::ROLE_ADMIN)) return true;

		$acl = $this->getAcl($tree_id);
		if(!$acl) return false; // no acl so you can do everything

		$retval = false;
		$group = $this->getGroup();
		foreach($acl as $item)
		{
			if(in_array($item['grp_id'], $group))
			{
				if(($item['rights'] & Acl::CREATE) == Acl::CREATE) 
        {
          $retval = true;
          break;
        }
			}
		}
		return $retval;
	}

	public function canModify($tree_id)
	{
		if($this->isRole(SystemUser::ROLE_ADMIN)) return true;

		$acl = $this->getAcl($tree_id);
		if(!$acl) return false; // no acl so you can do everything

		$retval = false;
		$group = $this->getGroup();
		foreach($acl as $item)
		{
			if(in_array($item['grp_id'], $group))
			{
				if(($item['rights'] & Acl::MODIFY) == Acl::MODIFY) 
        {
          $retval = true;
          break;
        }
				elseif(($item['rights'] & Acl::CREATE) == Acl::CREATE) 
				{
          $retval = true;
          break;
				}
			}
		}
		return $retval;
	}

	public function canDelete($tree_id)
	{
		if($this->isRole(SystemUser::ROLE_ADMIN)) return true;

		$acl = $this->getAcl($tree_id);
		if(!$acl) return false; // no acl so you can do everything

		$retval = false;
		$group = $this->getGroup();
		foreach($acl as $item)
		{
			if(in_array($item['grp_id'], $group))
			{
				if(($item['rights'] & Acl::DELETE) == Acl::DELETE) 
        {
          $retval = true;
          break;
        }
			}
		}
		return $retval;
	}
}
?>
