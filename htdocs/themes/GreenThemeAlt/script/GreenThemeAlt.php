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
class GreenThemeAlt extends Theme
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
		$this->addHeader('<link rel="shortcut icon" href="'.$this->getHtdocsPath().'images/favicon.ico" />');
		$this->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$this->addHeader('<script type="text/javascript" src="'.$this->getHtdocsPath().'js/parselinks.js"></script>');
	}

}

?>
