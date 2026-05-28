<?php

class EntityController {
    private $orm;
    private $authId;

    public function __construct($orm, $authId) {
        $this->orm = $orm;
        $this->authId = $authId;
    }

    // Projects
    public function getProjects() {
        return $this->orm->getAll('projects', $this->authId);
    }

    public function addProject($input) {
        return $this->orm->insert('projects', ['name' => $input['name'] ?? ''], $this->authId);
    }

    public function updateProject($input) {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            return ['error' => 'ID do projeto e obrigatorio.'];
        }
        unset($input['id']);
        return $this->orm->update('projects', $id, $input, $this->authId);
    }

    public function deleteProject($input) {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            return ['error' => 'ID do projeto e obrigatorio.'];
        }
        return ['success' => $this->orm->delete('projects', 'id', $id, $this->authId)];
    }

    // People
    public function getPeople() {
        return $this->orm->getAll('people', $this->authId);
    }

    public function addPerson($input) {
        return $this->orm->insert('people', ['name' => $input['name'] ?? ''], $this->authId);
    }

    public function updatePerson($input) {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            return ['error' => 'ID da pessoa e obrigatorio.'];
        }
        unset($input['id']);
        return $this->orm->update('people', $id, $input, $this->authId);
    }

    public function deletePerson($input) {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            return ['error' => 'ID da pessoa e obrigatorio.'];
        }
        return ['success' => $this->orm->delete('people', 'id', $id, $this->authId)];
    }

    // Registers
    public function getRegisters() {
        return $this->orm->getAll('registers', $this->authId);
    }

    public function saveRegister($input) {
        return $this->orm->insert('registers', $input, $this->authId);
    }

    public function updateRegister($input) {
        $recordId = $input['id'] ?? null;
        if (!$recordId) {
            http_response_code(400);
            return ['error' => 'ID do registro e obrigatorio para atualizacao.'];
        }
        unset($input['id']);
        $updatedRecord = $this->orm->update('registers', $recordId, $input, $this->authId);
        return $updatedRecord ? ['success' => true, 'record' => $updatedRecord] : ['error' => 'Erro ao atualizar o registro.'];
    }

    public function deleteRegister($input) {
        $recordId = $input['id'] ?? null;
        if (!$recordId) {
            http_response_code(400);
            return ['error' => 'ID do registro e obrigatorio para exclusao.'];
        }
        return $this->orm->delete('registers', 'id', $recordId, $this->authId) ? ['success' => true] : ['error' => 'Erro ao excluir o registro.'];
    }

    public function search($term) {
        return array_values($this->orm->search('registers', $term, $this->authId));
    }

    // Feedbacks
    public function getFeedbacks($tab) {
        $allFeedbacks = $this->orm->getAll('feedbacks');
        $isResolvedTab = ($tab === 'resolved');
        
        // Count total users for the 20% rule
        $allUsers = $this->orm->getAll('users');
        $totalUsers = count($allUsers);

        $filtered = array_filter($allFeedbacks, function($f) use ($isResolvedTab, $totalUsers) {
            if ($f['archived'] == 1) return false;
            
            // Check if hidden by 20% rule
            $reportCount = (int)($f['reports'] ?? 0);
            if ($totalUsers > 0 && ($reportCount / $totalUsers) >= 0.20) {
                return false;
            }
            if (isset($f['hidden_by_reports']) && $f['hidden_by_reports']) return false;

            $isOwner = (int)$f['user_id'] === (int)$this->authId;
            $isBug = $f['type'] === 'bug';
            $isResolved = $f['status'] === 'concluido';

            // Check if the current user has reported this feedback
            $hasReported = $this->orm->getBy('feedback_reports', 'feedback_id', $f['id'], $this->authId);
            if ($hasReported) return false;

            if ($isResolvedTab) {
                return $isResolved && ($isOwner || $isBug);
            } else {
                if ($isResolved) return false;
                return $isOwner || $isBug;
            }
        });

        $sanitized = array_map(function($f) {
            $isOwner = (int)$f['user_id'] === (int)$this->authId;
            $f['is_owner'] = $isOwner;
            $like = $this->orm->getBy('feedback_likes', 'feedback_id', $f['id'], $this->authId);
            $f['user_liked'] = ($like !== null);
            if (!$isOwner) unset($f['user_id']);
            return $f;
        }, $filtered);

        usort($sanitized, function($a, $b) {
            return ($b['likes'] ?? 0) <=> ($a['likes'] ?? 0);
        });

        return array_values($sanitized);
    }

    public function reportFeedback($input) {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            return ['error' => 'ID do feedback e obrigatorio.'];
        }

        $feedback = $this->orm->getBy('feedbacks', 'id', $id);
        if (!$feedback) {
            http_response_code(404);
            return ['error' => 'Feedback nao encontrado.'];
        }

        $existingReport = $this->orm->getBy('feedback_reports', 'feedback_id', $id, $this->authId);
        if ($existingReport) {
            return ['success' => true, 'message' => 'Voce ja denunciou este item.'];
        }

        $this->orm->insert('feedback_reports', ['feedback_id' => $id], $this->authId);
        $newReportCount = (int)($feedback['reports'] ?? 0) + 1;
        
        $updateData = ['reports' => $newReportCount];
        
        // Check 20% rule for automatic hiding
        $allUsers = $this->orm->getAll('users');
        $totalUsers = count($allUsers);
        if ($totalUsers > 0 && ($newReportCount / $totalUsers) >= 0.20) {
            $updateData['hidden_by_reports'] = 1;
        }

        $this->orm->update('feedbacks', $id, $updateData);

        return ['success' => true, 'reports' => $newReportCount];
    }

    public function getFeedbackStats() {
        // Method for Dev to check alerts and manage all feedbacks
        $allFeedbacks = $this->orm->getAll('feedbacks');
        
        // Alert if > 5 reports
        $alerts = array_filter($allFeedbacks, function($f) {
            return (int)($f['reports'] ?? 0) > 5 && !($f['viewed_by_dev'] ?? false);
        });

        return [
            'all' => $allFeedbacks,
            'alerts_count' => count($alerts),
            'alerts' => array_values($alerts)
        ];
    }

    public function adminUpdateFeedback($input) {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            return ['error' => 'ID do feedback e obrigatorio.'];
        }
        unset($input['id']);
        return $this->orm->update('feedbacks', $id, $input);
    }

    public function saveFeedback($input) {
        $type = $input['type'] ?? '';
        $subject = $input['subject'] ?? '';
        $message = $input['message'] ?? '';

        if (empty($type) || empty($subject) || empty($message)) {
            http_response_code(400);
            return ['error' => 'Tipo, assunto e mensagem sao obrigatorios.'];
        }

        return [
            'success' => true,
            'feedback' => $this->orm->insert('feedbacks', [
                'type' => $type,
                'subject' => $subject,
                'message' => $message,
                'status' => 'pendente',
                'archived' => 0
            ], $this->authId)
        ];
    }

    public function archiveFeedback($input) {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            return ['error' => 'ID do feedback e obrigatorio.'];
        }
        $updated = $this->orm->update('feedbacks', $id, ['archived' => 1], $this->authId);
        return ['success' => (bool)$updated];
    }

    public function likeFeedback($input) {
        $id = $input['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            return ['error' => 'ID do feedback e obrigatorio.'];
        }

        $feedback = $this->orm->getBy('feedbacks', 'id', $id);
        if (!$feedback) {
            http_response_code(404);
            return ['error' => 'Feedback nao encontrado.'];
        }

        if ((int)$feedback['user_id'] === (int)$this->authId) {
            http_response_code(403);
            return ['error' => 'Voce nao pode dar like no seu proprio feedback.'];
        }

        $existingLike = $this->orm->getBy('feedback_likes', 'feedback_id', $id, $this->authId);

        if ($existingLike) {
            $this->orm->delete('feedback_likes', 'id', $existingLike['id'], $this->authId);
            $newLikeCount = max(0, (int)$feedback['likes'] - 1);
        } else {
            $this->orm->insert('feedback_likes', ['feedback_id' => $id], $this->authId);
            $newLikeCount = (int)$feedback['likes'] + 1;
        }

        $this->orm->update('feedbacks', $id, ['likes' => $newLikeCount]);

        return ['success' => true, 'likes' => $newLikeCount, 'user_liked' => (!$existingLike)];
    }

    // Data Sync
    public function importData($input) {
        try {
            $result = DataSync::performImport($this->orm, $this->authId, $input);
            return ['success' => true, 'result' => $result];
        } catch (Exception $e) {
            http_response_code(500);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getExportData() {
        $data = [
            'people' => $this->orm->getAll('people', $this->authId),
            'projects' => $this->orm->getAll('projects', $this->authId),
            'registers' => $this->orm->getAll('registers', $this->authId)
        ];
        return DataSync::prepareExport($data);
    }
}
