<?php

final class UserRepository
{
    public function __construct(
        private Database $db,
        private string $domainUrl,
    ) {}

    public function signInOrCreate(string $firebaseId, string $type, string $name, string $email): array
    {
        $firebaseId = $this->db->escape($firebaseId);
        $type = $this->db->escape($type);
        $name = $this->db->escape($name);
        $email = $this->db->escape($email);

        $existing = $this->db->fetchOne(
            "SELECT * FROM users WHERE firebase_id='{$firebaseId}' LIMIT 1"
        );

        if ($existing !== null) {
            return $this->toSignupPayload($existing);
        }

        $welcomeCoins = $this->getWelcomeCoinAmount();

        $this->db->execute(
            "INSERT INTO users
            (firebase_id, name, email, mobile, type, profile, fcm_id, refer_code, friends_code, coins, ip_address, status)
            VALUES
            ('{$firebaseId}', '{$name}', '{$email}', '', '{$type}', '', '', '', '', '{$welcomeCoins}', '', '1')"
        );

        $createdUserId = $this->db->lastInsertId();
        $this->ensureReferralCode($createdUserId);
        $created = $this->db->fetchOne("SELECT * FROM users WHERE id={$createdUserId} LIMIT 1");

        return $this->toSignupPayload($created);
    }

    public function getUserById(int $userId): ?array
    {
        $this->ensureReferralCode($userId);
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id={$userId} LIMIT 1");
        if ($user === null) {
            return null;
        }

        $rankRow = $this->getLevelCompletionRankRow($userId);

        $user['profile'] = $this->normalizeProfile($user['profile'] ?? '');
        $user['all_time_score'] = $rankRow['score'] ?? '0';
        $user['all_time_rank'] = $rankRow['user_rank'] ?? '0';

        return $user;
    }

    public function getReferralInfo(int $userId): ?array
    {
        $this->ensureReferralCode($userId);
        $user = $this->db->fetchOne(
            "SELECT refer_code, friends_code FROM users WHERE id={$userId} LIMIT 1"
        );
        if ($user === null) {
            return null;
        }

        $config = $this->getSystemConfigurationValues();

        return [
            'refer_code' => (string) ($user['refer_code'] ?? ''),
            'friends_code' => (string) ($user['friends_code'] ?? ''),
            'can_redeem' => empty($user['friends_code']) ? '1' : '0',
            'referrer_reward' => (string) max(0, (int) ($config['refer_coin'] ?? 0)),
            'new_user_reward' => (string) max(0, (int) ($config['welcome_coin'] ?? 0)),
        ];
    }

    public function redeemReferralCode(int $userId, string $referralCode): array
    {
        $normalizedCode = strtoupper(trim($referralCode));
        if ($normalizedCode === '') {
            throw new DomainException('Enter a referral code.');
        }

        $safeCode = $this->db->escape($normalizedCode);
        $this->db->beginTransaction();

        try {
            $newUser = $this->db->fetchOne(
                "SELECT id, refer_code, friends_code, coins
                 FROM users WHERE id={$userId} LIMIT 1 FOR UPDATE"
            );
            if ($newUser === null) {
                throw new DomainException('User not found.');
            }
            if (!empty($newUser['friends_code'])) {
                throw new DomainException('A referral code was already used on this account.');
            }

            $this->ensureReferralCode($userId);
            $ownCode = strtoupper(trim((string) ($newUser['refer_code'] ?? '')));
            if ($ownCode === '') {
                $refreshed = $this->db->fetchOne(
                    "SELECT refer_code FROM users WHERE id={$userId} LIMIT 1"
                );
                $ownCode = strtoupper(trim((string) ($refreshed['refer_code'] ?? '')));
            }
            if ($ownCode === $normalizedCode) {
                throw new DomainException('You cannot use your own referral code.');
            }

            $referrer = $this->db->fetchOne(
                "SELECT id, coins FROM users
                 WHERE UPPER(refer_code)='{$safeCode}' AND status=1
                 LIMIT 1 FOR UPDATE"
            );
            if ($referrer === null) {
                throw new DomainException('Invalid referral code.');
            }
            if ((int) $referrer['id'] === $userId) {
                throw new DomainException('You cannot use your own referral code.');
            }

            $config = $this->getSystemConfigurationValues();
            $referrerReward = max(0, (int) ($config['refer_coin'] ?? 0));
            $newUserReward = max(0, (int) ($config['welcome_coin'] ?? 0));
            $nextReferrerCoins = max(0, (int) ($referrer['coins'] ?? 0)) + $referrerReward;

            $this->db->execute(
                "UPDATE users SET friends_code='{$safeCode}' WHERE id={$userId}"
            );
            $this->db->execute(
                "UPDATE users SET coins={$nextReferrerCoins} WHERE id=" . (int) $referrer['id']
            );
            $this->db->commit();

            return [
                'refer_code' => $normalizedCode,
                'referrer_reward' => (string) $referrerReward,
                'new_user_reward' => (string) $newUserReward,
                'coins' => (string) max(0, (int) ($newUser['coins'] ?? 0)),
            ];
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    public function updateProfile(int $userId, string $name, string $email, string $mobile): void
    {
        $name = $this->db->escape($name);
        $email = $this->db->escape($email);
        $mobile = $this->db->escape($mobile);

        $this->db->execute(
            "UPDATE users
            SET name='{$name}', email='{$email}', mobile='{$mobile}'
            WHERE id={$userId}"
        );
    }

    public function updateFcmId(int $userId, string $fcmId): bool
    {
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE id={$userId} LIMIT 1"
        );
        if ($existingUser === null) {
            return false;
        }

        $fcmId = $this->db->escape($fcmId);
        $this->db->execute(
            "UPDATE users SET fcm_id='{$fcmId}' WHERE id={$userId}"
        );

        return true;
    }

    public function getNotificationPreferences(int $userId): array
    {
        $this->ensureNotificationPreferencesTable();
        $row = $this->db->fetchOne(
            "SELECT notifications_enabled, daily_quiz, new_content, rewards,
                    reminders, events, sound_enabled, vibration_enabled
             FROM user_notification_preferences
             WHERE user_id={$userId}
             LIMIT 1"
        );

        $defaults = [
            'notifications_enabled' => '1',
            'daily_quiz' => '1',
            'new_content' => '1',
            'rewards' => '1',
            'reminders' => '1',
            'events' => '1',
            'sound_enabled' => '1',
            'vibration_enabled' => '1',
        ];

        if ($row === null) {
            return $defaults;
        }

        return array_map(
            static fn($value): string => ((int) $value) === 1 ? '1' : '0',
            array_merge($defaults, $row),
        );
    }

    public function updateNotificationPreferences(int $userId, array $preferences): bool
    {
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE id={$userId} LIMIT 1"
        );
        if ($existingUser === null) {
            return false;
        }

        $this->ensureNotificationPreferencesTable();
        $fields = [
            'notifications_enabled',
            'daily_quiz',
            'new_content',
            'rewards',
            'reminders',
            'events',
            'sound_enabled',
            'vibration_enabled',
        ];
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = ((string) ($preferences[$field] ?? '1')) === '0' ? 0 : 1;
        }

        $this->db->execute(
            "INSERT INTO user_notification_preferences
             (user_id, notifications_enabled, daily_quiz, new_content, rewards,
              reminders, events, sound_enabled, vibration_enabled, updated_at)
             VALUES (
                {$userId}, {$values['notifications_enabled']}, {$values['daily_quiz']},
                {$values['new_content']}, {$values['rewards']}, {$values['reminders']},
                {$values['events']}, {$values['sound_enabled']},
                {$values['vibration_enabled']}, NOW()
             )
             ON DUPLICATE KEY UPDATE
                notifications_enabled=VALUES(notifications_enabled),
                daily_quiz=VALUES(daily_quiz),
                new_content=VALUES(new_content),
                rewards=VALUES(rewards),
                reminders=VALUES(reminders),
                events=VALUES(events),
                sound_enabled=VALUES(sound_enabled),
                vibration_enabled=VALUES(vibration_enabled),
                updated_at=NOW()"
        );

        return true;
    }

    public function getCoinScore(int $userId): ?array
    {
        $user = $this->db->fetchOne("SELECT coins FROM users WHERE id={$userId} LIMIT 1");
        if ($user === null) {
            return null;
        }

        $coins = max(0, (int) ($user['coins'] ?? 0));

        $rankRow = $this->getLevelCompletionRankRow($userId);

        return [
            'coins' => (string) $coins,
            'score' => $rankRow['score'] ?? '0',
            'user_rank' => $rankRow['user_rank'] ?? '0',
        ];
    }

    public function updateCoinScore(int $userId, int $coinsDelta): ?array
    {
        $user = $this->db->fetchOne("SELECT coins FROM users WHERE id={$userId} LIMIT 1");
        if ($user === null) {
            return null;
        }

        $currentCoins = (int) ($user['coins'] ?? 0);
        $nextCoins = max(0, $currentCoins + $coinsDelta);

        $this->db->execute(
            "UPDATE users
            SET coins={$nextCoins}
            WHERE id={$userId}"
        );

        return [
            'coins' => (string) $nextCoins,
        ];
    }

    public function getUserStatistics(int $userId): ?array
    {
        $user = $this->db->fetchOne(
            "SELECT id, name, profile FROM users WHERE id={$userId} LIMIT 1"
        );
        if ($user === null) {
            return null;
        }

        $stats = $this->db->fetchOne(
            "SELECT us.*, u.name, u.profile,
                (SELECT category_name FROM category c WHERE c.id = us.strong_category) AS strong_category_name,
                (SELECT category_name FROM category c WHERE c.id = us.weak_category) AS weak_category_name
            FROM users_statistics us
            LEFT JOIN users u ON u.id = us.user_id
            WHERE us.user_id={$userId}
            LIMIT 1"
        );

        if ($stats === null) {
            $stats = [
                'user_id' => (string) $userId,
                'questions_answered' => '0',
                'correct_answers' => '0',
                'strong_category' => '0',
                'ratio1' => '0',
                'weak_category' => '0',
                'ratio2' => '0',
                'best_position' => '0',
                'date_created' => '',
                'name' => $user['name'] ?? '',
                'profile' => $user['profile'] ?? '',
                'strong_category_name' => null,
                'weak_category_name' => null,
            ];
        }

        $quizProgress = $this->db->fetchOne(
            "SELECT COALESCE(SUM(GREATEST(level - 1, 0)), 0) AS completed
             FROM tbl_level WHERE user_id={$userId}"
        );
        $wordSearchProgress = $this->db->fetchOne(
            "SELECT COUNT(*) AS completed,
                    MIN(NULLIF(best_time_seconds, 0)) AS best_time_seconds
             FROM word_search_user_progress
             WHERE user_id={$userId} AND is_completed=1"
        );
        $challengeProgress = $this->db->fetchOne(
            "SELECT COUNT(*) AS completed,
                    MAX(best_moves_left) AS best_moves_left
             FROM game_2048_challenge_user_progress
             WHERE user_id={$userId} AND is_completed=1"
        );

        $stats['profile'] = $this->normalizeProfile($stats['profile'] ?? '');
        $stats['strong_category'] = $stats['strong_category_name'] ?? '0';
        $stats['weak_category'] = $stats['weak_category_name'] ?? '0';
        $stats['quiz_levels_completed'] = (string) max(
            0,
            (int) ($quizProgress['completed'] ?? 0)
        );
        $stats['word_search_levels_completed'] = (string) max(
            0,
            (int) ($wordSearchProgress['completed'] ?? 0)
        );
        $stats['word_search_best_time_seconds'] = (string) max(
            0,
            (int) ($wordSearchProgress['best_time_seconds'] ?? 0)
        );
        $stats['game_2048_levels_completed'] = (string) max(
            0,
            (int) ($challengeProgress['completed'] ?? 0)
        );
        $stats['game_2048_best_moves_left'] = (string) max(
            0,
            (int) ($challengeProgress['best_moves_left'] ?? 0)
        );
        unset($stats['strong_category_name'], $stats['weak_category_name']);

        return $stats;
    }

    public function setMonthlyLeaderboard(int $userId, int $score): void
    {
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $safeScore = max(0, $score);

        $monthly = $this->db->fetchOne(
            "SELECT id, score FROM monthly_leaderboard
            WHERE user_id={$userId}
            AND MONTH(date_created)=MONTH('{$today}')
            AND YEAR(date_created)=YEAR('{$today}')
            LIMIT 1"
        );

        if ($monthly === null) {
            $this->db->execute(
                "INSERT INTO monthly_leaderboard (user_id, score, last_updated, date_created)
                VALUES ({$userId}, {$safeScore}, '{$now}', '{$now}')"
            );
        } else {
            $nextScore = max(0, ((int) ($monthly['score'] ?? 0)) + $score);
            $this->db->execute(
                "UPDATE monthly_leaderboard
                SET score={$nextScore}, last_updated='{$now}'
                WHERE id=" . (int) $monthly['id']
            );
        }

        $daily = $this->db->fetchOne(
            "SELECT id, score FROM daily_leaderboard
            WHERE user_id={$userId}
            AND DATE(date_created)=DATE('{$today}')
            LIMIT 1"
        );

        if ($daily === null) {
            $this->db->execute(
                "INSERT INTO daily_leaderboard (user_id, score, last_updated, date_created)
                VALUES ({$userId}, {$safeScore}, '{$now}', '{$now}')"
            );
        } else {
            $nextScore = max(0, ((int) ($daily['score'] ?? 0)) + $score);
            $this->db->execute(
                "UPDATE daily_leaderboard
                SET score={$nextScore}, last_updated='{$now}'
                WHERE id=" . (int) $daily['id']
            );
        }
    }

    public function setUserStatistics(
        int $userId,
        int $questionsAnswered,
        int $correctAnswers,
        int $categoryId,
        int $ratio
    ): void {
        $existing = $this->db->fetchOne(
            "SELECT * FROM users_statistics WHERE user_id={$userId} LIMIT 1"
        );

        $rankRow = $this->getLevelCompletionRankRow($userId);

        $bestPosition = (int) ($rankRow['user_rank'] ?? 0);

        if ($existing === null) {
            $strongCategory = $ratio >= 50 ? $categoryId : 0;
            $weakCategory = $ratio < 50 ? $categoryId : 0;
            $ratio1 = $ratio >= 50 ? $ratio : 0;
            $ratio2 = $ratio < 50 ? $ratio : 0;

            $this->db->execute(
                "INSERT INTO users_statistics
                (user_id, questions_answered, correct_answers, strong_category, ratio1, weak_category, ratio2, best_position)
                VALUES
                ({$userId}, {$questionsAnswered}, {$correctAnswers}, {$strongCategory}, {$ratio1}, {$weakCategory}, {$ratio2}, {$bestPosition})"
            );
            return;
        }

        $nextQuestions = ((int) ($existing['questions_answered'] ?? 0)) + $questionsAnswered;
        $nextCorrect = ((int) ($existing['correct_answers'] ?? 0)) + $correctAnswers;
        $currentStrongRatio = (int) ($existing['ratio1'] ?? 0);
        $currentWeakRatio = (int) ($existing['ratio2'] ?? 100);
        $strongCategory = (int) ($existing['strong_category'] ?? 0);
        $weakCategory = (int) ($existing['weak_category'] ?? 0);
        $storedBestPosition = (int) ($existing['best_position'] ?? 0);

        if ($ratio >= 50 && ($strongCategory === 0 || $ratio >= $currentStrongRatio)) {
            $strongCategory = $categoryId;
            $currentStrongRatio = $ratio;
        }

        if ($ratio < 50 && ($weakCategory === 0 || $ratio <= $currentWeakRatio)) {
            $weakCategory = $categoryId;
            $currentWeakRatio = $ratio;
        }

        if ($storedBestPosition > 0 && $bestPosition > 0) {
            $bestPosition = min($storedBestPosition, $bestPosition);
        } elseif ($storedBestPosition > 0) {
            $bestPosition = $storedBestPosition;
        }

        $this->db->execute(
            "UPDATE users_statistics
            SET questions_answered={$nextQuestions},
                correct_answers={$nextCorrect},
                strong_category={$strongCategory},
                ratio1={$currentStrongRatio},
                weak_category={$weakCategory},
                ratio2={$currentWeakRatio},
                best_position={$bestPosition}
            WHERE user_id={$userId}"
        );
    }

    public function getDailyLeaderboard(int $userId, int $limit = 50): array
    {
        return $this->getLevelCompletionLeaderboardData($userId, $limit);
    }

    public function getMonthlyLeaderboard(int $userId, int $limit = 50): array
    {
        return $this->getLevelCompletionLeaderboardData($userId, $limit);
    }

    public function getGlobalLeaderboard(int $userId, int $limit = 50): array
    {
        return $this->getLevelCompletionLeaderboardData($userId, $limit);
    }

    public function deleteAccount(int $userId): void
    {
        $existingUser = $this->db->fetchOne("SELECT id FROM users WHERE id={$userId} LIMIT 1");
        if ($existingUser === null) {
            throw new RuntimeException('User not found.');
        }

        $tablesByUserId = [
            'users_statistics',
            'monthly_leaderboard',
            'daily_leaderboard',
            'tbl_bookmark',
            'user_question_bookmarks',
            'user_notification_preferences',
            'daily_quiz_user',
            'question_reports',
            'contest_leaderboard',
            'word_search_user_progress',
            'user_purchased_category',
            'word_search_user_purchased_category',
        ];

        foreach ($tablesByUserId as $table) {
            if ($this->tableExists($table)) {
                $this->db->execute("DELETE FROM {$table} WHERE user_id={$userId}");
            }
        }

        if ($this->tableExists('battle_statistics')) {
            $this->db->execute(
                "DELETE FROM battle_statistics
                WHERE user_id1={$userId}
                   OR user_id2={$userId}
                   OR winner_id={$userId}"
            );
        }

        $this->db->execute("DELETE FROM users WHERE id={$userId}");
    }

    private function toSignupPayload(array $user): array
    {
        return [
            'user_id' => $user['id'],
            'firebase_id' => $user['firebase_id'] ?? '',
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'mobile' => $user['mobile'] ?? '',
            'profile' => $this->normalizeProfile($user['profile'] ?? ''),
            'type' => $user['type'] ?? 'email',
            'fcm_id' => $user['fcm_id'] ?? '',
            'refer_code' => $user['refer_code'] ?? '',
            'friends_code' => $user['friends_code'] ?? '',
            'coins' => $user['coins'] ?? '0',
            'ip_address' => $user['ip_address'] ?? '',
            'status' => $user['status'] ?? '1',
            'date_registered' => $user['date_registered'] ?? '',
        ];
    }

    private function normalizeProfile(string $profile): string
    {
        if ($profile === '') {
            return '';
        }
        if (filter_var($profile, FILTER_VALIDATE_URL)) {
            return $profile;
        }

        return $this->domainUrl . 'uploads/profile/' . $profile;
    }

    private function getWelcomeCoinAmount(): int
    {
        $config = $this->getSystemConfigurationValues();
        return max(0, (int) ($config['welcome_coin'] ?? 0));
    }

    private function getSystemConfigurationValues(): array
    {
        $row = $this->db->fetchOne(
            "SELECT message FROM settings WHERE type='system_configurations' LIMIT 1"
        );

        if ($row === null) {
            return [];
        }

        $config = json_decode((string) ($row['message'] ?? ''), true);
        if (!is_array($config)) {
            return [];
        }

        return $config;
    }

    private function ensureReferralCode(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }

        $user = $this->db->fetchOne(
            "SELECT refer_code FROM users WHERE id={$userId} LIMIT 1"
        );
        if ($user === null) {
            return '';
        }

        $existingCode = strtoupper(trim((string) ($user['refer_code'] ?? '')));
        if ($existingCode !== '') {
            return $existingCode;
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = 'QM' . strtoupper(base_convert((string) $userId, 10, 36));
            $candidate .= strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $safeCandidate = $this->db->escape($candidate);
            $duplicate = $this->db->fetchOne(
                "SELECT id FROM users WHERE refer_code='{$safeCandidate}' LIMIT 1"
            );
            if ($duplicate !== null) {
                continue;
            }

            $this->db->execute(
                "UPDATE users SET refer_code='{$safeCandidate}'
                 WHERE id={$userId} AND (refer_code IS NULL OR refer_code='')"
            );
            return $candidate;
        }

        throw new RuntimeException('Could not generate referral code.');
    }

    private function getLeaderboardData(
        string $table,
        string $whereClause,
        int $userId,
        int $limit,
    ): array {
        $safeLimit = max(1, min($limit, 100));
        $rankingSql = $this->buildRankingSql($table, $whereClause);

        $rows = $this->db->fetchAll(
            "SELECT ranked.user_id, ranked.score, ranked.user_rank, u.name, u.profile
            FROM ({$rankingSql}) ranked
            INNER JOIN users u ON u.id = ranked.user_id
            ORDER BY ranked.user_rank ASC
            LIMIT {$safeLimit}"
        );

        $currentUser = null;
        if ($userId > 0) {
            $currentUser = $this->db->fetchOne(
                "SELECT ranked.user_id, ranked.score, ranked.user_rank, u.name, u.profile
                FROM ({$rankingSql}) ranked
                INNER JOIN users u ON u.id = ranked.user_id
                WHERE ranked.user_id = {$userId}
                LIMIT 1"
            );
        }

        return [
            'entries' => array_map(fn(array $row) => $this->toLeaderboardPayload($row), $rows),
            'current_user' => $currentUser ? $this->toLeaderboardPayload($currentUser) : null,
        ];
    }

    private function getLevelCompletionLeaderboardData(int $userId, int $limit): array
    {
        $safeLimit = max(1, min($limit, 100));
        $rankingSql = $this->buildLevelCompletionRankingSql();

        $rows = $this->db->fetchAll(
            "SELECT ranked.user_id, ranked.score, ranked.user_rank, u.name, u.profile
            FROM ({$rankingSql}) ranked
            INNER JOIN users u ON u.id = ranked.user_id
            ORDER BY ranked.user_rank ASC
            LIMIT {$safeLimit}"
        );

        $currentUser = null;
        if ($userId > 0) {
            $currentUser = $this->db->fetchOne(
                "SELECT ranked.user_id, ranked.score, ranked.user_rank, u.name, u.profile
                FROM ({$rankingSql}) ranked
                INNER JOIN users u ON u.id = ranked.user_id
                WHERE ranked.user_id={$userId}
                LIMIT 1"
            );
        }

        return [
            'entries' => array_map(fn(array $row) => $this->toLeaderboardPayload($row), $rows),
            'current_user' => $currentUser ? $this->toLeaderboardPayload($currentUser) : null,
        ];
    }

    private function getLevelCompletionRankRow(int $userId): ?array
    {
        $rankingSql = $this->buildLevelCompletionRankingSql();

        return $this->db->fetchOne(
            "SELECT ranked.score, ranked.user_rank
            FROM ({$rankingSql}) ranked
            WHERE ranked.user_id={$userId}
            LIMIT 1"
        );
    }

    private function buildLevelCompletionRankingSql(): string
    {
        return "SELECT totals.user_id, totals.score,
                @level_rank := @level_rank + 1 AS user_rank
            FROM (
                SELECT u.id AS user_id,
                    COALESCE((
                        SELECT SUM(GREATEST(level - 1, 0))
                        FROM tbl_level quiz_progress
                        WHERE quiz_progress.user_id=u.id
                    ), 0)
                    + COALESCE((
                        SELECT COUNT(*)
                        FROM word_search_user_progress word_progress
                        WHERE word_progress.user_id=u.id
                          AND word_progress.is_completed=1
                    ), 0)
                    + COALESCE((
                        SELECT COUNT(*)
                        FROM game_2048_challenge_user_progress challenge_progress
                        WHERE challenge_progress.user_id=u.id
                          AND challenge_progress.is_completed=1
                    ), 0) AS score
                FROM users u
                WHERE u.status=1
                HAVING score > 0
                ORDER BY score DESC, user_id ASC
            ) totals,
            (SELECT @level_rank := 0) rank_init";
    }

    private function buildRankingSql(string $table, string $whereClause): string
    {
        return "SELECT aggregated.user_id, aggregated.score, @user_rank := @user_rank + 1 AS user_rank
            FROM (
                SELECT user_id, SUM(score) AS score
                FROM {$table}
                {$whereClause}
                GROUP BY user_id
                ORDER BY score DESC, user_id ASC
            ) aggregated,
            (SELECT @user_rank := 0) init";
    }

    private function toLeaderboardPayload(array $row): array
    {
        return [
            'user_id' => (string) ($row['user_id'] ?? ''),
            'name' => $row['name'] ?? '',
            'profile' => $this->normalizeProfile($row['profile'] ?? ''),
            'score' => (string) ($row['score'] ?? '0'),
            'user_rank' => (string) ($row['user_rank'] ?? '0'),
        ];
    }

    private function tableExists(string $table): bool
    {
        $safeTable = $this->db->escape($table);
        $row = $this->db->fetchOne("SHOW TABLES LIKE '{$safeTable}'");
        return $row !== null;
    }

    private function ensureNotificationPreferencesTable(): void
    {
        $this->db->execute(
            "CREATE TABLE IF NOT EXISTS user_notification_preferences (
                user_id INT NOT NULL,
                notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
                daily_quiz TINYINT(1) NOT NULL DEFAULT 1,
                new_content TINYINT(1) NOT NULL DEFAULT 1,
                rewards TINYINT(1) NOT NULL DEFAULT 1,
                reminders TINYINT(1) NOT NULL DEFAULT 1,
                events TINYINT(1) NOT NULL DEFAULT 1,
                sound_enabled TINYINT(1) NOT NULL DEFAULT 1,
                vibration_enabled TINYINT(1) NOT NULL DEFAULT 1,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}
