<?php
require_once dirname(__DIR__) . '/src/config/env.php';
require_once dirname(__DIR__) . '/src/EffectaORM.php';
require_once dirname(__DIR__) . '/src/helpers/SimpleJWT.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';

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
    $refreshToken = $input['refresh_token'] ?? $_COOKIE['refresh_token'] ?? '';

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
    $refreshToken = $input['refresh_token'] ?? $_COOKIE['refresh_token'] ?? '';

    if (!empty($refreshToken)) {
        $payload = SimpleJWT::decode($refreshToken);
        if ($payload && isset($payload['jti'])) {
            $orm->delete('user_sessions', 'id', $payload['jti']);
        }
    }

    // Limpa cookies
    setcookie('access_token', '', [
        'expires' => 1,
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
    setcookie('refresh_token', '', [
        'expires' => 1,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

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

    // Verifica se o token foi emitido para o client ID correto
    $googleClientId = getenv('GOOGLE_CLIENT_ID');
    if (!$googleClientId || ($googleUser['aud'] ?? '') !== $googleClientId) {
        http_response_code(401);
        echo json_encode(['error' => 'ID Token do Google invalido para este aplicativo.']);
        exit;
    }

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

$authenticatedUserId = $userPayload['user_id'];
$authenticatedUserRole = $userPayload['role'];


// --- ENDPOINTS PROTEGIDOS ---

// API Routes (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    // User Management Endpoints (Admin Only)
    if ($action === 'create_user' || $action === 'update_user' || $action === 'toggle_user_status') {
        if ($authenticatedUserRole !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado. Apenas administradores podem gerenciar usuarios.']);
            exit;
        }
    }

    if ($action === 'create_user') {
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'common';
        $active = $input['active'] ?? true;
        $dateOfBirth = $input['date_of_birth'] ?? null;
        $phoneNumber = $input['phone_number'] ?? null;
        $gender = $input['gender'] ?? null;
        $profilePictureUrl = $input['profile_picture_url'] ?? null;
        $bio = $input['bio'] ?? null;

        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome, e-mail e senha são obrigatórios para criar um usuário.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato de e-mail inválido.']);
            exit;
        }

        if ($orm->getBy('users', 'email', $email)) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'E-mail já cadastrado.']);
            exit;
        }

        $newUser = $orm->insert('users', [
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'active' => $active,
            'date_of_birth' => $dateOfBirth,
            'phone_number' => $phoneNumber,
            'gender' => $gender,
            'profile_picture_url' => $profilePictureUrl,
            'bio' => $bio
        ]);

        echo json_encode(['success' => true, 'user' => $newUser]);
        exit;
    }

    if ($action === 'update_user') {
        $userId = $input['id'] ?? null;
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do usuário é obrigatório.']);
            exit;
        }

        // Filtra apenas o que é permitido atualizar
        $allowedFields = ['name', 'email', 'role', 'date_of_birth', 'phone_number', 'gender', 'profile_picture_url', 'bio'];
        $dataToUpdate = array_intersect_key($input, array_flip($allowedFields));

        // Tratamento especial para o checkbox
        if (isset($input['active'])) {
            $dataToUpdate['active'] = $input['active'] ? 1 : 0;
        }

        // Se senha foi enviada, hasheia e adiciona
        if (!empty($input['password'])) {
            $dataToUpdate['password_hash'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }

        // Validação de e-mail único
        if (isset($dataToUpdate['email'])) {
            $existingUser = $orm->getBy('users', 'email', $dataToUpdate['email']);
            if ($existingUser && (int)$existingUser['id'] !== (int)$userId) {
                http_response_code(409);
                echo json_encode(['error' => 'E-mail já cadastrado para outro usuário.']);
                exit;
            }
        }

        $updatedUser = $orm->update('users', $userId, $dataToUpdate);

        if ($updatedUser) {
            echo json_encode(['success' => true, 'user' => $updatedUser]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar o usuário.']);
        }
        exit;
    }

    if ($action === 'toggle_user_status') {
        $userId = $input['id'] ?? null;
        $activeStatus = $input['active'] ?? null; // Expects a boolean true/false

        if (!$userId || !is_bool($activeStatus)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do usuário e status "active" (booleano) são obrigatórios.']);
            exit;
        }

        $updatedUser = $orm->update('users', $userId, ['active' => $activeStatus]);
        if ($updatedUser) {
            echo json_encode(['success' => true, 'user' => $updatedUser]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao alterar o status do usuário.']);
        }
        exit;
    }

    if ($action === 'add_project') {
        echo json_encode($orm->insert('projects', ['name' => $input['name'] ?? ''], $authenticatedUserId));
        exit;
    }

    if ($action === 'update_project') {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do projeto e obrigatorio.']);
            exit;
        }
        unset($input['id']);
        echo json_encode($orm->update('projects', $id, $input, $authenticatedUserId));
        exit;
    }

    if ($action === 'delete_project') {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do projeto e obrigatorio.']);
            exit;
        }
        echo json_encode(['success' => $orm->delete('projects', 'id', $id, $authenticatedUserId)]);
        exit;
    }

    if ($action === 'add_person') {
        echo json_encode($orm->insert('people', ['name' => $input['name'] ?? ''], $authenticatedUserId));
        exit;
    }

    if ($action === 'update_person') {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID da pessoa e obrigatorio.']);
            exit;
        }
        unset($input['id']);
        echo json_encode($orm->update('people', $id, $input, $authenticatedUserId));
        exit;
    }

    if ($action === 'delete_person') {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID da pessoa e obrigatorio.']);
            exit;
        }
        echo json_encode(['success' => $orm->delete('people', 'id', $id, $authenticatedUserId)]);
        exit;
    }

    if ($action === 'save_register') {
        echo json_encode($orm->insert('registers', $input, $authenticatedUserId));
        exit;
    }

    if ($action === 'update_register') {
        $recordId = $input['id'] ?? null;
        if (!$recordId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do registro e obrigatorio para atualizacao.']);
            exit;
        }
        unset($input['id']); // Remove ID from data to be updated

        $updatedRecord = $orm->update('registers', $recordId, $input, $authenticatedUserId);
        if ($updatedRecord) {
            echo json_encode(['success' => true, 'record' => $updatedRecord]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar o registro.']);
        }
        exit;
    }

    if ($action === 'delete_register') {
        $recordId = $input['id'] ?? null;
        if (!$recordId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID do registro e obrigatorio para exclusao.']);
            exit;
        }

        if ($orm->delete('registers', 'id', $recordId, $authenticatedUserId)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao excluir o registro.']);
        }
        exit;
    }

    // User Profile Management
    if ($action === 'update_my_profile') {
        unset($input['id']); // User cannot change their own ID
        unset($input['email']); // Email should not be changed via this endpoint
        unset($input['role']); // Role cannot be changed by user
        unset($input['active']); // Active status cannot be changed by user

        $updatedUser = $orm->update('users', $authenticatedUserId, $input);
        if ($updatedUser) {
            // Remove sensitive data before sending to frontend
            unset($updatedUser['password_hash']);
            echo json_encode(['success' => true, 'user' => $updatedUser]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao atualizar o perfil.']);
        }
        exit;
    }

    if ($action === 'change_my_password') {
        $oldPassword = $input['old_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            http_response_code(400);
            echo json_encode(['error' => 'Todos os campos de senha são obrigatórios.']);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['error' => 'A nova senha e a confirmação não coincidem.']);
            exit;
        }

        // Fetch current user to verify old password
        $currentUser = $orm->getBy('users', 'id', $authenticatedUserId);
        if (!$currentUser || !password_verify($oldPassword, $currentUser['password_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Senha antiga incorreta.']);
            exit;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updatedUser = $orm->update('users', $authenticatedUserId, ['password_hash' => $hashedPassword]);
        if ($updatedUser) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao alterar a senha.']);
        }
        exit;
    }
}

// API Routes (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // User Management Endpoint (Admin Only)
    if ($action === 'get_users') {
        if ($authenticatedUserRole !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado. Apenas administradores podem visualizar usuarios.']);
            exit;
        }
        // Fetch all users without user_id filter
        $allUsers = $orm->getAll('users');
        // Remove sensitive data before sending to frontend
        $sanitizedUsers = array_map(function ($user) {
            unset($user['password_hash']);
            return $user;
        }, $allUsers);
        echo json_encode($sanitizedUsers);
        exit;
    }

    if ($action === 'get_my_profile') {
        $userProfile = $orm->getBy('users', 'id', $authenticatedUserId);
        if ($userProfile) {
            // Remove sensitive data before sending to frontend
            unset($userProfile['password_hash']);
            echo json_encode(['success' => true, 'user' => $userProfile]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Perfil de usuário não encontrado.']);
        }
        exit;
    }

    if ($action === 'get_people') {
        echo json_encode($orm->getAll('people', $authenticatedUserId));
        exit;
    }
    if ($action === 'get_projects') {
        echo json_encode($orm->getAll('projects', $authenticatedUserId));
        exit;
    }
    if ($action === 'search') {
        echo json_encode(array_values($orm->search('registers', $_GET['term'] ?? '', $authenticatedUserId)));
        exit;
    }
    if ($action === 'get_registers') {
        echo json_encode($orm->getAll('registers', $authenticatedUserId));
        exit;
    }
}

// Rota inválida ou indefinida
http_response_code(400);
echo json_encode(['error' => 'Acao ou metodo de API invalido.']);
exit;


// --- FUNÇÃO AUXILIAR DE TOKENS REMOVIDA (UTILIZA SRC/HELPERS/AUTH.PHP) ---
