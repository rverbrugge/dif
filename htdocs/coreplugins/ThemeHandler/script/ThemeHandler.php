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

require_once(DIF_ROOT.'utils/Image.php');
require_once "File/Archive.php";

/**
 * Main configuration 
 * @package Common
 */
class ThemeHandler extends ThemeManager implements RpcProvider, GuiProvider
{

	const VIEW_CONFIG = 'tc1';
	const VIEW_EXPORT = 'tc2';
	const VIEW_SUCCESS = 'ts1';
	const VIEW_UPDATE = 'tu1';
	const SESSION_SAVE = 'themehandler';

	/**
	 * list of templates with location variable as key
	 * @var array
	 */
	private $template;
	private $basePath;
	private $templateFile;

	private $pagesize = 20;


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
		$this->templateFile = "themes.tpl";
		$this->basePath = realpath(dirname(__FILE__)."/../")."/";

		//add new view types
		$view = ViewManager::getInstance();
		$view->insert(self::VIEW_CONFIG, 'Configure');
		$view->insert(self::VIEW_EXPORT, 'Export');
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
		return ($absolute) ? $this->getPath()."htdocs/" : DIF_VIRTUAL_WEB_ROOT."coreplugins/".$this->getClassName()."/htdocs/";
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
		$viewManager = ViewManager::getInstance();

		switch($viewManager->getType())
		{
			case ViewManager::ADMIN_NEW : $this->handleAdminNewPost(); break;
			case ViewManager::ADMIN_EDIT : $this->handleAdminEditPost(); break;
			case ViewManager::ADMIN_DELETE : $this->handleAdminDeletePost(); break;
			case self::VIEW_CONFIG : $this->handleConfigPost(); break;
			case self::VIEW_UPDATE : $this->handleAdminUpdatePost(); break;
			default : $this->handleOverview(); break;
		}

	} 

	/**
	 * Handles data coming from a get request 
	 * @param array HTTP request
	 */
	public function handleHttpGetRequest()
	{
		$request = Request::getInstance();
		$viewManager = ViewManager::getInstance();

		if($viewManager->isType(ViewManager::OVERVIEW) && $this->director->isAdminSection()) 
			$viewManager->setType(ViewManager::ADMIN_OVERVIEW);

		switch($viewManager->getType())
		{
			case ViewManager::ADMIN_OVERVIEW : $this->handleAdminOverview(); break;
			case ViewManager::ADMIN_NEW : $this->handleAdminNewGet(); break;
			case ViewManager::ADMIN_EDIT : $this->handleAdminEditGet(); break;
			case ViewManager::ADMIN_DELETE : $this->handleAdminDeleteGet(); break;
			case self::VIEW_CONFIG : $this->handleConfigGet(); break;
			case self::VIEW_EXPORT : $this->handleExportGet(); break;
			case self::VIEW_UPDATE : $this->handleAdminUpdateGet(); break;
			default : $this->handleOverview(); break;
		}

	}
//}}}

/*------- overview request {{{ -------*/
	/**
	 * handle overview request
	*/
	private function handleOverview()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$page = $this->getPage();
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		$searchcriteria = array();
		$list = $this->getList(NULL, $this->pagesize, $page);

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$template->setVariable('list',  $list, false);
		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} //}}}

/*------- admin overview request {{{ -------*/
	/**
	 * handle admin overview request
	*/
	private function handleAdminOverview()
	{
		$request = Request::getInstance();
		$view = ViewManager::getInstance();
		$page = $this->getPage();
		$this->pagerUrl->setParameter($view->getUrlId(), $view->getType());

		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		$url = new Url(true);
		$url->clearParameter('id');

		$url_new = clone $url;
		$url_new->setParameter($view->getUrlId(), ViewManager::ADMIN_NEW);
		$template->setVariable('href_new',  $url_new->getUrl(true), false);

		$url_update = clone $url;
		$url_update->setParameter($view->getUrlId(), self::VIEW_UPDATE);
		$template->setVariable('href_update',  $url_update->getUrl(true), false);

		$url_edit = clone $url;
		$url_edit->setParameter($view->getUrlId(), ViewManager::ADMIN_EDIT);

		$url_config = clone $url;
		$url_config->setParameter($view->getUrlId(), self::VIEW_CONFIG);

		$url_export = clone $url;
		$url_export->setParameter($view->getUrlId(), self::VIEW_EXPORT);

		$url_del = clone $url;
		$url_del->setParameter($view->getUrlId(), ViewManager::ADMIN_DELETE);

		$searchcriteria = array();
		$list = $this->getList(NULL, $this->pagesize, $page);
		foreach($list['data'] as &$item)
		{
			$url_edit->setParameter('id', $item['id']);
			$url_config->setParameter('id', $item['id']);
			$url_export->setParameter('id', $item['id']);
			$url_del->setParameter('id', $item['id']);

			$item['href_edit'] = $url_edit->getUrl(true);
			$item['href_config'] = $url_config->getUrl(true);
			$item['href_export'] = $url_export->getUrl(true);
			$item['href_del'] = $url_del->getUrl(true);

			if($item['image'])
			{
				try
				{
					$img = new Image($item['image'], $this->getContentPath(true));
					$item['image'] = array('src' => $this->getContentPath(false).$img->getFileName(false), 'width' => $img->getWidth(), 'height' => $img->getHeight());
				}
				catch(Exception $err){}
			}
		}
		$template->setVariable('list',  $list, false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	} 
//}}}

/*------- new request {{{ -------*/
	/**
	 * handle new
	*/
	private function handleAdminNewGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

		$fields = $this->getFields(SqlParser::MOD_INSERT);
		$template->setVariable($fields, NULL, false);

		$view = ViewManager::getInstance();

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);
		$template->setVariable('id',  '', false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminNewPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));
		$values['content_path'] = $this->getContentPath(true);
		//todo check if neccesary
		//$values['image_path'] = $this->getHtdocsPath(true)."images/";

		try 
		{
			$this->install($values, null);
			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);;
			$this->handleAdminOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminNewGet();
		}
	} 
//}}}

/*------- edit request {{{ -------*/
	/**
	 * handle edit
	*/
	private function handleAdminEditGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();

		if(!$request->exists('id')) throw new Exception('Thema ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		if($retrieveFields)
		{
			$this->setFields($this->getDetail(array('id' => $id)));
		}

		$fields = $this->getFields(SqlParser::MOD_UPDATE);
		$template->setVariable($fields, NULL, false);

		$view = ViewManager::getInstance();

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminEditPost()
	{
		$request = Request::getInstance();
		$values = array_merge($request->getRequest(Request::POST), $request->getRequest(Request::FILES));
		$values['content_path'] = $this->getContentPath(true);
		//todo check if neccesary
		//$values['image_path'] = $this->getHtdocsPath(true)."images/";

		try 
		{
			if(!$request->exists('id')) throw new Exception('Thema ontbreekt.');
			$id = intval($request->getValue('id'));

			$this->install($values, array('id' => $id));
			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminEditGet(false);
		}

	} 
//}}}

/*------- delete request {{{ -------*/
	/**
	 * handle delete
	*/
	private function handleAdminDeleteGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();

		if(!$request->exists('id')) throw new Exception('Theme is missing.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		$template->setVariable('name',  $this->getName(array('id' => $id)), false);

		$view = ViewManager::getInstance();

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminDeletePost()
	{
		$request = Request::getInstance();

		try 
		{
			if(!$request->exists('id')) throw new Exception('Theme is missing.');
			$id = intval($request->getValue('id'));

			$this->delete(array('id' => $id));
			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);;
			$this->handleAdminOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminDeleteGet();
		}

	} 
//}}}

/*------- update request {{{ -------*/
	/**
	 * handle delete
	*/
	private function handleAdminUpdateGet()
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();
		$view = ViewManager::getInstance();

		$importPath = realpath($this->director->getImportPath());
		$template->setVariable('import_path',  $importPath, false);

		$url = new Url(true);
		$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url->getUrl(true), false);

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleAdminUpdatePost()
	{
		$request = Request::getInstance();

		try 
		{
			$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);

			$debug = $this->updateThemes();

			$view = ViewManager::getInstance();
			$view->setType(self::VIEW_SUCCESS);

			$url = new Url(true);
			$url->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
			$template->setVariable('href_back',  $url->getUrl(true), false);

			$template->setVariable('debug', is_array($debug) ? join('<br />', $debug) : '');
			$this->template[$this->director->theme->getConfig()->main_tag] = $template;
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleAdminUpdateGet();
		}

	} 
//}}}

/*----- update themes {{{ -------*/
	private function updateThemes()
	{
		$themeManager = $this->director->themeManager;
		$settings = array('active' => 1);
		$settings['content_path'] = $this->getContentPath(true);
		$tempPath = realpath($this->director->getImportPath());
		$logging = array();

		$dh = dir($tempPath);
		while(FALSE !== ($entry = $dh->read()))
		{
			$file = $tempPath.'/'.$entry;
			if(Utils::getExtension($file) != 'gz') continue;

			try
			{
				$themeManager->extractTheme($tempPath, $file);
				$logging = array_merge($logging, $themeManager->installTheme($tempPath, $settings, NULL));
			}
			catch(Exception $err)
			{
				$logging[] = $err->getMessage();
			}
		}
		$dh->close();

		// try to link plugins that are uploaded but not in the database
		$tempPath = $this->getThemePath();
		$dh = dir($tempPath);
		while(FALSE !== ($entry = $dh->read()))
		{
			$themeDir = $tempPath.'/'.$entry.'/script';

			if(!$themeManager->isTheme($themeDir)) continue;
			
			try
			{
				$logging = array_merge($logging, $themeManager->installTheme($themeDir, $settings, NULL, ThemeManager::ACTION_UPDATE));
			}
			catch(Exception $err)
			{
				$logging[] = $err->getMessage();
			}
		}
		$dh->close();

		return $logging;
	}
	//}}}
/*------- config request {{{ -------*/
	/**
	 * handle config
	*/
	private function handleConfigGet($retrieveFields=true)
	{
		$template = new TemplateEngine($this->getPath()."templates/".$this->templateFile);
		
		$request = Request::getInstance();

		if(!$request->exists('id')) throw new Exception('Thema ontbreekt.');
		$id = intval($request->getValue('id'));
		$template->setVariable('id',  $id, false);

		$key = array('id' => $id);
		$themedetail = $this->getDetail($key);
		$theme = $this->director->themeManager->getThemeFromId($key);

		if($retrieveFields)
		{
			$fileTpl = file_get_contents($theme->getTemplateFile());
			$fileIni = file_get_contents($theme->getConfigFile());
			$fileCss = file_get_contents($theme->getStyleSheetFile());
		}
		else
		{
			$fileTpl = $request->getValue('file_tpl');
			$fileIni = $request->getValue('file_ini');
			$fileCss = $request->getValue('file_css');
		}

		$template->setVariable('file_tpl',  $fileTpl, false);
		$template->setVariable('file_ini',  $fileIni, false);
		$template->setVariable('file_css',  $fileCss, false);

		$theme = $this->director->theme;
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/editarea/edit_area/edit_area_full.js"></script>');
		$theme->addHeader('<script type="text/javascript">
editAreaLoader.init({ 	id: "area1", 
							start_highlight: true, 
							allow_toggle: true, 
							allow_resize: true,
							language: "en", 
							syntax: "php", 
							syntax_selection_allow: "css,html,js,php", 
					});

editAreaLoader.init({ 	id: "area2", 
							start_highlight: true, 
							allow_toggle: true, 
							allow_resize: true,
							language: "en", 
							syntax: "html", 
							syntax_selection_allow: "css,html,js,php", 
					});

editAreaLoader.init({ 	id: "area3", 
							start_highlight: true, 
							allow_toggle: true, 
							allow_resize: true,
							language: "en", 
							syntax: "css", 
							syntax_selection_allow: "css,html,js,php", 
					});
</script>');

		$template->setVariable('templateVars',  $theme->getTemplateVars(), false);

		$view = ViewManager::getInstance();

		$url = new Url(true);
		$url_back = clone $url;
		$url_back->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url_back->getUrl(true), false);

		$theme->addBreadcrumb(array('name' => $themedetail['name'], 'path' => $url_back->getUrl(true)));
		$theme->addBreadcrumb(array('name' => $view->getName(), 'path' => $url->getUrl(true)));

		$this->template[$this->director->theme->getConfig()->main_tag] = $template;
	}

	private function handleConfigPost()
	{
		$request = Request::getInstance();
		$values = $request->getRequest(Request::POST);

		try 
		{
			if(!$request->exists('id')) throw new Exception('Thema ontbreekt.');
			$id = intval($request->getValue('id'));

			$key = array('id' => $id);
			$theme = $this->director->themeManager->getThemeFromId($key);

			$fileTpl = $theme->getTemplateFile();
			$fileIni = $theme->getConfigFile();
			$fileCss = $theme->getStyleSheetFile();
			
			$tpl_content = $request->getValue('file_tpl');
			$ini_content = $request->getValue('file_ini');
			$css_content = $request->getValue('file_css');
			if(!$tpl_content) throw new Exception("Template file is empty.");
			if(!$ini_content) throw new Exception("Configuration file is empty.");
			if(!$css_content) throw new Exception("Stylesheet file is empty.");


			if(!($hTpl = fopen($fileTpl, "w"))) throw new Exception("Error opening $fileTpl for writing.");
			if(!($hIni = fopen($fileIni, "w"))) throw new Exception("Error opening $fileIni for writing.");
			if(!($hCss = fopen($fileCss, "w"))) throw new Exception("Error opening $fileCss for writing.");

			fputs($hTpl, $tpl_content);
			fputs($hIni, $ini_content);
			fputs($hCss, $css_content);

			fclose($hTpl);
			fclose($hIni);
			fclose($hCss);

			// clear cache
			$cache = Cache::getInstance();
			$cache->clear();

			viewManager::getInstance()->setType(ViewManager::ADMIN_OVERVIEW);
			$this->handleAdminOverview();
		}
		catch(Exception $e)
		{
			$template = new TemplateEngine();
			$template->setVariable('errorMessage',  $e->getMessage(), false);
			$this->handleConfigGet(false);
		}
	} 
//}}}

/*------- export request {{{ -------*/
	/**
	 * handle export
	*/
	private function handleExportGet()
	{
		$request = Request::getInstance();

		if(!$request->exists('id')) throw new Exception('Thema ontbreekt.');
		$id = intval($request->getValue('id'));

		$key = array('id' => $id);
		$themedetail = $this->getDetail($key);
		$theme = $this->director->themeManager->getThemeFromId($key);

		$tempPath = $this->director->getTempPath()."/theme".session_id();
		$themePath = $themedetail['themePath'];
		$configPath = $theme->getConfigFile();

		mkdir($tempPath);
		Utils::copyRecursive($themePath, $tempPath);
		copy($configPath, $tempPath."/".basename($configPath));
		copy($themePath."/script/ThemeInstaller.php", $tempPath."/ThemeInstaller.php");
		$filename = sprintf("dif_%s_%s.tar.gz", strtolower($themedetail['classname']), $theme->getVersion());

		File_Archive::extract( 
			$tempPath,
			File_Archive::toArchive($filename, File_Archive::toOutput()) 
		);

		Utils::removeRecursive($tempPath);
		exit;
		



/*
		$view = ViewManager::getInstance();

		$url = new Url(true);
		$url_back = clone $url;
		$url_back->setParameter($view->getUrlId(), ViewManager::ADMIN_OVERVIEW);
		$template->setVariable('href_back',  $url_back->getUrl(true), false);

		$theme->addBreadcrumb(array('name' => $themedetail['name'], 'path' => $url_back->getUrl(true)));
		$theme->addBreadcrumb(array('name' => $view->getName(), 'path' => $url->getUrl(true)));

		$this->template[$this->director->theme->getExport()->main_tag] = $template;
		*/
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

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	public function getTagList($param)
	{
		$id = $param['id'];
		$tree_id = $param['tree_id'];

		$url_edit = new Url();
		$url_del = new Url();

		$url_edit->fromString($param['href_edit_theme_tag']);
		$url_del->fromString($param['href_del_theme_tag']);

		try
		{
			$siteTag = new SystemSiteTag();
			$theme = $this->director->themeManager->getThemeFromId(array('id' => $id));

			$usedTags = $siteTag->getTagList(array('tree_id' => $tree_id));
			$taglist = $theme->getTagList();

			foreach($taglist as &$item)
			{
				$item['userdefined'] = (array_key_exists($item['id'], $usedTags)) ? join(', ',$usedTags[$item['id']]) : '';
				$url_edit->setParameter('parent_tag', $item['id']);
				$url_del->setParameter('parent_tag', $item['id']);
				$item['href_edit'] = $url_edit->getUrl();
				$item['href_del'] = $item['userdefined'] ? $url_del->getUrl() : '';
			}
			return array_values($taglist);
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
		//xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getTypeList", array(&$this,'getTypeList'));
		xmlrpc_server_register_method($rpcServer->server,__CLASS__.".getTagList", array(&$this,'handleRpcRequest'));
	}

}

?>
