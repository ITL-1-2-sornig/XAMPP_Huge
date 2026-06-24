
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `user_roles` (
  `id` tinyint(11) NOT NULL,
  `role_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_roles` (`id`, `role_name`) VALUES
(1, 'Guest'),
(7, 'Administrator');

ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`user_account_type`) REFERENCES `user_roles` (`id`);

COMMIT;