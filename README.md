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