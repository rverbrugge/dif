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

require_once(DIF_ROOT.'plugin/InstallPlugin.php');

/**
 * Main configuration 
 * @package Common
 */
class FormInstaller extends InstallPlugin 
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
		$this->backupFiles 	= array("templates", "htdocs/css");

		$this->basePath = realpath(dirname(__FILE__))."/";
	}

	public function updateSql()
	{
		$db = $this->getDb();

		//use this to modify tables (add / change columns)
		if(!$this->columnExists('form_mandatorysign', 'form_settings'))
		{
			$query = "alter table form_settings add form_mandatorysign varchar(100) default null after form_templatefield";
			$res = $db->query($query);
		}

		if(!$this->columnExists('rcd_create', 'form_record'))
		{
			$query = "alter table form_record add rcd_create datetime default null after rcd_client";
			$res = $db->query($query);

			$query = "update form_record set rcd_create = rcd_ts";
			$res = $db->query($query);
		}

		if(!$this->columnExists('rcd_optin', 'form_record'))
		{
			$query = "alter table form_record add rcd_optin varchar(25) default '' after rcd_client";
			$res = $db->query($query);
		}

		if($this->columnExists('form_cc', 'form_settings'))
		{
			$query = "alter table form_settings change form_cc form_action integer default 1";
			$res = $db->query($query);
		}
		else
		{
			$query = "alter table form_settings add form_action integer default 1 after form_to";
			$res = $db->query($query);
		}

		if(!$this->columnExists('form_optin_tree_id', 'form_settings'))
		{
			$query = "alter table form_settings add form_optin_tree_id integer default null after form_ref_tree_id";
			$res = $db->query($query);
		}

		if(!$this->columnExists('form_mail_text', 'form_settings'))
		{
			$query = "alter table form_settings add form_mail_text text default null after form_action";
			$res = $db->query($query);
		}

		if(!$this->columnExists('form_caption', 'form_settings'))
		{
			$query = "alter table form_settings add form_caption varchar(50) default '' after form_to";
			$res = $db->query($query);
		}

		if(!$this->columnExists('item_elm_id', 'form_record_item'))
		{
			$query = "alter table form_record_item add item_elm_id varchar(15) default '' after item_rcd_id";
			$res = $db->query($query);

			$query = "alter table form_record_item add item_classname varchar(25) default '' after item_elm_id";
			$res = $db->query($query);

			$query = "alter table form modify form_type varchar(25) NOT NULL";
			$res = $db->query($query);

			// create temporary table
			$query = "create temporary table formtype ( id int, name char(25))";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			$query = "insert into formtype values ".
								"(0, 'InputDescription'),".
								"(1, 'InputTextField'),".
								"(2, 'InputTextArea'),".
								"(3, 'InputEmail'),".
								"(4, 'InputEmailSender'),".
								"(5, 'InputNumeric'),".
								"(6, 'InputPhone'),".
								"(7, 'InputDate'),".
								"(8, 'InputLogin'),".
								"(9, 'InputCombo'),".
								"(10, 'InputCheckbox'),".
								"(11, 'InputRadio'),".
								"(12, 'InputRadioHorizontal'),".
								"(13, 'InputRadioExtra'),".
								"(14, 'InputMultiCheckbox'),".
								"(15, 'InputMultiCheckboxHorizontal'),".
								"(16, 'InputMultiCheckboxExtra')";

			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());

			$query = "update form as a inner join formtype as b on a.form_type = b.id set a.form_type = b.name";
			$res = $db->query($query);
			if($db->isError($res)) throw new Exception($res->getDebugInfo());
		}

		if(!$this->tableExists('form_tree_settings'))
		{
			$query = "rename table form_settings to form_tree_settings";
			$res = $db->query($query);

			// add new mandatory fields tag to email text do display elements
			$query = "update form_tree_settings set form_mail_text = concat(form_mail_text,'\n','<?=\$fields;?>') where form_mail_text <> ''";
			$res = $db->query($query);
		}
	}
}

?>
