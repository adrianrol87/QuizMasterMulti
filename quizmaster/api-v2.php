<?php

declare(strict_types=1);

$config = require __DIR__ . '/config/app.php';

require __DIR__ . '/src/ApiResponse.php';
require __DIR__ . '/src/Database.php';
require __DIR__ . '/src/QuizRepository.php';
require __DIR__ . '/src/UserRepository.php';

try {
    if (($_POST['access_key'] ?? '') !== $config['access_key']) {
        ApiResponse::error('Invalid Access Key', 401);
    }

    $db = new Database($config['db']);
    $quizRepository = new QuizRepository($db, rtrim($config['domain_url'], '/') . '/');
    $userRepository = new UserRepository($db, rtrim($config['domain_url'], '/') . '/');

    if (isset($_POST['get_system_configurations'])) {
        $data = $quizRepository->getSystemConfigurations();
        if ($data === null) {
          ApiResponse::error('No configurations found yet!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_about_us'])) {
        $languageCode = trim((string) ($_POST['language_code'] ?? ''));
        $data = $quizRepository->getLocalizedSettingContent('about_us', $languageCode);
        if ($data === null || trim($data) === '') {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['privacy_policy_settings'])) {
        $languageCode = trim((string) ($_POST['language_code'] ?? ''));
        $data = $quizRepository->getLocalizedSettingContent('privacy_policy', $languageCode);
        if ($data === null || trim($data) === '') {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_terms_conditions_settings'])) {
        $languageCode = trim((string) ($_POST['language_code'] ?? ''));
        $data = $quizRepository->getLocalizedSettingContent('update_terms', $languageCode);
        if ($data === null || trim($data) === '') {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_instructions'])) {
        $languageCode = trim((string) ($_POST['language_code'] ?? ''));
        $data = $quizRepository->getInstructionContent($languageCode);
        if ($data === null || trim($data) === '') {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_delete_account'])) {
        $languageCode = trim((string) ($_POST['language_code'] ?? ''));
        $data = $quizRepository->getLocalizedSettingContent(
            'delete_account',
            $languageCode,
            ['delete_account_en', 'delete_account_es']
        );
        if ($data === null || trim($data) === '') {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['delete_account'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $userRepository->deleteAccount($userId);
        ApiResponse::success('Account deleted successfully.');
    }

    if (isset($_POST['get_categories_by_language'])) {
        $languageId = (int) ($_POST['language_id'] ?? 0);
        if ($languageId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }
        $type = (int) ($_POST['type'] ?? 1);
        $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : null;
        $data = $quizRepository->getCategories($type, $languageId, $userId);
        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_categories'])) {
        $type = (int) ($_POST['type'] ?? 1);
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : null;
        $data = $quizRepository->getCategories($type, null, $userId);
        if ($id !== null) {
            $data = array_values(array_filter($data, fn(array $row): bool => (int) $row['id'] === $id));
            if ($data === []) {
                ApiResponse::error('No data found!', 404);
            }
            ApiResponse::success($data[0]);
        }
        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_subcategory_by_maincategory'])) {
        $mainId = (int) ($_POST['main_id'] ?? 0);
        if ($mainId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }
        $data = $quizRepository->getSubcategories($mainId);
        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_questions_by_category'])) {
        $categoryId = (int) ($_POST['category'] ?? 0);
        if ($categoryId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }
        $data = $quizRepository->getQuestionsByCategory($categoryId);
        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_questions_by_subcategory'])) {
        $subcategoryId = (int) ($_POST['subcategory'] ?? 0);
        if ($subcategoryId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }
        $data = $quizRepository->getQuestionsBySubcategory($subcategoryId);
        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_daily_quiz'])) {
        $languageId = isset($_POST['language_id']) ? (int) $_POST['language_id'] : null;
        $data = $quizRepository->getDailyQuiz($languageId);
        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_word_search_categories'])) {
        $languageId = isset($_POST['language_id']) ? (int) $_POST['language_id'] : null;
        $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : null;
        $data = $quizRepository->getWordSearchCategories($languageId, $userId);
        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_word_search_levels'])) {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $languageId = isset($_POST['language_id']) ? (int) $_POST['language_id'] : null;

        if ($categoryId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $data = $quizRepository->getWordSearchLevels($categoryId, $languageId);
        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_word_search_level'])) {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $levelNumber = (int) ($_POST['level_number'] ?? 0);
        $languageId = isset($_POST['language_id']) ? (int) $_POST['language_id'] : null;

        if ($categoryId <= 0 || $levelNumber <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $data = $quizRepository->getWordSearchLevel($categoryId, $levelNumber, $languageId);
        if ($data === null) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_word_search_progress'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        if ($userId <= 0 || $categoryId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $data = $quizRepository->getWordSearchProgress($userId, $categoryId);
        ApiResponse::success($data);
    }

    if (isset($_POST['set_word_search_progress'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $levelNumber = (int) ($_POST['level_number'] ?? 0);
        $isCompleted = ((string) ($_POST['is_completed'] ?? '0')) === '1';
        $bestTimeSeconds = (int) ($_POST['best_time_seconds'] ?? 0);

        if ($userId <= 0 || $categoryId <= 0 || $levelNumber <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $data = $quizRepository->saveWordSearchProgress(
            $userId,
            $categoryId,
            $levelNumber,
            $isCompleted,
            $bestTimeSeconds,
        );
        ApiResponse::success($data, 'Word search progress saved successfully');
    }

    if (isset($_POST['get_game_2048_challenge_levels'])) {
        $data = $quizRepository->getGame2048ChallengeLevels();
        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_game_2048_challenge_progress'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $data = $quizRepository->getGame2048ChallengeProgress($userId);
        ApiResponse::success($data);
    }

    if (isset($_POST['set_game_2048_challenge_progress'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $levelNumber = (int) ($_POST['level_number'] ?? 0);
        $isCompleted = ((string) ($_POST['is_completed'] ?? '0')) === '1';
        $bestMovesLeft = (int) ($_POST['best_moves_left'] ?? 0);

        if ($userId <= 0 || $levelNumber <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $data = $quizRepository->saveGame2048ChallengeProgress(
            $userId,
            $levelNumber,
            $isCompleted,
            $bestMovesLeft,
        );
        ApiResponse::success($data, '2048 challenge progress saved successfully');
    }

    if (isset($_POST['user_purchased_word_search_category'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $categoryId = (int) ($_POST['cate_id'] ?? 0);

        if ($userId <= 0 || $categoryId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        try {
            $data = $quizRepository->unlockWordSearchCategoryWithCoins($userId, $categoryId);
            ApiResponse::success($data, 'Word search category unlocked successfully');
        } catch (DomainException $exception) {
            ApiResponse::error($exception->getMessage(), 422);
        }
    }

    if (isset($_POST['get_questions_by_level'])) {
        $level = (int) ($_POST['level'] ?? 0);
        $categoryId = isset($_POST['category']) ? (int) $_POST['category'] : null;
        $subcategoryId = isset($_POST['subcategory']) ? (int) $_POST['subcategory'] : null;
        $languageId = isset($_POST['language_id']) ? (int) $_POST['language_id'] : null;

        if ($level <= 0 || (($categoryId ?? 0) <= 0 && ($subcategoryId ?? 0) <= 0)) {
            ApiResponse::error('Please pass all the fields');
        }

        $data = $quizRepository->getQuestionsByLevel(
            $level,
            $categoryId,
            $subcategoryId,
            $languageId,
        );

        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }

        ApiResponse::success($data);
    }

    if (isset($_POST['report_question'])) {
        $questionId = (int) ($_POST['question_id'] ?? 0);
        $userId = (int) ($_POST['user_id'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($questionId <= 0 || $userId <= 0 || $message === '') {
            ApiResponse::error('Please pass all the fields');
        }

        $quizRepository->reportQuestion($questionId, $userId, $message);
        ApiResponse::success(null, 'Question reported successfully.');
    }

    if (isset($_POST['user_signup'])) {
        $firebaseId = trim((string) ($_POST['firebase_id'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? 'email'));
        if ($firebaseId === '') {
            ApiResponse::error('Please pass all the fields');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $data = $userRepository->signInOrCreate($firebaseId, $type, $name, $email);
        ApiResponse::success($data, 'Successfully logged in');
    }

    if (isset($_POST['get_user_by_id'])) {
        $userId = (int) ($_POST['id'] ?? 0);
        if ($userId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }
        $data = $userRepository->getUserById($userId);
        if ($data === null) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['update_fcm_id'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $fcmId = trim((string) ($_POST['fcm_id'] ?? ''));
        if ($userId <= 0 || $fcmId === '') {
            ApiResponse::error('Please pass all the fields');
        }
        if (!$userRepository->updateFcmId($userId, $fcmId)) {
            ApiResponse::error('User not found.', 404);
        }
        ApiResponse::success(null, 'FCM token updated successfully');
    }

    if (isset($_POST['update_profile'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($userId <= 0 || $name === '') {
            ApiResponse::error('Please pass all the fields');
        }
        $userRepository->updateProfile(
            $userId,
            $name,
            trim((string) ($_POST['email'] ?? '')),
            trim((string) ($_POST['mobile'] ?? ''))
        );
        ApiResponse::success(null, 'Profile updated successfully');
    }

    if (isset($_POST['get_user_coin_score'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            ApiResponse::error('Please Pass all the fields!');
        }
        $data = $userRepository->getCoinScore($userId);
        if ($data === null) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['set_user_coin_score'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $coins = (int) ($_POST['coins'] ?? 0);
        if ($userId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $data = $userRepository->updateCoinScore($userId, $coins);
        if ($data === null) {
            ApiResponse::error('No data found!', 404);
        }

        ApiResponse::success($data, 'Coins updated successfully');
    }

    if (isset($_POST['get_users_statistics'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            ApiResponse::error('Please Pass all the fields!');
        }
        $data = $userRepository->getUserStatistics($userId);
        if ($data === null) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['set_monthly_leaderboard'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $score = (int) ($_POST['score'] ?? 0);
        if ($userId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $userRepository->setMonthlyLeaderboard($userId, $score);
        ApiResponse::success(null, 'successfully update score');
    }

    if (isset($_POST['set_users_statistics'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $questionsAnswered = (int) ($_POST['questions_answered'] ?? 0);
        $correctAnswers = (int) ($_POST['correct_answers'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $ratio = (int) ($_POST['ratio'] ?? -1);

        if ($userId <= 0 || $categoryId <= 0 || $questionsAnswered < 0 || $correctAnswers < 0 || $ratio < 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $userRepository->setUserStatistics(
            $userId,
            $questionsAnswered,
            $correctAnswers,
            $categoryId,
            $ratio
        );
        ApiResponse::success(null, 'User statistics updated successfully');
    }

    if (isset($_POST['get_datewise_leaderboard'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $limit = (int) ($_POST['limit'] ?? 50);
        ApiResponse::success($userRepository->getDailyLeaderboard($userId, $limit));
    }

    if (isset($_POST['get_monthly_leaderboard'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $limit = (int) ($_POST['limit'] ?? 50);
        ApiResponse::success($userRepository->getMonthlyLeaderboard($userId, $limit));
    }

    if (isset($_POST['get_global_leaderboard'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $limit = (int) ($_POST['limit'] ?? 50);
        ApiResponse::success($userRepository->getGlobalLeaderboard($userId, $limit));
    }

    if (isset($_POST['user_purchased_category'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $categoryId = (int) ($_POST['cate_id'] ?? 0);

        if ($userId <= 0 || $categoryId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        try {
            $data = $quizRepository->unlockCategoryWithCoins($userId, $categoryId);
            ApiResponse::success($data, 'Category unlocked successfully');
        } catch (DomainException $exception) {
            ApiResponse::error($exception->getMessage(), 422);
        }
    }

    if (isset($_POST['set_level_data'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $categoryId = (int) ($_POST['category'] ?? 0);
        $subcategoryId = (int) ($_POST['subcategory'] ?? 0);
        $level = (int) ($_POST['level'] ?? 0);

        if ($userId <= 0 || $categoryId <= 0 || $level <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $quizRepository->setLevelData(
            $userId,
            $categoryId,
            $level,
            $subcategoryId,
        );

        ApiResponse::success(null, 'Level data updated successfully');
    }

    if (isset($_POST['get_level_data'])) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $categoryId = (int) ($_POST['category'] ?? 0);
        $subcategoryId = (int) ($_POST['subcategory'] ?? 0);

        if ($userId <= 0 || $categoryId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }

        $data = $quizRepository->getLevelData(
            $userId,
            $categoryId,
            $subcategoryId,
        );

        ApiResponse::success($data);
    }

    ApiResponse::error('Unsupported endpoint', 404);
} catch (Throwable $exception) {
    $logLine = sprintf(
        "[%s] %s in %s:%d\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );
    @file_put_contents(__DIR__ . '/debug.log', $logLine, FILE_APPEND);
    ApiResponse::error($exception->getMessage(), 500);
}
