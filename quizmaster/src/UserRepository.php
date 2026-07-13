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

        $created = $this->db->fetchOne(
            "SELECT * FROM users WHERE id=" . $this->db->lastInsertId() . " LIMIT 1"
        );

        return $this->toSignupPayload($created);
    }

    public function getUserById(int $userId): ?array
    {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id={$userId} LIMIT 1");
        if ($user === null) {
            return null;
        }

        $rankRow = $this->db->fetchOne(
            "SELECT r.score, r.user_rank
            FROM (
                SELECT s.*, @user_rank := @user_rank + 1 AS user_rank
                FROM (
                    SELECT user_id, SUM(score) AS score
                    FROM monthly_leaderboard
                    GROUP BY user_id
                ) s,
                (SELECT @user_rank := 0) init
                ORDER BY score DESC
            ) r
            WHERE r.user_id = {$userId}"
        );

        $user['profile'] = $this->normalizeProfile($user['profile'] ?? '');
        $user['all_time_score'] = $rankRow['score'] ?? '0';
        $user['all_time_rank'] = $rankRow['user_rank'] ?? '0';

        return $user;
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

    public function getCoinScore(int $userId): ?array
    {
        $user = $this->db->fetchOne("SELECT coins FROM users WHERE id={$userId} LIMIT 1");
        if ($user === null) {
            return null;
        }

        $coins = max(0, (int) ($user['coins'] ?? 0));

        $rankRow = $this->db->fetchOne(
            "SELECT r.score, r.user_rank
            FROM (
                SELECT s.*, @user_rank := @user_rank + 1 AS user_rank
                FROM (
                    SELECT user_id, SUM(score) AS score
                    FROM monthly_leaderboard
                    GROUP BY user_id
                ) s,
                (SELECT @user_rank := 0) init
                ORDER BY score DESC
            ) r
            WHERE r.user_id = {$userId}"
        );

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
            return null;
        }

        $stats['profile'] = $this->normalizeProfile($stats['profile'] ?? '');
        $stats['strong_category'] = $stats['strong_category_name'] ?? '0';
        $stats['weak_category'] = $stats['weak_category_name'] ?? '0';
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

        $rankRow = $this->db->fetchOne(
            "SELECT r.user_rank
            FROM (
                SELECT s.*, @user_rank := @user_rank + 1 AS user_rank
                FROM (
                    SELECT user_id, SUM(score) AS score
                    FROM monthly_leaderboard
                    GROUP BY user_id
                ) s,
                (SELECT @user_rank := 0) init
                ORDER BY score DESC
            ) r
            WHERE r.user_id = {$userId}"
        );

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
        return $this->getLeaderboardData(
            table: 'daily_leaderboard',
            whereClause: "WHERE DATE(date_created)=DATE('" . date('Y-m-d') . "')",
            userId: $userId,
            limit: $limit,
        );
    }

    public function getMonthlyLeaderboard(int $userId, int $limit = 50): array
    {
        $today = date('Y-m-d');
        return $this->getLeaderboardData(
            table: 'monthly_leaderboard',
            whereClause: "WHERE MONTH(date_created)=MONTH('{$today}') AND YEAR(date_created)=YEAR('{$today}')",
            userId: $userId,
            limit: $limit,
        );
    }

    public function getGlobalLeaderboard(int $userId, int $limit = 50): array
    {
        return $this->getLeaderboardData(
            table: 'monthly_leaderboard',
            whereClause: '',
            userId: $userId,
            limit: $limit,
        );
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
        $row = $this->db->fetchOne(
            "SELECT message FROM settings WHERE type='system_configurations' LIMIT 1"
        );

        if ($row === null) {
            return 0;
        }

        $config = json_decode((string) ($row['message'] ?? ''), true);
        if (!is_array($config)) {
            return 0;
        }

        return max(0, (int) ($config['welcome_coin'] ?? 0));
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
}
