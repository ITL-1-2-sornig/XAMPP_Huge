# XAMPP_Huge
## Projekt für ITL_1_2

## Verwendete externe Libraries
- [phpqrcode](https://phpqrcode.sourceforge.net/) ([Lizenz](huge-3.3.1/application/core/phpqrcode/LICENSE))

## Datenbank
Zum erstellen der Datenbank empfiehlt es sich, die SQL-Dateien in [application/_installation](huge-3.3.1/application/_installation) auszuführen. Die Liste an SQL-Befehlen hier dient ausschließlich dazu, wenn bereits eine standardmässige huge-Datenbank aufgesetzt wurde und diese nun auf das Projekt angepasst werden soll.

## User Tabelle manuel anpassen
``` sql
ALTER TABLE `users` CHANGE `user_email` `user_email` VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT 'user\'s email';
ALTER TABLE `users` DROP INDEX `user_email`;
```
## user_role Tabelle manuel erstellen
``` sql
CREATE TABLE `user_roles` (
  `id` tinyint(11) NOT NULL,
  `role_name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`user_account_type`) REFERENCES `user_roles` (`id`);
INSERT INTO `user_roles` (`id`, `role_name`) VALUES
(1, 'Guest'),
(7, 'Administrator');
```
## Tabellen für die Messenger-Funktionen

``` sql
CREATE TABLE `messages` (`message_id` INT NOT NULL AUTO_INCREMENT , `user_sender` INT NOT NULL , `user_reciever` INT DEFAULT NULL, `message_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `message_text` VARCHAR(10000) NOT NULL , `message_seen` BOOLEAN NOT NULL DEFAULT FALSE , PRIMARY KEY (`message_id`)) ENGINE = InnoDB;
ALTER TABLE `messages` ADD FOREIGN KEY (`user_reciever`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `messages` ADD FOREIGN KEY (`user_sender`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `huge`.`chat_threads` (
  `thread_id` INT NOT NULL AUTO_INCREMENT,
  `created_by` INT NOT NULL,
  `thread_name` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`thread_id`),
  KEY `idx_chat_threads_created_by` (`created_by`),
  CONSTRAINT `fk_chat_threads_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
);

CREATE TABLE `chat_messages` (
  `chat_message_id` BIGINT NOT NULL AUTO_INCREMENT,
  `thread_id` INT NOT NULL,
  `sender_user_id` INT NOT NULL,
  `message_text` VARCHAR(10000) NOT NULL,
  `message_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`chat_message_id`),
  KEY `idx_chat_messages_thread_message` (`thread_id`, `chat_message_id`),
  KEY `idx_chat_messages_sender` (`sender_user_id`),
  CONSTRAINT `fk_chat_messages_thread`
    FOREIGN KEY (`thread_id`) REFERENCES `chat_threads`(`thread_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_messages_sender`
    FOREIGN KEY (`sender_user_id`) REFERENCES `users`(`user_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

CREATE TABLE `chat_members` (
  `thread_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `joined_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_read_message_id` BIGINT DEFAULT NULL,
  `last_read_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`thread_id`, `user_id`),
  KEY `idx_chat_members_user` (`user_id`),
  KEY `idx_chat_members_last_read` (`last_read_message_id`),
  CONSTRAINT `fk_chat_members_thread`
    FOREIGN KEY (`thread_id`) REFERENCES `chat_threads`(`thread_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_members_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_members_last_read_message`
    FOREIGN KEY (`last_read_message_id`) REFERENCES `chat_messages`(`chat_message_id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
);
```

## Erstellen der Tabellen für die Event-Verwaltung
```sql
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

```

## Erstellen der Stored Procedures

``` sql
DELIMITER /
CREATE DEFINER=`root`@`localhost` PROCEDURE `addMemberToGroupchat`(
	IN `userID` INT,
	IN `groupID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	INSERT INTO chat_members (thread_id, user_id) VALUES (groupID, userID);
END /

CREATE DEFINER=`root`@`localhost` PROCEDURE `createGroupChat`(
	IN `chatName` VARCHAR(255),
	IN `createdBy` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	INSERT INTO chat_threads (thread_name, created_by) VALUES (chatName, createdBy);
	SELECT LAST_INSERT_ID() AS newID;
END /

CREATE DEFINER=`root`@`localhost` PROCEDURE `getAllGroupChatsForUser`(
	IN `userID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT chat_threads.thread_name, chat_threads.thread_id FROM chat_threads
      JOIN chat_members ON chat_threads.thread_id = chat_members.thread_id
      AND chat_members.user_id = userID;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getallMessages`(
	IN `userID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT user_sender, user_reciever, message_seen
        FROM messages WHERE user_sender = userID OR user_reciever = userID;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getAllMessagesBetweenUsers`(
	IN `user_1_id` INT,
	IN `user_2_id` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT * FROM messages
        WHERE (user_sender = user_1_id AND user_reciever = user_2_id)
        OR (user_sender = user_2_id AND user_reciever = user_1_id)
        ORDER BY message_timestamp;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getAllMessagesInGroupchat`(
	IN `groupID` INT,
	IN `userID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	DECLARE lastSeen INT;
	SELECT last_read_message_id INTO lastSeen FROM chat_members
      WHERE thread_id = groupID AND user_id = userID;
	SELECT u.user_name AS sender_name,
     u.user_id AS sender_id,
     m.chat_message_id AS message_id,
     m.message_timestamp AS message_timestamp,
     m.message_text AS message_text,
     (m.chat_message_id <= lastSeen) AS seen
     FROM chat_messages AS m
     JOIN users AS u
     ON m.sender_user_id = u.user_id
     WHERE m.thread_id = groupID
     ORDER BY message_timestamp;
        
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getAllUserIDs`()
LANGUAGE SQL
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT user_id FROM users;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getChatNameByID`(
	IN `groupID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT thread_name FROM chat_threads WHERE thread_id = groupID;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getMemberInGroup`(
	IN `userID` INT,
	IN `groupID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT * FROM chat_members WHERE thread_id = groupID AND user_id = userID;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `getNumUnreadGroupMessages`(
	IN `userID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	SELECT COUNT(m.chat_message_id) AS unread, t.thread_id AS group_id
	FROM chat_messages AS m
	JOIN chat_threads AS t
	ON t.thread_id = m.thread_id
	JOIN chat_members AS u
	ON u.thread_id = m.thread_id AND m.sender_user_id = u.user_id AND u.user_id <> UserID
	JOIN chat_members AS u2
	ON u2.thread_id = t.thread_id AND u2.user_id = UserID
	WHERE u2.last_read_message_id < m.chat_message_id;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `newMessage`(
	IN `senderID` INT,
	IN `recieverID` INT,
	IN `messageText` VARCHAR(10000)
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	INSERT INTO messages (user_sender, user_reciever, message_text)
      VALUES (senderID, recieverID, messageText);
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `newMessageGroup`(
	IN `senderID` INT,
	IN `groupID` INT,
	IN `messageText` VARCHAR(10000)
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	INSERT INTO chat_messages (thread_id, sender_user_id, message_text)
      VALUES (groupID, senderID, messageText);
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `setStatusSeen`(
	IN `senderID` INT,
	IN `recieverID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	UPDATE messages SET message_seen = '1'
      WHERE user_sender=senderID AND user_reciever=recieverID;
END/

CREATE DEFINER=`root`@`localhost` PROCEDURE `setStatusSeenGroup`(
	IN `userID` INT,
	IN `groupID` INT,
	IN `lastReadMessageID` INT
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN
	UPDATE chat_members SET last_read_message_id = lastReadMessageID
      WHERE thread_id = groupID AND user_id = userID;
END/
DELIMITER ;
```
## Tabelle für Bilder
```sql
CREATE TABLE `huge`.`images` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(255) NOT NULL,
  `hash` varchar(32) DEFAULT NULL,
  `downloads` int(11) NOT NULL DEFAULT 0,
  `shared` tinyint(1) NOT NULL DEFAULT 0,
  `uploader_id` int(11) NOT NULL,
  `size` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`user_id`);
```
