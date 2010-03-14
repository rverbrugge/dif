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
class UpdateHandler extends Observer implements GuiProvider
{
	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	const VIEW_SUCCESS = 's1';

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
		$this->template = array();
		$this->templateFile = "updatehandler.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";
	}

	private function getPath()
	{
		return $this->basePath;
	}

	/**
	* returns the absolute htdocs web path eg. /themes/foobar/htdocs/
	* @return string name of theme
	*/
	public function getHtdocsPath($absolute=false)
	{
		return DIF_VIRTUAL_WEB_ROOT."coreplugins/".$this->getClassName()."/htdocs/";
	}

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
			default : $this->handleAdminOverviewPost(); break;
		}

	} 

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$viewManager = ViewManager::getInstance();

		if($viewManager->isType(ViewManager::OVERVIEW) && $this->director->isAdminSection()) 
			$viewManager->setType(ViewManager::ADMIN_OVERVIEW);

		switch($viewManager->getType())
		{
			default : $this->handleAdminOverviewGet(); break;
		}
	}
//}}}

/*------- admin overview request {{{ -------*/
	/**
	 * handle admin overview request
	*/
	private function handleAdminOverviewGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} 

	private function handleAdminOverviewPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));

		try 
		{
			$this->install($values);
			viewManager::getInstance()->setType(self::VIEW_SUCCESS);;
			$this->handleAdminOverviewGet();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminOverviewGet();
		}
	} 
//}}}

	/**
	 * installs a new plugin
	 *
	 * @param array with posted values
	 * @return void
	 */
	protected function install($values)
	{
		// check if plugin file is added. if not, only update pluginmanager settings
		if(!array_key_exists('diffile', $values) || 
			 !array_key_exists('tmp_name', $values['diffile']) ||
			 !$values['diffile']['tmp_name']
			 ) throw new Exception('Upload file not set. Please supply DIF installation / update file.');


		$file = $values['diffile'];

		$tempPath = realpath($this->director->getTempPath());
		$uploadFile = $tempPath.'/'.$file['name'];
		if(!move_uploaded_file($file['tmp_name'], $uploadFile)) throw new Exception("Error moving {$file['tmp_name']} to $uploadFile.");

		try
		{
			$gzip = new Gzip($uploadFile);
			$gzip->extract($tempPath);

			$installFile = $tempPath.'/DIFInstaller.php';
			if(!file_exists($installFile)) throw new Exception("Wrong DIF installation  file. Use DIF install or update files.");

			// get installation script
			require_once($installFile);
			$install = new DIFInstaller();
			$install->install();

			unlink($installFile);
			unlink($uploadFile);
		}
		catch(Exception $e)
		{
			if(isset($installFile)) unlink($installFile);
			if(isset($uploadFile)) unlink($uploadFile);
			throw $e;
		}
	}

	/**
	 * Manages form output rendering
	 * @param string Smarty template object
	 */
	public function renderForm($theme)
	{
		$template = $theme->getTemplate();

		foreach($this->template as $key => $value)
		{
			$template->setVariable($key,  $value, false);
		}
	}
}

?>
