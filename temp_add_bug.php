<?php
require_once 'src/config/database.php';
$config = require 'src/config/database.php';
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("INSERT INTO feedbacks (user_id, type, subject, message, status, created_at) VALUES (3, 'bug', 'Bug Externo Teste', 'Este e um bug de outro usuario para testar o botao de denuncia.', 'pendente', NOW())");
    echo "Bug inserido com sucesso.\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
