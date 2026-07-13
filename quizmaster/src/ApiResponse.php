<?php

final class ApiResponse
{
    public static function send(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success($data = null, ?string $message = null): void
    {
        $payload = ['error' => 'false'];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        if ($message !== null) {
            $payload['message'] = $message;
        }
        self::send($payload);
    }

    public static function error(string $message, int $statusCode = 400): void
    {
        self::send([
            'error' => 'true',
            'message' => $message,
        ], $statusCode);
    }
}
