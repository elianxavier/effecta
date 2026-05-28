<?php

class UserController {
    private $orm;
    private $authId;
    private $authRole;
    private $isDev;

    public function __construct($orm, $authId, $authRole) {
        $this->orm = $orm;
        $this->authId = $authId;
        $this->authRole = $authRole;
        $this->isDev = ($authRole === 'dev');
    }

    public function getUsers() {
        $allUsers = $this->orm->getAll('users');
        return array_map(function ($user) {
            unset($user['password_hash']);
            return $user;
        }, $allUsers);
    }

    public function createUser($input) {
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'common';
        $active = $input['active'] ?? true;

        if ($role === 'dev' && !$this->isDev) {
            http_response_code(403);
            return ['error' => 'Apenas desenvolvedores podem criar outros desenvolvedores.'];
        }

        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400);
            return ['error' => 'Nome, e-mail e senha são obrigatórios para criar um usuário.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return ['error' => 'Formato de e-mail inválido.'];
        }

        if ($this->orm->getBy('users', 'email', $email)) {
            http_response_code(409);
            return ['error' => 'E-mail já cadastrado.'];
        }

        return [
            'success' => true,
            'user' => $this->orm->insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'role' => $role,
                'active' => $active,
                'date_of_birth' => $input['date_of_birth'] ?? null,
                'phone_number' => $input['phone_number'] ?? null,
                'gender' => $input['gender'] ?? null,
                'profile_picture_url' => $input['profile_picture_url'] ?? null,
                'bio' => $input['bio'] ?? null
            ])
        ];
    }

    public function updateUser($input) {
        $userId = $input['id'] ?? null;
        if (!$userId) {
            http_response_code(400);
            return ['error' => 'ID do usuário é obrigatório.'];
        }

        $targetUser = $this->orm->getBy('users', 'id', $userId);
        if (!$targetUser) {
            http_response_code(404);
            return ['error' => 'Usuário não encontrado.'];
        }

        if ((int)$userId === (int)$this->authId && isset($input['role']) && $input['role'] !== $targetUser['role']) {
            http_response_code(403);
            return ['error' => 'Você não pode alterar sua própria função.'];
        }

        if ($targetUser['role'] === 'dev' && !$this->isDev) {
            http_response_code(403);
            return ['error' => 'Apenas desenvolvedores podem modificar outros desenvolvedores.'];
        }

        if (isset($input['role']) && $input['role'] === 'dev' && !$this->isDev) {
            http_response_code(403);
            return ['error' => 'Apenas desenvolvedores podem atribuir a função de desenvolvedor.'];
        }

        $allowedFields = ['name', 'email', 'role', 'date_of_birth', 'phone_number', 'gender', 'profile_picture_url', 'bio'];
        $dataToUpdate = array_intersect_key($input, array_flip($allowedFields));

        if (isset($input['active'])) {
            if ((int)$userId === (int)$this->authId && !$input['active']) {
                http_response_code(403);
                return ['error' => 'Você não pode desativar sua própria conta.'];
            }
            $dataToUpdate['active'] = $input['active'] ? 1 : 0;
        }

        if (!empty($input['password'])) {
            $dataToUpdate['password_hash'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }

        if (isset($dataToUpdate['email'])) {
            $existingUser = $this->orm->getBy('users', 'email', $dataToUpdate['email']);
            if ($existingUser && (int)$existingUser['id'] !== (int)$userId) {
                http_response_code(409);
                return ['error' => 'E-mail já cadastrado para outro usuário.'];
            }
        }

        $updatedUser = $this->orm->update('users', $userId, $dataToUpdate);
        return $updatedUser ? ['success' => true, 'user' => $updatedUser] : ['error' => 'Erro ao atualizar o usuário.'];
    }

    public function toggleUserStatus($input) {
        $userId = $input['id'] ?? null;
        $activeStatus = $input['active'] ?? null;

        if (!$userId || !is_bool($activeStatus)) {
            http_response_code(400);
            return ['error' => 'ID do usuário e status "active" (booleano) são obrigatórios.'];
        }

        $targetUser = $this->orm->getBy('users', 'id', $userId);
        if (!$targetUser) {
            http_response_code(404);
            return ['error' => 'Usuário não encontrado.'];
        }

        if ((int)$userId === (int)$this->authId && !$activeStatus) {
            http_response_code(403);
            return ['error' => 'Você não pode desativar sua própria conta.'];
        }

        if ($targetUser['role'] === 'dev' && !$this->isDev) {
            http_response_code(403);
            return ['error' => 'Apenas desenvolvedores podem alterar o status de outros desenvolvedores.'];
        }

        $updatedUser = $this->orm->update('users', $userId, ['active' => $activeStatus]);
        return $updatedUser ? ['success' => true, 'user' => $updatedUser] : ['error' => 'Erro ao alterar o status do usuário.'];
    }

    public function getMyProfile() {
        $userProfile = $this->orm->getBy('users', 'id', $this->authId);
        if ($userProfile) {
            unset($userProfile['password_hash']);
            return ['success' => true, 'user' => $userProfile];
        }
        http_response_code(404);
        return ['error' => 'Perfil de usuário não encontrado.'];
    }

    public function updateMyProfile($input) {
        unset($input['id'], $input['email'], $input['role'], $input['active']);
        $updatedUser = $this->orm->update('users', $this->authId, $input);
        if ($updatedUser) {
            unset($updatedUser['password_hash']);
            return ['success' => true, 'user' => $updatedUser];
        }
        http_response_code(500);
        return ['error' => 'Erro ao atualizar o perfil.'];
    }

    public function changeMyPassword($input) {
        $oldPassword = $input['old_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            http_response_code(400);
            return ['error' => 'Todos os campos de senha são obrigatórios.'];
        }

        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            return ['error' => 'A nova senha e a confirmação não coincidem.'];
        }

        $currentUser = $this->orm->getBy('users', 'id', $this->authId);
        if (!$currentUser || !password_verify($oldPassword, $currentUser['password_hash'])) {
            http_response_code(401);
            return ['error' => 'Senha antiga incorreta.'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updatedUser = $this->orm->update('users', $this->authId, ['password_hash' => $hashedPassword]);
        return $updatedUser ? ['success' => true] : ['error' => 'Erro ao alterar a senha.'];
    }
}
