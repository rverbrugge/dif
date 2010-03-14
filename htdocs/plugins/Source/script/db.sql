CREATE TABLE IF NOT EXISTS `source` (
  `src_tree_id` int(11) NOT NULL,
  `src_tag` varchar(25) NOT NULL,
  `src_text` text,
  `src_usr_id` int(11) default NULL,
  `src_create` datetime default NULL,
  `src_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`src_tree_id`,`src_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
