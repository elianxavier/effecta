<?php
class EffectaORM
{
    private $storageType;
    private $baseDir;
    private $pdo;

    private $allowedTables = ['people', 'projects', 'registers', 'users', 'user_sessions', 'feedbacks', 'feedback_likes', 'feedback_reports'];

    public function __construct($storageType = 'json')
    {
        $this->storageType = $storageType;
        $this->baseDir = dirname(__DIR__) . '/data';

        if ($this->storageType === 'mysql') {
            $configFile = __DIR__ . '/config/database.php';
            if (file_exists($configFile)) {
                $config = require $configFile;
                try {
                    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
                    $this->pdo = new PDO($dsn, $config['user'], $config['password'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                } catch (PDOException $e) {
                    // Em ambiente de produção, logar o erro e exibir uma mensagem genérica.
                    // die("Erro ao conectar no MySQL: " . $e->getMessage()); 
                    error_log("MySQL Connection Error: " . $e->getMessage());
                    die("Erro interno do servidor.");
                }
            } else {
                // Em ambiente de produção, logar o erro e exibir uma mensagem genérica.
                // die("Configuração de banco de dados (src/config/database.php) não encontrada.");
                error_log("Database configuration file not found: src/config/database.php");
                die("Erro interno do servidor.");
            }
        }
    }

    private function validateTableName($table)
    {
        if (!in_array($table, $this->allowedTables)) {
            error_log("Attempted to access unauthorized table: " . $table);
            throw new Exception("Access to table '{$table}' is not allowed.");
        }
        return $table;
    }

    private function validateColumnName($column)
    {
        // Basic alphanumeric validation for column names
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            error_log("Attempted to use invalid column name: " . $column);
            throw new Exception("Column name '{$column}' is invalid.");
        }
        return $column;
    }

    private function findWhereJson($table, $conditions, $authenticatedUserId = null)
    {
        $file = $this->getFile($table);
        $content = json_decode(file_get_contents($file), true) ?: [];

        return array_filter($content, function ($item) use ($conditions, $authenticatedUserId, $table) {
            // Apply authenticated user ID filter if table is user-scoped
            if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports'])) {
                if ($authenticatedUserId !== null && (!isset($item['user_id']) || (int)$item['user_id'] !== (int)$authenticatedUserId)) {
                    return false;
                }
            }

            foreach ($conditions as $col => $val) {
                if (!isset($item[$col]) || (string)$item[$col] !== (string)$val) {
                    return false;
                }
            }
            return true;
        });
    }

    private function getFile($table)
    {
        $table = $this->validateTableName($table);
        $file = $this->baseDir . '/' . $table . '.json';
        if ($this->storageType === 'json' && !file_exists($file)) {
            file_put_contents($file, json_encode([]));
        }
        return $file;
    }

    public function insert($table, $data, $authenticatedUserId = null)
    {
        $table = $this->validateTableName($table);
        $data['created_at'] = date('Y-m-d H:i:s');

        // Automatically add user_id for user-scoped tables if provided
        if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports'])) {
            if ($authenticatedUserId === null) {
                throw new Exception("Authenticated user ID is required for table '{$table}'.");
            }
            $data['user_id'] = $authenticatedUserId;
        }

        // Converte strings vazias para null para evitar erros de tipo no MySQL
        foreach ($data as $key => $value) {
            $this->validateColumnName($key); // Validate column names

            if ($value === '') {
                $data[$key] = null;
            }
        }

        if ($this->storageType === 'json') {
            $file = $this->getFile($table);
            $content = json_decode(file_get_contents($file), true) ?: [];

            // Simple numeric auto-increment for JSON
            $maxId = 0;
            foreach ($content as $item) {
                if (isset($item['id']) && is_numeric($item['id'])) {
                    $maxId = max($maxId, (int)$item['id']);
                }
            }
            $data['id'] = $maxId + 1;

            $content[] = $data;
            file_put_contents($file, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $data;
        } elseif ($this->storageType === 'mysql') {
            $columns = implode(', ', array_map(function ($col) {
                return "`{$col}`";
            }, array_keys($data)));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));

            $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));

            $lastId = $this->pdo->lastInsertId();
            return $this->getBy($table, 'id', $lastId);
        }

        return $data;
    }

    public function getAll($table, $authenticatedUserId = null)
    {
        $table = $this->validateTableName($table);
        if ($this->storageType === 'json') {
            $file = $this->getFile($table);
            $content = json_decode(file_get_contents($file), true) ?: [];

            // Apply authenticated user ID filter if table is user-scoped
            if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports']) && $authenticatedUserId !== null) {
                $content = array_filter($content, function ($item) use ($authenticatedUserId) {
                    return isset($item['user_id']) && (int)$item['user_id'] === (int)$authenticatedUserId;
                });
            }

            // If table is registers, enrich with project and person names
            if ($table === 'registers') {
                $projects = $this->getAll('projects', $authenticatedUserId);
                $people = $this->getAll('people', $authenticatedUserId);

                $projectMap = [];
                foreach ($projects as $p) $projectMap[$p['id']] = $p['name'];

                $peopleMap = [];
                foreach ($people as $p) $peopleMap[$p['id']] = $p['name'];

                $content = array_map(function ($item) use ($projectMap, $peopleMap) {
                    $item['projeto_name'] = $projectMap[$item['projeto_id']] ?? 'Projeto Removido';
                    $item['pessoa_feedback_name'] = $peopleMap[$item['pessoa_feedback_id']] ?? 'Nenhum';
                    return $item;
                }, $content);
            }

            return array_values($content);
        } elseif ($this->storageType === 'mysql') {
            if ($table === 'registers') {
                $sql = "SELECT r.*, p.name as projeto_name, pe.name as pessoa_feedback_name 
                        FROM `registers` r
                        LEFT JOIN `projects` p ON r.projeto_id = p.id
                        LEFT JOIN `people` pe ON r.pessoa_feedback_id = pe.id";
                $params = [];
                if ($authenticatedUserId !== null) {
                    $sql .= " WHERE r.`user_id` = ?";
                    $params[] = $authenticatedUserId;
                }
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll();
            }

            $sql = "SELECT * FROM `{$table}`";
            $params = [];
            if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports']) && $authenticatedUserId !== null) {
                $sql .= " WHERE `user_id` = ?";
                $params[] = $authenticatedUserId;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
        return [];
    }

    public function search($table, $term, $authenticatedUserId = null)
    {
        $table = $this->validateTableName($table);
        if (empty($term)) {
            return $this->getAll($table, $authenticatedUserId);
        }

        if ($this->storageType === 'mysql') {
            $stmt = $this->pdo->query("DESCRIBE `{$table}`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $conditions = [];
            $params = [];

            if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports']) && $authenticatedUserId !== null) {
                $conditions[] = "`user_id` = ?";
                $params[] = $authenticatedUserId;
            }

            foreach ($columns as $column) {
                $this->validateColumnName($column); // Validate column names
                $conditions[] = "`{$column}` LIKE ?";
                $params[] = "%{$term}%";
            }

            // Adjust the WHERE clause logic for multiple conditions
            $sql = "SELECT * FROM `{$table}` WHERE " . implode(' AND (', array_slice($conditions, 0, 1)) . (count($conditions) > 1 ? ' OR ' . implode(' OR ', array_slice($conditions, 1)) . ')' : '');

            // The previous line for WHERE clause is wrong. Let's rebuild it better.
            $sqlConditions = [];
            $sqlParams = [];

            if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports']) && $authenticatedUserId !== null) {
                $sqlConditions[] = "`user_id` = ?";
                $sqlParams[] = $authenticatedUserId;
            }

            $termConditions = [];
            foreach ($columns as $column) {
                $this->validateColumnName($column);
                $termConditions[] = "`{$column}` LIKE ?";
                $sqlParams[] = "%{$term}%";
            }
            if (!empty($termConditions)) {
                $sqlConditions[] = "(" . implode(' OR ', $termConditions) . ")";
            }

            $sql = "SELECT * FROM `{$table}`" . (!empty($sqlConditions) ? " WHERE " . implode(" AND ", $sqlConditions) : "");

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($sqlParams);
            return $stmt->fetchAll();
        }

        // Fallback JSON
        $all = $this->findWhereJson($table, [], $authenticatedUserId); // Use findWhereJson for initial filtering
        $term = strtolower($term);
        return array_filter($all, function ($item) use ($term) {
            foreach ($item as $key => $value) {
                if (is_string($value) && strpos(strtolower($value), $term) !== false) {
                    return true;
                }
            }
            return false;
        });
    }

    public function getBy($table, $column, $value, $authenticatedUserId = null)
    {
        $table = $this->validateTableName($table);
        $column = $this->validateColumnName($column);

        if ($this->storageType === 'json') {
            $conditions = [$column => $value];
            $results = $this->findWhereJson($table, $conditions, $authenticatedUserId);
            return reset($results) ?: null; // Return the first matching item or null
        } elseif ($this->storageType === 'mysql') {
            // Type safety check for numeric IDs
            if ($column === 'id' && !is_numeric($value)) {
                return null;
            }

            $sql = "SELECT * FROM `{$table}` WHERE `{$column}` = ?";
            $params = [$value];

            if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports']) && $authenticatedUserId !== null) {
                $sql .= " AND `user_id` = ?";
                $params[] = $authenticatedUserId;
            }
            $sql .= " LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() ?: null;
        }
        return null;
    }

    public function update($table, $id, $data, $authenticatedUserId = null)
    {
        $table = $this->validateTableName($table);
        // Converte strings vazias para null para evitar erros de tipo no MySQL
        foreach ($data as $key => $value) {
            $this->validateColumnName($key); // Validate column names
            if ($value === '') {
                $data[$key] = null;
            }
        }

        if ($this->storageType === 'json') {
            $all = $this->getAll($table);
            $updated = false;
            foreach ($all as $index => $item) {
                if (isset($item['id']) && $item['id'] === $id) {
                    // Check user_id for user-scoped tables
                    if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports']) && $authenticatedUserId !== null && (!isset($item['user_id']) || (int)$item['user_id'] !== (int)$authenticatedUserId)) {
                        continue; // Skip if not owned by authenticated user
                    }
                    $all[$index] = array_merge($item, $data);
                    $updated = true;
                    break;
                }
            }
            if ($updated) {
                file_put_contents($this->getFile($table), json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            return $this->getBy($table, 'id', $id, $authenticatedUserId); // Use authenticatedUserId in getBy
        } elseif ($this->storageType === 'mysql') {
            // Type safety check for numeric IDs
            if (!is_numeric($id)) {
                return null;
            }

            $sets = [];
            $params = [];
            foreach ($data as $key => $val) {
                $this->validateColumnName($key); // Re-validate keys as they are directly used
                $sets[] = "`{$key}` = ?";
                // Explicitly cast boolean for 'active' column if it's a boolean value
                if ($key === 'active' && is_bool($val)) {
                    $params[] = (int)$val;
                } else {
                    $params[] = $val;
                }
            }

            $sql = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE `id` = ?";
            $params[] = $id;

            if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports']) && $authenticatedUserId !== null) {
                $sql .= " AND `user_id` = ?";
                $params[] = $authenticatedUserId;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->getBy($table, 'id', $id, $authenticatedUserId); // Use authenticatedUserId in getBy
        }
        return null;
    }

    public function delete($table, $column, $value, $authenticatedUserId = null)
    {
        $table = $this->validateTableName($table);
        $column = $this->validateColumnName($column);
        if ($this->storageType === 'json') {
            $file = $this->getFile($table);
            $all = $this->getAll($table);
            $filtered = array_filter($all, function ($item) use ($column, $value, $authenticatedUserId, $table) {
                // Check user_id for user-scoped tables
                if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports']) && $authenticatedUserId !== null && (!isset($item['user_id']) || (int)$item['user_id'] !== (int)$authenticatedUserId)) {
                    return true; // Keep if not owned by authenticated user
                }
                return !isset($item[$column]) || (string)$item[$column] !== (string)$value;
            });
            file_put_contents($file, json_encode(array_values($filtered), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return true;
        } elseif ($this->storageType === 'mysql') {
            // Type safety check for numeric columns (id, user_id, projeto_id, etc)
            if (in_array($column, ['id', 'user_id', 'projeto_id', 'pessoa_feedback_id']) && !is_numeric($value)) {
                return false;
            }

            $sql = "DELETE FROM `{$table}` WHERE `{$column}` = ?";
            $params = [$value];

            if (in_array($table, ['people', 'projects', 'registers', 'feedbacks', 'feedback_likes', 'feedback_reports']) && $authenticatedUserId !== null) {
                $sql .= " AND `user_id` = ?";
                $params[] = $authenticatedUserId;
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return true;
        }
        return false;
    }

    public function getByEmail($email)
    {
        if ($this->storageType === 'json') {
            $users = $this->getAll('users'); // This getAll should not be filtered by user_id
            foreach ($users as $user) {
                if (isset($user['email']) && $user['email'] === $email) {
                    return $user;
                }
            }
            return null;
        } elseif ($this->storageType === 'mysql') {
            $sql = "SELECT * FROM `users` WHERE `email` = ? LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch() ?: null;
        }
        return null;
    }
}
