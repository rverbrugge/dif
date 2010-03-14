<?php
/**
 * Section Tree
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
 * @copyright 2006 Ramses Verbrugge
 * @package core
 * @version $Id: $
 */


/**
 * Class for generating a tree object.
 * @package Core
 * @author Ramses Verbrugge <ramses@freethemallocs.nl>
 */
abstract class Tree
{
	
	/**
	 * BITMASK VALUES FOR SELECTING THE RIGHT AUTHORIZATION GROUPS IN SEARCHCRITERIA OF GETLIST FUNCTION
	 */
	const MASK_GROUP_R = 1;
	const MASK_GROUP_W = 2;
	const MASK_GROUP_X = 4;

	const TREE_DEFAULT = 1;
	const TREE_ORIGINAL = 2;

  /**
  * prefix to generate path
  * @var string prefix of path
  */
  protected $prefix;

  /**
  * current node id
  * @var string 
  */
  protected $currentId;
  protected $currentIdExists;

  /**
  * xml document tree object
  * @var DOMDocument object
  */
  protected $doc;
  protected $docOriginal;

  /**
  * xml tree object
  * @var DOMXPath object
  */
  protected $tree;
  protected $treeOriginal;

	/**
	 * name of xml node element
	 */
	protected $nodename;
  
  /**
  * root id of tree
  * @var integer
  */
  private $rootId = 0;
  protected $rootName;
  protected $treeName;
  protected $treeTitle;
  protected $treeDescription;
  protected $keywords;
  protected $fileName;
  
  
	/**
	 * Constructor. Can't be called directly, use getInstance() instead.
	 */
	public function __construct() 
	{
	}

  public function setPrefix($s_name)
	{
		$this->prefix = $s_name;
	}
	
	public function getPrefix()
	{
		return $this->prefix;
	}
	
  public function setCurrentId($id)
	{
		$this->currentId = $id;
	}
	
  public function getCurrentId($type = self::TREE_DEFAULT)
	{
		return $type == self::TREE_DEFAULT ? $this->currentId : $this->currentIdExists;
	}
	
  public function setCurrentIdExists($value)
	{
		$this->currentIdExists = $value;
	}

	public function currentIdExists()
	{
		return $this->currentIdExists;
	}

  public function pathExists($path)
	{
		return $this->getIdFromPath($path, self::TREE_ORIGINAL);
	}
	
  public function setRootName($s_name)
	{
		$this->rootName = $s_name;
	}
	
	public function getTreeName()
	{
		return $this->treeName;
	}
	
	public function setTreeName($value)
	{
		$this->treeName = $value;
	}
	
	public function getTreeTitle()
	{
		return $this->treeTitle;
	}
	
	public function setTreeTitle($value)
	{
		$this->treeTitle = $value;
	}
	
	public function getTreeDescription()
	{
		return $this->treeDescription;
	}
	
	public function setTreeDescription($value)
	{
		$this->treeDescription = $value;
	}
	
	public function getKeywords()
	{
		return $this->keywords;
	}
	
	public function setKeywords($value)
	{
		$this->keywords = $value;
	}
	
	public function getRootName()
	{
		return $this->rootName;
	}
	
  public function setNodeName($s_name)
	{
		$this->nodeName = $s_name;
	}
	
	public function getNodeName()
	{
		return $this->nodeName;
	}
	
  public function setFileName($s_name)
	{
		$this->fileName = $s_name;
	}
	
	public function getFileName()
	{
		return $this->fileName;
	}

	public function getDomDocument()
	{
		return $this->doc;
	}
	
  /**
   * checks if the current path is the root of a site (not yet a tree node)
   * 
   * @return bool     integer|PEAR_Error      structure id or error
   * @access public
   */
  public function isSiteRoot()
  {
		$request = Request::getInstance();
		$path = trim($request->getPath(), '/');
		return (!$path || $path == $this->getPrefix());
  }
  
  /**
   * retrieves the first parent structure identifier (the root) of the structure tree
   * 
   * @return bool     integer|PEAR_Error      structure id or error
   * @access public
   */
  public function getRootId()
  {
  	if(isset($this->rootId)) return $this->rootId;
  	
  }
  
  /**
   * retrieves the first parent structure identifier (the root) of the structure tree
   * 
   * @return bool     integer|PEAR_Error      structure id or error
   * @access public
   */
  public function setRootId($rootId)
  {
		$this->rootId = $rootId;
  	
  }
  
   /**
   * retrieves the tree
   * 
   * @param boolean   clone true if tree has to be cloned
   * @return DOMXpath tree
   */
  abstract public function getTree($clone=false, $type=self::TREE_DEFAULT);
   
	public function resetTree()
	{
		unset($this->tree);
	}

	protected function node2array($result)
	{
		$retval = array();

		foreach($result as $item)
		{
			$element = array();
			foreach($item->attributes as $attr)
			{
				$element[$attr->name] = $attr->value;
			}

			foreach($item->childNodes as $elm)
			{
				if($elm->nodeName == $this->nodename || $elm->nodeType == XML_TEXT_NODE) continue;
				$element[$elm->nodeName] = $elm->nodeValue;
			}
			$retval[] = $element;

		}
		return $retval;
  }
	
  /**
   * gives the depth of a node from the root
   * 
   * @param integer   $i_id				          identifier of the current structure
   * @return bool     integer|PEAR_Error      depth of node
   * @access public
   */
  public function getDepth($id)
  {
		$retval = sizeof($this->getAncestorList($id, true));
		if($this->getPrefix()) $retval++;
		return $retval;
  }

  /**
   * retrieves the parent structure identifier of the given structure
   * 
   * @param integer   $i_id				          identifier of the current structure
   * @return bool     integer|PEAR_Error      identifier of the parent structure or error
   * @access public
   */
  public function getFollowingSiblingId($id)
  {
		//TODO check if result give the right value
		$retval = NULL;
		if(!$id) return $retval;

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']/following-sibling::{$this->nodename}[1]/@id";
		$result = $xpath->query($query);

		foreach($result as $item)
		{
			$retval =  $item->nodeValue;
		}
		if(!$retval) return $this->getRootId();

		return $retval;
  }
  
  /**
   * retrieve all the parents of the current node till the root
   * 
   * @param integer   $i_id				          Identifier of the current Tree
   * @return bool     array|PEAR_Error      Array[i_id='identifier', s_name='structure name'] or error
   * @access public
   */
  public function getFollowingSiblingNode($id)
  {
		if(!$id) return array();

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']/following-sibling::{$this->nodename}[1]";
		$result = $xpath->query($query);

		return reset($this->node2array($result));
	}
	
  /**
   * retrieves the parent structure identifier of the given structure
   * 
   * @param integer   $i_id				          identifier of the current structure
   * @return bool     integer|PEAR_Error      identifier of the parent structure or error
   * @access public
   */
  public function getPrecedingSiblingId($id)
  {
		//TODO check if result give the right value
		$retval = NULL;
		if(!$id) return $retval;

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']/preceding-sibling::{$this->nodename}[1]/@id";
		$result = $xpath->query($query);

		foreach($result as $item)
		{
			$retval =  $item->nodeValue;
		}
		if(!$retval) return $this->getRootId();

		return $retval;
  }
  
  /**
   * retrieve all the parents of the current node till the root
   * 
   * @param integer   $i_id				          Identifier of the current Tree
   * @return bool     array|PEAR_Error      Array[i_id='identifier', s_name='structure name'] or error
   * @access public
   */
  public function getPrecedingSiblingNode($id)
  {
		if(!$id) return array();

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']/preceding-sibling::{$this->nodename}[1]";
		$result = $xpath->query($query);

		return end($this->node2array($result));
	}

  /**
   * retrieves the parent structure identifier of the given structure
   * 
   * @param integer   $i_id				          identifier of the current structure
   * @return bool     integer|PEAR_Error      identifier of the parent structure or error
   * @access public
   */
  public function getParentId($id)
  {
		$retval = NULL;
		if(!$id) return $retval;

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']/parent::{$this->nodename}/@id";
		$result = $xpath->query($query);

		foreach($result as $item)
		{
			$retval =  $item->nodeValue;
		}
		if(!$retval) return $this->getRootId();

		return $retval;
  }
  
  /**
   * retrieve all the parents of the current node till the root
   * 
   * @param integer   $i_id				          Identifier of the current Tree
   * @return bool     array|PEAR_Error      Array[i_id='identifier', s_name='structure name'] or error
   * @access public
   */
  public function getParentNode($id)
  {
		if(!$id) return array();

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']/parent::{$this->nodename}";
		$result = $xpath->query($query);

		return $this->node2array($result);
	}

  /**
   * retrieve all the childs of the current node
   * 
   * @param integer   $i_id				          Identifier of the current Tree
   * @return bool     array|PEAR_Error      Array[i_id='identifier', s_name='structure name'] or error
   * @access public
   */
  public function getChildList($id)
	{
		// return rootlist if node is root
		if($id == $this->getRootId()) return $this->getRootList();

		// node is not root so get childs
		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']/child::{$this->nodename}";
		$result = $xpath->query($query);

		return $this->node2array($result);
	}

  /**
   * retrieve all the childs of the current node
   * 
   * @param integer   $i_id				          Identifier of the current Tree
   * @return bool     array|PEAR_Error      Array[i_id='identifier', s_name='structure name'] or error
   * @access public
   */
  public function getRootList()
	{
		$xpath = $this->getTree();
		$query = "/{$this->rootName}/{$this->nodename}";
		$result = $xpath->query($query);

		return $this->node2array($result);
	}

  /**
   * retrieve all the parents of the current node till the root
   * 
   * @param integer   $i_id				          Identifier of the current Tree
   * @return bool     array|PEAR_Error      Array[i_id='identifier', s_name='structure name'] or error
   * @access public
   */
  public function getAncestorList($id, $includeSelf=false, $type=self::TREE_DEFAULT)
  {
		if(!$id) return array();

		$xpath = $this->getTree(false, $type);
		//$query = "//{$this->nodename}[@id='$id']/ancestor::{$this->nodename}";
		$query = sprintf("//%s[@id='%s']/%s::%s", $this->nodename, $id, ($includeSelf) ? "ancestor-or-self" : "ancestor", $this->nodename);
		$result = $xpath->query($query);

		return $this->node2array($result);
	}

  /**
   * retrieve all the childs of the current node
   * 
   * @param integer   $i_id				          Identifier of the current Tree
   * @return bool     array|PEAR_Error      Array[i_id='identifier', s_name='structure name'] or error
   * @access public
   */
  public function getDescendantList($id, $includeSelf=false)
	{
		if(!$id) return array();

		$xpath = $this->getTree();
		$query = sprintf("//%s[@id='%s']/%s::%s", $this->nodename, $id, ($includeSelf) ? "descendant-or-self" : "descendant", $this->nodename);
		$result = $xpath->query($query);

		return $this->node2array($result);
	}

  /**
   * retrieves a array list of the full structure path (all parents) for the specified structure id. it is ordered from root to the current structure.
   * 
   * @param integer   $i_id				          Identifier of the current Tree
   * @return bool     array|PEAR_Error      Array[i_id='identifier', s_name='structure name'] or error
   * @access public
   */
  public function getNode($id)
	{
		if(!$id) return array();

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']";
		$result = $xpath->query($query);

		return current($this->node2array($result));
	}

	public function getPath($id, $delimiter="/", $type=self::TREE_DEFAULT)
	{
		return ($this->exists($id)) ? $this->toString($id, $delimiter, 'url', $type) : $delimiter;
	}

	public function toString($id, $delimiter="/", $field='url', $type=self::TREE_DEFAULT)
	{
		$retval = array();
		if($this->prefix) $retval[] = $this->prefix;

		$list = $this->getAncestorList($id, true, $type);
		foreach($list as $item)
		{
			$retval[] = $item[$field];
		}

		return $delimiter.join($delimiter, $retval).$delimiter;
	}

	public function getIdFromPath($path, $type=Tree::TREE_DEFAULT)
	{
		$retval = NULL;

		$xpath = $this->getTree(false, $type);

		$path = trim($path, '/');
		$names = explode('/', $path);
		if(isset($this->prefix) && $names[0] == $this->prefix) unset($names[0]);

		$search = "/{$this->rootName}/";
		foreach($names as $item)
		{
			$search .= "{$this->nodename}[@url='$item']/";
		}

		$query = "$search@id";
		$result = $xpath->query($query);

		foreach($result as $item)
		{
			$retval =  $item->nodeValue;
		}
		return $retval;
	}

	public function getIdFromClassname($classname)
	{
		if(!$classname) return NULL;
		$retval = array();

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@class='$classname']/@id";
		$result = $xpath->query($query);

		foreach($result as $item)
		{
			$retval[] =  $item->nodeValue;
		}

		// TODO is this really neccesary?
		//dont bother giving back an array when there is only 1 result
		if(sizeof($retval) == 1) $retval = current($retval);
		return $retval;
	}

	public function getFirstNodeId()
	{
		$retval = NULL;

		$xpath = $this->getTree();
		$query = "/{$this->rootName}/{$this->nodename}[1]/@id";
		$result = $xpath->query($query);

		foreach($result as $item)
		{
			$retval =  $item->nodeValue;
		}
		return $retval;
	}

	public function getStartNodeId()
	{
		$retval = NULL;

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@startpage='1']/@id";
		$result = $xpath->query($query);

		foreach($result as $item)
		{
			$retval =  $item->nodeValue;
		}
		// get first node as starting point
		if(!$retval) $retval = $this->getFirstNodeId();

		return $retval;
	}


	public function getFirstAncestorNode($id)
	{
		$list = $this->getAncestorList($id, true);
		reset($list);
		return current($list);
	}

  /**
   * checks if the structure exists in the current view
   * 
   * @param integer $i_id				          Tree identifier
   * @return bool   true if the identifier exists
   * @access public
   */
	function exists($id)
	{
		$retval = false;

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']";
		$result = $xpath->query($query);

		foreach($result as $item)
		{
			$retval =  true;
		}
		if(!$retval && $id == $this->getRootId()) $retval = true;

		return $retval;
	}

  /**
   * checks if the structure exists in the current view
   * 
   * @param integer $i_id				          Tree identifier
   * @return bool   true if the identifier exists
   * @access public
   */
	function isLeafNode($id)
	{
		$retval = true;
		if(!$id) return $retval;

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']/child::{$this->nodename}";
		$result = $xpath->query($query);
		foreach($result as $item)
		{
			$retval = false;
		}
		return $retval;
	}


  /**
   * retrieves the name of the specified structure identifier
   * 
   * @param integer $i_id				          Tree identifier
   * @return bool   string|PEAR_Error     Tree name or error
   * @access public
   */
  function getName($id)
	{
		$retval = NULL;
		//if(!$id) return $retval;
		if($id == $this->getRootId()) return $this->getTreeName();

		$xpath = $this->getTree();
		$query = "//{$this->nodename}[@id='$id']/@name";
		$result = $xpath->query($query);

		foreach($result as $item)
		{
			$retval =  $item->nodeValue;
		}
		return $retval;
	}
 

	public function getList($skipId=NULL, $getId=NULL)
	{
		$xpath = $this->getTree((isset($skipId) || isset($getId)));

		if(isset($skipId))
		{
			$ids = '';
			if(is_array($skipId))
			{
				foreach($skipId as $item)
				{
					if($ids) $ids .= " and ";
					$ids .= "@id=$item";
				}
			}
			else
				$ids = "@id=$skipId";

			$query = "//{$this->nodename}[$ids]/descendant-or-self::{$this->nodename}";

			$result = $xpath->query($query);

			foreach($result as $item)
			{
				$parent =  $item->parentNode;
				$parent->removeChild($item);
			}
		}

		if(isset($getId))
		{
			$ids = '';
			if(is_array($getId))
			{
				foreach($getId as $item)
				{
					if($ids) $ids .= " and ";
					$ids .= "@id!=$item";
				}
			}
			else
				$ids = "@id=$getId";

			$query = "//{$this->nodename}[$ids]/descendant-or-self::{$this->nodename}";

			$result = $xpath->query($query);

			foreach($result as $item)
			{
				$parent =  $item->parentNode;
				$parent->removeChild($item);
			}
		}

		$query = "//{$this->nodename}";
		$result = $xpath->query($query);
		$list = $this->node2array($result);
		return $list;
	}

	function tree2array()
	{
		//return $this->xml2array($this->doc);
		return $this->xml2array($this->tree);
	}

		//FIXME this does not work!
	function xml2array($domnode)
	{
		$nodearray = array();
		$domnode = $domnode->firstChild;

		while (!is_null($domnode))
		{
			$currentnode = $domnode->nodeName;
			switch ($domnode->nodeType)
			{
				case XML_TEXT_NODE:
				if(!(trim($domnode->nodeValue) == "")) $nodearray['cdata'] = $domnode->nodeValue;
				break;
				case XML_ELEMENT_NODE:
				if ($domnode->hasAttributes() )
				{
					$elementarray = array();
					$attributes = $domnode->attributes;
					foreach ($attributes as $index => $domobj)
					{
						$elementarray[$domobj->name] = $domobj->value;
					}
				}
				break;
			}

			if ( $domnode->hasChildNodes() )
			{
				$nodearray[$currentnode][] = $this->xml2array($domnode);
				if (isset($elementarray))
				{
					$currnodeindex = count($nodearray[$currentnode]) - 1;
					$nodearray[$currentnode][$currnodeindex]['@'] = $elementarray;
				}
			} else 
			{
				if (isset($elementarray) && $domnode->nodeType != XML_TEXT_NODE)
				{
					$nodearray[$currentnode]['@'] = $elementarray;
				}
			}
			$domnode = $domnode->nextSibling;
		}
		return $nodearray;
	}
}
?>
