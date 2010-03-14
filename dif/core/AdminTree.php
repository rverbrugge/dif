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


require_once('Tree.php');

/**
 * Class for generating a tree object.
 * @package Core
 * @author Ramses Verbrugge <ramses@freethemallocs.nl>
 */
class AdminTree extends Tree
{
	
	/**
	 * Constructor. Can't be called directly, use getInstance() instead.
	 */
	public function __construct($fileName, $useLogin) 
	{
		parent::__construct();
		
		$this->useLogin = $useLogin;
		$this->fileName = $fileName;
		$this->nodename = 'item';
		$this->rootName = 'tree';
		$this->treeName = 'DIF';
		$this->treeTitle = $this->treeName;
	}

  
   /**
   * retrieves the tree
   * 
   * @param boolean   clone true if tree has to be cloned
   * @return DOMXpath tree
   */
  public function getTree($clone=false, $type=Tree::TREE_DEFAULT)
	{
		/*
		 TODO dit is de werkwijze van een database tree
		 kijk of xml bestand bestaat en geef terug
		 haal tabel op en creer xml met dom
		 schrijf xml weg naar bestand
		 bij een wijziging, gooi het xml bestand weg
		 voer AdminTree xml functies uit (in standaard abstracte klasse..


		 tree is abstract en gebruikt xml functies, implementatie gebruikt eventueel een dbConnector object.
		 */
		
		if($type == Tree::TREE_DEFAULT)
		{
			if(isset($this->tree)) 
			{
				if(!$clone) return $this->tree;
				$doc = clone $this->doc;
				return new DOMXPath($doc);
			}
		}
		elseif($type == Tree::TREE_ORIGINAL)
		{
			if(isset($this->treeOriginal)) 
			{
				if(!$clone) return $this->treeOriginal;
				$doc = clone $this->doc;
				return new DOMXPath($doc);
			}
		}

		$doc = new DOMDocument('1.0', 'iso-8859-1');

		// We don't want to bother with white spaces
		$doc->preserveWhiteSpace = false;
		$doc->Load($this->fileName);

		$this->tree = new DOMXPath($doc);

		// create a backup of tree
		$this->treeOriginal = new DOMXpath(clone $doc);

		$this->filterTree();
		$this->doc = $doc;

		if($type == Tree::TREE_ORIGINAL)
			return $this->treeOriginal;
		else
			return $this->tree;
	}

	private function filterTree()
	{
		$authentication = Authentication::getInstance();
			//$authentication->isBackdoor())

		$search = "@active='0'";
		$groupsearch = array();

		if((!$authentication->isLogin() || !$this->useLogin) && !$authentication->isRole(SystemUser::ROLE_ADMIN))
		{
			$groupsearch[] = "@role != ''";
		}
		elseif(!$authentication->isRole(SystemUser::ROLE_ADMIN))
		{
			// retrieve groups if user is not an administrator (admin can see all groups)
			/*
			$role = $authentication->getRole();
			if(!$role) throw new Exception("User has no role");
			$role = SystemUser::getRoleDesc($role);
			*/
			foreach(SystemUser::$roleList as $roleKey=>$roleValue)
			{
				if($authentication->isRole($roleKey))
					$groupsearch[] = "@role != '$roleValue'";
			}

		}

		if($groupsearch) $search .= sprintf(" or (%s)", join(" and ", $groupsearch));

		$xpath = $this->tree;
		$query = "//{$this->nodename}[$search]/descendant-or-self::{$this->nodename}";
		$result = $xpath->query($query);

		foreach($result as $item)
		{
			$parent =  $item->parentNode;
			$parent->removeChild($item);
		}
	}
}
?>
