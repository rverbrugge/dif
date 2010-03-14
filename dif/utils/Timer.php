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
class Timer
{
	static private $instance;

	/**
	 * Start time 
	 *  
	 * @var integer $timerstart
	 * @access private 
	 */
	private $timerstart=0;
	
	/**
	 * Constructor
	 *  
	 * @access public
	 */
	private function __construct()
	{
		$this->resetTimer();
	}

	static public function getInstance()
	{
		if(self::$instance == NULL)
			self::$instance = new Timer();

		return self::$instance;
	}

	/**
	 * returns microtime 
	 *  
	 * @return float
	 * @access private
	 */
	public function _getMicroTime(){ 
		list($usec, $sec) = explode(" ",microtime()); 
		return ((float)$usec + (float)$sec); 
	} 

	/**
	 * Reset timer
	 *   
	 * @return void
	 * @access public
	 */
	public function resetTimer()
	{
		$this->timerstart = $this->_getMicroTime();
	}

	/**
	 * Returns elapsed time 
	 *   
	 * @return string formatted seconds
	 * @access public
	 */
	public function getTime()
	{
		$time_end = $this->_getMicroTime();
		$time = $time_end - $this->timerstart;
		return number_format($time,5);
	}
}
?>
