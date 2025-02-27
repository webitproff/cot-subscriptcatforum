-- Таблица для подписок пользователей
CREATE TABLE IF NOT EXISTS `cot_subscriptcatforum` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `cat_forum_subs` text NOT NULL,
  `created_at` INT UNSIGNED NOT NULL DEFAULT 0
);


