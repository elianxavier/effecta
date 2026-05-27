<?php
class EffectaORM
{
    private $storageType;
    private $baseDir;
    private $pdo;

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
                    die("Erro ao conectar no MySQL: " . $e->getMessage());
                }
            } else {
                die("Configuração de banco de dados (src/config/database.php) não encontrada.");
            }
        }
    }

    private function getFile($table)
    {
        $file = $this->baseDir . '/' . $table . '.json';
        if ($this->storageType === 'json' && !file_exists($file)) {
            file_put_contents($file, json_encode([]));
        }
        return $file;
    }

    public function insert($table, $data)
    {
        $data['id'] = uniqid();
        $data['created_at'] = date('Y-m-d H:i:s');

        // Converte strings vazias para null para evitar erros de tipo no MySQL
        foreach ($data as $key => $value) {
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
        if (empty($term)) {
            return $this->getAll($table);
        }

        if ($this->storageType === 'mysql') {
            $stmt = $this->pdo->query("DESCRIBE `{$table}`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $conditions = [];
            $params = [];
            foreach ($columns as $column) {
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
}
