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
class ReservationUserLink extends DbConnector
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
		parent::__construct();
		
		$this->sqlParser->setSelect('select');
		$this->sqlParser->setTable('reservation_userlink', 'a');
		$this->sqlParser->addField(new SqlField('a', 'lnk_usr_id', 'usr_id', 'User id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'lnk_grp_id', 'grp_id', 'Group id', SqlParser::getTypeSelect()|SqlParser::getTypeModify()|SqlParser::PKEY, SqlField::TYPE_INTEGER, true));
		$this->sqlParser->addField(new SqlField('a', 'lnk_ts', 'ts', 'Gewijzigd', SqlParser::getTypeSelect(), SqlField::TYPE_DATE));
	}

/*-------- DbConnector insert function {{{------------*/

	/**
	 * Returns protected var $oberserver.
	 * @param string observer key
	 * @return array
	 */
	protected function parseCriteria($sqlParser, $searchcriteria)
	{
		$adduserfrom = false;
		if(!$searchcriteria || !is_array($searchcriteria)) return;

		foreach($searchcriteria as $key=>$value)
		{
			switch($key)
			{
				case 'tree_id' : 
					if(!$adduserfrom)
					{
						$sqlParser->addFrom('inner join reservation_usergroup as b on a.lnk_grp_id = b.grp_id');
						$adduserfrom = true;
					}
					$sqlParser->addCriteria(new SqlCriteria('b.grp_tree_id', $value, '='));
					break;
				case 'tag' : 
					if(!$adduserfrom)
					{
						$sqlParser->addFrom('inner join reservation_usergroup as b on a.lnk_grp_id = b.grp_id');
						$adduserfrom = true;
					}
					$sqlParser->addCriteria(new SqlCriteria('b.grp_tag', $value, '='));
					break;
				case 'own_id' : 
					if(!$adduserfrom)
					{
						$sqlParser->addFrom('inner join reservation_usergroup as b on a.lnk_grp_id = b.grp_id');
						$adduserfrom = true;
					}
					$sqlParser->addCriteria(new SqlCriteria('b.grp_usr_id', $value, '='));
					break;
			}
		}
	}

	/**
	 * filters field values like checkbox conversion and date conversion
	 *
	 * @param array unfiltered values
	 * @return array filtered values
	 * @see DbConnector::filterFields
	 */
	public function filterFields($fields)
	{
		return $fields;
	}

	//}}}

}

?>
