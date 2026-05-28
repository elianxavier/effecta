<?php

class AuthController {
    private $orm;

    public function __construct($orm) {
        $this->orm = $orm;
    }

    public function login($input) {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            return ['error' => 'Email e senha sao obrigatorios.'];
        }

        $user = $this->orm->getBy('users', 'email', $email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            return ['error' => 'Credenciais invalidas.'];
        }

        return generateTokensAndSession($this->orm, $user);
    }

    public function refresh($input) {
        $refreshToken = $input['refresh_token'] ?? $_COOKIE['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            http_response_code(400);
            return ['error' => 'Refresh token e obrigatorio.'];
        }

        $payload = SimpleJWT::decode($refreshToken);
        if (!$payload || !isset($payload['jti']) || !isset($payload['user_id'])) {
            http_response_code(401);
            return ['error' => 'Refresh token invalido ou expirado.'];
        }

        $session = $this->orm->getBy('user_sessions', 'id', $payload['jti']);
        if (!$session || $session['refresh_token'] !== $refreshToken || strtotime($session['expires_at']) < time()) {
            if ($session) {
                $this->orm->delete('user_sessions', 'id', $payload['jti']);
            }
            http_response_code(401);
            return ['error' => 'Sessao expirada ou revogada.'];
        }

        $user = $this->orm->getBy('users', 'id', $payload['user_id']);
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Usuario nao encontrado.'];
        }

        $this->orm->delete('user_sessions', 'id', $payload['jti']);
        return generateTokensAndSession($this->orm, $user);
    }

    public function logout($input) {
        $refreshToken = $input['refresh_token'] ?? $_COOKIE['refresh_token'] ?? '';

        if (!empty($refreshToken)) {
            $payload = SimpleJWT::decode($refreshToken);
            if ($payload && isset($payload['jti'])) {
                $this->orm->delete('user_sessions', 'id', $payload['jti']);
            }
        }

        setcookie('access_token', '', ['expires' => 1, 'path' => '/', 'httponly' => false, 'samesite' => 'Lax']);
        setcookie('refresh_token', '', ['expires' => 1, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);

        return ['success' => true];
    }

    public function googleLogin($input) {
        $idToken = $input['id_token'] ?? '';
        if (empty($idToken)) {
            http_response_code(400);
            return ['error' => 'Google ID Token e obrigatorio.'];
        }

        $verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idToken);
        $opts = ['http' => ['method' => 'GET', 'header' => 'Accept: application/json', 'timeout' => 5]];
        $context = stream_context_create($opts);
        $response = @file_get_contents($verifyUrl, false, $context);

        if (!$response) {
            http_response_code(401);
            return ['error' => 'Falha ao autenticar token junto ao Google.'];
        }

        $googleUser = json_decode($response, true);
        $email = $googleUser['email'] ?? '';
        $googleClientId = getenv('GOOGLE_CLIENT_ID');

        if (!$googleClientId || ($googleUser['aud'] ?? '') !== $googleClientId) {
            http_response_code(401);
            return ['error' => 'ID Token do Google invalido para este aplicativo.'];
        }

        if (empty($email)) {
            http_response_code(400);
            return ['error' => 'ID token do Google nao possui e-mail.'];
        }

        $user = $this->orm->getBy('users', 'email', $email);
        if (!$user) {
            $user = $this->orm->insert('users', [
                'name' => $googleUser['name'] ?? explode('@', $email)[0],
                'email' => $email,
                'password_hash' => password_hash(uniqid(), PASSWORD_BCRYPT),
                'role' => 'common'
            ]);
        }

        return generateTokensAndSession($this->orm, $user);
    }
}
