CREATE TABLE IF NOT EXISTS `huge`.`events` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `participant_limit` smallint(5) unsigned DEFAULT NULL,
  `user_creator` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `FK_event_users` (`user_creator`),
  CONSTRAINT `FK_event_users` FOREIGN KEY (`user_creator`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
