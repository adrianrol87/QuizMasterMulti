<?php

final class QuizRepository
{
    public function __construct(
        private Database $db,
        private string $domainUrl,
    ) {}

    public function getSystemConfigurations(): ?array
    {
        $row = $this->db->fetchOne(
            "SELECT message FROM settings WHERE type='system_configurations' LIMIT 1"
        );

        if ($row === null) {
            return null;
        }

        return json_decode($row['message'], true);
    }

    public function getSettingContent(string $type): ?string
    {
        $safeType = $this->db->escape($type);
        $row = $this->db->fetchOne(
            "SELECT message FROM settings WHERE type='{$safeType}' LIMIT 1"
        );

        if ($row === null) {
            return null;
        }

        return (string) ($row['message'] ?? '');
    }

    public function getLocalizedSettingContent(
        string $baseType,
        ?string $languageCode = null,
        array $fallbackTypes = [],
    ): ?string {
        $normalizedLanguage = strtolower(trim((string) $languageCode));
        $typesToTry = [];

        if (in_array($normalizedLanguage, ['es', 'en'], true)) {
            $typesToTry[] = "{$baseType}_{$normalizedLanguage}";
        }

        foreach ($fallbackTypes as $fallbackType) {
            $typesToTry[] = $fallbackType;
        }

        $typesToTry[] = $baseType;
        $typesToTry = array_values(array_unique($typesToTry));

        foreach ($typesToTry as $type) {
            $content = $this->getSettingContent($type);
            if ($content !== null && trim($content) !== '') {
                return $content;
            }
        }

        return null;
    }

    public function getInstructionContent(?string $languageCode = null): ?string
    {
        return $this->getLocalizedSettingContent('instructions', $languageCode);
    }

    public function getCategories(int $type = 1, ?int $languageId = null, ?int $userId = null): array
    {
        $conditions = ["c.type={$type}"];
        if ($languageId !== null) {
            $conditions[] = "c.language_id={$languageId}";
        }

        if ($type === 1) {
            $sql = "SELECT *,
                (SELECT COUNT(id) FROM question WHERE question.category = c.id) AS no_of_que,
                (SELECT COUNT(id) FROM subcategory s WHERE s.maincat_id = c.id AND s.status = 1) AS no_of,
                (SELECT language FROM languages l WHERE l.id = c.language_id) AS language,
                IF(
                    (SELECT COUNT(id) FROM subcategory s WHERE s.maincat_id = c.id AND s.status = 1) = 0,
                    (SELECT MAX(level + 0) FROM question q WHERE q.category = c.id),
                    0
                ) AS maxlevel
                FROM category c
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY CAST(c.row_order AS UNSIGNED) ASC";
        } elseif ($type === 2) {
            $sql = "SELECT *,
                (SELECT COUNT(id) FROM tbl_learning WHERE tbl_learning.category = c.id) AS no_of,
                (SELECT language FROM languages l WHERE l.id = c.language_id) AS language
                FROM category c
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY CAST(c.row_order AS UNSIGNED) ASC";
        } else {
            $sql = "SELECT *,
                (SELECT COUNT(id) FROM tbl_maths_question WHERE tbl_maths_question.category = c.id) AS no_of_que,
                (SELECT COUNT(id) FROM subcategory s WHERE s.maincat_id = c.id AND s.status = 1) AS no_of,
                (SELECT language FROM languages l WHERE l.id = c.language_id) AS language,
                0 AS maxlevel
                FROM category c
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY CAST(c.row_order AS UNSIGNED) ASC";
        }

        $rows = $this->db->fetchAll($sql);

        return array_map(function (array $row) use ($userId): array {
            return [
                'id' => $row['id'],
                'language_id' => $row['language_id'] ?? '',
                'category_name' => $row['category_name'] ?? '',
                'type' => $row['type'] ?? '1',
                'image' => $this->normalizeCategoryImage($row['image'] ?? ''),
                'plan' => $row['plan'] ?? 'Free',
                'amount' => $row['amount'] ?? '0',
                'row_order' => $row['row_order'] ?? '0',
                'no_of_que' => $row['no_of_que'] ?? '0',
                'language' => $row['language'] ?? '',
                'maxlevel' => $row['maxlevel'] ?? '0',
                'no_of' => $row['no_of'] ?? '0',
                'IsPurchased' => $this->resolvePurchaseState($row, $userId),
            ];
        }, $rows);
    }

    public function getSubcategories(int $mainCategoryId): array
    {
        $parent = $this->db->fetchOne(
            "SELECT id, type FROM category WHERE id={$mainCategoryId} AND status='1' LIMIT 1"
        );

        if ($parent === null) {
            return [];
        }

        $type = (int) ($parent['type'] ?? 1);
        $extraFields = $type === 3
            ? ", (SELECT COUNT(id) FROM tbl_maths_question WHERE tbl_maths_question.subcategory = subcategory.id) AS no_of, 0 AS maxlevel"
            : ", (SELECT COUNT(id) FROM question WHERE question.subcategory = subcategory.id) AS no_of,
               (SELECT MAX(level + 0) FROM question WHERE question.subcategory = subcategory.id) AS maxlevel";

        $sql = "SELECT *{$extraFields}
            FROM subcategory
            WHERE maincat_id={$mainCategoryId} AND status=1
            ORDER BY CAST(row_order AS UNSIGNED) ASC";

        $rows = $this->db->fetchAll($sql);

        return array_map(function (array $row): array {
            return [
                'id' => $row['id'],
                'maincat_id' => $row['maincat_id'],
                'subcategory_name' => $row['subcategory_name'] ?? '',
                'image' => $this->normalizeSubcategoryImage($row['image'] ?? ''),
                'row_order' => $row['row_order'] ?? '0',
                'no_of' => $row['no_of'] ?? '0',
                'maxlevel' => $row['maxlevel'] ?? '0',
            ];
        }, $rows);
    }

    public function getQuestionsByCategory(int $categoryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT *
             FROM question
             WHERE category={$categoryId}
             ORDER BY id ASC"
        );

        return array_map(fn(array $row): array => $this->mapQuestion($row), $rows);
    }

    public function getQuestionsBySubcategory(int $subcategoryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT *
             FROM question
             WHERE subcategory={$subcategoryId}
             ORDER BY id ASC"
        );

        return array_map(fn(array $row): array => $this->mapQuestion($row), $rows);
    }

    public function getDailyQuiz(?int $languageId = null): array
    {
        $conditions = ["DATE(date_published)=CURDATE()"];
        if ($languageId !== null && $languageId > 0) {
            $conditions[] = "language_id={$languageId}";
        }

        $dailyRow = $this->db->fetchOne(
            "SELECT *
             FROM daily_quiz
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY id DESC
             LIMIT 1"
        );

        if ($dailyRow === null && $languageId !== null && $languageId > 0) {
            $dailyRow = $this->db->fetchOne(
                "SELECT *
                 FROM daily_quiz
                 WHERE DATE(date_published)=CURDATE()
                 ORDER BY id DESC
                 LIMIT 1"
            );
        }

        if ($dailyRow === null) {
            return [];
        }

        $questionIds = array_values(array_unique(array_filter(array_map(
            static fn(string $value): int => (int) trim($value),
            explode(',', (string) ($dailyRow['questions_id'] ?? ''))
        ), static fn(int $value): bool => $value > 0)));

        if ($questionIds === []) {
            return [];
        }

        $idList = implode(',', $questionIds);
        $questionConditions = ["id IN ({$idList})"];
        if ($languageId !== null && $languageId > 0) {
            $questionConditions[] = "language_id={$languageId}";
        }

        $rows = $this->db->fetchAll(
            "SELECT *
             FROM question
             WHERE " . implode(' AND ', $questionConditions) . "
             ORDER BY FIELD(id, {$idList})"
        );

        return array_map(fn(array $row): array => $this->mapQuestion($row), $rows);
    }

    public function getQuestionsByLevel(
        int $level,
        ?int $categoryId = null,
        ?int $subcategoryId = null,
        ?int $languageId = null,
    ): array {
        $conditions = ["level={$level}"];

        if ($subcategoryId !== null && $subcategoryId > 0) {
            $conditions[] = "subcategory={$subcategoryId}";
        } elseif ($categoryId !== null && $categoryId > 0) {
            $conditions[] = "category={$categoryId}";
        } else {
            return [];
        }

        if ($languageId !== null && $languageId > 0) {
            $conditions[] = "language_id={$languageId}";
        }

        $rows = $this->db->fetchAll(
            "SELECT *
             FROM question
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY RAND()"
        );

        return array_map(fn(array $row): array => $this->mapQuestion($row), $rows);
    }

    public function reportQuestion(int $questionId, int $userId, string $message): void
    {
        $safeMessage = $this->db->escape($message);
        $now = date('Y-m-d H:i:s');

        $question = $this->db->fetchOne(
            "SELECT id
             FROM question
             WHERE id={$questionId}
             LIMIT 1"
        );
        if ($question === null) {
            throw new RuntimeException('Question not found.');
        }

        $user = $this->db->fetchOne(
            "SELECT id
             FROM users
             WHERE id={$userId}
             LIMIT 1"
        );
        if ($user === null) {
            throw new RuntimeException('User not found.');
        }

        $this->db->execute(
            "INSERT INTO question_reports (question_id, user_id, message, date)
             VALUES ({$questionId}, {$userId}, '{$safeMessage}', '{$now}')"
        );
    }

    public function getBookmarkedQuestions(int $userId): array
    {
        $this->ensureBookmarksTable();
        $rows = $this->db->fetchAll(
            "SELECT q.*, b.created_at AS bookmarked_at,
                    c.category_name AS category_name
             FROM user_question_bookmarks b
             INNER JOIN question q ON q.id=b.question_id
             LEFT JOIN category c ON c.id=q.category
             WHERE b.user_id={$userId}
             ORDER BY b.created_at DESC, b.id DESC"
        );

        return array_map(function (array $row): array {
            $question = $this->mapQuestion($row);
            $question['category_name'] = $row['category_name'] ?? '';
            $question['bookmarked_at'] = $row['bookmarked_at'] ?? '';
            return $question;
        }, $rows);
    }

    public function getBookmarkedQuestionIds(int $userId): array
    {
        $this->ensureBookmarksTable();
        $rows = $this->db->fetchAll(
            "SELECT question_id FROM user_question_bookmarks
             WHERE user_id={$userId} ORDER BY id DESC"
        );

        return array_map(
            static fn(array $row): string => (string) ($row['question_id'] ?? ''),
            $rows
        );
    }

    public function setQuestionBookmark(int $userId, int $questionId, bool $bookmarked): bool
    {
        $this->ensureBookmarksTable();
        $question = $this->db->fetchOne(
            "SELECT id FROM question WHERE id={$questionId} LIMIT 1"
        );
        if ($question === null) {
            throw new DomainException('Question not found.');
        }

        if (!$bookmarked) {
            $this->db->execute(
                "DELETE FROM user_question_bookmarks
                 WHERE user_id={$userId} AND question_id={$questionId}"
            );
            return false;
        }

        $this->db->execute(
            "INSERT IGNORE INTO user_question_bookmarks
             (user_id, question_id, created_at)
             VALUES ({$userId}, {$questionId}, NOW())"
        );
        return true;
    }

    public function getLevelData(
        int $userId,
        int $categoryId,
        int $subcategoryId = 0,
    ): array {
        $row = $this->db->fetchOne(
            "SELECT level
             FROM tbl_level
             WHERE user_id={$userId} AND category={$categoryId} AND subcategory={$subcategoryId}
             LIMIT 1"
        );

        return [
            'level' => (string) ($row['level'] ?? '1'),
        ];
    }

    public function setLevelData(
        int $userId,
        int $categoryId,
        int $level,
        int $subcategoryId = 0,
    ): void {
        $existing = $this->db->fetchOne(
            "SELECT id, level
             FROM tbl_level
             WHERE user_id={$userId} AND category={$categoryId} AND subcategory={$subcategoryId}
             LIMIT 1"
        );

        if ($existing !== null) {
            $savedLevel = max(1, (int) ($existing['level'] ?? 1));
            $nextLevel = max($savedLevel, $level);
            $this->db->execute(
                "UPDATE tbl_level
                 SET level={$nextLevel}
                 WHERE user_id={$userId} AND category={$categoryId} AND subcategory={$subcategoryId}"
            );
            return;
        }

        $this->db->execute(
            "INSERT INTO tbl_level (user_id, category, subcategory, level)
             VALUES ({$userId}, {$categoryId}, {$subcategoryId}, {$level})"
        );
    }

    public function unlockCategoryWithCoins(int $userId, int $categoryId): array
    {
        $category = $this->db->fetchOne(
            "SELECT id, plan, amount
             FROM category
             WHERE id={$categoryId} AND status=1
             LIMIT 1"
        );

        if ($category === null) {
            throw new DomainException('Category not found.');
        }

        $plan = strtolower(trim((string) ($category['plan'] ?? 'free')));
        if ($plan !== 'paid') {
            $this->markCategoryAsPurchased($userId, $categoryId);
            return [
                'category_id' => (string) $categoryId,
                'coins' => $this->getUserCoins($userId),
                'is_purchased' => 'true',
            ];
        }

        if ($this->isCategoryPurchased($userId, $categoryId)) {
            return [
                'category_id' => (string) $categoryId,
                'coins' => $this->getUserCoins($userId),
                'is_purchased' => 'true',
            ];
        }

        $cost = max(0, (int) ($category['amount'] ?? 0));
        $coins = $this->getUserCoins($userId);

        if ($coins < $cost) {
            throw new DomainException('Not enough coins.');
        }

        $connection = $this->db->connection();
        $connection->begin_transaction();

        try {
            $nextCoins = $coins - $cost;

            $this->db->execute(
                "UPDATE users
                 SET coins={$nextCoins}
                 WHERE id={$userId}"
            );

            $this->markCategoryAsPurchased($userId, $categoryId);

            $connection->commit();

            return [
                'category_id' => (string) $categoryId,
                'coins' => (string) $nextCoins,
                'is_purchased' => 'true',
            ];
        } catch (Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }

    public function getWordSearchCategories(?int $languageId = null, ?int $userId = null): array
    {
        $conditions = ["status=1"];
        if ($languageId !== null && $languageId > 0) {
            $conditions[] = "language_id={$languageId}";
        }

        $rows = $this->db->fetchAll(
            "SELECT c.*,
                (SELECT COUNT(id) FROM word_search_level l WHERE l.category_id = c.id AND l.status = 1) AS total_levels
             FROM word_search_category c
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY CAST(c.row_order AS UNSIGNED) ASC, c.id ASC"
        );

        return array_map(function (array $row): array {
            return [
                'id' => (string) ($row['id'] ?? ''),
                'language_id' => (string) ($row['language_id'] ?? '0'),
                'title' => $row['title'] ?? '',
                'image' => $this->normalizeWordSearchCategoryImage($row['image'] ?? ''),
                'plan' => $row['plan'] ?? 'Free',
                'amount' => $row['amount'] ?? '0',
                'IsPurchased' => $this->resolveWordSearchPurchaseState($row, $userId),
                'row_order' => (string) ($row['row_order'] ?? '0'),
                'total_levels' => (string) ($row['total_levels'] ?? '0'),
            ];
        }, $rows);
    }

    public function unlockWordSearchCategoryWithCoins(int $userId, int $categoryId): array
    {
        $category = $this->db->fetchOne(
            "SELECT id, plan, amount
             FROM word_search_category
             WHERE id={$categoryId} AND status=1
             LIMIT 1"
        );

        if ($category === null) {
            throw new DomainException('Category not found.');
        }

        $plan = strtolower(trim((string) ($category['plan'] ?? 'free')));
        if ($plan !== 'paid') {
            $this->markWordSearchCategoryAsPurchased($userId, $categoryId);
            return [
                'category_id' => (string) $categoryId,
                'coins' => $this->getUserCoins($userId),
                'is_purchased' => 'true',
            ];
        }

        if ($this->isWordSearchCategoryPurchased($userId, $categoryId)) {
            return [
                'category_id' => (string) $categoryId,
                'coins' => $this->getUserCoins($userId),
                'is_purchased' => 'true',
            ];
        }

        $cost = max(0, (int) ($category['amount'] ?? 0));
        $coins = $this->getUserCoins($userId);

        if ($coins < $cost) {
            throw new DomainException('Not enough coins.');
        }

        $connection = $this->db->connection();
        $connection->begin_transaction();

        try {
            $nextCoins = $coins - $cost;
            $this->db->execute("UPDATE users SET coins={$nextCoins} WHERE id={$userId}");
            $this->markWordSearchCategoryAsPurchased($userId, $categoryId);
            $connection->commit();

            return [
                'category_id' => (string) $categoryId,
                'coins' => (string) $nextCoins,
                'is_purchased' => 'true',
            ];
        } catch (Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }

    public function getWordSearchLevels(int $categoryId, ?int $languageId = null): array
    {
        $conditions = ["category_id={$categoryId}", "status=1"];
        if ($languageId !== null && $languageId > 0) {
            $conditions[] = "language_id={$languageId}";
        }

        $rows = $this->db->fetchAll(
            "SELECT *
             FROM word_search_level
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY level_number ASC, id ASC"
        );

        return array_map(fn(array $row): array => $this->mapWordSearchLevel($row), $rows);
    }

    public function getWordSearchLevel(
        int $categoryId,
        int $levelNumber,
        ?int $languageId = null,
    ): ?array {
        $conditions = [
            "category_id={$categoryId}",
            "level_number={$levelNumber}",
            "status=1",
        ];
        if ($languageId !== null && $languageId > 0) {
            $conditions[] = "language_id={$languageId}";
        }

        $row = $this->db->fetchOne(
            "SELECT *
             FROM word_search_level
             WHERE " . implode(' AND ', $conditions) . "
             LIMIT 1"
        );

        if ($row === null && $languageId !== null && $languageId > 0) {
            $row = $this->db->fetchOne(
                "SELECT *
                 FROM word_search_level
                 WHERE category_id={$categoryId} AND level_number={$levelNumber} AND status=1
                 LIMIT 1"
            );
        }

        return $row === null ? null : $this->mapWordSearchLevel($row);
    }

    public function getWordSearchProgress(int $userId, int $categoryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT level_number, is_completed, best_time_seconds
             FROM word_search_user_progress
             WHERE user_id={$userId} AND category_id={$categoryId}
             ORDER BY level_number ASC"
        );

        $completedLevels = [];
        $bestTimes = [];
        $highestCompleted = 0;

        foreach ($rows as $row) {
            $levelNumber = max(1, (int) ($row['level_number'] ?? 0));
            $isCompleted = ((string) ($row['is_completed'] ?? '0')) === '1';
            if ($isCompleted) {
                $completedLevels[] = $levelNumber;
                $highestCompleted = max($highestCompleted, $levelNumber);
            }

            $bestTime = max(0, (int) ($row['best_time_seconds'] ?? 0));
            if ($bestTime > 0) {
                $bestTimes[(string) $levelNumber] = (string) $bestTime;
            }
        }

        return [
            'category_id' => (string) $categoryId,
            'completed_levels' => array_map(static fn(int $level): string => (string) $level, $completedLevels),
            'highest_completed_level' => (string) $highestCompleted,
            'next_unlocked_level' => (string) max(1, $highestCompleted + 1),
            'best_times' => $bestTimes,
        ];
    }

    public function saveWordSearchProgress(
        int $userId,
        int $categoryId,
        int $levelNumber,
        bool $isCompleted,
        int $bestTimeSeconds = 0,
    ): array {
        $existing = $this->db->fetchOne(
            "SELECT id, is_completed, best_time_seconds
             FROM word_search_user_progress
             WHERE user_id={$userId} AND category_id={$categoryId} AND level_number={$levelNumber}
             LIMIT 1"
        );

        $completedValue = $isCompleted ? 1 : 0;
        $timeValue = max(0, $bestTimeSeconds);

        if ($existing !== null) {
            $existingCompleted = ((string) ($existing['is_completed'] ?? '0')) === '1';
            $existingBestTime = max(0, (int) ($existing['best_time_seconds'] ?? 0));

            $completedValue = ($existingCompleted || $isCompleted) ? 1 : 0;

            if ($existingBestTime > 0 && $timeValue > 0) {
                $timeValue = min($existingBestTime, $timeValue);
            } elseif ($existingBestTime > 0) {
                $timeValue = $existingBestTime;
            }

            $this->db->execute(
                "UPDATE word_search_user_progress
                 SET is_completed={$completedValue},
                     best_time_seconds={$timeValue},
                     updated_at=NOW()
                 WHERE id=" . (int) $existing['id']
            );
        } else {
            $this->db->execute(
                "INSERT INTO word_search_user_progress
                 (user_id, category_id, level_number, is_completed, best_time_seconds, updated_at)
                 VALUES ({$userId}, {$categoryId}, {$levelNumber}, {$completedValue}, {$timeValue}, NOW())"
            );
        }

        return $this->getWordSearchProgress($userId, $categoryId);
    }

    public function getGame2048ChallengeLevels(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT *
             FROM game_2048_challenge_level
             WHERE status=1
             ORDER BY level_number ASC, id ASC"
        );

        return array_map(
            fn(array $row): array => $this->mapGame2048ChallengeLevel($row),
            $rows
        );
    }

    public function getGame2048ChallengeProgress(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT level_number, is_completed, best_moves_left
             FROM game_2048_challenge_user_progress
             WHERE user_id={$userId}
             ORDER BY level_number ASC"
        );

        $completedLevels = [];
        $bestMovesLeft = [];
        $highestCompleted = 0;

        foreach ($rows as $row) {
            $levelNumber = max(1, (int) ($row['level_number'] ?? 0));
            $isCompleted = ((string) ($row['is_completed'] ?? '0')) === '1';

            if ($isCompleted) {
                $completedLevels[] = $levelNumber;
                $highestCompleted = max($highestCompleted, $levelNumber);
            }

            $bestMoves = max(0, (int) ($row['best_moves_left'] ?? 0));
            if ($bestMoves > 0) {
                $bestMovesLeft[(string) $levelNumber] = (string) $bestMoves;
            }
        }

        return [
            'completed_levels' => array_map(
                static fn(int $level): string => (string) $level,
                $completedLevels
            ),
            'highest_completed_level' => (string) $highestCompleted,
            'next_unlocked_level' => (string) max(1, $highestCompleted + 1),
            'best_moves_left' => $bestMovesLeft,
        ];
    }

    public function saveGame2048ChallengeProgress(
        int $userId,
        int $levelNumber,
        bool $isCompleted,
        int $bestMovesLeft = 0,
    ): array {
        $existing = $this->db->fetchOne(
            "SELECT id, is_completed, best_moves_left
             FROM game_2048_challenge_user_progress
             WHERE user_id={$userId} AND level_number={$levelNumber}
             LIMIT 1"
        );

        $completedValue = $isCompleted ? 1 : 0;
        $movesValue = max(0, $bestMovesLeft);

        if ($existing !== null) {
            $existingCompleted = ((string) ($existing['is_completed'] ?? '0')) === '1';
            $existingBestMoves = max(0, (int) ($existing['best_moves_left'] ?? 0));

            $completedValue = ($existingCompleted || $isCompleted) ? 1 : 0;
            $movesValue = max($existingBestMoves, $movesValue);

            $this->db->execute(
                "UPDATE game_2048_challenge_user_progress
                 SET is_completed={$completedValue},
                     best_moves_left={$movesValue},
                     updated_at=NOW()
                 WHERE id=" . (int) $existing['id']
            );
        } else {
            $this->db->execute(
                "INSERT INTO game_2048_challenge_user_progress
                 (user_id, level_number, is_completed, best_moves_left, updated_at)
                 VALUES ({$userId}, {$levelNumber}, {$completedValue}, {$movesValue}, NOW())"
            );
        }

        return $this->getGame2048ChallengeProgress($userId);
    }

    private function mapQuestion(array $row): array
    {
        return [
            'id' => $row['id'] ?? '',
            'category' => $row['category'] ?? '0',
            'subcategory' => $row['subcategory'] ?? '0',
            'language_id' => $row['language_id'] ?? '0',
            'image' => $this->normalizeQuestionImage($row['image'] ?? ''),
            'question' => $row['question'] ?? '',
            'question_type' => $row['question_type'] ?? '1',
            'optiona' => $row['optiona'] ?? '',
            'optionb' => $row['optionb'] ?? '',
            'optionc' => $row['optionc'] ?? '',
            'optiond' => $row['optiond'] ?? '',
            'optione' => $row['optione'] ?? '',
            'answer' => $row['answer'] ?? '',
            'level' => $row['level'] ?? '0',
            'note' => $row['note'] ?? '',
        ];
    }

    private function ensureBookmarksTable(): void
    {
        $this->db->execute(
            "CREATE TABLE IF NOT EXISTS user_question_bookmarks (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                question_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_user_question (user_id, question_id),
                KEY idx_bookmark_user (user_id),
                KEY idx_bookmark_question (question_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function normalizeCategoryImage(string $image): string
    {
        if ($image === '') {
            return '';
        }

        return $this->domainUrl . 'images/category/' . $image;
    }

    private function normalizeSubcategoryImage(string $image): string
    {
        if ($image === '') {
            return '';
        }

        return $this->domainUrl . 'images/subcategory/' . $image;
    }

    private function normalizeQuestionImage(string $image): string
    {
        if ($image === '') {
            return '';
        }
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        return $this->domainUrl . 'images/questions/' . $image;
    }

    private function normalizeWordSearchCategoryImage(string $image): string
    {
        if ($image === '') {
            return '';
        }
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        return $this->domainUrl . 'images/category/' . $image;
    }

    private function mapWordSearchLevel(array $row): array
    {
        $words = json_decode((string) ($row['words_json'] ?? '[]'), true);
        if (!is_array($words)) {
            $words = [];
        }

        $normalizedWords = array_values(array_filter(array_map(
            static fn(mixed $word): string => strtoupper(trim((string) $word)),
            $words
        ), static fn(string $word): bool => $word !== ''));

        return [
            'id' => (string) ($row['id'] ?? ''),
            'category_id' => (string) ($row['category_id'] ?? '0'),
            'language_id' => (string) ($row['language_id'] ?? '0'),
            'level_number' => (string) ($row['level_number'] ?? '0'),
            'board_rows' => (string) ($row['board_rows'] ?? '15'),
            'board_cols' => (string) ($row['board_cols'] ?? '15'),
            'time_limit' => (string) ($row['time_limit'] ?? '120'),
            'reward_coins' => (string) ($row['reward_coins'] ?? '0'),
            'words' => $normalizedWords,
        ];
    }

    private function mapGame2048ChallengeLevel(array $row): array
    {
        $goals = [];

        for ($index = 1; $index <= 3; $index++) {
            $tileValue = max(0, (int) ($row["goal_{$index}_value"] ?? 0));
            $targetCount = max(0, (int) ($row["goal_{$index}_count"] ?? 0));

            if ($tileValue > 0 && $targetCount > 0) {
                $goals[] = [
                    'tile_value' => (string) $tileValue,
                    'target_count' => (string) $targetCount,
                ];
            }
        }

        return [
            'id' => (string) ($row['id'] ?? ''),
            'level_number' => (string) ($row['level_number'] ?? '0'),
            'move_limit' => (string) ($row['move_limit'] ?? '0'),
            'time_limit_seconds' => '300',
            'difficulty' => $row['difficulty'] ?? '',
            'note_balance' => $row['note_balance'] ?? '',
            'goals' => $goals,
        ];
    }

    private function resolvePurchaseState(array $row, ?int $userId): string
    {
        $plan = strtolower(trim((string) ($row['plan'] ?? 'free')));
        if ($plan !== 'paid') {
            return 'true';
        }

        if ($userId === null || $userId <= 0) {
            return 'false';
        }

        $purchase = $this->db->fetchOne(
            "SELECT is_Purchased
             FROM user_purchased_category
             WHERE category_id=" . (int) $row['id'] . " AND user_id={$userId}
             LIMIT 1"
        );

        if ($purchase === null) {
            return 'false';
        }

        return ((string) ($purchase['is_Purchased'] ?? '0')) === '1' ? 'true' : 'false';
    }

    private function resolveWordSearchPurchaseState(array $row, ?int $userId): string
    {
        $plan = strtolower(trim((string) ($row['plan'] ?? 'free')));
        if ($plan !== 'paid') {
            return 'true';
        }

        if ($userId === null || $userId <= 0) {
            return 'false';
        }

        $purchase = $this->db->fetchOne(
            "SELECT is_Purchased
             FROM word_search_user_purchased_category
             WHERE category_id=" . (int) $row['id'] . " AND user_id={$userId}
             LIMIT 1"
        );

        if ($purchase === null) {
            return 'false';
        }

        return ((string) ($purchase['is_Purchased'] ?? '0')) === '1' ? 'true' : 'false';
    }

    private function getUserCoins(int $userId): int
    {
        $user = $this->db->fetchOne(
            "SELECT coins
             FROM users
             WHERE id={$userId}
             LIMIT 1"
        );

        if ($user === null) {
            throw new DomainException('User not found.');
        }

        return max(0, (int) ($user['coins'] ?? 0));
    }

    private function isCategoryPurchased(int $userId, int $categoryId): bool
    {
        $purchase = $this->db->fetchOne(
            "SELECT is_Purchased
             FROM user_purchased_category
             WHERE category_id={$categoryId} AND user_id={$userId}
             LIMIT 1"
        );

        if ($purchase === null) {
            return false;
        }

        return ((string) ($purchase['is_Purchased'] ?? '0')) === '1';
    }

    private function markCategoryAsPurchased(int $userId, int $categoryId): void
    {
        if ($this->isCategoryPurchased($userId, $categoryId)) {
            return;
        }

        $existing = $this->db->fetchOne(
            "SELECT id
             FROM user_purchased_category
             WHERE category_id={$categoryId} AND user_id={$userId}
             LIMIT 1"
        );

        if ($existing !== null) {
            $this->db->execute(
                "UPDATE user_purchased_category
                 SET is_Purchased=1
                 WHERE id=" . (int) $existing['id']
            );
            return;
        }

        $this->db->execute(
            "INSERT INTO user_purchased_category (user_id, category_id, is_Purchased)
             VALUES ({$userId}, {$categoryId}, 1)"
        );
    }

    private function isWordSearchCategoryPurchased(int $userId, int $categoryId): bool
    {
        $purchase = $this->db->fetchOne(
            "SELECT is_Purchased
             FROM word_search_user_purchased_category
             WHERE category_id={$categoryId} AND user_id={$userId}
             LIMIT 1"
        );

        if ($purchase === null) {
            return false;
        }

        return ((string) ($purchase['is_Purchased'] ?? '0')) === '1';
    }

    private function markWordSearchCategoryAsPurchased(int $userId, int $categoryId): void
    {
        if ($this->isWordSearchCategoryPurchased($userId, $categoryId)) {
            return;
        }

        $existing = $this->db->fetchOne(
            "SELECT id
             FROM word_search_user_purchased_category
             WHERE category_id={$categoryId} AND user_id={$userId}
             LIMIT 1"
        );

        if ($existing !== null) {
            $this->db->execute(
                "UPDATE word_search_user_purchased_category
                 SET is_Purchased=1
                 WHERE id=" . (int) $existing['id']
            );
            return;
        }

        $this->db->execute(
            "INSERT INTO word_search_user_purchased_category (user_id, category_id, is_Purchased)
             VALUES ({$userId}, {$categoryId}, 1)"
        );
    }
}
