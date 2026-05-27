<?php
require_once __DIR__ . '/src/config/env.php';

// Previne cache do navegador para evitar o botão de voltar acessar páginas privadas após logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/src/helpers/SimpleJWT.php';
require_once __DIR__ . '/src/EffectaORM.php';
require_once __DIR__ . '/src/helpers/auth.php';

// Roteamento de Páginas (Front Controller)
$page = $_GET['page'] ?? 'registros';

// Sanitiza o nome do parâmetro de página para evitar Directory Traversal
if (!preg_match('/^[a-zA-Z0-9_]+$/', $page)) {
    $page = 'registros';
}

// Verifica Autenticação
$isLoggedIn = false;
$userRole = 'common';

if (isset($_COOKIE['access_token'])) {
    $payload = SimpleJWT::decode($_COOKIE['access_token']);
    if ($payload) {
        $isLoggedIn = true;
        $userRole = $payload['role'] ?? 'common';
    }
}

// Se não logado via access_token, tenta renovar silenciosamente usando o refresh_token HttpOnly
if (!$isLoggedIn && isset($_COOKIE['refresh_token'])) {
    $refreshToken = $_COOKIE['refresh_token'];
    $refreshPayload = SimpleJWT::decode($refreshToken);
    if ($refreshPayload && isset($refreshPayload['jti']) && isset($refreshPayload['user_id'])) {
        $storageType = 'json';
        $configFile = __DIR__ . '/src/config/database.php';
        if (file_exists($configFile)) {
            $dbConfig = require $configFile;
            $storageType = $dbConfig['storage_type'] ?? 'json';
        }
        $orm = new EffectaORM($storageType);
        
        $session = $orm->getBy('user_sessions', 'id', $refreshPayload['jti']);
        if ($session && $session['refresh_token'] === $refreshToken && strtotime($session['expires_at']) > time()) {
            $user = $orm->getBy('users', 'id', $refreshPayload['user_id']);
            if ($user && $user['active']) {
                // Rotaciona a sessão e gera novos cookies
                $orm->delete('user_sessions', 'id', $refreshPayload['jti']);
                $sessionData = generateTokensAndSession($orm, $user);
                
                $isLoggedIn = true;
                $userRole = $user['role'] ?? 'common';
            }
        }
    }
}

// Páginas Públicas
$publicPages = ['login'];

if (in_array($page, $publicPages)) {
    // Se já estiver logado, não pode ver a tela de login
    if ($isLoggedIn) {
        header('Location: index.php?page=registros');
        exit;
    }
} else {
    // Se for página privada e não estiver logado, redireciona para login
    if (!$isLoggedIn) {
        header('Location: index.php?page=login');
        exit;
    }

    // Role-based Access Control (Exemplo de página de admin)
    if ($page === 'users' && $userRole !== 'admin') {
        http_response_code(403);
        echo "Acesso Negado (403). Apenas administradores possuem acesso a esta pagina.";
        exit;
    }
}

$pageFile = __DIR__ . '/pages/' . $page . '.php';

if (file_exists($pageFile)) {
    include_once $pageFile;
} else {
    // Fallback para a página de registros caso a página não exista
    include_once __DIR__ . '/pages/registros.php';
}