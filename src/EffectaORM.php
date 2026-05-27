<?php
class EffectaORM
{
    private $storageType;
    private $baseDir;
    private $pdo;

    private $allowedTables = ['people', 'projects', 'registers', 'users', 'user_sessions'];

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

    private function getFile($table)
    {
        $table = $this->validateTableName($table);
        $file = $this->baseDir . '/' . $table . '.json';
        if ($this->storageType === 'json' && !file_exists($file)) {
            file_put_contents($file, json_encode([]));
        }
        return $file;
    }

    public function insert($table, $data)
    {
        $table = $this->validateTableName($table);
        $data['id'] = uniqid();
        $data['created_at'] = date('Y-m-d H:i:s');

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
            $content[] = $data;
            file_put_contents($file, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } elseif ($this->storageType === 'mysql') {
            $columns = implode(', ', array_map(function($col) { return "`{$col}`"; }, array_keys($data)));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));

            $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));
        }

        return $data;
    }

    public function getAll($table)
    {
        $table = $this->validateTableName($table);
        if ($this->storageType === 'json') {
            $file = $this->getFile($table);
            return json_decode(file_get_contents($file), true) ?: [];
        } elseif ($this->storageType === 'mysql') {
            $sql = "SELECT * FROM `{$table}`";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        }
        return [];
    }

    public function search($table, $term)
    {
        $table = $this->validateTableName($table);
        if (empty($term)) {
            return $this->getAll($table);
        }

        if ($this->storageType === 'mysql') {
            $stmt = $this->pdo->query("DESCRIBE `{$table}`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $conditions = [];
            $params = [];
            foreach ($columns as $column) {
                $this->validateColumnName($column); // Validate column names
                $conditions[] = "`{$column}` LIKE ?";
                $params[] = "%{$term}%";
            }
            
            $sql = "SELECT * FROM `{$table}` WHERE " . implode(' OR ', $conditions);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        // Fallback JSON
        $all = $this->getAll($table);
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

    public function getBy($table, $column, $value)
    {
        $table = $this->validateTableName($table);
        $column = $this->validateColumnName($column);
        if ($this->storageType === 'json') {
            $all = $this->getAll($table);
            foreach ($all as $item) {
                if (isset($item[$column]) && (string)$item[$column] === (string)$value) {
                    return $item;
                }
            }
            return null;
        } elseif ($this->storageType === 'mysql') {
            $sql = "SELECT * FROM `{$table}` WHERE `{$column}` = ? LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$value]);
            return $stmt->fetch() ?: null;
        }
        return null;
    }

    public function update($table, $id, $data)
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
            $file = $this->getFile($table);
            $all = $this->getAll($table);
            $updated = false;
            foreach ($all as $index => $item) {
                if (isset($item['id']) && $item['id'] === $id) {
                    $all[$index] = array_merge($item, $data);
                    $updated = true;
                    break;
                }
            }
            if ($updated) {
                file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            return $this->getBy($table, 'id', $id);
        } elseif ($this->storageType === 'mysql') {
            $sets = [];
            $params = [];
            foreach ($data as $key => $val) {
                $sets[] = "`{$key}` = ?";
                $params[] = $val;
            }
            $params[] = $id;
            
            $sql = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE `id` = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->getBy($table, 'id', $id);
        }
        return null;
    }

    public function delete($table, $column, $value)
    {
        $table = $this->validateTableName($table);
        $column = $this->validateColumnName($column);
        if ($this->storageType === 'json') {
            $file = $this->getFile($table);
            $all = $this->getAll($table);
            $filtered = array_filter($all, function($item) use ($column, $value) {
                return !isset($item[$column]) || (string)$item[$column] !== (string)$value;
            });
            file_put_contents($file, json_encode(array_values($filtered), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return true;
        } elseif ($this->storageType === 'mysql') {
            $sql = "DELETE FROM `{$table}` WHERE `{$column}` = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$value]);
            return true;
        }
        return false;
    }
}
