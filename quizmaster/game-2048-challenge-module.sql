CREATE TABLE IF NOT EXISTS `game_2048_challenge_level` (
  `id` int NOT NULL AUTO_INCREMENT,
  `level_number` int NOT NULL,
  `move_limit` int NOT NULL,
  `goal_1_value` int NOT NULL DEFAULT 0,
  `goal_1_count` int NOT NULL DEFAULT 0,
  `goal_2_value` int NOT NULL DEFAULT 0,
  `goal_2_count` int NOT NULL DEFAULT 0,
  `goal_3_value` int NOT NULL DEFAULT 0,
  `goal_3_count` int NOT NULL DEFAULT 0,
  `difficulty` varchar(50) NOT NULL DEFAULT '',
  `note_balance` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_game_2048_challenge_level_number` (`level_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `game_2048_challenge_user_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `level_number` int NOT NULL,
  `is_completed` tinyint NOT NULL DEFAULT 0,
  `best_moves_left` int NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_game_2048_challenge_user_progress` (`user_id`,`level_number`),
  KEY `idx_game_2048_challenge_progress_user` (`user_id`),
  KEY `idx_game_2048_challenge_progress_level` (`level_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
