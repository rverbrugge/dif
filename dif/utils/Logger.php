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

//require_once(DIF_ROOT . 'utils/Log4phpInit.php');

/**
 * Create urls
 * @package utils
 */
class Logger 
{
	
	// message types
	const INFO = 'INFO';
	const WARNING = 'WARNING';
	const ERROR = 'ERROR';

	/**
	* singelton object
	* @var Locationmanager
  */
	static private $instance;

	private $messages;
	private $logfile;
	private $enableList=array();
	private $maxFileSize;

	/**
	 * constructor
	 * @return void
	 */
	private function __construct()
	{
		//$this->log = LoggerManager::getLogger(get_class($this));

		$director = Director::getInstance();
		$this->logfile = $director->getConfig()->log_file;
		foreach(split(',', $director->getConfig()->log_enable) as $item)
		{
			switch(trim($item))
			{
				case 'info' : $this->enableList[] = self::INFO;
				case 'warning' : $this->enableList[] = self::WARNING;
				case 'error' : $this->enableList[] = self::ERROR;
			}
		}
		$this->setMaxFileSize($director->getConfig()->log_size);

		$this->messages = array();
	}

	/**
	 * destructor
	 * @return void
	 */
	public function __destruct()
	{
		$this->saveLog();
	}

	static public function getInstance()
	{
		if(self::$instance == NULL)
			self::$instance = new Logger();

		return self::$instance;
	}

	public function getLogFile()
	{
		return Director::getLogPath().$this->logfile;
	}

	/**
		* Collect system message when things go wrong.
		* These system messages can be displayed by a theme 
		*
		* @param message message string
		* @param class class that adds the message
		* @param function function that adds the message
		* 
		*/
	private function log($message, $type=self::INFO, $class=NULL, $function=NULL)
	{

		if(!isset($function))
		{
			$trace=debug_backtrace();
			array_shift($trace);
			array_shift($trace);
			$caller=array_shift($trace);
			$function = $caller['function'];
			$class = isset($caller['class']) ? $caller['class'] : '';
		}

		$this->messages[] = array('ts' => time(), 'message' => $message, 'type' => $type, 'class' => $class, 'function' => $function);
	}

	/**
		* Log message as info
		* This function ensures backward compatibility
		*
		* @param message message string
		* @param class class that adds the message
		* @param function function that adds the message
		* 
		*/
	public function info($message, $class=NULL, $function=NULL)
	{
		$this->log($message, self::INFO, $class, $function);
	}

	/**
		* Log message as warning
		* This function ensures backward compatibility
		*
		* @param message message string
		* @param class class that adds the message
		* @param function function that adds the message
		* 
		*/
	public function warn($message, $class=NULL, $function=NULL)
	{
		$this->log($message, self::WARNING, $class, $function);
	}

	/**
		* Log message as error
		* This function ensures backward compatibility
		*
		* @param message message string
		* @param class class that adds the message
		* @param function function that adds the message
		* 
		*/
	public function error($message, $class=NULL, $function=NULL)
	{
		$this->log($message, self::ERROR, $class, $function);
	}

	private function saveLog()
	{
		// skip if nothing to do
		if(!$this->messages) return;

		$logfile = $this->getLogFile();

		// default write mode is append
		$mode = 'a';

		if(file_exists($logfile) && filesize($logfile) >= $this->maxFileSize)
		{
			// log file exeeds file size. compress and rotate file
			$this->rotateLogFile();
			// reset log file with mode w
			$mode = 'w';
		}

		$fh = fopen($logfile, $mode);
		if(!$fh) throw new Exception ("Error opening Log file $logfile for writing");

		$authentication = Authentication::getInstance();
		$userId = join(',',$authentication->getUserId() ? $authentication->getUserId() : array());
		$userName = $authentication->getUserName();

		$ip = Request::getInstance()->getValue('REMOTE_ADDR', Request::SERVER);

		foreach($this->messages as $item)
		{
			// skip disabled types
			if(!$this->isEnabled($item['type'])) continue;

			$msg = sprintf("%s %s %s (%d) %s [%s->%s] %s\n", strftime("%a %b %d %Y %T", $item['ts']), $item['type'], $userName, $userId, $ip, $item['class'], $item['function'], $item['message']);
			fputs($fh, $msg);
		}
		fclose($fh);
		chmod($logfile, 0644);
	}

	public function isEnabled($type=self::INFO)
	{
		return ($this->enableList && in_array($type, $this->enableList));
	}

	public function getLog()
	{
		return $this->messages;
	}

	private function getNextLogFile()
	{
		// log file format is x.y.gz
		// where x is the base name of the log file and y is an incremental number
		$directory = Director::getLogPath();
		$logName = substr($this->logfile, 0, strpos($this->logfile, '.'));
		$fileLength = strlen($logName);
		$index = 0;

		if(!($handle = opendir($directory))) throw new Exception("Error opening directory $directory for reading.");


		while(false !== ($file = readdir($handle)))
		{ 
			if($file == '.' || $file == '..' || substr($file, 0, $fileLength) != $logName || Utils::getFileExtension($file) != 'gz') continue;
			// extract the incremental number
			$id = (int) substr($file, $fileLength+1, strpos($file, '.')-1);
			// update index
			if($id > $index) $index = $id;
		}
		closedir($handle);

		// return next log file name
		return sprintf('%s.%d.gz', $logName, ++$index);
	}

	private function rotateLogFile()
	{
		$logfile = Director::getLogPath().$this->getNextLogFile();
		if(!($ofh = gzopen($logfile, "wb9"))) throw new Exception("Error openening compressed archive <strong>$logfile</strong> for writing");

		gzwrite($ofh, file_get_contents(Director::getLogPath().$this->logfile));
		gzclose($ofh);
	}

	/**
		* Set the maximum size that the output file is allowed to reach
		* before being rolled over to backup files.
		* <p>In configuration files, the <b>MaxFileSize</b> option takes an
		* long integer in the range 0 - 2^63. You can specify the value
		* with the suffixes "KB", "MB" or "GB" so that the integer is
		* interpreted being expressed respectively in kilobytes, megabytes
		* or gigabytes. For example, the value "10KB" will be interpreted
		* as 10240.
		*
		* @param mixed $value
		*/
	private function setMaxFileSize($value)
	{
		$maxFileSize = null;
		$numpart = substr($value,0, strlen($value) -2);
		$suffix  = strtoupper(substr($value, -2));

		switch ($suffix) 
		{
			case 'KB': $maxFileSize = (int)((int)$numpart * 1024); break;
			case 'MB': $maxFileSize = (int)((int)$numpart * 1024 * 1024); break;
			case 'GB': $maxFileSize = (int)((int)$numpart * 1024 * 1024 * 1024); break;
			default:
				if (is_numeric($value)) $maxFileSize = (int)$value;
		}

		if ($maxFileSize === null) 
			throw new Exception('Wrong declaration in log_size setting. Please check your system.ini');
		else 
			$this->maxFileSize = abs($maxFileSize);
	}

}
?>
