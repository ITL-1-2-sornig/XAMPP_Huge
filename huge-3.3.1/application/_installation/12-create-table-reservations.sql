CREATE TABLE IF NOT EXISTS `huge`.`reservations` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `user_participant` int(11) NOT NULL,
  `event` int(11) NOT NULL,
  `confirmed` tinyint(1) NOT NULL DEFAULT FALSE,
  `code` char(8) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `FK_reservations_users` (`user_participant`),
  KEY `FK_reservations_events` (`event`),
  CONSTRAINT `FK_reservations_events` FOREIGN KEY (`event`) REFERENCES `events` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_reservations_users` FOREIGN KEY (`user_participant`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
