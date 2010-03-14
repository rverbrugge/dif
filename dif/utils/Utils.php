<?php
/**
 * General functions used in CartoWeb 
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
 * @version $Id: Utils.php,v 1.32 2006/03/02 16:27:03 fgiraud Exp $
 */

//require_once ('log4php/LoggerManager.php');

/**
* Utility class containing static methods for various common tasks.
* @package Common
*/
class Utils 
{

	/**
	 * convert dutch date format to us format
	 * @param string dutch date format dd-mm-yyyy
	 * @return string us date format yyyy-mm-dd
	 */
	public static function convertDate($value)
	{
		if(!$value) return;

		// try to split the values
		list($day, $month, $year) = sscanf($value, "%u%*c%u%*c%u");
		// return original if split failed
		if(!(isset($day) && isset($month) && isset($year) )) return $value;

		$year = intval(date("Y", mktime(0,0,0,2,1,$year)));
		return "$year-$month-$day";
	}

	/**
	 * checks if date is valid
	 * @param string us date format yyyy-mm-dd
	 * @return boolean if date is valid
	 */
	public static function isDate($value)
	{
		if(!$value) return;

		// try to split the values
		list($year, $month, $day) = sscanf($value, "%u%*c%u%*c%u");
		// return original if split failed
		if(!(isset($day) && isset($month) && isset($year) )) return;

		$year = intval(date("Y", mktime(0,0,0,2,1,$year)));

		return checkdate($month, $day, $year);
	}

	public static function isPhone($value)
	{
		return (preg_match('/^\+?[0-9]{1,4} *[-\.]? *\(? *[0-9]+ *\)? *[-\.]? *[0-9 ]+$/i', $value));
	}

	public static function isEmail($value)
	{
		return (preg_match('/^[a-z0-9_\.-]+@([a-z0-9]+([\.\-]?[a-z0-9]+)*\.)+[a-z]{2,6}$/i', $value));
	}

	public static function isUpload($value)
	{
		return (is_array($value) && array_key_exists('name', $value));
	}

	public static function isUrl($value)
	{
		$s_regexp = "^(https?://)?"; // http:// 
		$s_regexp .= "(([0-9a-z_!~*'().&=+$%-]+:)?[0-9a-z_!~*'().&=+$%-]+@)?"; // username:password@ 
		$s_regexp .= "(([0-9]{1,3}\.){3}[0-9]{1,3}"; // IP- 199.194.52.184 
		$s_regexp .= "|"; // allows either IP or domain or relative url
		$s_regexp .= "([0-9a-z_!~*'()-]+\.)*"; // tertiary domain(s)- www. 
		$s_regexp .= "([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\."; // second level domain 
		$s_regexp .= "[a-z]{2,6})"; // first level domain- .com or .museum 
		$s_regexp .= "(:[0-9]{1,4})?"; // port number- :80 
		$s_regexp .= "((/?)|"; // a slash isn't required if there is no file name 
		$s_regexp .= "(/[0-9a-z_!~*'().;?:@&=+$,%#-]+)+/?)$"; // filename/queries/anchors 
		$s_regexp .= "|"; // allows either IP or domain or relative url
		$s_regexp .= "(/([0-9a-z][0-9a-z-]{0,61})?[0-9a-z])+/?"; // relative path

		return eregi($s_regexp, $value);
	}

	private function getRandomChar($type) 
	{ 
		$lenght = sizeof($type); 
		$position = mt_rand(0, $lenght-1); 
		return($type[$position]); 
	} 

	public static function generatePassword() 
	{ 
		 mt_srand((double)microtime() * 1000000); 

		 $vowel = array("b","d","f","g","h","j","k","l","m","n","p","r","s","t","v","w","x","z"); 
		 $doublevowel = array("bl","br","cl","cr","dr","fl","fr","gl","gn","gr","kl","kn","kr", "ph", "pr", "sh", "sj", "sk", "sl", "sm", "sn", "sp", "st", "tr", "vl", "vr", "zw"); 
		 $consonant = array("a","e","i","o","u"); 
		 $number = array("1","2","3","4","5","6","7","8","9","0"); 

		 $password = ""; 
		 $password .= self::getRandomChar($doublevowel); 
		 $password .= self::getRandomChar($consonant); 
		 $password .= self::getRandomChar($vowel); 
		 $password .= self::getRandomChar($consonant); 
		 $password .= self::getRandomChar($vowel); 
		 $password .= self::getRandomChar($number); 
		 $password .= self::getRandomChar($number); 
		 
		 return $password;
	}

	/**
   * returns according to the values array an option list (excl. select tags)
   * @param   array     $a_values
   * @param   integer   $i_selected
   * @param   string    $s_empty
   * @return  bool true if succes, else false
   * @access public
   */
  function getHtmlCombo($values, $selected=NULL, $empty="", $id='id', $name='name')
  {
  	$result = array();
  	if($empty) $result[] = '<option value="">'.$empty."</option>";
  	if(!$values) return join($result, "\n");
  	
		$arraySelect = is_array($selected);
  	foreach($values as $value)
  	{
			$result[] = sprintf('<option value="%s" %s >%s</option>', 
													htmlentities($value[$id],ENT_COMPAT),
													$arraySelect ? (in_array($value[$id], $selected) ? 'selected="selected"' : '') : ($value[$id] == $selected ? 'selected="selected"' : ''), 
													str_replace(' ', '&nbsp;', htmlentities($value[$name])));

  	}
  	return join($result, "\n");
  }
  
  /**
   * returns according to the values array an radio button list
   * @param   array     $values[id, name]
   * @param   mixed   	$selected, selected value
   * @param   string    $name, name of the radiolist
   * @param   string    $separator, type of separator between the elements
   * @return  bool 			html radio list if succes, else false
   * @access public
   */
  function getHtmlRadio($option, $selected, $name, $separator=" ", $options='class="noborder"')
	{
		$result = array();
		foreach($option as $item)
		{
			$result[] = sprintf('<input %s type="radio" name="%s" value="%s" %s /> %s', 
													$options, 
													$name, 
													htmlentities($item["id"],ENT_COMPAT), 
													$item['id'] == $selected ? 'checked="checked"' : '', 
													htmlentities($item['name']));
		}
		return join($result, "$separator\n");
	}

  /**
   * returns according to the values array an checkbox list
   * @param   array     $values[id, name]
   * @param   mixed   	$selected, selected value(s) that are checked
   * @param   string    $separator, type of separator between the elements
   * @return  bool 			html radio list if succes, else false
   * @access public
   */
  function getHtmlCheckbox($values, $selected, $name, $separator=" ", $options='class="noborder"')
  {
  	if(!$values) return;

  	$retval = array();
  	
  	// add brackets if checkbox has an array of options
  	if (is_array($values) && substr($name,-2) != "[]") $name .= "[]"; 
  	
		$arraySelect = is_array($selected);

  	foreach($values as $value)
  	{
			$retval[] = sprintf('<input %s type="checkbox" name="%s" value="%s" %s />%s', 
													$options,
													$name,
													htmlentities($value["id"],ENT_COMPAT),
													$arraySelect ? (in_array($value['id'], $selected) ? 'checked="checked"' : '') : ($value['id'] == $selected ? 'checked="checked"' : ''), 
													htmlentities($value['name']));
  	}
  	return join($retval, "$separator\n");
  }

	public static function getExtension($file)
	{
		return strtolower(substr($file, (strrpos($file, '.') + 1 )));
	}

	public static function handleHttp404($theme)
	{
		header("HTTP/1.0 404 Not Found");
		$template = $theme->getTemplate();
		$template->setVariable($theme->getConfig()->main_tag, 'File not Found.', false);
		echo $theme->fetchTheme();
	}

	public static function handleDbError($theme)
	{
		$director = Director::getInstance();

		$template = $theme->getTemplate();
		$template->setVariable('siteTitle', 'DIF', false);
		$content ='<h1>Welcome to DIF, the Dynamic Information Framework!</h1>
		<p>This screen shows that DIF is working but not configured yet.<br />
		Please <a href="'.$director->getConfig()->admin_path.'">login</a> and proceed with the configuration.</p>';

		$template->setVariable($theme->getConfig()->main_tag, $content, false);
	}

	static public function getFileExtension($file)
	{
		return strtolower(substr($file, (strrpos($file, '.') + 1 )));
	}


	/**
	 * Escapes special characters taking into account if magic_quotes_gpc
	 * is ON or not. Multidimensional arrays are accepted.
	 * @param mixed
	 * @param boolean (optional) magic_quotes_gpc status. Detected if missing.
	 * @return mixed
	 */
	public static function addslashes($data, $magic_on = NULL) 
	{
		if (!isset($magic_on)) {
			$magic_on = get_magic_quotes_gpc();
		}

		if (!$magic_on) {
			if (is_array($data)) {
				foreach ($data as $key => &$val) {
					$val = self::addslashes($val, false);
				}
			} else {
				$data = addslashes($data);
			}
		}
		return $data;
	}

	/**
	 *
	 * copy a directory and all subdirectories and files (recursive)
	 * void dircpy( str 'source directory', str 'destination directory' [, bool 'overwrite existing files'] )
	 */
	public static function copyRecursive($source, $dest)
	{
		$source = realpath($source);

		if(!is_dir($source)) throw new Exception("$source is not a directory.");

		$dir = basename($source);
		if(basename($dest) != $dir) $dest .= "/$dir";

		//Lets just make sure our new folder is already created. Alright so its not efficient to check each time... bite me
		if(!is_dir($dest)) 
		{
			mkdir($dest, 0755);
		}

		if(!($handle = opendir($source))) throw new Exception("unable to open $source.");
		
		while(false !== ($file = readdir($handle)))
		{ 
			if($file == '.' || $file == '..') continue;
			
			$path = $source.'/'.$file;
			$destination = $dest.'/'.$file;

			if(is_file($path))
			{
				copy($path, $destination);
			}
			elseif(is_dir($path))
				self::copyRecursive($path, $destination); //recurse!
		}

		closedir($handle);
	}

	/**
	 *
	 * copy a directory and all subdirectories and files (recursive)
	 * void dircpy( str 'source directory', str 'destination directory' [, bool 'overwrite existing files'] )
	 */
	public static function moveRecursive($source, $dest)
	{
		$source = realpath($source);

		if(!is_dir($source)) throw new Exception("$source is not a directory.");

		$dir = basename($source);
		if(basename($dest) != $dir) $dest .= "/$dir";

		//Lets just make sure our new folder is already created. Alright so its not efficient to check each time... bite me
		if(!is_dir($dest)) 
		{
			mkdir($dest, 0755);
		}

		if(!($handle = opendir($source))) throw new Exception("unable to open $source.");
		
		while(false !== ($file = readdir($handle)))
		{ 
			if($file == '.' || $file == '..') continue;
			
			$path = $source.'/'.$file;
			$destination = $dest.'/'.$file;

			if(is_file($path))
			{
				rename($path, $destination);
			}
			elseif(is_dir($path))
				self::moveRecursive($path, $destination); //recurse!
		}

		closedir($handle);
		rmdir($source);
	}

	/**
	 *
	 * copy a directory and all subdirectories and files (recursive)
	 * void dircpy( str 'source directory', str 'destination directory' [, bool 'overwrite existing files'] )
	 */
	public static function removeRecursive($source)
	{
		$source = realpath($source);

		if(!is_dir($source)) throw new Exception("$source is not a directory.");

		if(!($handle = opendir($source))) throw new Exception("unable to open $source.");
		
		while(false !== ($file = readdir($handle)))
		{ 
			if($file == '.' || $file == '..') continue;
			
			$path = $source.'/'.$file;

			if(is_file($path))
				unlink($path);
			elseif(is_dir($path))
				self::removeRecursive($path); //recurse!
		}

		closedir($handle);
		rmdir($source);
	}


	/**
	 * Escapes special characters taking into account if magic_quotes_gpc
	 * is ON or not. Multidimensional arrays are accepted.
	 * @param mixed
	 * @param boolean (optional) magic_quotes_gpc status. Detected if missing.
	 * @return mixed
	 */
	public static function removeslashes($data, $magic_on = NULL) 
	{
		if (!isset($magic_on)) {
			$magic_on = get_magic_quotes_gpc();
		}

		if (!$magic_on) {
			if (is_array($data)) {
				foreach ($data as $key => &$val) {
					$val = self::addslashes($val, false);
				}
			} else {
				$data = addslashes($data);
			}
		}
		return $data;
	}

	public static function nl2array($text)
	{
		return preg_split("/\r\n|\n|\r/", $text);
	}

	public static function nl2var($text, $replacement='<br />')
	{
		return preg_replace("/\r\n|\n|\r/", $replacement, $text);
	}

	/**
	 * Converts a character-separated string to an array.
	 * @param string
	 * @param string string divider
	 * @return array
	 */
	static public function parseArray($string, $divider = ',') 
	{
		if (is_null($string))
			return array();

		$array = explode($divider, $string);
		$array = array_map('trim', $array); // removes white spaces
		$array = array_diff($array, array('')); // removes empty values
		return array_values($array);
	}

	/**
	 * Tells if given path is relative.
	 * @param string path
	 * @return boolean true if relative
	 */
	static public function isRelativePath($path) 
	{
		return (substr($path, 0, 4) != 'http' && substr($path, 0, 1) != '/' && substr($path, 1, 1) != ':');
	}

	static public function mergeIniFile($inifile1, $inifile2, $destination)
	{
		// retrieve current settings
		$settings = parse_ini_file($inifile2, true);
		// the force array in the new distrubution file specifies which entries are not allowed to be overwritten (merged) by the existing settings (like version number etc)
		$forceSettings = parse_ini_file($inifile1, true);
		print_r($forceSettings);
		if(array_key_exists('difsettings', $forceSettings) && array_key_exists('force', $forceSettings['difsettings']))
		{
			foreach($forceSettings['difsettings']['force'] as $item)
			{
				// force variables in sections are specified as <section>:<variable>
				$info = explode(":", $item);
				if(sizeof($info) > 1)
					unset($settings[$info[0]][$info[1]]);
				else
					unset($settings[$info[0]]);
			}
		}
		self::storeIniValues($inifile1, $destination, $settings);
	}

	static public function storeIniValues($source, $dest, $settings)
	{
		$newfile = '';
		$section = '';

		// read new file and change new settings to current settings
		if(!($fh = fopen($source, 'r'))) throw new Exception("Error opening file $source for reading.");
		while(!feof($fh))
		{
			$line = fgets($fh, 1024);
			$is_array = false;

			// check if we are in a section
			if(preg_match("/^\s*\[(\w+)\]\s*$/", $line, $matches))
				$section = $matches[1];

			// check if we are dealing with a variable
			if(!($pos = strrpos($line, '=')))
			{
				$newfile .= $line;
				continue;
			}

			// check if variable is array
			if(preg_match("/^\s*(\S+)\[\]\s*=/", $line, $matches))
			{
				$key = $matches[1];
				$is_array = true;
			}
			else
				$key = trim(substr($line, 0, $pos-1));

			// if section definition was previously found, use the settings in this section instead
			// arrays are define with the [name] definition in the config file
			$settingsList = ($section) ? $settings[$section] : $settings;

			if(is_array($settingsList) && array_key_exists($key, $settingsList))
			{
				$value = ($is_array) ? ($section ? array_shift($settings[$section][$key]) : array_shift($settings[$key])) : $settingsList[$key];
				if(is_numeric($value))
					$value = $value;
				elseif(is_bool($value)) 
					$value = $value ? 'true' : 'false';
				else
					$value = '"'.$value.'"';

				$newfile .= substr_replace($line, " ".$value, $pos+1)."\n";
				//$newfile .= substr_replace($line, " ".self::iniToString($value, $key), $pos+1)."\n";
				
				// remove settings so we can append new settings at the end of the file
				if($is_array)
				{
					if($section && sizeof($settings[$section][$key]) == 0)
						unset($settings[$section][$key]);
					elseif(sizeof($settings[$key]) == 0)
						unset($settings[$key]);
				}
				else
				{
					if($section)
						unset($settings[$section][$key]);
					else
						unset($settings[$key]);
				}
			}
			else
			{
				$newfile .= $line;
			}

		}
		fclose($fh);

		// add new settings
		$newfile .= self::iniToString($settings);

		// save new file
		if(!($fh = fopen($dest, 'w'))) throw new Exception("Error opening file $dest for writing.");
		fputs($fh, $newfile);
		fclose($fh);
	}

	public static function iniToString($values, $id=NULL)
	{
		$prefix = isset($id) ? "$id = " : "";
		$retval = "";

		if(is_array($values))
		{
			foreach($values as $key=>$value)
			{
				if(is_array($value) && sizeof($value) > 0)
				{
					if(is_numeric(key($value)))
						$retval .= self::iniToString($value, $key.'[]');
					else
						$retval .= "[$key]\n".self::iniToString($value);
				}
				else
					$retval .= self::iniToString($value, is_numeric($key) ? $id : $key);
			}
		}
		elseif(is_numeric($values))
			$retval = "$prefix$values\n";
		elseif(is_bool($values)) 
			$retval =  $prefix.($values ? 'true' : 'false')."\n";
		else
			$retval =  $prefix.'"'.$values.'"'."\n";

		return $retval;
	}

    /**
     * Sets the include path, to contain include directory.
     */
    public static function setIncludePath() {
        set_include_path(get_include_path() . PATH_SEPARATOR . 
                 DIF_ROOT . 'include/'. PATH_SEPARATOR .
                 DIF_ROOT . 'include/pear/');
    }

		public static function debug($message, $filename='debug.log', $append=true)
		{
			$msg = sprintf("%s : %s\n", strftime('%c') ,$message);

			$dest = DIF_SYSTEM_ROOT.'log/'.$filename;
			$fh = fopen($dest, $append ? 'a' : 'w');
			if(!$fh) return;
			fputs($fh, $msg);
			fclose($fh);
		}

	/**
	 * Declares calendar javascript for supplied fields
	 * @param Theme $theme Theme object
	 * @param Array $fields List of html object to attach a calendar. Format: fields[]{dateField, triggerElement, selectHandler}
	 */
	public static function getDatePicker($theme, $fields)
	{
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/prototype.js"></script>');
		$theme->addHeader('<script type="text/javascript" src="'.DIF_VIRTUAL_WEB_ROOT.'js/calendarview/calendarview.js"></script>');
		$theme->addHeader('<link rel="stylesheet" media="screen" href="'.DIF_VIRTUAL_WEB_ROOT.'js/calendarview/calendarview.css" type="text/css" />');

		$keys = array('dateField', 'triggerElement', 'selectHandler');

		$init = "Event.observe( window, 'load', function() { ";
		foreach($fields as $item)
		{
			$options = "";
			foreach($item as $key=>$value)
			{
				if(!in_array($key, $keys)) throw new Exception("Option $key is not a valid Calendar option");
				if($options) $options .= ",";
				if($key == 'selectHandler') 
					$options .= " $key : $value";
				else
					$options .= " $key : '$value'";

			}
			$init .= "Calendar.setup({ $options });\n";
		}
		$init .= "} );";
		$theme->addJavascript($init);
	}


}

class DifException extends Exception 
{
	public function __construct($message, $code = 0, Exception $previous = null) 
	{
		// log error
		$logger = Logger::getInstance();

		// retrieve class and function
		$trace=debug_backtrace();
		array_shift($trace);
		array_shift($trace);
		$caller=array_shift($trace);
		$function = $caller['function'];
		$class = isset($caller['class']) ? $caller['class'] : '';

		$logger->log($message, $code, $class, $function);

		// make sure everything is assigned properly
		parent::__construct($message, $code, $previous);
	}


}
class HttpException extends Exception 
{

}
class DbException extends Exception 
{
}

/**
 * Internationalization methods for automatic strings retrieving
 *
 * Using these methods only tells to gettext's strings retriever (xgettext)
 * that the string must be added to PO template. It does nothing in runtime.
 * @package Common
 */
class I18nNoop {
    
    /**
     * @param string
     * @return string
     */
    static public function gt($text) {
        return $text;
    }
    
    /**
     * @param string
     * @param string
     * @param int
     * @return string
     */
    static public function ngt($text, $plural, $count) {
        return $text;
    }
}

?>
