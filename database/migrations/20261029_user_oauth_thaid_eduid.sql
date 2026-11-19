-- Extend social OAuth providers: ThaID (DOPA) + Education ID
ALTER TABLE `user_oauth`
  MODIFY COLUMN `provider` ENUM('facebook','google','line','thaid','eduid') NOT NULL;
