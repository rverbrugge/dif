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
 * @copyright 2007 Dif systems
 * @author Ramses Verbrugge <ramses@huizeverbrugge.nl>
 * @package Dif
 */

require_once 'Pager_Wrapper.php';
require_once('MDB2.php');
require_once('SqlParser.php');
require_once(DIF_ROOT.'/core/DataConnector.php');

/**
 * Provides an interface to manage database tables
 * 
 * @package Database
 */
abstract class DbConnector implements DataConnector
{

	/**
	 * Array with SqlCriteria objects, which holds all related where statements 
	 * @var array
	 */
	protected $sqlParser;

	/**
	 * Array with order types
	 * eg. $retval = array(self::TYPE_DEFAULT => 'order by a.usr_id %s');
	 * @var array
	 */
	protected $orderStatement;

	protected $db;

	/**
	 * Logger object 
	 * @var Logger
	 */
	protected $log;

	/**
	 * Director object
	 * @var Director
	 */
	protected $director;

	/**
	 * Stores name of page variable to store page numbers
	 * @var string
	 */
	protected $pagerKey;

	/**
	 * Url that is used to create links to split up pages
	 * @var Url
	 */
	protected $pagerUrl;

	/**
	 * Options for the Pager Object
	 * @see Pager_Wrapper_MDB2()
	 * @var array
	 */
	protected $pagerOptions = array(	'mode'       => 'Sliding',
																		'append' 			=> false, //don't append the GET parameters to the url 
																		'prevImg'               => '&laquo;',
																		'nextImg'               => '&raquo;',
																		'separator'             => '|',
																		'spacesBeforeSeparator' => '',
																		'spacesAfterSeparator'  => '',
																		'curPageSpanPre'        => '<span class="selected">',
																		'curPageSpanPost'       => '</span>');
																		//'path' => 'http://myserver.com/products/' . $cat, 

	/**
	 * Constructor
	 *
	 * retrieves instance of Director {@link $director}
	 * sets pager key and url {@link $pagerKey}, {@link $pagerUrl}
	 * initialize sqlParser {@link $sqlParser}
	 */
	protected function __construct()
	{
		$this->log = Logger::getInstance();
		$this->director = Director::getInstance();
		$this->pagerKey = 'page';
		$this->pagerUrl = new Url(true);

		$this->sqlParser = new SqlParser();
		$this->orderStatement = array();
	}


	/**
	 * Returns key name to assign a page to
	 * @return string
	 */
	public function getPagerKey()
	{
		return $this->pagerKey;
	}

	/**
	 * Set page key identifier to store page number
	 * page key is used in GET urls and SESSION variable
	 * @param string $key name of pager key
	 */
	public function setPagerKey($key)
	{
		$this->pagerKey = $key;
	}

	/**
	 * Returns url name to assign a page to
	 * @return string
	 */
	public function getPagerUrl()
	{
		return $this->pagerUrl;
	}

	/**
	 * Set page url identifier to store page number
	 * page url is used in GET urls and SESSION variable
	 * @param string $url name of pager url
	 */
	public function setPagerUrl($url)
	{
		$this->pagerUrl = $url;
	}

	/**
	 * Retrieves page number form get or post request
	 * Uses session to store page number in post request
	 * @return integer
	 */
	public function getPage()
	{
		$request 	= Request::getInstance();
		$key 			= $this->getPagerKey();
		$value 		= 1;

		// try to get page
		if($request->exists($key))
			$value = intval($request->getValue($key));
		elseif($request->getValue('REQUEST_METHOD', Request::SERVER) == 'POST' && $request->exists($key, Request::SESSION))
			$value = intval($request->getValue($key, Request::SESSION));

		// default to 1 if answer is garbage
		if($value < 1) $value = 1;

		// save to session
		$request->setValue($key, $value);
		return $value;
	}

	/**
	 * Returns global reference to database object from Director {@link $director}
	 * @see Pear::MDB2
	 * @return Pear::MDB2
	 */
	protected function getDb() 
	{
		return  $this->director->getDb();
	}

	/**
	 * parse an array of search criteria
	 *
	 * @param SqlParser $SqlParser reference to object  
	 * @param array $searchcriteria supplied search criteria
	 */
	protected function parseCriteria($SqlParser, $searchcriteria, $prefix=true)
	{
	}

	/**
	 * parse an array with fieldname => value 
	 * @param SqlParser object  
	 * @param array searchcriteria supplied criteria
	 * @return 
	 */
	 /*
	protected function parseFields($SqlParser, $values)
	{
		if(!$sqlParser) throw new Exception('Sql Parser is missing.');
		if(!$values || !is_array($values)) throw new Exception('Nothing to parse. no values specified.');

		foreach($values as $key=>$value)
		{
			$field = $sqlParser->getFieldsByName($key);
			if!$field) continue;
			$sqlParser->addCriteria(new SqlCriteria($field->getField(), $value));
		}
	}
	*/

	/**
	 * returns an order strings used in sprintf for acsending descending option
	 * @return array with order strings
	 */
	private function getOrderStatement($type)
	{
		if(!array_key_exists($type, $this->orderStatement)) return current($this->orderStatement);
		return $this->orderStatement[$type];
	}

	protected function getOrder($type)
	{
		$order = '';
		if(($type & SqlParser::ORDER_DESC) == SqlParser::ORDER_DESC)
			$order = SqlParser::ORDER_DESC;
		elseif(($type & SqlParser::ORDER_ASC) == SqlParser::ORDER_ASC)
			$order = SqlParser::ORDER_ASC;

		$statement = $order ? $type ^ $order : $type;

		return sprintf($this->getOrderStatement($statement), ($order == SqlParser::ORDER_ASC) ? 'asc' : 'desc');
	}

	/**
	 * retrieves a list from a database
	 * @param array $searchcriteria supplied criteria
	 * @param integer $pagesize size of result
	 * @param integer $page offset
	 * @param integer $order type of order
	 * @return array
	 */
	public function getList($searchcriteria=NULL, $pagesize=0, $page=1, $order=NULL)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($searchcriteria);
		$this->parseCriteria($sqlParser, $searchcriteria);
		$sqlParser->setOrderby($this->getOrder($order));

		$query = $sqlParser->getSql(SqlParser::SEL_LIST);
		//if($sqlParser->getTable() == 'siteplugin') echo $query."<br />\n";
		//if($sqlParser->getTable() == 'users') Utils::debug($query);

		$db = $this->getDb();

		$this->pagerUrl->setParameter($this->getPagerKey(),'%d', false);//Pager replaces "%d" with the page number 
		$this->pagerOptions['perPage']    = $pagesize;
		$this->pagerOptions['currentPage']= $page;
		$this->pagerOptions['fileName']		= $this->pagerUrl->getUrl(false);

		$res =  Pager_Wrapper_MDB2($db, $query, $this->pagerOptions, ($pagesize == 0));
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		// do postprocessing
		foreach($res['data'] as &$item)
		{
			if(!$item) continue;
			$item['formatName'] =  $this->formatName($item);
			$item = $this->handlePostGetList($item);
		}

		return $res;
	}

	/**
	 * retrieves a record
	 * @param array whith searchcriteria [fieldname => value]
	 * @return array
	 */
	public function getDetail($id, $order=NULL)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($id);
		$this->parseCriteria($sqlParser, $id);
		$sqlParser->setOrderby($this->getOrder($order));

		$sql = $sqlParser->getSql(SqlParser::SEL_DETAIL);
		//if($sqlParser->getTable() == 'reservation') echo $sql."<br />\n";

		$db = $this->getDb();

		$db->setLimit(1);

		$res = $db->query($sql);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$row =  $res->fetchRow(MDB2_FETCHMODE_ASSOC);

		// do postprocessing
		if($row) 
		{
			$row['formatName'] =  $this->formatName($row);
			$row = $this->handlePostGetDetail($row);
		}
		return $row;
	}

	/**
	 * insert a record
	 * @param array whith properties [fieldname => value]
	 * @return void
	 */
	public function insert($values)
	{
		$values = $this->setFields($values);
		$sqlParser = clone $this->sqlParser;

		try
		{
			$this->handlePreInsert($values);

			$db = $this->getDb();
      $sqlParser->validate(SqlParser::MOD_INSERT);

			$sql = $sqlParser->getSql(SqlParser::MOD_INSERT);
			$res = $db->query($sql);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			//return Utils::getInsertId($db);
			$retval = array();
			$key = $sqlParser->getFieldByType(SqlParser::PKEY);
			if(sizeof($key) > 1)
			{
				// multi key table
				foreach($key as $item)
				{
					$retval[$item->getName()] = $item->getValue();
				}
			}
			else
			{
				// single key table
				$field = current($key);
				// check if key is autoincrement or not. It is not auto if the key already contains a value
				if($field->getValue()) 
				{
					$retval[$field->getName()] = $field->getValue();
				}
				else
				{
					// field is autoincrement
					//$id =  $db->lastInsertID();//$sqlParser->getTable(), $field->getField());
					// lastInsertID throws an open_basedir restriction error because it tries to inclue Datatype/mysql.php
					// this is because lastInsertID provides datatype integer to the query.
					$id = $db->queryOne('SELECT LAST_INSERT_ID()');
					$retval[$field->getName()] = $id;
				}
			}

			$this->handlePostInsert($retval, $values);

			$debug = array_merge($values, $retval);
			$this->infoLog(__FUNCTION__, $debug);

			$this->director->notify($this, $retval, Director::INSERT);

			return $retval;
		}
		catch(Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * handle post getlist additions 
	 * eg. add image 
   *
	 * @param array row array
	 * @return array
	 */
	protected function handlePostGetList($values)
	{
		return $values;
	}

	/**
	 * handle post getDetail additions 
	 * eg. add image 
   *
	 * @param array row array
	 * @return array
	 */
	protected function handlePostGetDetail($values)
	{
		return $values;
	}

	/**
	 * handle pre insert checks and additions 
	 * eg. check for uniqueness of set default values
   *
	 * @param array filtered values for insertion
	 * @return void
	 */
	protected function handlePreInsert($values)
	{
	}

	/**
	 * handle post insert checks and additions 
	 * eg. insert image
   *
	 * @param integer id of inserted object
	 * @param array filtered values for insertion
	 * @return void
	 */
	protected function handlePostInsert($id, $values)
	{
	}

	protected function handlePreUpdate($id, $values)
	{
	}

	protected function handlePostUpdate($id, $values)
	{
	}

	/**
	 * retrieves a record
	 * @param array whith searchcriteria [fieldname => value]
	 * @param array whith properties [fieldname => value]
	 * @return array
	 */
	public function update($id, $values)
	{
		$values = $this->setFields($values);

		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($id);
		$this->parseCriteria($sqlParser, $id);

		try
		{
			$this->handlePreUpdate($id, $values);

			$db = $this->getDb();

      $sqlParser->validate(SqlParser::MOD_UPDATE);
			$sql = $sqlParser->getSql(SqlParser::MOD_UPDATE);

			$res = $db->query($sql);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			$this->handlePostUpdate($id, $values);
			$values = array_merge($values, $id);
			$this->infoLog(__FUNCTION__, $values);

			$this->director->notify($this, $id, Director::UPDATE);
		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	protected function handlePreDelete($id, $values)
	{
	}

	protected function handlePostDelete($id, $values)
	{
	}

	/**
	 * retrieves a record
	 * @param array whith searchcriteria [fieldname => value]
	 * @return array
	 */
	public function delete($id)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($id);
		$this->parseCriteria($sqlParser, $id);

		// check if id is a key or a searchcriteria
		$unique = !array_diff($this->getKeyList(), array_keys($id));

		try
		{
			$db = $this->getDb();

			if($unique)
			{
				$values = $this->getDetail($id);
				if(!$values) return; // item does not exist.
				$this->handlePreDelete($id, $values);
			}

			$this->infoLog(__FUNCTION__, $unique ? $values : $id, $unique);

			if($unique) $this->director->notify($this, $id, Director::DELETE);

			$sql = $sqlParser->getSql(SqlParser::MOD_DELETE);
			//if($sqlParser->getTable() == 'form_record_item') echo $sql."<br />\n";
			//if($sqlParser->getTable() == 'form_record') echo $sql."<br />\n";
			$res = $db->query($sql);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			if($unique) $this->handlePostDelete($id, $values);

		}
		catch(Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * retrieves a record
	 * @param array whith searchcriteria [fieldname => value]
	 * @return array
	 */
	public function getName($id)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($id);
		$this->parseCriteria($sqlParser, $id);

		$db = $this->getDb();

		$query = $sqlParser->getSql(SqlParser::NAME);

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		$row = $res->fetchRow(MDB2_FETCHMODE_ASSOC);
		return $this->formatName($row);
	}

	public function exists($key)
	{
		return $this->getCount($key, 1);
	}

	/**
	 * retrieves a record
	 * @param array whith searchcriteria [fieldname => value]
	 * @return array
	 */
	public function getCount($key, $limit=0)
	{
		$sqlParser = clone $this->sqlParser;
		$sqlParser->parseCriteria($key);
		$this->parseCriteria($sqlParser, $key);

		$db = $this->getDb();
		if($limit) $db->setLimit($limit);

		$query = $sqlParser->getSql(SqlParser::PKEY);
		//if($sqlParser->getTable() == 'newsletter_user') echo $query."<br />\n";
		//if($sqlParser->getTable() == 'reservation') echo $query."<br />\n";

		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		return $res->numRows();
	}


	/**
	 * returns formatted name string
	 * @param array name columns
	 * @return string a formatted name of the element
	 */
	protected function formatName($values)
	{
		if(!$values) return;
		$retval = array();

		if(!isset($this->nameFields))
		{
			$this->nameFields = $this->sqlParser->getFieldByType(SqlParser::NAME);
		}

		foreach($this->nameFields as $item)
		{
			$key = $item->getName();
			$retval[$key] = $values[$key];
		}
		if(!$retval) return;

		return join(", ", $retval);
	}

	/**
	 * returns default value of a field
	 * @return mixed
	 */
	public function getDefaultValue($fieldname)
	{
	}

	/**
	 * filters field values like checkbox conversion and date conversion
	 *
	 * @param array unfiltered values
	 * @return array filtered values
	 */
	protected function filterFields($fields)
	{
		return $fields;
	}

	/**
	 * returns array with fieldnames as key and default value as value
	 *
	 * @return array
	 */
	public function getFields($type)
	{
		$fields = $this->sqlParser->getFieldByType($type);//SqlParser::MOD_INSERT);
		$retval = array();
		foreach($fields as $item)
		{
			$retval[$item->getName()] = ($item->hasValue()) ? $item->getValue() : $this->getDefaultValue($item->getName());
		}
		return $retval;
	}

	/**
	 * fills sql parser with field values
	 *
	 * @param array values from form or detail
	 * @return void
	 */
	public function setFields($values)
	{
		$values = $this->filterFields($values);
		$this->sqlParser->setFieldValues($values);
		return $values;
	}

	/**
	 * fills sql parser with field values
	 *
	 * @param array values from form or detail
	 * @return void
	 */
	public function setField($key, $value, $escape=true)
	{
		$this->sqlParser->setFieldValue($key, $value, $escape);
	}

	/**
	 * return a key pair array with values from the parameter array
	 *
	 * @param array values of fields including key fields
	 * @return array key value pair
	 */
	public function getKey($values = NULL)
	{
		$request = Request::getInstance();

		$retval = array();
		$keyList = $this->getKeyList();

		foreach($keyList as $item)
		{
			if($values)
			{
				if(!array_key_exists($item, $values)) throw new Exception("$item key is not present in provided variables in ".get_class($this));
				$retval[$item] = $values[$item];
			}
			else
			{
				if(!$request->exists($item)) throw new Exception("$item key is missing from GET/POST request in ".get_class($this));
				$retval[$item] = $request->getValue($item);
			}
		}
		return $retval;
	}

	protected function getKeyList()
	{
		if(isset($this->keyList)) return $this->keyList;
		$this->keyList = array_keys($this->getFields(SqlParser::PKEY));
		return $this->keyList;
	}

	/**
	 * return a key pair array with values from the parameter array
	 *
	 * @param array values of fields including key fields
	 * @return array key value pair
	 */
	public function getNameFromValues($values)
	{
		$retval = array();
		$nameList = $this->getNameList();
		foreach($nameList as $item)
		{
			//if(!array_key_exists($item, $values)) throw new Exception("Key is not present in provided variables in ".get_class($this));
			if(!array_key_exists($item, $values)) continue;

			$retval[$item] = $values[$item];
		}
		return $retval;
	}

	protected function getNameList()
	{
		if(isset($this->nameList)) return $this->nameList;
		$this->nameList = array_keys($this->getFields(SqlParser::NAME));
		return $this->nameList;
	}

	protected function tableExists($tableName)
	{
		$db = $this->getDb();
		$query = sprintf("show tables like  '%s'", addslashes($tableName));
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		return ($res->fetchRow());
	}

	protected function columnExists($columnName, $tableName)
	{
		$db = $this->getDb();
		$query = sprintf("show columns from %s", $tableName);
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		while($row = $res->fetchRow())
		{
			if($row[0] == $columnName) return true;
		}

		return false;
	}

	protected function getColumn($columnName, $tableName, $item)
	{
		$retval = '';

		$db = $this->getDb();
		//$query = sprintf("show columns from %s where Field = '%s'", $tableName, $columnName);
		$query = sprintf("show columns from %s", $tableName);
		$res = $db->query($query);
		if($db->isError($res)) throw new Exception($res->getDebugInfo());

		while($row = $res->fetchRow())
		{
			if($row[0] == $columnName) $retval = $row[$item];
		}

		return $retval;
	}

	protected function infoLog($function, $values, $haskey=true)
	{
		if(!$this->log->isEnabled(Logger::INFO)) return;

		$infoarray = $haskey ? array_merge($this->getKey($values),$this->getNameFromValues($values)) : $values;
		$info = array();
		foreach($infoarray as $key=>$value)
		{
			$info[] = "$key = $value";
		}

		$this->log->info(join(', ',$info), get_class($this), $function);
	}

}
?>
