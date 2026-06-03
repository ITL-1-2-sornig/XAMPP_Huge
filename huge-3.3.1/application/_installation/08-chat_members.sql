CREATE TABLE `huge`.`chat_members` (
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
) ENGINE=InnoDB;