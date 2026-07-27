-- Add account roles and enforce unique usernames.
ALTER TABLE `users`
  ADD COLUMN `Role` varchar(20) NOT NULL DEFAULT 'user' AFTER `Password`;

UPDATE `users`
SET `Role` = 'admin'
WHERE `UserID` = 1;

ALTER TABLE `users`
  ADD UNIQUE KEY `uq_users_username` (`Username`);
