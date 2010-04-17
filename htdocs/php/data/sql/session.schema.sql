CREATE TABLE `session` (
`session_id` bigint(20) NOT NULL AUTO_INCREMENT,                                                                                                
`key` varchar(40) NOT NULL,
`created` int(11) NOT NULL DEFAULT 0,
`last_request` int(11) NOT NULL DEFAULT 0,
`timedout` tinyint(1) NOT NULL DEFAULT 0,
`logged_in` tinyint(1) NOT NULL DEFAULT 0,
`remember` tinyint(1) NOT NULL DEFAULT 0,
`users_id` int(11) DEFAULT 0,
`timezone` int(11) DEFAULT 0,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_key` (`key`),
  KEY (`last_request`,`timedout`)
) ENGINE=InnoDB;