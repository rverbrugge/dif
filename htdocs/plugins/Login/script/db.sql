CREATE TABLE IF NOT EXISTS `login` (
  `login_tree_id` mediumint(9) NOT NULL default '0',
  `login_tag` varchar(100) NOT NULL,
  `login_ref_tree_id` mediumint(9) default NULL,
  `login_field_width` int(11) default '30',
  `login_cap_username` varchar(50) default NULL,
  `login_cap_password` varchar(50) default NULL,
  `login_cap_submit` varchar(50) default NULL,
  `login_usr_id` mediumint(9) NOT NULL default '0',
  `login_own_id` mediumint(9) default NULL,
  `login_create` datetime default NULL,
  `login_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`login_tree_id`,`login_tag`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
