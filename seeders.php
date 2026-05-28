<?php
// Script de Seeders do Effecta
echo "Iniciando a inserção de dados iniciais (Seeders)...\n";

$configFile = __DIR__ . '/src/config/database.php';
if (!file_exists($configFile)) {
    echo "Erro: Arquivo src/config/database.php nao encontrado.\n";
    exit(1);
}

$config = require $configFile;
$storageType = $config['storage_type'] ?? 'json';

if ($storageType === 'mysql') {
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $stmt = $pdo->query("SELECT COUNT(*) FROM `users`");
        $userCount = $stmt->fetchColumn();

        if ($userCount == 0) {
            echo "Inserindo usuarios padrão (MySQL)...\n";
            $now = date('Y-m-d H:i:s');

            // Admin
            $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("INSERT INTO `users` (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute(['Administrador', 'admin@effecta.com', $adminHash, 'admin', $now]);

            // Dev
            $devHash = password_hash('dev123', PASSWORD_BCRYPT);
            $stmtInsert->execute(['Desenvolvedor', 'dev@effecta.com', $devHash, 'dev', $now]);

            // Common User
            $commonHash = password_hash('user123', PASSWORD_BCRYPT);
            $stmtInsert->execute(['Usuario Comun', 'user@effecta.com', $commonHash, 'common', $now]);

            echo "Seeders MySQL inseridos com sucesso!\n";

            // Add some test feedbacks
            //$stmtFeed = $pdo->prepare("INSERT INTO `feedbacks` (user_id, type, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            //$stmtFeed->execute([3, 'bug', 'Erro no Menu Lateral', 'O menu lateral some em telas pequenas.', 'pendente', $now]);
            echo "Feedback de teste (Bug) inserido para Dev.\n";
        } else {
            echo "A tabela `users` ja possui dados. Seeders ignorados.\n";
        }
    } catch (PDOException $e) {
        echo "Erro SQL: " . $e->getMessage() . "\n";
    }
} else {
    $dataDir = __DIR__ . '/data';
    $usersFile = $dataDir . '/users.json';

    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true) ?: [];
        if (empty($users)) {
            echo "Inserindo usuarios padrão (JSON)...\n";
            $now = date('Y-m-d H:i:s');
            $users[] = [
                'id' => 1,
                'name' => 'Administrador',
                'email' => 'admin@effecta.com',
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
                'role' => 'admin',
                'active' => true,
                'date_of_birth' => null,
                'phone_number' => null,
                'gender' => null,
                'profile_picture_url' => null,
                'bio' => null,
                'created_at' => $now
            ];
            $users[] = [
                'id' => 2,
                'name' => 'Usuario Comun',
                'email' => 'user@effecta.com',
                'password_hash' => password_hash('user123', PASSWORD_BCRYPT),
                'role' => 'common',
                'active' => true,
                'date_of_birth' => null,
                'phone_number' => null,
                'gender' => null,
                'profile_picture_url' => null,
                'bio' => null,
                'created_at' => $now
            ];
            $users[] = [
                'id' => 3,
                'name' => 'Desenvolvedor',
                'email' => 'dev@effecta.com',
                'password_hash' => password_hash('dev123', PASSWORD_BCRYPT),
                'role' => 'dev',
                'active' => true,
                'date_of_birth' => null,
                'phone_number' => null,
                'gender' => null,
                'profile_picture_url' => null,
                'bio' => null,
                'created_at' => $now
            ];
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "Seeders JSON inseridos com sucesso!\n";
        } else {
            echo "O arquivo `users.json` ja possui dados. Seeders ignorados.\n";
        }
    }
}
