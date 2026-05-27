<?php
require_once __DIR__ . '/SimpleJWT.php';

function generateTokensAndSession($orm, $user)
{
    $jti = uniqid();
    $now = time();

    // Access Token (15 minutos)
    $accessToken = SimpleJWT::encode([
        'user_id' => $user['id'],
        'name' => $user['name'],
        'role' => $user['role'],
        'exp' => $now + (15 * 60)
    ]);

    // Refresh Token (7 dias)
    $refreshToken = SimpleJWT::encode([
        'jti' => $jti,
        'user_id' => $user['id'],
        'exp' => $now + (7 * 24 * 60 * 60)
    ]);

    // Grava Sessão no Banco
    $orm->insert('user_sessions', [
        'user_id' => $user['id'],
        'refresh_token' => $refreshToken,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'expires_at' => date('Y-m-d H:i:s', $now + (7 * 24 * 60 * 60))
    ]);

    // Grava Cookies
    setcookie('access_token', $accessToken, [
        'expires' => $now + (15 * 60),
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Lax'
    ]);

    setcookie('refresh_token', $refreshToken, [
        'expires' => $now + (7 * 24 * 60 * 60),
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    return [
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'user' => [
            'name' => $user['name'],
            'role' => $user['role']
        ]
    ];
}
