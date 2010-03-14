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
class SiteTree extends Tree
{
	private $systemSite;
	private $sitegroupId;
	
	private $isAdmin;
	private $userGroupList;
	private $aclList;
	private $director;
	
	/**
	 * Constructor. Can't be called directly, use getInstance() instead.
	 */
	public function __construct(SystemSite $site) 
	{
		parent::__construct();
		$this->systemSite = $site;
		$this->nodename = 'item';
		$this->rootName = 'tree';

		$this->initialize();
	}

	private function initialize()
	{
		//$siteGroup = new SystemSiteGroup();
		$director = Director::getInstance();
		$siteGroup = $director->siteGroup;
		$this->sitegroupId = $siteGroup->getCurrentId();
		if(!$this->sitegroupId) return;

		$sitegroup = $siteGroup->getDetail(array('id' => $this->sitegroupId));

		$this->setRootId($sitegroup['tree_root_id']);
		$this->treeName = $sitegroup['formatName'];
		$this->treeTitle = $sitegroup['title'] ? $sitegroup['title'] : $sitegroup['formatName'];
		$this->keywords = $sitegroup['keywords'];
		$this->treeDescription = $sitegroup['description'];

		//$this->treeName = $siteGroup->getName(array('id' => $this->sitegroupId));
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

		$this->docOriginal = new DOMDocument('1.0', 'iso-8859-1');

		$cache = Cache::getInstance();
		if(!$cache->isCached(__CLASS__))
		{
			$node = $this->docOriginal->createElement($this->rootName);
			$root = $this->docOriginal->appendChild($node);

			$searchcriteria = array('sitegroup_id' => $this->sitegroupId);

			// create a reference list with node id as key
			$nodelist = array();
			$list = $this->systemSite->getList($searchcriteria);
			foreach($list['data'] as $item)
			{
				$nodelist[$item['id']] = $item;
			}

			// retrieve acl list
			$auth = Authentication::getInstance();
			$acl = new Acl();
			$this->isAdmin = $auth->isRole(SystemUser::ROLE_ADMIN);
			$this->aclList = $acl->getAclList($searchcriteria);
			$this->userGroupList = $auth->getGroup();
			$this->director = Director::getInstance();

			foreach($nodelist as $item)
			{
				$this->createNode($this->docOriginal, $root, $nodelist, $item);
			}

			$cache->save($this->docOriginal->saveXML(), __CLASS__);
		}
		else
		{
			$this->docOriginal->loadXML($cache->getCache(__CLASS__));
		}
		$this->treeOriginal = new DOMXpath($this->docOriginal);

		// filter tree
		$this->doc = new DOMDocument('1.0', 'iso-8859-1');
		if(!$cache->isCached(__CLASS__."filter"))
		{
			$this->doc = clone $this->docOriginal;

			$this->tree = new DOMXPath($this->doc);
			$this->filterTree();
			$cache->save($this->doc->saveXML(), __CLASS__."filter");
		}
		else
		{
			$this->doc->loadXML($cache->getCache(__CLASS__."filter"));
			$this->tree = new DOMXPath($this->doc);
		}

/*
		// We don't want to bother with white spaces
		$doc->preserveWhiteSpace = false;
		$doc->Load($this->xmlfile);
		*/

		if($type == Tree::TREE_ORIGINAL)
			return $this->treeOriginal;
		else
			return $this->tree;
	}

	private function createNode($doc, $rootNode, $nodelist, $current)
	{
		// check if node already exists
		$tmpnode = $doc->getElementById($current['id']);
		if($tmpnode) return;

		$node = $doc->createElement($this->nodename);
		
		// skip if user is admin
		$acl = 0;
		if(!$this->isAdmin)
		{
			$acl = $this->director->isAdminSection() ? 1 : 0; // assume acl exists for back end and no acl exists for front end
			if(array_key_exists($current['id'], $this->aclList))
			{
				// extract view permission acls
				$tmpAcl = array();
				foreach($this->aclList[$current['id']] as $item)
				{
					if($this->director->isAdminSection())
					{
						// back end
						if(($item['rights'] | Acl::VIEW) != Acl::VIEW) 
						{
							$acl = 1; // other rights than view (backend) found. activate acl
							$tmpAcl[] = $item; // add acl group to templist
						}
					}
					else
					{
						// front end
						if(($item['rights'] & Acl::VIEW) == Acl::VIEW) 
						{
							$acl = 1; // view (frontend) rights found. activate acl
							$tmpAcl[] = $item; // add acl group to templist
						}
					}
				}
				if($acl && $this->userGroupList) // only proceed if user has usergroup and acl exists
				{
					// search if user has any acl
					foreach($tmpAcl as $item)
					{
						if(in_array($item['grp_id'], $this->userGroupList)) 
						{
							// user has a acl in this list, reset acl and break
							$acl = 0; 
							break;
						}
					}
				}
			}
		}

		/*
		$name = $doc->createElement('name', $current['name']);
		$title = $doc->createElement('title', $current['title']);
		$node->appendChild($name);
		$node->appendChild($title);
		*/
		//FIXME make sure the parent already exists1
		$parent = ($current['parent'] == $this->getRootId()) ? $rootNode : $doc->getElementById($current['parent']);
		if(!$parent) $parent = $this->createNode($doc, $rootNode, $nodelist, $nodelist[$current['parent']]);

		$element = $parent->appendChild($node);
		$element->setAttribute('id', $current['id']);
		$element->setAttribute('parent', $current['parent']);
		$element->setAttribute('startpage', $current['startpage']);
		$element->setAttribute('name', $current['name']);
		$element->setAttribute('title', $current['title']);
		//$element->setAttribute('xml:id', $current['id']);
		$element->setAttribute('url', $current['url']);
		$element->setAttribute('external', $current['external']);
		$element->setAttribute('weight', $current['weight']);
		$element->setAttribute('active', $current['active']);
		$element->setAttribute('activated', $current['activated']);
		$element->setAttribute('visible', $current['visible']);
		$element->setAttribute('acl', $acl);
		$element->setIdAttribute('id', true);
		return $element;
	}

	private function filterTree()
	{
		$search = "@acl != 0"; 

		if(!$this->director->isAdminSection())
		{
			//handle filter for live site (front end)
			$active = "@activated='0'"; 
			$search = "($search) or $active";
		}

		if(!$search) return;
	
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
