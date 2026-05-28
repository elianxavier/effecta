<?php
require_once dirname(__DIR__) . '/src/config/env.php';
require_once dirname(__DIR__) . '/src/EffectaORM.php';
require_once dirname(__DIR__) . '/src/helpers/SimpleJWT.php';
require_once dirname(__DIR__) . '/src/helpers/auth.php';
require_once dirname(__DIR__) . '/src/helpers/DataSync.php';

// Controllers
require_once dirname(__DIR__) . '/src/controllers/AuthController.php';
require_once dirname(__DIR__) . '/src/controllers/UserController.php';
require_once dirname(__DIR__) . '/src/controllers/EntityController.php';

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

$input = json_decode(file_get_contents('php://input'), true) ?: [];

// --- ENDPOINTS PÚBLICOS ---
$authController = new AuthController($orm);

switch ($action) {
    case 'login':
        echo json_encode($authController->login($input));
        exit;
    case 'refresh':
        echo json_encode($authController->refresh($input));
        exit;
    case 'logout':
        echo json_encode($authController->logout($input));
        exit;
    case 'google_login':
        echo json_encode($authController->googleLogin($input));
        exit;
}

// --- MIDDLEWARE DE VALIDAÇÃO DE ACCESS TOKEN ---
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

// Controllers Protegidos
$userController = new UserController($orm, $authenticatedUserId, $authenticatedUserRole);
$entityController = new EntityController($orm, $authenticatedUserId);

// --- ENDPOINTS PROTEGIDOS ---

$response = null;

switch ($action) {
    // User Management (Admin or Dev)
    case 'get_users':
    case 'create_user':
    case 'update_user':
    case 'toggle_user_status':
        if (!in_array($authenticatedUserRole, ['admin', 'dev'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado. Apenas administradores podem gerenciar usuarios.']);
            exit;
        }
        if ($action === 'get_users') $response = $userController->getUsers();
        if ($action === 'create_user') $response = $userController->createUser($input);
        if ($action === 'update_user') $response = $userController->updateUser($input);
        if ($action === 'toggle_user_status') $response = $userController->toggleUserStatus($input);
        break;

    // User Profile
    case 'get_my_profile':
        $response = $userController->getMyProfile();
        break;
    case 'update_my_profile':
        $response = $userController->updateMyProfile($input);
        break;
    case 'change_my_password':
        $response = $userController->changeMyPassword($input);
        break;

    // Projects
    case 'get_projects':
        $response = $entityController->getProjects();
        break;
    case 'add_project':
        $response = $entityController->addProject($input);
        break;
    case 'update_project':
        $response = $entityController->updateProject($input);
        break;
    case 'delete_project':
        $response = $entityController->deleteProject($input);
        break;

    // People
    case 'get_people':
        $response = $entityController->getPeople();
        break;
    case 'add_person':
        $response = $entityController->addPerson($input);
        break;
    case 'update_person':
        $response = $entityController->updatePerson($input);
        break;
    case 'delete_person':
        $response = $entityController->deletePerson($input);
        break;

    // Registers
    case 'get_registers':
        $response = $entityController->getRegisters();
        break;
    case 'save_register':
        $response = $entityController->saveRegister($input);
        break;
    case 'update_register':
        $response = $entityController->updateRegister($input);
        break;
    case 'delete_register':
        $response = $entityController->deleteRegister($input);
        break;
    case 'search':
        $response = $entityController->search($_GET['term'] ?? '');
        break;

    // Feedbacks
    case 'get_feedbacks':
        $response = $entityController->getFeedbacks($_GET['tab'] ?? 'active');
        break;
    case 'save_feedback':
        $response = $entityController->saveFeedback($input);
        break;
    case 'archive_feedback':
        $response = $entityController->archiveFeedback($input);
        break;
    case 'like_feedback':
        $response = $entityController->likeFeedback($input);
        break;
    case 'report_feedback':
        $response = $entityController->reportFeedback($input);
        break;
    case 'get_feedback_stats':
        if ($authenticatedUserRole !== 'dev') {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado. Apenas desenvolvedores podem visualizar alertas.']);
            exit;
        }
        $response = $entityController->getFeedbackStats();
        break;
    case 'admin_update_feedback':
        if ($authenticatedUserRole !== 'dev') {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado. Apenas desenvolvedores podem moderar feedbacks.']);
            exit;
        }
        $response = $entityController->adminUpdateFeedback($input);
        break;

    // Dashboard
    case 'get_dashboard_stats':
        $response = $entityController->getDashboardStats();
        break;

    // Import/Export
    case 'get_export_data':
        $response = $entityController->getExportData();
        break;
    case 'import_data':
        $response = $entityController->importData($input);
        break;

    default:
        http_response_code(400);
        $response = ['error' => 'Acao ou metodo de API invalido.'];
        break;
}

echo json_encode($response);
exit;
