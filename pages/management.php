<?php
include_once dirname(__DIR__) . '/src/components/head.php';
?>

<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col">

    <?php
    include_once dirname(__DIR__) . '/src/components/header.php';
    ?>

    <main class="flex-grow max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gerenciamento de Entidades</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Gerencie seus projetos e autores de feedback de forma centralizada.</p>
        </div>

        <!-- Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-700 mb-6">
            <nav class="flex space-x-8" aria-label="Tabs">
                <button onclick="switchTab('projects')" id="tab-projects" class="border-primary text-primary whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all">
                    <i class="fa-solid fa-folder mr-2"></i> Projetos
                </button>
                <button onclick="switchTab('people')" id="tab-people" class="border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all">
                    <i class="fa-solid fa-user-circle mr-2"></i> Autores de Feedback
                </button>
            </nav>
        </div>

        <!-- Tab Content: Projects -->
        <div id="content-projects" class="tab-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Lista de Projetos</h2>
                <button onclick="openManagementModal('projects', 'create')" class="bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-md flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Novo Projeto
                </button>
            </div>
            <div class="bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nome do Projeto</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Criado em</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody id="projectsTableBody" class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800">
                        <!-- Content loaded by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Content: People -->
        <div id="content-people" class="tab-content hidden">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Autores de Feedback</h2>
                <button onclick="openManagementModal('people', 'create')" class="bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-md flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Novo Autor
                </button>
            </div>
            <div class="bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nome</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Criado em</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody id="peopleTableBody" class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-800">
                        <!-- Content loaded by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Management Modal -->
    <div id="managementModal" class="modal hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeManagementModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-semibold text-slate-900 dark:text-white" id="managementModalTitle">
                        Gerenciar
                    </h3>
                    <button type="button" onclick="closeManagementModal()" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300 transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <form id="managementForm" class="px-6 py-5">
                    <input type="hidden" id="managementIdHidden">
                    <input type="hidden" id="managementTypeHidden">

                    <div>
                        <label for="managementName" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome *</label>
                        <input type="text" id="managementName" required class="w-full form-input" placeholder="Digite o nome...">
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" onclick="closeManagementModal()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium transition-colors shadow-sm">
                            Cancelar
                        </button>
                        <button type="submit" id="managementSubmitBtn" class="px-4 py-2 bg-primary hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors shadow-md flex items-center gap-2">
                            <i class="fa-solid fa-check"></i> Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/components.js"></script>
    <script src="assets/js/management.js"></script>
</body>

</html>