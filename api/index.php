<?php
require_once dirname(__DIR__) . '/src/EffectaORM.php';
require_once dirname(__DIR__) . '/src/helpers/SimpleJWT.php';

$storageType = 'json';
$configFile = dirname(__DIR__) . '/src/config/database.php';
if (file_exists($configFile)) {
    $dbConfig = require $configFile;
    $storageType = $dbConfig['storage_type'] ?? 'json';
}

$orm = new EffectaORM($storageType);
$action = $_GET['action'] ?? '';

// API Response Headers
header('Content-Type: application/json; charset=utf-8');

// --- ENDPOINTS PÚBLICOS ---

// Login Tradicional
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email e senha sao obrigatorios.']);
        exit;
    }

    $user = $orm->getBy('users', 'email', $email);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciais invalidas.']);
        exit;
    }

    // Cria Sessão e Tokens
    $sessionData = generateTokensAndSession($orm, $user);
    echo json_encode($sessionData);
    exit;
}

// Refresh Token (Renovação de Sessão)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'refresh') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $refreshToken = $input['refresh_token'] ?? '';

    if (empty($refreshToken)) {
        http_response_code(400);
        echo json_encode(['error' => 'Refresh token e obrigatorio.']);
        exit;
    }

    // Valida Token
    $payload = SimpleJWT::decode($refreshToken);
    if (!$payload || !isset($payload['jti']) || !isset($payload['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Refresh token invalido ou expirado.']);
        exit;
    }

    // Busca Sessão no Banco
    $session = $orm->getBy('user_sessions', 'id', $payload['jti']);
    if (!$session || $session['refresh_token'] !== $refreshToken || strtotime($session['expires_at']) < time()) {
        if ($session) {
            $orm->delete('user_sessions', 'id', $payload['jti']);
        }
        http_response_code(401);
        echo json_encode(['error' => 'Sessao expirada ou revogada.']);
        exit;
    }

    // Busca Usuário
    $user = $orm->getBy('users', 'id', $payload['user_id']);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario nao encontrado.']);
        exit;
    }

    // Rotaciona o Token: deleta sessão antiga, cria nova sessão
    $orm->delete('user_sessions', 'id', $payload['jti']);
    $sessionData = generateTokensAndSession($orm, $user);
    echo json_encode($sessionData);
    exit;
}

// Logout (Revogação de Sessão específica)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'logout') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $refreshToken = $input['refresh_token'] ?? '';

    if (!empty($refreshToken)) {
        $payload = SimpleJWT::decode($refreshToken);
        if ($payload && isset($payload['jti'])) {
            $orm->delete('user_sessions', 'id', $payload['jti']);
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

// Login com Google
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'google_login') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $idToken = $input['id_token'] ?? '';

    if (empty($idToken)) {
        http_response_code(400);
        echo json_encode(['error' => 'Google ID Token e obrigatorio.']);
        exit;
    }

    // Valida Token com API do Google
    $verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idToken);
    
    // Configura tempo limite no request HTTP
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => 'Accept: application/json',
            'timeout' => 5
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($verifyUrl, false, $context);
    
    if (!$response) {
        http_response_code(401);
        echo json_encode(['error' => 'Falha ao autenticar token junto ao Google.']);
        exit;
    }

    $googleUser = json_decode($response, true);
    $email = $googleUser['email'] ?? '';
    
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID token do Google nao possui e-mail.']);
        exit;
    }

    // Busca ou Cria usuário
    $user = $orm->getBy('users', 'email', $email);
    if (!$user) {
        $now = date('Y-m-d H:i:s');
        $user = $orm->insert('users', [
            'name' => $googleUser['name'] ?? explode('@', $email)[0],
            'email' => $email,
            'password_hash' => password_hash(uniqid(), PASSWORD_BCRYPT), // senha aleatória inacessível
            'role' => 'common'
        ]);
    }

    // Cria Sessão e Tokens
    $sessionData = generateTokensAndSession($orm, $user);
    echo json_encode($sessionData);
    exit;
}


// --- MIDDLEWARE DE VALIDAÇÃO DE ACCESS TOKEN ---
// Valida que o usuário possui autorização Bearer para endpoints protegidos
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = '';

if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
    $token = $matches[1];
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Access token nao fornecido.']);
    exit;
}

$userPayload = SimpleJWT::decode($token);
if (!$userPayload) {
    http_response_code(401);
    echo json_encode(['error' => 'Access token invalido ou expirado.']);
    exit;
}


// --- ENDPOINTS PROTEGIDOS ---

// API Routes (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    if ($action === 'add_person') {
        echo json_encode($orm->insert('people', ['name' => $input['name'] ?? '']));
        exit;
    }

    if ($action === 'add_project') {
        echo json_encode($orm->insert('projects', ['name' => $input['name'] ?? '']));
        exit;
    }

    if ($action === 'save_register') {
        echo json_encode($orm->insert('registers', $input));
        exit;
    }
}

// API Routes (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_people') {
        echo json_encode($orm->getAll('people'));
        exit;
    }
    if ($action === 'get_projects') {
        echo json_encode($orm->getAll('projects'));
        exit;
    }
    if ($action === 'search') {
        echo json_encode(array_values($orm->search('registers', $_GET['term'] ?? '')));
        exit;
    }
    if ($action === 'get_registers') {
        echo json_encode($orm->getAll('registers'));
        exit;
    }
}

// Rota inválida ou indefinida
http_response_code(400);
echo json_encode(['error' => 'Acao ou metodo de API invalido.']);
exit;


// --- FUNÇÃO AUXILIAR DE TOKENS ---
function generateTokensAndSession($orm, $user) {
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
        'id' => $jti,
        'user_id' => $user['id'],
        'refresh_token' => $refreshToken,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'expires_at' => date('Y-m-d H:i:s', $now + (7 * 24 * 60 * 60))
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
