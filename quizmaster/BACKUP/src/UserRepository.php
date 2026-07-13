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

        $this->db->execute(
            "INSERT INTO users
            (firebase_id, name, email, mobile, type, profile, fcm_id, refer_code, friends_code, coins, ip_address, status)
            VALUES
            ('{$firebaseId}', '{$name}', '{$email}', '', '{$type}', '', '', '', '', '0', '', '1')"
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

    public function getCoinScore(int $userId): ?array
    {
        $user = $this->db->fetchOne("SELECT coins FROM users WHERE id={$userId} LIMIT 1");
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

        return [
            'coins' => $user['coins'] ?? '0',
            'score' => $rankRow['score'] ?? '0',
            'user_rank' => $rankRow['user_rank'] ?? '0',
        ];
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
}
