CREATE TABLE IF NOT EXISTS `siteselect` (
  `site_tree_id` mediumint(9) NOT NULL default '0',
  `site_tag` varchar(100) NOT NULL,
  `site_type` tinyint(1) default '0',
  `site_usr_id` mediumint(9) NOT NULL default '0',
  `site_own_id` mediumint(9) default NULL,
  `site_create` datetime default NULL,
  `site_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`site_tree_id`,`site_tag`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
