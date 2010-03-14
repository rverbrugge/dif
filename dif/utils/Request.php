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
class Request
{

	// post constants
	const GET 	= 1;
	const POST 	= 2;
	const FILES = 3;
	const COOKIE 	= 4;
	const SESSION 	= 5;
	const REQUEST 	= 6;
	const SERVER 	= 7;
	const GLOBALS 	= 8;

	/**
	* singelton object
	* @var Locationmanager
  */
	static private $instance;

	/**
	* current request url path
	* @var string
  */
	private $path;

	/**
	* full request url
	* @var string
  */
	private $url;

	/**
	* true if connection request is secure (https) 
	* @var boolean
  */
	private $secure;

	/**
	* domain name like http://www.foo.bar
	* @var string
  */
	private $domain;

	/**
	* request path parsed into array
	* @var array
  */
	private $patharray = array();


	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	private function __construct()
	{
		// dont do anything if run from command line
		if($this->isCli()) return;

		$this->url = $_SERVER['REQUEST_URI'];
		$request = parse_url($this->url);
		//TODO check if we need http! now disabled $this->domain = "http://".$_SERVER['HTTP_HOST'];
		$this->secure =  strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['SERVER_PORT'] == 443 || strtolower(substr($_SERVER['SCRIPT_URI'], 0, 5)) == 'https';
		$this->domain = $_SERVER['HTTP_HOST'];
		$path = strip_tags(trim($request['path'], '/'));
		$this->patharray = $path ? split('/', $path) : array();
		$this->path = ($path) ? "/".$path : '';

		// initialize session
		if(!session_id()) session_start();
	}
	
	static public function getInstance()
	{
		if(self::$instance == NULL)
			self::$instance = new Request();

		return self::$instance;
	}

	public function getRoot()
	{
		reset($this->patharray);
		return current($this->patharray);
	}

	public function getDomain()
	{
		return $this->domain;
	}

	public function setDomain($domain)
	{
		$this->domain = $domain;
	}

	public function getDepth()
	{
		return sizeof($this->patharray);
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function getProtocol()
	{
		return $this->secure ? 'https://' : 'http://';
	}

	public function getRequestType()
	{
		$methods = array('POST' => self::POST,
											'GET'	=> self::GET);

		$method = trim(strtoupper($this->getValue('REQUEST_METHOD', Request::SERVER)));
		if(!array_key_exists($method, $methods)) return self::GET;
		return $methods[$method];
	}

	public function getRequest($type=NULL)
	{
		$retval = null;
		switch($type)
		{
			case self::GET: $retval = $_GET; break;
			case self::POST: $retval = $_POST; break;
			case self::COOKIE: $retval = $_COOKIE; break;
			case self::SESSION: return $_SESSION; break;
			case self::SERVER: return  $_SERVER; break;
			case self::GLOBALS: return $GLOBALS; break;
			case self::FILES: $retval = $_FILES; break;
			default: $retval = $_REQUEST; break;
		}
		return get_magic_quotes_gpc() ? $this->stripslashes_deep($retval) : $retval;
	}

	public function getValue($key, $type=self::REQUEST)
	{
		$source = $this->getRequest($type);
		if(array_key_exists($key, $source)) return $source[$key];
	}

	private function stripslashes_deep($value) 
	{
		$value = is_array($value) ? array_map(array($this, "stripslashes_deep"), $value) : stripslashes($value);
		return $value;
	}

	public function setValue($key, $value, $type=self::SESSION, $expire=null)
	{
		switch($type)
		{
			case self::COOKIE : $this->setCookieValue($key, $value, $expire); break;
			default: $this->setSessionValue($key, $value); break;
		}
	}

	private function setSessionValue($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	private function setCookieValue($key, $value, $expire=null)
	{
		if(!$expire) $expire = time()+86400; //default is 24 hours
		setcookie($key, $value, $expire, '/', '', 0);
	}

	public function exists($key, $type=self::REQUEST)
	{
		$source = $this->getRequest($type);
		return array_key_exists($key, $source);
	}

	public function isCli()
	{
		return ($this->exists('USER', self::SERVER) && $this->getValue('USER', self::SERVER));
	}

}

/**
 * Create urls
 * @package utils
 */
class Url 
{
	/**
	 * Basename of the url
	 *
	 * @private string
	 */
	protected $path;
	
	/**
	 * array of URL parameters
	 *
	 * @private array
	 */
	protected $parameters;
	
	/**
	 * constructor
	 * @param string 	$url 			the optional URL (a string) to base the Url on
	 * @param string 	$parameters the optional URL (a string) with parameters only
	 * @return void
	 */
	public function __construct($useCurrent=false)
	{
		$this->parameters 		= array();
		if($useCurrent) $this->useCurrent();
	}
	
	/**
	 * Set the object to the URL of the current page; this can be either the full
	 * URL (with parameters) or just the path.
	 * @param string	$url  			the URL (a string) to base this Url on
	 * @param string	$parameter  optional string of parameters (URL-encoded)
	 * @return void
	 */
	public function useCurrent($addParameter=true)
	{
		$request = Request::getInstance();
		$this->setPath($request->getPath());
		if($addParameter) $this->addParameters($request->getRequest(Request::GET));
	}

	public function addParameters($parameters)
	{
		/*
		if(!isset($parameters))
		{
			$request = Request::getInstance();
			$this->parameters = $request->getRequest(Request::GET);
		}

		$this->parameters = array_merge($this->parameters, $parameter);
		*/

		foreach($parameters as $key=>$value)
		{
			$this->setParameter($key, $value);
		}
	}
	
	/**
	 * Set the base/page name for the Url
	 * @param string	$path  	a string representing the new path for the Url
	 * @return void
	 */
	public function setPath($path)
	{
		$this->path = rtrim($path, "/")."/";
	}
	
	/**
	 * Get the value of the specified Url parameter
	 * @param string	$name  	name of the parameter
	 * @return mixed
	 */
	public function getParameter($name)
	{
		if(key_exists($name, $this->parameters)) return $this->parameters[$name];
	}
	
	/**
	 * Returns all parameters as array[<name>]=<value>
	 * @return array
	 */
	public function getParameterList()
	{
		$retval = array();
		foreach($this->parameters as $key=>$item)
		{
			$retval[$key] = $item['value'];
		}
		return $retval;
	}
	
	/**
	 * Update the value of a parameter
	 * @param string	$parameter  	name of the parameter
	 * @param mixed		$value  			value of the parameter
	 * @return void
	 */
	public function setParameter($parameter, $value, $urlencode=true) 
	{
		$this->parameters[$parameter] = array('value' => $value, 'urlencode' => $urlencode);
	}
	
	 /**
	 * clears a parameter
	 * @param string	$parameter  	name of the parameter
	 * @return void
	 */
	public function clearParameter($parameter) 
	{
	  if(array_key_exists($parameter, $this->parameters)) unset($this->parameters[$parameter]);
	}
	
	 
	/**
	 * Return a string representation of the URL
	 * @param boolean	$htmlentities  	true if the returning url have to be xhtml compatible
	 * @return string
	 */
	public function getUrl($htmlentities=false) 
	{
		$retval     = $this->path;
		$parameters = $this->parameterToString('&', $htmlentities);

		if($parameters) $retval .= '?'.$parameters;
		
		return $retval;
	}
	
	/**
   * Returns all parameters of the url as a string with the following format: <name>=<value>[s_delimiter]
   *
   * @param  string  $delimiter          The delimiter used to separate the values
   * @param  bool  	$htmlentities        Specifies if the string have to be xhtml compatible
   * @return string                        Parameters in string format
   */
	public function parameterToString($delimiter=';', $htmlentities=false)
	{
		ksort($this->parameters);
		
		$parameters = array();
		foreach ($this->parameters as $key => $values)
		{
	    if(!$key || !$values) continue;
			$value = $values['value'];
			$urlencode = $values['urlencode'];

			//FIXME see if this doesnt give problems (allowing integer value 0) otherwise add extra parameter to function that specifies if 0 is allowed
			if(!$value && !is_numeric($value)) continue;
	    
    	if(is_array($value))
    	{
    		foreach($value as $item)
    		{
	    		$parameters[] = sprintf("%s=%s", urlencode($key.'[]'), ($urlencode) ? urlencode($item) : $item);
	    	}
    	}
    	else
    	{
    		$parameters[] = sprintf("%s=%s", $key, ($urlencode) ? urlencode($value) : $value);
    	}
		}
		
		if($htmlentities) $delimiter = htmlentities($delimiter);		
		return join($delimiter, $parameters);
	}
	
	/**
	 * Return the base/page name of this Url
	 * @return string
	 */
	public function getPath()
	{
	    return $this->path;
	}

	public function fromString($url)
	{
		if(!$url) return;

		$query = parse_url($url);
		$this->setPath($query['path']);
		parse_str($query['query'], $parameters);
		foreach($parameters as $key=>$value)
		{
			$this->setParameter($key, $value);
		}
	}
}

/**
 * Main configuration 
 * @package Common
 */
class ViewManager
{

	// view type constants
	// navigation in website (frontend)
	const OVERVIEW 				= 1;
	// navigation witing admin section
	const ADMIN_OVERVIEW 	= 3;
	const ADMIN_NEW 			= 4;
	const ADMIN_EDIT 			= 5;
	const ADMIN_DELETE 		= 6;
	// navigaition within tree structure
	const TREE_OVERVIEW 	= 7;
	const TREE_NEW 				= 8;
	const TREE_EDIT 			= 9;
	const TREE_DELETE 		= 10;
	// navigaition within plugin config
	const CONF_OVERVIEW 	= 11;
	const CONF_NEW 				= 12;
	const CONF_EDIT 			= 13;
	const CONF_DELETE 		= 14;
	 
	 // url id key
	 const URL_KEY_DEFAULT		= 'vt';
	 const URL_KEY_ADMIN			= 'avt';

	/**
	* singelton object
	* @var Locationmanager
  */
	static private $instance;

	/**
	* list of available view types
	* key is identifies, value is the name 
	* @var array
  */
	private $view;

	/**
	* the type that is currently used by the script
	* @var integer
  */
	private $currentId;

	/**
	* the variable name of the type when used in forms POST / GET
	* @var string
	* @access private
  */
	private $urlId;

	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	private function __construct()
	{

		$this->view = array(self::OVERVIEW			=> 'overzicht',

												self::ADMIN_OVERVIEW=> 'overzicht',
												self::ADMIN_NEW			=> 'nieuw',
												self::ADMIN_EDIT		=> 'bewerken',
												self::ADMIN_DELETE	=> 'verwijderen',
												
												self::TREE_OVERVIEW	=> 'overzicht',
												self::TREE_NEW			=> 'nieuw',
												self::TREE_EDIT			=> 'bewerken',
												self::TREE_DELETE		=> 'verwijderen',
												
												self::CONF_OVERVIEW	=> 'overzicht',
												self::CONF_NEW			=> 'nieuw',
												self::CONF_EDIT			=> 'bewerken',
												self::CONF_DELETE		=> 'verwijderen'
												);

		// use different url id so normal section and admin section does not interfere
		$director = Director::getInstance();
		$this->urlId = $director->isAdminSection() ? self::URL_KEY_ADMIN : self::URL_KEY_DEFAULT;

		$request = Request::getInstance();
		$value = '';
		if($request->exists($this->urlId))
			$value = $request->getValue($this->urlId);
		elseif($request->getValue('REQUEST_METHOD', Request::SERVER) == 'POST' && $request->exists($this->urlId, Request::SESSION))
			$value = $request->getValue($this->urlId, Request::SESSION);

		// if type is userdefined, then this cannot be done
		//if(!array_key_exists($value, $this->view)) $value = self::OVERVIEW;
		$this->setType($value);
	}
	
	static public function getInstance()
	{
		if(self::$instance == NULL)
			self::$instance = new ViewManager();

		return self::$instance;
	}

	public function getTypeList()
	{
		return $this->view;
	}

	/**
   * check if current type is the same as the requested type
   * 
   * @param integer   $type          the type to be checked
   * @return boolean			true if same as current type
   * @access public
   */
	public function isType($type)
	{
		return ($type == $this->getType());
	}

	/**
   * retrieve the current type
   * 
   * @return integer 		current type id
   * @access public
   */
	public function getType()
	{
		//TODO check if we need to check if the current id is defined (i dont think so...)
		//if(!$this->currentId || !array_key_exists($this->currentId, $this->view)) $this->currentId = self::OVERVIEW;
		if(!$this->currentId) $this->currentId = self::OVERVIEW;
		return $this->currentId;
	}

	/**
   * set the current type
   * 
   * @param integer   $type          the default type id
   * @return void
   * @access public
   */
	public function setType($type)
	{
		$this->currentId = $type;

		$request = Request::getInstance();
		$request->setValue($this->getUrlId(), $this->currentId);
	}

	/**
   * retrieve the variable name used in forms
   * 
   * @return string 		name of variable
   * @access public
   */
	public function getUrlId()
	{
		return $this->urlId;
	}

	/**
   * set the name of the url variable
   * 
   * @param integer   $urlId          the url name
   * @return void
   * @access public
   */
	public function setUrlId($urlId)
	{
		$this->urlId = $urlId;
	}

	/**
   * set the name of a type
   * 
   * @param integer   $type          the type id
   * @param string   $s_desc          description
   * @return void
   * @access public
   */
	public function insert($type, $name)
	{
		$this->view[$type] = $name;
	}

  /**
   * Remove a type
   *
   * @param  integer  $i_id                 type id
   * @return void
   * @access public
   */
	public function delete($type)
	{
		if(array_key_exists($type, $this->view)) unset($this->view[$type]);
	}

	/**
   * retrieve the name of the curren type
   * 
   * @return string 		name of the type
   * @access public
   */
	public function getName($type=NULL)
	{
		if(!$type) $type = $this->getType();
		if(array_key_exists($type, $this->view)) return $this->view[$type];
	}

	/**
   * retrieve the name of the curren type
   * 
   * @return string 		name of the type
   * @access public
   */
	public function setName($name, $type=NULL)
	{
		if(!$type) $type = $this->getType();
		if(array_key_exists($type, $this->view)) $this->view[$type] = $name;
	}

}

?>
