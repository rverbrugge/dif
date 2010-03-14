CREATE TABLE IF NOT EXISTS `text` (
  `text_tree_id` int(11) NOT NULL,
  `text_tag` varchar(25) NOT NULL,
  `text_text` text,
  `text_usr_id` int(11) default NULL,
  `text_create` datetime default NULL,
  `text_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`text_tree_id`,`text_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
