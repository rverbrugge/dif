CREATE TABLE IF NOT EXISTS `poll_archive` (
  `poll_tree_id` integer NOT NULL,
  `poll_tag` varchar(25) NOT NULL,
  `poll_online` datetime default NULL,
  `poll_offline` datetime default NULL,
  `poll_usr_id` integer default NULL,
  `poll_own_id` integer default NULL,
  `poll_create` datetime default NULL,
  `poll_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
PRIMARY KEY  (`poll_tree_id`,`poll_tag`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `poll` (
  `poll_id` mediumint NOT NULL auto_increment,
  `poll_tree_id` integer default NULL,
  `poll_tag` varchar(25) default NULL,
  `poll_active` tinyint(1) default '1',
  `poll_online` datetime default NULL,
  `poll_offline` datetime default NULL,
  `poll_name` varchar(50) default NULL,
  `poll_text` varchar(100) default NULL,
  `poll_usr_id` integer default NULL,
  `poll_own_id` integer default NULL,
  `poll_create` datetime default NULL,
  `poll_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
PRIMARY KEY  (`poll_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `poll_item` (
  `item_id` mediumint NOT NULL auto_increment,
  `item_poll_id` integer default NULL,
  `item_active` tinyint(1) default '1',
  `item_name` varchar(50) default NULL,
  `item_votes` integer default NULL,
  `item_create` datetime default NULL,
  `item_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
PRIMARY KEY  (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `poll_tree_ref` (
  `poll_tree_id` integer NOT NULL,
  `poll_tag` varchar(25) NOT NULL,
  `poll_ref_tree_id` integer NOT NULL,
  `poll_usr_id` integer default NULL,
  `poll_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
PRIMARY KEY  (`poll_tree_id`,`poll_tag`,`poll_ref_tree_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `poll_settings` (
  `poll_id` mediumint NOT NULL auto_increment,
  `poll_display` integer default NULL,
  `poll_rows` integer default NULL,
  `poll_usr_id` integer default NULL,
  `poll_own_id` integer default NULL,
  `poll_create` datetime default NULL,
  `poll_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
PRIMARY KEY  (`poll_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `poll_overview_settings` (
  `set_tree_id` integer NOT NULL,
  `set_tag` varchar(25) NOT NULL,
  `set_display` integer default NULL,
  `set_cap_submit` varchar(25) default NULL,
  `set_cap_back` varchar(25) default NULL,
  `set_cap_detail` varchar(25) default NULL,
  `set_usr_id` integer default NULL,
  `set_own_id` integer default NULL,
  `set_create` datetime default NULL,
  `set_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
PRIMARY KEY  (`set_tree_id`,`set_tag`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
