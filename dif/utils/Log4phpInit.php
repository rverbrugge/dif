<?php
/**
 * Initializes the log4php library, uses a LoggerConfiguration which changes
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
 * the file path, to that logs are written to CARTOWEB_HOME/log
 * @package Common
 * @version $Id: Log4phpInit.php,v 1.6 2005/09/06 15:11:55 sypasche Exp $
 */

/**
 * Initializes the log4php library
 */
function initializeLog4php() 
{
	if (defined('LOG4PHP_CONFIGURATION')) return;

	define('LOG4PHP_CONFIGURATION', DIF_SYSTEM_ROOT.'conf/logger.properties');

	define('LOG4PHP_CONFIGURATOR_CLASS', 'LoggerPropertyOverriderConfigurator');
	define('LOG4PHP_DEFAULT_INIT_OVERRIDE', true);


	require_once ('log4php/src/log4php/LoggerManager.php');
	require_once ('log4php/src/log4php/LoggerPropertyConfigurator.php');

	class LoggerPropertyOverriderConfigurator extends LoggerPropertyConfigurator 
	{
		public function configure($url = '') 
		{
			$configurator = new LoggerPropertyOverriderConfigurator();
			$repository = & LoggerManager :: getLoggerRepository();
			return $configurator->doConfigure($url, $repository);
		}

		public function doConfigureProperties($properties, & $hierarchy) 
		{
			// TODO: should search for file appenders instead
			define('FILE_APPENDER', 'log4php.appender.A1.file');

			if (isset ($properties[FILE_APPENDER])) 
			{
					$logFilename = $properties[FILE_APPENDER];

					$logFilename = str_replace('LOG_HOME', DIF_SYSTEM_ROOT.'log', $logFilename);

					// make logfile available to everyone
					define('LOG_FILE', $logFilename);

					$properties[FILE_APPENDER] = $logFilename;
			}
			parent :: doConfigureProperties($properties, $hierarchy);
		}
	}

	LoggerManagerDefaultInit();
}

?>
