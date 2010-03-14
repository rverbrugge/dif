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
class Logging extends Observer implements GuiProvider
{
	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	private $pagesize = 20;

	const VIEW_FILE = 'file';

	public function __construct()
	{
		parent::__construct();
		$this->template = array();
		$this->templateFile = "logging.tpl";
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
		$this->handleAdminOverview();
	} 

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$this->handleAdminOverview();
	}
//}}}

/*------- admin overview request {{{ -------*/
	/**
	 * handle admin overview request
	*/
	private function handleAdminOverview()
	{
		$view = ViewManager::getInstance();

		$log = Logger::getInstance();
		$logfile = $log->getLogFile();

		if($view->isType(self::VIEW_FILE))
		{
			$request = Request::getInstance();

			$extension = ".log";
			$filename = $request->getDomain().$extension;

			header("Content-type: application/$extension");
			header("Content-Length: ".filesize($logfile));
			// stupid bastards of microsnob: ie does not like attachment option
			$browser = $request->getValue('HTTP_USER_AGENT', Request::SERVER);
			if (strstr($browser, 'MSIE'))
				header("Content-Disposition: filename=\"$filename\"");
			else
				header("Content-Disposition: attachment; filename=\"$filename\"");

			readfile($logfile);
			exit;
		}
		else
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

			$template->setVariable('logfile',  nl2br(file_get_contents($logfile)), false);

			$url = new Url(true);
			$url->setParameter($view->getUrlId(), self::VIEW_FILE);
			$template->setVariable('href_export',  $url->getUrl(true), false);

			$this->template[$this->director->theme->getConfig()->main_tag] = $template;
		}
	} 
//}}}

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
