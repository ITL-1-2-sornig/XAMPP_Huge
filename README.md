# XAMPP_Huge
Projekt für ITL_1_2


Um die User Tabelle manuel anzupassen
``` sql
ALTER TABLE `users` CHANGE `user_email` `user_email` VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT 'user\'s email, unique';
```