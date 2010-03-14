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

//require_once(DIF_ROOT.'plugin/Theme.php');

/**
 * Main configuration 
 * @package Common
 */
class AdminTheme extends Theme
{


	/**
	 * Constructor
	 *
	 * Reads project's and default .ini file, sets project handler's 
	 * and initializes paths.
	 * @param location config file
	 */
	public function __construct()
	{

		$this->basePath = realpath(dirname(__FILE__)."/../")."/";
		$this->templatePath = $this->basePath."templates/";
		$this->configFile = strtolower(__CLASS__.".ini");

		parent::__construct();
	}

	public function handleInitPreProcessing()
	{
	}

	public function handleInitPostProcessing()
	{ 
		$template = new TemplateEngine();
		$template->setVariable('tpl_menu',  $this->renderTree(), false);

		$this->addHeader('<link rel="shortcut icon" href="'.$this->getHtdocsPath().'images/favicon.ico" />');

		$request = Request::getInstance();
		$browser = $request->getValue('HTTP_USER_AGENT', Request::SERVER);
		if (strstr($browser, 'MSIE 5') || strstr($browser, 'MSIE 6'))
		{
			$this->addHeader('<script type="text/javascript" src="'.$this->getHtdocsPath().'js/pngfix.js"></script>');
		}

		parent::handleInitPostProcessing();
	}


	/**
	 * renders tree into menu template
	 * @return  object
	 */
	public function renderTree()
	{
		if(!$this->getConfig()->template_menu) return;
		if(!$this->tree) return;

		$template = new TemplateEngine($this->templatePath.$this->getConfig()->template_menu);
		$template->setCacheable(true);

		$cache = Cache::getInstance();
		if(!$cache->isCached('submenu'))
		{
			$childs = array();
			$childlist = $this->tree->getChildList($this->tree->getCurrentId());
			foreach($childlist as $item)
			{
				if(isset($item['visible']) && !$item['visible']) continue;
				$item['path'] = $this->tree->getPath($item['id']);
				$childs[] = $item;
			}

			$template->setVariable('submenu',  $childs, false);
			$cache->save(serialize($childs), 'submenu');
		}
		else
			$template->setVariable('submenu',  unserialize($cache->getCache('submenu')), false);

		// check if template is in cache
		if($template->isCached()) return $template;

		$menu = $this->tree->getRootList();

		// get selected main menu item
		$firstNode = $this->tree->getFirstAncestorNode($this->tree->getCurrentId());
		$firstId = ($firstNode) ? $firstNode['id'] : 0;

		foreach($menu as &$item)
		{
			$item['path'] = (isset($item['external']) && $item['external']) ? $item['url'] : $this->tree->getPath($item['id']);
			$item['selected'] = ($item['id'] == $firstId);
		}
		$template->setVariable('menu',  $menu, false);

		$auth = Authentication::getInstance();
		$template->setVariable('loginName',  $auth->getUserName(), false);
		return $template;
	}

}

?>
