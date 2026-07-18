<?php

function fcm_base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function fcm_service_account_path()
{
    return __DIR__ . '/../service-accounts/firebase-adminsdk.json';
}

function fcm_service_account_exists()
{
    return file_exists(fcm_service_account_path());
}

function fcm_load_service_account()
{
    $path = fcm_service_account_path();
    if (!file_exists($path)) {
        throw new Exception('Firebase service account JSON was not found.');
    }

    $raw = file_get_contents($path);
    $json = json_decode($raw, true);

    if (!is_array($json)) {
        throw new Exception('Firebase service account JSON is invalid.');
    }

    $requiredKeys = array('project_id', 'client_email', 'private_key');
    foreach ($requiredKeys as $key) {
        if (empty($json[$key])) {
            throw new Exception('Firebase service account JSON is missing "' . $key . '".');
        }
    }

    return $json;
}

function fcm_get_access_token()
{
    $serviceAccount = fcm_load_service_account();

    $issuedAt = time();
    $expiresAt = $issuedAt + 3600;

    $header = array(
        'alg' => 'RS256',
        'typ' => 'JWT'
    );

    $claims = array(
        'iss' => $serviceAccount['client_email'],
        'sub' => $serviceAccount['client_email'],
        'aud' => 'https://oauth2.googleapis.com/token',
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'iat' => $issuedAt,
        'exp' => $expiresAt
    );

    $unsignedJwt = fcm_base64url_encode(json_encode($header)) . '.' . fcm_base64url_encode(json_encode($claims));

    $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
    if (!$privateKey) {
        throw new Exception('Could not read Firebase private key.');
    }

    $signature = '';
    $signed = openssl_sign($unsignedJwt, $signature, $privateKey, 'sha256WithRSAEncryption');
    openssl_free_key($privateKey);

    if (!$signed) {
        throw new Exception('Could not sign Firebase JWT.');
    }

    $jwt = $unsignedJwt . '.' . fcm_base64url_encode($signature);

    $postFields = http_build_query(array(
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Could not request Firebase access token: ' . $error);
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);

    if ($statusCode < 200 || $statusCode >= 300 || empty($decoded['access_token'])) {
        $message = isset($decoded['error_description']) ? $decoded['error_description'] : $response;
        throw new Exception('Firebase access token request failed: ' . $message);
    }

    return array(
        'access_token' => $decoded['access_token'],
        'project_id' => $serviceAccount['project_id']
    );
}

function fcm_send_v1_message($deviceToken, $payload, $deliveryPreferences = array())
{
    $auth = fcm_get_access_token();
    $accessToken = $auth['access_token'];
    $projectId = $auth['project_id'];

    $soundEnabled = !isset($deliveryPreferences['sound_enabled'])
        || $deliveryPreferences['sound_enabled'] === true;
    $vibrationEnabled = !isset($deliveryPreferences['vibration_enabled'])
        || $deliveryPreferences['vibration_enabled'] === true;
    if ($soundEnabled && $vibrationEnabled) {
        $androidChannel = 'default_channel';
    } elseif ($soundEnabled) {
        $androidChannel = 'sound_only_channel';
    } elseif ($vibrationEnabled) {
        $androidChannel = 'vibration_only_channel';
    } else {
        $androidChannel = 'silent_channel';
    }

    $message = array(
        'message' => array(
            'token' => $deviceToken,
            'notification' => array(
                'title' => $payload['title'],
                'body' => $payload['body']
            ),
            'data' => array(
                'title' => (string) $payload['title'],
                'body' => (string) $payload['body'],
                'image' => (string) $payload['image'],
                'type' => (string) $payload['type'],
                'type_id' => (string) $payload['type_id'],
                'language_id' => (string) $payload['language_id'],
                'maxlevel' => (string) $payload['maxlevel'],
                'no_of' => (string) $payload['no_of'],
                'category_type' => (string) $payload['category_type'],
                'notification_category' => (string) ($payload['notification_category'] ?? 'general')
            ),
            'android' => array(
                'priority' => 'high',
                'notification' => array(
                    'channel_id' => $androidChannel,
                    'default_vibrate_timings' => $vibrationEnabled
                )
            )
        )
    );

    if ($soundEnabled) {
        $message['message']['android']['notification']['sound'] = 'default';
        $message['message']['apns'] = array(
            'payload' => array(
                'aps' => array('sound' => 'default')
            )
        );
    }

    if (!empty($payload['image']) && $payload['image'] !== 'no_image') {
        $message['message']['android']['notification']['image'] = $payload['image'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Could not send Firebase notification: ' . $error);
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);

    if ($statusCode < 200 || $statusCode >= 300) {
        $messageText = isset($decoded['error']['message']) ? $decoded['error']['message'] : $response;
        throw new Exception('Firebase notification failed: ' . $messageText);
    }

    return $decoded;
}
