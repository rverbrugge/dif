CREATE TABLE IF NOT EXISTS `poll_item` (
  `item_id` mediumint(9) NOT NULL auto_increment,
  `item_poll_id` int(11) default NULL,
  `item_active` tinyint(1) default '1',
  `item_weight` int(11) default '0',
  `item_name` varchar(50) default NULL,
  `item_votes` int(11) default NULL,
  `item_create` datetime default NULL,
  `item_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=39 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `poll_settings` (
  `poll_id` mediumint(9) NOT NULL auto_increment,
  `poll_display` int(11) default NULL,
  `poll_rows` int(11) default NULL,
  `poll_usr_id` int(11) default NULL,
  `poll_own_id` int(11) default NULL,
  `poll_create` datetime default NULL,
  `poll_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`poll_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `poll_tree_ref` (
  `poll_tree_id` int(11) NOT NULL,
  `poll_tag` varchar(25) NOT NULL,
  `poll_ref_tree_id` int(11) NOT NULL,
  `poll_usr_id` int(11) default NULL,
  `poll_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`poll_tree_id`,`poll_tag`,`poll_ref_tree_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `poll_overview_settings` (
  `set_tree_id` int(11) NOT NULL,
  `set_tag` varchar(25) NOT NULL,
  `set_display` int(11) default NULL,
  `set_width` int(11) default '200',
  `set_cap_submit` varchar(25) default NULL,
  `set_cap_back` varchar(25) default NULL,
  `set_cap_detail` varchar(25) default NULL,
  `set_usr_id` int(11) default NULL,
  `set_own_id` int(11) default NULL,
  `set_create` datetime default NULL,
  `set_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`set_tree_id`,`set_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `poll_archive` (
  `poll_tree_id` int(11) NOT NULL,
  `poll_tag` varchar(25) NOT NULL,
  `poll_online` datetime default NULL,
  `poll_offline` datetime default NULL,
  `poll_usr_id` int(11) default NULL,
  `poll_own_id` int(11) default NULL,
  `poll_create` datetime default NULL,
  `poll_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`poll_tree_id`,`poll_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `poll` (
  `poll_id` mediumint(9) NOT NULL auto_increment,
  `poll_tree_id` int(11) default NULL,
  `poll_tag` varchar(25) default NULL,
  `poll_active` tinyint(1) default '1',
  `poll_online` datetime default NULL,
  `poll_offline` datetime default NULL,
  `poll_name` varchar(50) default NULL,
  `poll_text` varchar(100) default NULL,
  `poll_usr_id` int(11) default NULL,
  `poll_own_id` int(11) default NULL,
  `poll_create` datetime default NULL,
  `poll_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`poll_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;
