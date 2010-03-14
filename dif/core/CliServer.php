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
class CliServer
{
	private $plugins;
	private $director;
	private $parameter;

	public function __construct()
	{
		$this->director = Director::getInstance();
		$this->initialize();
	}

	private function initialize()
	{
		$this->parameter = array();
		$request = Request::getInstance();

		foreach ($request->getValue('argv', Request::SERVER)	 as $arg) 
		{
			if (ereg('--([^=]+)=(.*)',$arg,$reg)) 
			{
				$this->parameter[$reg[1]] = $reg[2];
			} 
			elseif(ereg('-([a-zA-Z0-9])',$arg,$reg)) 
			{
				$this->parameter[$reg[1]] = 'true';
			}
		}
	}

	public function getParameter($key)
	{
		if(array_key_exists($key, $this->parameter)) return $this->parameter[$key];
	}

	public function parameterExists($key)
	{
		return array_key_exists($key, $this->parameter);
	}

	public function registerObjects()
	{
		$key = 'class';
		if(!$this->parameterExists($key)) throw new Exception("Parameter '$key' not set\n");
		$classname = $this->getParameter($key);

		// user must log in 
		$auth = Authentication::getInstance();
		$key = 'username';
		if(!$this->parameterExists($key)) throw new Exception("Parameter '$key' not set\n");
		$username = $this->getParameter($key);
		$key = 'password';
		if(!$this->parameterExists($key)) throw new Exception("Parameter '$key' not set\n");
		$password = $this->getParameter($key);

		$auth->login($username, $password, false);

		// user must have backend rights
		if($auth->isLogin() && !$auth->isRole(SystemUser::ROLE_BACKEND)) throw new Exception('Access denied.');

		try 
		{
			$this->director->pluginManager->loadPlugin($classname);
		}
		catch(Exception $e)
		{
			// normal plugin failed, try to load admin plugin
			$this->director->adminManager->loadPlugin($classname);
			//throw new Exception($e->getMessage());
		}
	}

	public function handleRequest()
	{
	}
}

?>
