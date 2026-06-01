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
) ENGINE=InnoDB;