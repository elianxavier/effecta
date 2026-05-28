<?php
require_once __DIR__ . '/SimpleJWT.php';

function generateTokensAndSession($orm, $user)
{
    $now = time();
    $expiresAt = date('Y-m-d H:i:s', $now + (7 * 24 * 60 * 60));

    // 1. Grava Sessão inicial no Banco para obter o ID autoincremental
    $session = $orm->insert('user_sessions', [
        'user_id' => $user['id'],
        'refresh_token' => 'pending', // Temporário
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'expires_at' => $expiresAt
    ]);

    $sessionId = $session['id'];

    // 2. Access Token (15 minutos)
    $accessToken = SimpleJWT::encode([
        'user_id' => $user['id'],
        'name' => $user['name'],
        'role' => $user['role'],
        'exp' => $now + (15 * 60)
    ]);

    // 3. Refresh Token (7 dias) usando o ID do banco como JTI
    $refreshToken = SimpleJWT::encode([
        'jti' => $sessionId,
        'user_id' => $user['id'],
        'exp' => $now + (7 * 24 * 60 * 60)
    ]);

    // 4. Atualiza a Sessão com o token real
    $orm->update('user_sessions', $sessionId, ['refresh_token' => $refreshToken]);

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
