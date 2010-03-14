CREATE TABLE IF NOT EXISTS `attachment_headlines` (
  `att_tree_id` mediumint(9) NOT NULL default '0',
  `att_tag` varchar(100) NOT NULL,
  `att_ref_tree_id` mediumint(9) default '0',
  `att_name` varchar(100) default NULL,
  `att_rows` int(11) default '5',
  `att_order` tinyint(4) default NULL,
  `att_usr_id` mediumint(9) NOT NULL default '0',
  `att_own_id` mediumint(9) default NULL,
  `att_create` datetime default NULL,
  `att_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`att_tree_id`,`att_tag`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `attachment_archive` (
  `att_tree_id` mediumint(9) NOT NULL default '0',
  `att_tag` varchar(100) NOT NULL,
  `att_online` date default NULL,
  `att_offline` date default NULL,
  `att_usr_id` mediumint(9) NOT NULL default '0',
  `att_own_id` mediumint(9) default NULL,
  `att_create` datetime default NULL,
  `att_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`att_tree_id`,`att_tag`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `attachment_settings` (
  `att_id` mediumint(8) NOT NULL auto_increment,
  `att_display` tinyint(1) default '1',
  `att_display_hdl` tinyint(1) default '1',
  `att_rows` int(11) default NULL,
  `att_order` tinyint(4) default '4',
  `att_usr_id` mediumint(9) NOT NULL default '0',
  `att_own_id` mediumint(9) default NULL,
  `att_create` datetime default NULL,
  `att_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`att_id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `attachment` (
  `att_id` mediumint(8) NOT NULL auto_increment,
  `att_tree_id` mediumint(9) NOT NULL default '0',
  `att_tag` varchar(100) default NULL,
  `att_ref_id` mediumint(9) default '0',
  `att_weight` int(11) default '10',
  `att_active` tinyint(1) default '1',
  `att_online` date default NULL,
  `att_offline` date default NULL,
  `att_name` varchar(100) default NULL,
  `att_intro` varchar(255) default NULL,
  `att_file` varchar(255) default NULL,
  `att_usr_id` mediumint(9) NOT NULL default '0',
  `att_own_id` mediumint(9) default NULL,
  `att_create` datetime default NULL,
  `att_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`att_id`),
  KEY `idx_att_tree_id` (`att_tree_id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `attachment_tree_ref` (
  `att_tree_id` mediumint(9) NOT NULL default '0',
  `att_tag` varchar(100) NOT NULL,
  `att_ref_tree_id` mediumint(9) NOT NULL default '0',
  `att_usr_id` mediumint(9) NOT NULL default '0',
  `att_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`att_tree_id`,`att_tag`,`att_ref_tree_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
