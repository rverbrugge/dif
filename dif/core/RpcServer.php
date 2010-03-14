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
class RpcServer
{
	private $plugins;
	private $director;
	private $log;
	public $server;
	static private  $postRequest;

	public function __construct()
	{
		$this->director = Director::getInstance();

		if(!function_exists("xmlrpc_server_create"))
		{
			require_once("xmlrpc/xmlrpc.inc");
			require_once("xmlrpc/xmlrpcs.inc");
			require_once("xmlrpc/xmlrpc_wrappers.inc");
			require_once("xmlrpc/xmlrpc_extension_api.inc");
		}
		$this->server = xmlrpc_server_create();
		$this->log = Logger::getInstance();
	}
	public function __destruct()
	{
		xmlrpc_server_destroy($this->server);
	}

	private function getRequest()
	{
		if(self::$postRequest != NULL) return self::$postRequest;

		$request = Request::getInstance();

		if($request->exists('HTTP_RAW_POST_DATA', Request::GLOBALS))
			self::$postRequest = $request->getValue('HTTP_RAW_POST_DATA', Request::GLOBALS);
		else
			self::$postRequest = implode("\r\n", file('php://input'));

		return self::$postRequest;
	}

	public function registerObjects()
	{
		$postRequest = $this->getRequest();
		//Utils::debug($postRequest, 'rpc.log', false);

		//extract method
		$methodRequest = '';
		$classname = '';
		$method = '';
		$result = xmlrpc_decode_request($postRequest, $methodRequest);
		if($methodRequest) list($classname, $method) = explode('.',$methodRequest);
		//Utils::debug("class = $classname, method = $method");

		try 
		{
			$this->director->pluginManager->loadPlugin($classname);
		}
		catch(Exception $e)
		{
			// normal plugin failed, try to load admin plugin
			try
			{
				$this->director->adminManager->loadPlugin($classname);
			}
			catch(Exception $err)
			{
				$this->log->error("error loading coreplugin : $err");
			}
			//throw new Exception($e->getMessage());
		}
	}

	public function handleRequest()
	{
		$postRequest = $this->getRequest();
		//$this->log->info(__FUNCTION__." $postRequest");

		$resp = xmlrpc_server_call_method($this->server, $postRequest, null);
		//$this->log->info("response: $resp");
		//Utils::debug($resp);

		if ($resp) 
		{
			header ('Content-Type: text/xml');
			echo $resp;
		}
	}

}

?>
