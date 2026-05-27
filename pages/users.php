<?php
// Inclui o cabeçalho comum de metatags e bibliotecas
include_once dirname(__DIR__) . '/src/components/head.php';
?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col">
    <?php include_once dirname(__DIR__) . '/src/components/header.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Gerenciamento de Usuários</h1>
            <button onclick="openUserModal('create')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-all">
                <i class="fa-solid fa-user-plus mr-2"></i> Adicionar Usuário
            </button>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                                Nome
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                                E-mail
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                                Função
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Ações</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody" class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        <!-- User rows will be injected here by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- User Modal (Create/Edit) -->
    <div id="userModal" class="fixed inset-0 bg-slate-900 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-lg mx-auto p-6 relative">
            <div class="flex justify-between items-center mb-4 border-b border-slate-200 dark:border-slate-700 pb-4">
                <h3 class="text-2xl font-semibold text-slate-900 dark:text-white" id="userModalTitle">
                    Adicionar Novo Usuário
                </h3>
                <button type="button" onclick="closeUserModal()" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300 transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <form id="userForm" class="space-y-4">
                <input type="hidden" name="id" id="userIdHidden">
                <div>
                    <label for="userName" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome</label>
                    <input type="text" name="name" id="userName" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="userEmail" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">E-mail</label>
                    <input type="email" name="email" id="userEmail" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="userPassword" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Senha (deixe em branco para não alterar)</label>
                    <input type="password" name="password" id="userPassword" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="userDateOfBirth" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Data de Nascimento</label>
                    <input type="date" name="date_of_birth" id="userDateOfBirth" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="userPhoneNumber" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Telefone</label>
                    <input type="tel" name="phone_number" id="userPhoneNumber" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="userGender" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Gênero</label>
                    <select name="gender" id="userGender" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Selecione</option>
                        <option value="masculino">Masculino</option>
                        <option value="feminino">Feminino</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div>
                    <label for="userProfilePictureUrl" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL da Foto de Perfil</label>
                    <input type="url" name="profile_picture_url" id="userProfilePictureUrl" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="userBio" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Bio</label>
                    <textarea name="bio" id="userBio" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div>
                    <label for="userRole" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Função</label>
                    <select name="role" id="userRole" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="common">Comum</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="active" id="userActive" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded dark:bg-slate-700 dark:border-slate-600">
                    <label for="userActive" class="ml-2 block text-sm text-slate-900 dark:text-slate-300">Ativo</label>
                </div>
                <div class="flex justify-end pt-4 border-t border-slate-200 dark:border-slate-700 mt-6">
                    <button type="button" onclick="closeUserModal()" class="mr-3 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-all">
                        Salvar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/components.js"></script>
    <script src="assets/js/users.js"></script> <!-- New JS file for user management -->
</body>
</html>
