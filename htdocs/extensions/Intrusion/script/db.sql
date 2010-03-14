CREATE TABLE IF NOT EXISTS `intrusion` (
  `intr_ip` varchar(100) NOT NULL default '',
  `intr_permanent` tinyint(1) default '1',
  `intr_count` int(11) default '0',
  `intr_create` datetime default NULL,
  `intr_ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`intr_ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
