CREATE TABLE IF NOT EXISTS `acl` (
  `acl_tree_id` mediumint(9) NOT NULL default '0',
  `acl_grp_id` mediumint(9) NOT NULL default '0',
  `acl_rights` tinyint(1) default '0',
  `acl_create` datetime default NULL,
  `acl_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`acl_tree_id`,`acl_grp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `extension` (
  `ext_id` mediumint(8) NOT NULL auto_increment,
  `ext_active` tinyint(1) default '1',
  `ext_name` varchar(100) default NULL,
  `ext_description` text,
  `ext_classname` varchar(100) default NULL,
  `ext_version` varchar(25) default NULL,
  `ext_dif_version` varchar(15) default NULL,
  `ext_usr_id` mediumint(9) default NULL,
  `ext_create` datetime default NULL,
  `ext_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`ext_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `groups` (
  `grp_id` mediumint(8) NOT NULL auto_increment,
  `grp_name` varchar(50) default NULL,
  PRIMARY KEY  (`grp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `plugin` (
  `plug_id` mediumint(8) NOT NULL auto_increment,
  `plug_active` tinyint(1) default '1',
  `plug_name` varchar(100) default NULL,
  `plug_description` text,
  `plug_classname` varchar(100) default NULL,
  `plug_version` varchar(25) default NULL,
  `plug_dif_version` varchar(15) default NULL,
  `plug_usr_id` mediumint(9) default NULL,
  `plug_create` datetime default NULL,
  `plug_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`plug_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `sitegroup` (
  `grp_id` mediumint(8) NOT NULL auto_increment,
  `grp_active` tinyint(1) default '1',
  `grp_startpage` tinyint(1) default '0',
  `grp_name` varchar(50) default NULL,
  `grp_title` varchar(50) default NULL,
  `grp_description` varchar(255) default NULL,
  `grp_keywords` varchar(255) default NULL,
  `grp_language` varchar(4) default 'nl',
  `grp_tree_root_id` tinyint(4) default '0',
  `grp_usr_id` mediumint(8) default '0',
  `grp_own_id` mediumint(8) default '0',
  `grp_create` datetime default NULL,
  `grp_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`grp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `siteplugin` (
  `tree_id` mediumint(9) NOT NULL default '0',
  `tag` varchar(100) NOT NULL default '',
  `plug_id` mediumint(9) NOT NULL default '0',
  `plug_type` tinyint(4) NOT NULL default '0',
  `plug_view` mediumint(9) default '0',
  `plug_recursive` tinyint(1) default '0',
  `plug_version` int(11) default '0',
  `usr_id` mediumint(9) default '0',
  `createdate` datetime default NULL,
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`tree_id`,`tag`),
  KEY `idx_plug_id` (`plug_id`),
  KEY `idx_usr_id` (`usr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `sitetag` (
  `tree_id` mediumint(9) NOT NULL default '0',
  `parent_tag` varchar(100) NOT NULL default '',
  `tags` varchar(100) NOT NULL default '',
  `remove_container` tinyint(4) default '1',
  `template` text,
  `stylesheet` text,
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`tree_id`,`parent_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `sitetheme` (
  `tree_id` mediumint(9) NOT NULL default '0',
  `theme_id` mediumint(9) NOT NULL default '0',
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`tree_id`),
  KEY `idx_theme_id` (`theme_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `sitetree` (
  `tree_id` mediumint(9) NOT NULL auto_increment,
  `tree_sitegrp_id` mediumint(9) default NULL,
  `tree_parent_id` mediumint(9) NOT NULL default '0',
  `tree_weight` mediumint(9) default NULL,
  `tree_active` tinyint(1) NOT NULL default '0',
  `tree_visible` tinyint(1) NOT NULL default '1',
  `tree_hide` tinyint(1) default '0',
  `tree_startpage` tinyint(1) NOT NULL default '0',
  `tree_online` date default NULL,
  `tree_offline` date default NULL,
  `tree_name` varchar(100) NOT NULL default '',
  `tree_title` varchar(100) NOT NULL default '',
  `tree_url` varchar(100) NOT NULL default '',
  `tree_external` tinyint(1) NOT NULL default '0',
  `tree_usr_id` mediumint(9) default '0',
  `tree_own_id` mediumint(9) default '0',
  `tree_create` datetime default NULL,
  `tree_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`tree_id`),
  KEY `idx_tree_parent_id` (`tree_parent_id`),
  KEY `idx_tree_usr_id` (`tree_usr_id`),
  KEY `idx_tree_own_id` (`tree_own_id`),
  KEY `idx_tree_sitegrp_id` (`tree_sitegrp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=98 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `theme` (
  `theme_id` mediumint(8) NOT NULL auto_increment,
  `theme_active` tinyint(1) default '1',
  `theme_selected` tinyint(1) default '0',
  `theme_name` varchar(100) default NULL,
  `theme_description` varchar(255),
  `theme_classname` varchar(100) default NULL,
  `theme_version` varchar(25) default NULL,
  `theme_dif_version` varchar(15) default NULL,
  `theme_image` varchar(255) default NULL,
  `theme_create` datetime default NULL,
  `theme_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`theme_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `usergroup` (
  `usr_id` mediumint(8) NOT NULL,
  `grp_id` mediumint(8) NOT NULL,
  PRIMARY KEY  (`usr_id`,`grp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `users` (
  `usr_id` mediumint(9) NOT NULL auto_increment,
  `usr_active` tinyint(1) default '1',
  `usr_notify` tinyint(4) default '0',
  `usr_role` tinyint(4) default '0',
  `usr_name` varchar(50) default NULL,
  `usr_firstname` varchar(50) default NULL,
  `usr_address` varchar(50) default NULL,
  `usr_address_nr` varchar(10) default NULL,
  `usr_zipcode` varchar(10) default NULL,
  `usr_city` varchar(50) default NULL,
  `usr_country` varchar(25) default NULL,
  `usr_phone` varchar(25) default NULL,
  `usr_mobile` varchar(25) default NULL,
  `usr_email` varchar(50) default NULL,
  `usr_username` varchar(100) NOT NULL,
  `usr_password` varchar(50) default '',
  `usr_logincount` int(11) default '0',
  `usr_logindate` datetime default NULL,
  `usr_create` datetime default NULL,
  `usr_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`usr_id`)
) ENGINE=MyISAM AUTO_INCREMENT=792 DEFAULT CHARSET=latin1;
