<?php
/**
 * Object to parse sql where statements
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
 * @package Database
 */
interface DataConnector
{

	/**
	 * retrieves a list from a database
	 * @param integer rowcount number of records found
	 * @param array searchcriteria supplied criteria
	 * @param integer pagesize size of result
	 * @param integer page offset
	 * @param integer order type of order
	 * @return array
	 */
	public function getList($searchcriteria=NULL, $pagesize=0, $page=1, $order=NULL);

	/**
	 * retrieves a record
	 * @param array whith searchcriteria [fieldname => value]
	 * @return array
	 */
	public function getDetail($id);

	/**
	 * retrieves a record
	 * @param array whith searchcriteria [fieldname => value]
	 * @return array
	 */
	public function getName($id);

	/**
	 * insert a record
	 * @param array whith properties [fieldname => value]
	 * @return void
	 */
	public function insert($values);

	/**
	 * retrieves a record
	 * @param array whith searchcriteria [fieldname => value]
	 * @param array whith properties [fieldname => value]
	 * @return array
	 */
	public function update($id, $values);

	/**
	 * retrieves a record
	 * @param array whith searchcriteria [fieldname => value]
	 * @return array
	 */
	public function delete($id);
}

?>
