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

    if (isset($_POST['get_categories_by_language'])) {
        $languageId = (int) ($_POST['language_id'] ?? 0);
        if ($languageId <= 0) {
            ApiResponse::error('Please pass all the fields');
        }
        $type = (int) ($_POST['type'] ?? 1);
        $data = $quizRepository->getCategories($type, $languageId);
        if ($data === []) {
            ApiResponse::error('No data found!', 404);
        }
        ApiResponse::success($data);
    }

    if (isset($_POST['get_categories'])) {
        $type = (int) ($_POST['type'] ?? 1);
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $data = $quizRepository->getCategories($type, null);
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
