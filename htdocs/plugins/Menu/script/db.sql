CREATE TABLE IF NOT EXISTS `menu` (
  `menu_tree_id` mediumint(9) NOT NULL default '0',
  `menu_tag` varchar(100) NOT NULL,
  `menu_type` tinyint(1) default '0',
  `menu_delimiter` varchar(25) default NULL,
  `menu_usr_id` mediumint(9) NOT NULL default '0',
  `menu_own_id` mediumint(9) default NULL,
  `menu_create` datetime default NULL,
  `menu_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`menu_tree_id`,`menu_tag`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
