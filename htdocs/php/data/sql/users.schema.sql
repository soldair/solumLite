CREATE TABLE `users` (
`users_id` bigint(20) NOT NULL AUTO_INCREMENT,                                                                                                
`email` varchar(200) NOT NULL,
`password` varchar(43) NOT NULL,
`created` int(11) NOT NULL DEFAULT 0,
`name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`users_id`),
  UNIQUE KEY (`email`),
  KEY (`created`,name)
) ENGINE=InnoDB;