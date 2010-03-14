<?php
/**
 * General configuration classes
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
class Config {

    /**
     * Array of string which contains contents of .ini configuration file
     * @var array
     */
    protected $ini_array;

    /**
     * Property access method
     *
     * Will return value set in .ini files or NULL if it doesn't exist
     * !! WARNING: do not use empty() to test agains properties returned
     * by __get(). It will be always empty !!
     * @param string index
     * @return string value
     */
    public function __get($nm) {
        if (isset($this->ini_array[$nm])) {
            $r = $this->ini_array[$nm];
            return $r;
        } else {
            return NULL;
        }
    }

		private $filename;
		private $path;

    /**
     * Constructor
     *
     * Reads project's and default .ini file, sets project handler's 
     * and initializes paths.
     * @param location config file
     */
    public function __construct($path=null, $file=null) 
		{
			if(!$path || !$file) return;
			$this->path = rtrim($path, '/').'/';
			$this->filename = $file;
			$this->ini_array = parse_ini_file("$path$file", true);
    }

		public function getFile()
		{
			return $this->path.$this->filename;
		}

    /**
     * Returns protected var $ini_array.
     * @return array
     */
    public function getArray() {
        return $this->ini_array;
    }
    /**
     * Returns protected var $ini_array.
     * @return array
     */
    public function setArray($values) {
         $this->ini_array = $values;
    }
}

?>
