CREATE TABLE IF NOT EXISTS `newsletter_group` (
  `grp_id` mediumint(9) NOT NULL auto_increment,
  `grp_tree_id` int(11) default NULL,
  `grp_tag` text,
  `grp_name` text,
  `grp_count` int(11) default '0',
  `grp_usr_id` int(11) default NULL,
  `grp_own_id` int(11) default NULL,
  `grp_create` datetime default NULL,
  `grp_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`grp_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `newsletter_tree_ref` (
  `nl_tree_id` mediumint(9) NOT NULL default '0',
  `nl_tag` varchar(100) NOT NULL,
  `nl_ref_tree_id` mediumint(9) NOT NULL default '0',
  `nl_usr_id` mediumint(9) NOT NULL default '0',
  `nl_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`nl_tree_id`,`nl_tag`,`nl_ref_tree_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `newsletter_attachment` (
  `att_id` mediumint(9) NOT NULL auto_increment,
  `att_nl_id` int(11) default NULL,
  `att_weight` int(11) default NULL,
  `att_active` tinyint(1) default '1',
  `att_name` text,
  `att_file` text,
  `att_usr_id` int(11) default NULL,
  `att_own_id` int(11) default NULL,
  `att_create` datetime default NULL,
  `att_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`att_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `newsletter_archive` (
  `nl_tree_id` int(11) NOT NULL,
  `nl_tag` varchar(25) NOT NULL,
  `nl_online` datetime default NULL,
  `nl_offline` datetime default NULL,
  `nl_usr_id` int(11) default NULL,
  `nl_own_id` int(11) default NULL,
  `nl_create` datetime default NULL,
  `nl_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`nl_tree_id`,`nl_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `newsletter_plugin` (
  `plug_nl_id` int(11) NOT NULL,
  `plug_tag` varchar(25) NOT NULL,
  `plug_type` int(11) default NULL,
  `plug_text` text,
  `plug_plugin_id` int(11) default NULL,
  `plug_plugin_type` int(11) default NULL,
  `plug_plugin_keys` text,
  `plug_usr_id` int(11) default NULL,
  `plug_own_id` int(11) default NULL,
  `plug_create` datetime default NULL,
  `plug_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`plug_nl_id`,`plug_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `newsletter_settings` (
  `set_id` mediumint(9) NOT NULL auto_increment,
  `set_image_width` int(11) default NULL,
  `set_image_height` int(11) default NULL,
  `set_image_max_width` int(11) default NULL,
  `set_theme_id` int(11) default NULL,
  `set_rows` int(11) default NULL,
  `set_usr_id` int(11) default NULL,
  `set_own_id` int(11) default NULL,
  `set_create` datetime default NULL,
  `set_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`set_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `newsletter_overview_settings` (
  `set_tree_id` int(11) NOT NULL,
  `set_tag` varchar(25) NOT NULL,
  `set_display` int(11) default NULL,
  `set_msg_subject` varchar(100) default NULL,
  `set_msg_from` varchar(50) default NULL,
  `set_msg_action` int(11) default NULL,
  `set_msg_text` text,
  `set_msg_ref_tree_id` int(11) default NULL,
  `set_msg_optin_tree_id` int(11) default NULL,
  `set_del_tree_id` int(11) default NULL,
  `set_cap_gender` varchar(25) default NULL,
  `set_cap_name` varchar(25) default NULL,
  `set_cap_email` varchar(25) default NULL,
  `set_cap_submit` varchar(25) default NULL,
  `set_field_width` int(11) default '35',
  `set_usr_id` int(11) default NULL,
  `set_own_id` int(11) default NULL,
  `set_create` datetime default NULL,
  `set_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`set_tree_id`,`set_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `newsletter_user` (
  `usr_id` mediumint(9) NOT NULL auto_increment,
  `usr_tree_id` int(11) default NULL,
  `usr_tag` text,
  `usr_active` tinyint(1) default '1',
  `usr_gender` int(11) default NULL,
  `usr_name` text,
  `usr_email` text,
  `usr_count` int(11) default '0',
  `usr_bounce` int(11) default NULL,
  `usr_ip` text,
  `usr_host` text,
  `usr_client` text,
  `usr_optin` text,
  `usr_unsubscribe` datetime default NULL,
  `usr_create` datetime default NULL,
  `usr_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`usr_id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `newsletter_usergroup` (
  `usr_id` mediumint(9) NOT NULL default '0',
  `grp_id` mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (`usr_id`,`grp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `newsletter_tag` (
  `tag_nl_id` int(11) NOT NULL,
  `tag_parent_tag` varchar(25) NOT NULL,
  `tag_tags` text,
  `tag_template` text,
  `tag_stylesheet` text,
  `tag_usr_id` int(11) default NULL,
  `tag_own_id` int(11) default NULL,
  `tag_create` datetime default NULL,
  `tag_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`tag_nl_id`,`tag_parent_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `newsletter` (
  `nl_id` mediumint(9) NOT NULL auto_increment,
  `nl_tree_id` int(11) default NULL,
  `nl_tag` text,
  `nl_theme_id` int(11) default NULL,
  `nl_active` tinyint(1) default '1',
  `nl_online` datetime default NULL,
  `nl_offline` datetime default NULL,
  `nl_name` text,
  `nl_intro` text,
  `nl_thumbnail` text,
  `nl_image` text,
  `nl_img_x` int(11) default NULL,
  `nl_img_y` int(11) default NULL,
  `nl_img_width` int(11) default NULL,
  `nl_img_height` int(11) default NULL,
  `nl_count` int(11) default NULL,
  `nl_send_count` int(11) default NULL,
  `nl_send_date` datetime default NULL,
  `nl_usr_id` int(11) default NULL,
  `nl_own_id` int(11) default NULL,
  `nl_create` datetime default NULL,
  `nl_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`nl_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
