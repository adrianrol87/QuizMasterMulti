ALTER TABLE `word_search_category`
    ADD COLUMN `plan` varchar(16) NOT NULL DEFAULT 'Free' AFTER `image`,
    ADD COLUMN `amount` int(11) NOT NULL DEFAULT 0 AFTER `plan`;

CREATE TABLE IF NOT EXISTS `word_search_user_purchased_category` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `category_id` int(11) NOT NULL,
    `is_Purchased` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_word_search_purchase` (`user_id`,`category_id`),
    KEY `idx_word_search_purchase_user` (`user_id`),
    KEY `idx_word_search_purchase_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
