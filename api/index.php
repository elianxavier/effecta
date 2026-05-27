<?php
require_once dirname(__DIR__) . '/src/EffectaORM.php';

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
