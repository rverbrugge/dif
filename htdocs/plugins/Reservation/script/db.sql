CREATE TABLE IF NOT EXISTS `reservation_overview_settings` (
  `set_tree_id` int(11) NOT NULL,
  `set_tag` varchar(100) NOT NULL,
  `set_max_subscribe` tinyint(4) default NULL,
  `set_slots` tinyint(4) default NULL,
  `set_vip_slots` tinyint(4) default '0',
  `set_schedule` varchar(500) default NULL,
  `set_display` tinyint(4) default NULL,
  `set_cap_subscribe` varchar(50) default NULL,
  `set_cap_unsubscribe` varchar(50) default NULL,
  `set_vip_grp_id` int(11) default '0',
  `set_usr_id` int(11) default NULL,
  `set_own_id` int(11) default NULL,
  `set_create` datetime default NULL,
  `set_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`set_tree_id`,`set_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `reservation_settings` (
  `set_id` mediumint(9) NOT NULL auto_increment,
  `set_display` tinyint(4) default NULL,
  `set_max_subscribe` tinyint(4) default NULL,
  `set_slots` tinyint(4) default NULL,
  `set_schedule` varchar(500) default NULL,
  `set_usr_id` int(11) default NULL,
  `set_own_id` int(11) default NULL,
  `set_create` datetime default NULL,
  `set_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`set_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `reservation_usergroup` (
  `grp_id` mediumint(9) NOT NULL auto_increment,
  `grp_tree_id` int(11) default NULL,
  `grp_tag` text,
  `grp_usr_id` mediumint(9) NOT NULL,
  `grp_create` datetime default NULL,
  `grp_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`grp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `reservation` (
  `res_id` mediumint(9) NOT NULL auto_increment,
  `res_tree_id` int(11) default NULL,
  `res_tag` varchar(100) NOT NULL,
  `res_active` tinyint(1) default '1',
  `res_usr_id` int(11) default NULL,
  `res_date` date default NULL,
  `res_time` tinyint(4) default NULL,
  `res_vip` tinyint(4) default '0',
  `res_own_id` int(11) default NULL,
  `res_create` datetime default NULL,
  `res_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`res_id`),
  KEY `res_tree_id` (`res_tree_id`,`res_tag`,`res_date`)
) ENGINE=MyISAM AUTO_INCREMENT=276 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `reservation_userlink` (
  `lnk_usr_id` mediumint(9) NOT NULL,
  `lnk_grp_id` mediumint(9) NOT NULL,
  `lnk_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`lnk_usr_id`,`lnk_grp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `reservation_blockperiod` (
  `res_id` mediumint(9) NOT NULL auto_increment,
  `res_tree_id` int(11) default NULL,
  `res_tag` varchar(100) NOT NULL,
  `res_start` date default NULL,
  `res_stop` date default NULL,
  `res_create` datetime default NULL,
  `res_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`res_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
