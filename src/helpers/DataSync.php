<?php

class DataSync
{
    private static $method = 'aes-256-cbc';

    private static function getKey()
    {
        return hash('sha256', getenv('JWT_SECRET') ?: 'default_secret_key', true);
    }

    public static function encryptId($id)
    {
        if (empty($id)) return null;
        $key = self::getKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$method));
        $encrypted = openssl_encrypt((string)$id, self::$method, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decryptId($encryptedId)
    {
        if (empty($encryptedId)) return null;
        $key = self::getKey();
        $data = base64_decode($encryptedId);
        $ivSize = openssl_cipher_iv_length(self::$method);
        $iv = substr($data, 0, $ivSize);
        $encrypted = substr($data, $ivSize);
        return openssl_decrypt($encrypted, self::$method, $key, 0, $iv);
    }

    public static function prepareExport($data)
    {
        $exported = [
            'people' => [],
            'projects' => [],
            'registers' => []
        ];

        // Cache to ensure same ID gets same encrypted string in this export session
        $idMap = [];
        $getSyncId = function($id) use (&$idMap) {
            if (empty($id)) return null;
            if (!isset($idMap[$id])) {
                $idMap[$id] = self::encryptId($id);
            }
            return $idMap[$id];
        };

        foreach ($data['people'] as $person) {
            $p = $person;
            $p['sync_id'] = $getSyncId($person['id']);
            unset($p['id'], $p['user_id'], $p['created_at']);
            $exported['people'][] = $p;
        }

        foreach ($data['projects'] as $project) {
            $p = $project;
            $p['sync_id'] = $getSyncId($project['id']);
            unset($p['id'], $p['user_id'], $p['created_at']);
            $exported['projects'][] = $p;
        }

        foreach ($data['registers'] as $register) {
            $r = $register;
            $r['sync_id'] = $getSyncId($register['id']);
            $r['projeto_sync_id'] = $getSyncId($register['projeto_id']);
            $r['pessoa_feedback_sync_id'] = $getSyncId($register['pessoa_feedback_id']);
            
            unset($r['id'], $r['user_id'], $r['created_at']);
            unset($r['projeto_id'], $r['pessoa_feedback_id']);
            unset($r['projeto_name'], $r['pessoa_feedback_name']); 
            
            $exported['registers'][] = $r;
        }

        return $exported;
    }

    public static function performImport($orm, $userId, $importData)
    {
        $peopleMap = [];
        $projectsMap = [];
        
        $report = [
            'people' => ['added' => [], 'existing' => 0],
            'projects' => ['added' => [], 'existing' => 0],
            'registers' => ['added' => [], 'updated' => [], 'unchanged' => 0, 'errors' => []],
            'logs' => []
        ];

        // 1. Process People (Dependencies First)
        if (!empty($importData['people'])) {
            $existingPeople = $orm->getAll('people', $userId);
            foreach ($importData['people'] as $importedPerson) {
                if (!isset($importedPerson['sync_id'])) continue;

                $found = null;
                foreach ($existingPeople as $existing) {
                    if (trim(strtolower($existing['name'])) === trim(strtolower($importedPerson['name']))) {
                        $found = $existing;
                        break;
                    }
                }

                if ($found) {
                    $peopleMap[$importedPerson['sync_id']] = $found['id'];
                    $report['people']['existing']++;
                } else {
                    $newPerson = $orm->insert('people', ['name' => $importedPerson['name']], $userId);
                    if ($newPerson) {
                        $peopleMap[$importedPerson['sync_id']] = $newPerson['id'];
                        $report['people']['added'][] = $newPerson['name'];
                    }
                }
            }
        }

        // 2. Process Projects (Dependencies First)
        if (!empty($importData['projects'])) {
            $existingProjects = $orm->getAll('projects', $userId);
            foreach ($importData['projects'] as $importedProject) {
                if (!isset($importedProject['sync_id'])) continue;

                $found = null;
                foreach ($existingProjects as $existing) {
                    if (trim(strtolower($existing['name'])) === trim(strtolower($importedProject['name']))) {
                        $found = $existing;
                        break;
                    }
                }

                if ($found) {
                    $projectsMap[$importedProject['sync_id']] = $found['id'];
                    $report['projects']['existing']++;
                } else {
                    $newProject = $orm->insert('projects', ['name' => $importedProject['name']], $userId);
                    if ($newProject) {
                        $projectsMap[$importedProject['sync_id']] = $newProject['id'];
                        $report['projects']['added'][] = $newProject['name'];
                    }
                }
            }
        }

        // 3. Process Registers (Dependents Last)
        if (!empty($importData['registers'])) {
            $existingRegisters = $orm->getAll('registers', $userId);
            foreach ($importData['registers'] as $importedRegister) {
                $projetoSyncId = $importedRegister['projeto_sync_id'] ?? null;
                $pessoaSyncId = $importedRegister['pessoa_feedback_sync_id'] ?? null;

                $projetoId = $projectsMap[$projetoSyncId] ?? null;
                $pessoaFeedbackId = $peopleMap[$pessoaSyncId] ?? null;

                if (!$projetoId) {
                    $report['registers']['errors'][] = "Registro '{$importedRegister['atividade']}' pulado: Projeto não encontrado ou não criado.";
                    continue;
                }

                $dataToSync = $importedRegister;
                unset($dataToSync['sync_id'], $dataToSync['projeto_sync_id'], $dataToSync['pessoa_feedback_sync_id']);
                
                // FORCE OWNERSHIP to the current user
                $dataToSync['projeto_id'] = $projetoId;
                $dataToSync['pessoa_feedback_id'] = $pessoaFeedbackId;
                $dataToSync['user_id'] = $userId;

                $found = null;
                foreach ($existingRegisters as $existing) {
                    if ((int)$existing['projeto_id'] === (int)$projetoId && trim(strtolower($existing['atividade'])) === trim(strtolower($dataToSync['atividade']))) {
                        $found = $existing;
                        break;
                    }
                }

                if ($found) {
                    $diff = false;
                    foreach ($dataToSync as $key => $val) {
                        if ($key === 'user_id' || $key === 'created_at') continue;
                        if (array_key_exists($key, $found)) {
                            $currentVal = (string)$found[$key];
                            $newVal = (string)$val;
                            
                            if (is_numeric($currentVal) && is_numeric($newVal)) {
                                if (abs((float)$currentVal - (float)$newVal) > 0.0001) $diff = true;
                            } else if ($currentVal !== $newVal) {
                                $diff = true;
                            }
                        }
                        if ($diff) break;
                    }

                    if ($diff) {
                        $updated = $orm->update('registers', $found['id'], $dataToSync, $userId);
                        if ($updated) {
                            $report['registers']['updated'][] = $updated['atividade'];
                        }
                    } else {
                        $report['registers']['unchanged']++;
                    }
                } else {
                    $newRegister = $orm->insert('registers', $dataToSync, $userId);
                    if ($newRegister) {
                        $report['registers']['added'][] = $newRegister['atividade'];
                    }
                }
            }
        }

        // Confirmação final direta do banco
        $report['final_db_summary'] = [
            'people_count' => count($orm->getAll('people', $userId)),
            'projects_count' => count($orm->getAll('projects', $userId)),
            'registers_count' => count($orm->getAll('registers', $userId))
        ];

        return $report;
    }
}
