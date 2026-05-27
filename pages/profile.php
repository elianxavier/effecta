<?php
// Inclui o cabeçalho comum de metatags e bibliotecas
include_once dirname(__DIR__) . '/src/components/head.php';
?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col">
    <?php include_once dirname(__DIR__) . '/src/components/header.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-6">Meu Perfil</h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Detalhes do Perfil -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Informações Pessoais</h2>
                <form id="profileForm" class="space-y-4">
                    <div>
                        <label for="profileName" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome</label>
                        <input type="text" name="name" id="profileName" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="profileEmail" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">E-mail</label>
                        <input type="email" name="email" id="profileEmail" disabled class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400 cursor-not-allowed">
                    </div>
                    <div>
                        <label for="profileRole" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Função</label>
                        <input type="text" name="role" id="profileRole" disabled class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400 cursor-not-allowed">
                    </div>
                    <div>
                        <label for="profileDateOfBirth" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Data de Nascimento</label>
                        <input type="date" name="date_of_birth" id="profileDateOfBirth" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="profilePhoneNumber" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Telefone</label>
                        <input type="tel" name="phone_number" id="profilePhoneNumber" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="profileGender" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Gênero</label>
                        <select name="gender" id="profileGender" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Selecione</option>
                            <option value="masculino">Masculino</option>
                            <option value="feminino">Feminino</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div>
                        <label for="profileProfilePictureUrl" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL da Foto de Perfil</label>
                        <input type="url" name="profile_picture_url" id="profileProfilePictureUrl" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="profileBio" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Bio</label>
                        <textarea name="bio" id="profileBio" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    <div class="flex justify-end pt-4 border-t border-slate-200 dark:border-slate-700 mt-6">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-all">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>

            <!-- Alterar Senha -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">Alterar Senha</h2>
                <form id="passwordForm" class="space-y-4">
                    <div>
                        <label for="oldPassword" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Senha Antiga</label>
                        <input type="password" name="old_password" id="oldPassword" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="newPassword" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nova Senha</label>
                        <input type="password" name="new_password" id="newPassword" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="confirmPassword" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirmar Nova Senha</label>
                        <input type="password" name="confirm_password" id="confirmPassword" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md shadow-sm bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="flex justify-end pt-4 border-t border-slate-200 dark:border-slate-700 mt-6">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-all">
                            Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/components.js"></script>
    <script src="assets/js/profile.js"></script> <!-- New JS file for profile management -->
</body>
</html>
