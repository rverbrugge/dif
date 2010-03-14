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
interface GuiProvider
{

	/**
	 * Create html header tags
	 */
	public function getHtdocsPath($absolute=false);

	/**
	 * Handles data coming from a post request 
	 */
	public function handleHttpPostRequest();

	/**
	 * Handles data coming from a get request 
	 */
	public function handleHttpGetRequest();

	/**
	 * Manages form output rendering
	 * @param Theme object
	 */
	public function renderForm($theme);

}

/**
 * Main configuration 
 * @package Common
 */
interface ExtensionProvider
{

	/**
	 * Handles data coming from a post request 
	 */
	public function handleRequest();

}

/**
 * Main configuration 
 * @package Common
 */
interface RpcProvider
{

	/**
	 * registerRpcMethods
	 */
	public function registerRpcMethods(RpcServer $rpcServer);

}

/**
 * Main configuration 
 * @package Common
 */
interface PluginProvider
{
	const TYPE_SELECT = 1;
	const TYPE_LIST = 2;

	/**
	 * Form variable name to store selected values (used by checkbox items)
	 */
	const KEY_SELECT = 'selectkeys';

	/**
	 * Form variable name to store values shown in list (can be subset of total selected values if result set is split up in pages)
	 */
	const KEY_RANGE = 'rangekeys';

	/**
	 * Handles data coming from another plugin
	 */
	public function handlePluginRequest($pluginType, $requestType, $templateTag, $parameters=NULL);
	public function renderPluginRequest($theme);

}

/**
 * Main configuration 
 * @package Common
 */
interface CliProvider
{

	/**
	 * Handles data comming from command line
	 */
	public function handleCliRequest(CliServer $cliServer);

}

?>
