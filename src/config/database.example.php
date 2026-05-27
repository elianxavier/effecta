<?php
// Exemplo de Configurações de Armazenamento e Conexão MySQL
// Renomeie para "database.php" e mude 'storage_type' para 'mysql' para ativar ou 'json' para modo sem banco.
return [
    'storage_type' => 'json', // Mude para 'mysql' para ativar o banco de dados MySQL
    'host' => 'localhost',
    'dbname' => 'effecta',
    'user' => 'root',
    'password' => 'sua_senha_aqui',
    'charset' => 'utf8mb4'
];
