<?php
// Script de Migração do Effecta
echo "Iniciando a migracao do banco de dados...\n";

$configFile = __DIR__ . '/src/config/database.php';
if (!file_exists($configFile)) {
    echo "Erro: Arquivo src/config/database.php nao encontrado.\n";
    exit(1);
}

$config = require $configFile;
$storageType = $config['storage_type'] ?? 'json';

if ($storageType === 'mysql') {
    echo "Modo de armazenamento: MySQL\n";
    try {
        // Conexão inicial sem especificar o banco de dados para garantir que ele exista
        $dsnWithoutDb = "mysql:host={$config['host']};charset={$config['charset']}";
        $pdoInit = new PDO($dsnWithoutDb, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Criação do banco de dados se não existir
        echo "Verificando/Criando o banco de dados '{$config['dbname']}'...\n";
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Reconecta especificando o banco de dados
        $dsnWithDb = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsnWithDb, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Tabela: people
        echo "Criando tabela `people`...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `people` (
            `id` VARCHAR(50) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Tabela: projects
        echo "Criando tabela `projects`...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `projects` (
            `id` VARCHAR(50) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Tabela: registers
        echo "Criando tabela `registers`...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `registers` (
            `id` VARCHAR(50) NOT NULL,
            `projeto` VARCHAR(255) NOT NULL,
            `atividade` VARCHAR(255) NOT NULL,
            `tipo_prazo` VARCHAR(20) NOT NULL,
            `horas_trabalhadas` DECIMAL(10,2) DEFAULT NULL,
            `prazo` DATE DEFAULT NULL,
            `data_entrega` DATE DEFAULT NULL,
            `horas_gastas` DECIMAL(10,2) DEFAULT NULL,
            `meta` TEXT DEFAULT NULL,
            `contribuicao` TEXT DEFAULT NULL,
            `impacto` TEXT DEFAULT NULL,
            `treinamentos` TEXT DEFAULT NULL,
            `stakeholders` TEXT DEFAULT NULL,
            `autor_feedback` VARCHAR(255) DEFAULT NULL,
            `feedbacks` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Tabela: users
        echo "Criando tabela `users`...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` VARCHAR(50) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) NOT NULL DEFAULT 'common',
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Tabela: user_sessions
        echo "Criando tabela `user_sessions`...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `user_sessions` (
            `id` VARCHAR(50) NOT NULL,
            `user_id` VARCHAR(50) NOT NULL,
            `refresh_token` VARCHAR(255) NOT NULL,
            `user_agent` TEXT DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `expires_at` DATETIME NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Seeder: admin e common test users
        $stmt = $pdo->query("SELECT COUNT(*) FROM `users`");
        $userCount = $stmt->fetchColumn();

        if ($userCount == 0) {
            echo "Inserindo usuarios padrão (Seeders)...\n";
            $now = date('Y-m-d H:i:s');
            
            // Admin
            $adminId = uniqid();
            $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("INSERT INTO `users` (id, name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$adminId, 'Administrador', 'admin@effecta.com', $adminHash, 'admin', $now]);
            
            // Common User
            $commonId = uniqid();
            $commonHash = password_hash('user123', PASSWORD_BCRYPT);
            $stmtInsert->execute([$commonId, 'Usuario Comun', 'user@effecta.com', $commonHash, 'common', $now]);
            
            echo "Seeders inseridos com sucesso!\n";
        }

        echo "Migracao MySQL concluida com sucesso!\n";

    } catch (PDOException $e) {
        echo "Erro de Conexao/SQL: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "Modo de armazenamento: JSON (Arquivos locais)\n";
    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
        echo "Diretorio /data criado.\n";
    }

    $tables = ['people', 'projects', 'registers', 'users', 'user_sessions'];
    foreach ($tables as $table) {
        $file = $dataDir . '/' . $table . '.json';
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([]));
            echo "Arquivo /data/{$table}.json criado.\n";
        }
    }

    // Seeder JSON
    $usersFile = $dataDir . '/users.json';
    $users = json_decode(file_get_contents($usersFile), true) ?: [];
    if (empty($users)) {
        echo "Inserindo usuarios padrão no JSON (Seeders)...\n";
        $now = date('Y-m-d H:i:s');
        $users[] = [
            'id' => uniqid(),
            'name' => 'Administrador',
            'email' => 'admin@effecta.com',
            'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'created_at' => $now
        ];
        $users[] = [
            'id' => uniqid(),
            'name' => 'Usuario Comun',
            'email' => 'user@effecta.com',
            'password_hash' => password_hash('user123', PASSWORD_BCRYPT),
            'role' => 'common',
            'created_at' => $now
        ];
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Seeders JSON inseridos com sucesso!\n";
    }

    echo "Migracao JSON concluida com sucesso!\n";
}
