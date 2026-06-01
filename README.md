# XAMPP_Huge
Projekt für ITL_1_2


Um die User Tabelle manuel anzupassen
``` sql
ALTER TABLE `users` CHANGE `user_email` `user_email` VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT 'user\'s email';
ALTER TABLE `users` DROP INDEX `user_email`;
```
Um die user_role Tabelle manuel zu erstellen
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
Tabellen für die Messenger-Funktionen

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