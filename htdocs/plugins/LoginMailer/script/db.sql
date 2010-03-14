CREATE TABLE IF NOT EXISTS `login_request` (
  `login_key` varchar(50) NOT NULL,
  `login_usr_id` mediumint(9) NOT NULL default '0',
  `login_ts` datetime default NULL,
  PRIMARY KEY  (`login_key`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `login_mail` (
  `login_tree_id` mediumint(9) NOT NULL default '0',
  `login_tag` varchar(100) NOT NULL,
  `login_ref_tree_id` mediumint(9) default NULL,
  `login_fin_tree_id` int(11) default '0',
  `login_intro` text,
  `login_subject` varchar(100) NOT NULL,
  `login_from` varchar(100) default NULL,
  `login_content` text,
  `login_footer` varchar(255) default NULL,
  `login_cap_submit` varchar(50) default '',
  `login_cap_fin_submit` varchar(50) default '',
  `login_usr_id` mediumint(9) NOT NULL default '0',
  `login_own_id` mediumint(9) default NULL,
  `login_create` datetime default NULL,
  `login_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`login_tree_id`,`login_tag`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
