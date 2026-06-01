CREATE TABLE `huge`.`chat_messages` (
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
) ENGINE=InnoDB;