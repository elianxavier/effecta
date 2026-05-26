<?php
class EffectaORM
{
    private $storageType;
    private $baseDir = __DIR__;

    public function __construct($storageType = 'json')
    {
        $this->storageType = $storageType;
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

        if ($this->storageType === 'json') {
            $file = $this->getFile($table);
            $content = json_decode(file_get_contents($file), true) ?: [];
            $content[] = $data;
            file_put_contents($file, json_encode($content));
        }

        return $data;
    }

    public function getAll($table)
    {
        if ($this->storageType === 'json') {
            $file = $this->getFile($table);
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }

    public function search($table, $term)
    {
        $all = $this->getAll($table);
        if (empty($term)) return $all;

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
