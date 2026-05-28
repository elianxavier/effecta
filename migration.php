<?php
// Script de Migração Incremental do Effecta
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
        $dsnWithoutDb = "mysql:host={$config['host']};charset={$config['charset']}";
        $pdoInit = new PDO($dsnWithoutDb, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        echo "Verificando/Criando o banco de dados '{$config['dbname']}'...\n";
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $dsnWithDb = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsnWithDb, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Helper para adicionar coluna se não existir
        $addColumn = function ($table, $column, $definition) use ($pdo) {
            $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetch();
            if (!$check) {
                echo "Adicionando coluna `$column` na tabela `$table`...\n";
                $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        };

        // --- TABELAS ---

        // Users
        echo "Verificando/Criando tabela 'users'...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` VARCHAR(50) NOT NULL DEFAULT 'common',
            `active` BOOLEAN NOT NULL DEFAULT TRUE,
            `date_of_birth` DATE DEFAULT NULL,
            `phone_number` VARCHAR(20) DEFAULT NULL,
            `gender` VARCHAR(10) DEFAULT NULL,
            `profile_picture_url` TEXT DEFAULT NULL,
            `bio` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // People
        echo "Verificando/Criando tabela 'people'...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `people` (
            `id` INT AUTO_INCREMENT NOT NULL,
            `user_id` INT NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Projects
        echo "Verificando/Criando tabela 'projects'...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `projects` (
            `id` INT AUTO_INCREMENT NOT NULL,
            `user_id` INT NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Registers
        echo "Verificando/Criando tabela 'registers'...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `registers` (
            `id` INT AUTO_INCREMENT NOT NULL,
            `user_id` INT NOT NULL,
            `projeto_id` INT NOT NULL,
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
            `pessoa_feedback_id` INT DEFAULT NULL,
            `feedbacks` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`projeto_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`pessoa_feedback_id`) REFERENCES `people`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // user_sessions
        echo "Verificando/Criando tabela 'user_sessions'...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `user_sessions` (
            `id` INT AUTO_INCREMENT NOT NULL,
            `user_id` INT NOT NULL,
            `refresh_token` VARCHAR(255) NOT NULL,
            `user_agent` TEXT DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `expires_at` DATETIME NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // feedbacks
        echo "Verificando/Criando tabela 'feedbacks'...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `feedbacks` (
            `id` INT AUTO_INCREMENT NOT NULL,
            `user_id` INT NOT NULL,
            `type` VARCHAR(50) NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'pendente',
            `archived` BOOLEAN NOT NULL DEFAULT FALSE,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // feedback_likes
        echo "Verificando/Criando tabela 'feedback_likes'...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback_likes` (
            `id` INT AUTO_INCREMENT NOT NULL,
            `user_id` INT NOT NULL,
            `feedback_id` INT NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_feedback` (`user_id`, `feedback_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`feedback_id`) REFERENCES `feedbacks`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // feedback_reports
        echo "Verificando/Criando tabela 'feedback_reports'...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback_reports` (
            `id` INT AUTO_INCREMENT NOT NULL,
            `user_id` INT NOT NULL,
            `feedback_id` INT NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_report` (`user_id`, `feedback_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`feedback_id`) REFERENCES `feedbacks`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // --- ATUALIZAÇÕES INCREMENTAIS (COLUNAS NOVAS) ---
        echo "Processando atualizações incrementais...\n";
        $addColumn('feedbacks', 'likes', "INT NOT NULL DEFAULT 0 AFTER `archived` ");
        $addColumn('feedbacks', 'reports', "INT NOT NULL DEFAULT 0 AFTER `likes` ");
        $addColumn('feedbacks', 'hidden_by_reports', "BOOLEAN NOT NULL DEFAULT FALSE AFTER `reports` ");
        $addColumn('feedbacks', 'viewed_by_dev', "BOOLEAN NOT NULL DEFAULT FALSE AFTER `hidden_by_reports` ");
        $addColumn('feedbacks', 'resolved_at', "DATETIME DEFAULT NULL AFTER `viewed_by_dev` ");

        // Remove coluna antiga se existir
        $checkOld = $pdo->query("SHOW COLUMNS FROM `feedbacks` LIKE 'likes_data'")->fetch();
        if ($checkOld) {
            echo "Removendo coluna obsoleta `likes_data` de `feedbacks`...\n";
            $pdo->exec("ALTER TABLE `feedbacks` DROP COLUMN `likes_data` ");
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

    $tables = ['people', 'projects', 'registers', 'users', 'user_sessions', 'feedbacks', 'feedback_likes', 'feedback_reports'];
    foreach ($tables as $table) {
        $file = $dataDir . '/' . $table . '.json';
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([]));
            echo "Arquivo /data/{$table}.json criado.\n";
        } else {
            echo "Arquivo /data/{$table}.json ja existe.\n";
        }
    }
    echo "Migracao JSON concluida com sucesso!\n";
}

echo "Dica: Execute 'php seeders.php' para inserir dados iniciais se necessario.\n";
