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
class Login extends Observer implements GuiProvider
{
	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	public function __construct()
	{
		parent::__construct();
		$this->template = array();
		$this->templateFile = "login.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";
	}

	private function getPath()
	{
		return $this->basePath;
	}

	/**
	 * returns the absolute htdocs path 
	 * absolute parameter specifies which path has to be retrieved:
	 * if absolute is true, it returns the absolute system path. eg. /var/www/site/themes/foobar/htdocs/
	 * if false it returns the absolute web path eg. /themes/foobar/htdocs/
	 *
	 * @param boolean specifies which path has to be retrieved, default false
	 * @return string path to htdocs directory
	 */
	public function getHtdocsPath($absolute=false)
	{
		return ($absolute) ? $this->getPath()."htdocs/" : DIF_VIRTUAL_WEB_ROOT."coreplugins/{$this->getClassName()}/htdocs/";
	}

	/**
	 * returns the absolute htdocs path 
	 * absolute parameter specifies which path has to be retrieved:
	 * if absolute is true, it returns the absolute system path. eg. /var/www/site/themes/foobar/htdocs/
	 * if false it returns the absolute web path eg. /themes/foobar/htdocs/
	 *
	 * @param boolean specifies which path has to be retrieved, default false
	 * @return string path to htdocs directory
	 */
	public function getContentPath($absolute=false)
	{
		$path =  $this->director->getContentPath($absolute).strtolower($this->getClassName())."/";
		if($absolute && !is_dir($path))
		{
			if(!mkdir($path, 0775)) throw new Exception("Error creating direcotry ".$path);
		}
		return $path;
	}

	/**
	 * returns the absolute htdocs path 
	 * absolute parameter specifies which path has to be retrieved:
	 * if absolute is true, it returns the absolute system path. eg. /var/www/site/themes/foobar/htdocs/
	 * if false it returns the absolute web path eg. /themes/foobar/htdocs/
	 *
	 * @param boolean specifies which path has to be retrieved, default false
	 * @return string path to htdocs directory
	 */
	public function getCachePath($absolute=false)
	{
		$path =  $this->director->getCachePath($absolute).strtolower($this->getClassName())."/";
		if($absolute && !is_dir($path))
		{
			if(!mkdir($path, 0775)) throw new Exception("Error creating direcotry ".$path);
		}
		return $path;
	}

/*----- handle http requests {{{ -------*/
	/**
	 * Handles data coming from a post request  
	 * @param array HTTP request
	 */
	public function handleHttpPostRequest()
	{
		$request = Request::getInstance();
		$referer = $request->getValue('referer');

		try 
		{
			$autentication = Authentication::getInstance();
			$autentication->login($request->getValue('username'), $request->getValue('password'));
			header("Location: $referer");
			exit;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleLoginGet($referer);
		}
	}

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$this->handleLoginGet();
	}

	private function handleLoginGet($referer='')
	{
		$request = Request::getInstance();
		if(!$referer)
		{
			$tree = $this->director->tree;
			//$referer = $tree->getPath($tree->getFirstNodeId());
			$referer = $request->getUrl();
		}

		// clear login state
		$autentication = Authentication::getInstance();
		if($autentication->isLogin())
		{
			$autentication->logout();
			header("Location: /".$request->getRoot());
			exit;
		}

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('referer',  $referer, false);
		$template->setVariable('pageTitle',  'Login', false);
		$template->setVariable('dbExists',  $this->director->dbExists, false);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}
//}}}

	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 */
	public function renderForm($theme)
	{
		$template = $theme->getTemplate();

		$stylesheet_src = $this->getHtdocsPath(true)."css/style.css.in";
		$stylesheet_dst = $this->getContentPath(true)."style.css";
		$theme->parseFile($stylesheet_src, $stylesheet_dst);
		$theme->addHeader('<link href="'.$this->getHtdocsPath().'css/style.css" rel="stylesheet" type="text/css" media="screen" />');

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}

}

?>
