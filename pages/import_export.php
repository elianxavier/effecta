<?php
include_once dirname(__DIR__) . '/src/components/head.php';
?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col">

    <?php
    include_once dirname(__DIR__) . '/src/components/header.php';
    ?>

    <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Importar e Exportar Dados</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2">Gerencie seus dados de registros, projetos e pessoas de forma centralizada.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Export Section -->
            <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col h-full">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-xl flex items-center justify-center text-primary dark:text-indigo-400">
                        <i class="fa-solid fa-file-export text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Exportar</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Baixe todos os seus dados.</p>
                    </div>
                </div>
                
                <p class="text-slate-600 dark:text-slate-300 mb-8 flex-grow">
                    Esta ação criará um arquivo JSON contendo todos os seus <strong>registros</strong>, <strong>projetos</strong> e <strong>pessoas</strong> cadastradas. Use isso para fazer backups ou migrar seus dados.
                </p>

                <button onclick="exportData()" class="w-full bg-primary hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                    <i class="fa-solid fa-download"></i> Baixar Arquivo JSON
                </button>
            </div>

            <!-- Import Section -->
            <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col h-full">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/50 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <i class="fa-solid fa-file-import text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Importar</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Restaure seus dados de um arquivo.</p>
                    </div>
                </div>

                <p class="text-slate-600 dark:text-slate-300 mb-6">
                    Selecione um arquivo JSON exportado anteriormente para importar seus dados. 
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium block mt-2">
                        <i class="fa-solid fa-circle-check mr-1"></i> Sincronização Inteligente: O sistema detectará duplicatas e atualizará registros existentes se houver alterações.
                    </span>
                </p>

                <div class="mt-auto">
                    <label class="block mb-4">
                        <span class="sr-only">Escolher arquivo</span>
                        <input type="file" id="importFile" accept=".json" class="block w-full text-sm text-slate-500 dark:text-slate-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-full file:border-0
                            file:text-sm file:font-semibold
                            file:bg-indigo-50 file:text-primary
                            hover:file:bg-indigo-100
                            dark:file:bg-slate-700 dark:file:text-indigo-300
                            cursor-pointer
                        "/>
                    </label>

                    <button onclick="importData()" id="importButton" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-upload"></i> Importar Dados
                    </button>
                </div>
            </div>
        </div>

        <div id="statusMessage" class="mt-8 hidden p-4 rounded-xl text-center font-medium"></div>

        <!-- Import Results Summary -->
        <div id="importResults" class="mt-8 hidden bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-indigo-500"></i> Resumo da Importação
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="text-slate-500 border-b border-slate-100 dark:border-slate-700">
                            <th class="py-2 font-semibold">Tipo</th>
                            <th class="py-2 font-semibold">Adicionados</th>
                            <th class="py-2 font-semibold">Existentes</th>
                            <th class="py-2 font-semibold">Atualizados</th>
                        </tr>
                    </thead>
                    <tbody id="summaryTable">
                    </tbody>
                </table>
            </div>

            <div id="logsContainer" class="mt-6 hidden">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Observações:</h4>
                <ul id="logsList" class="list-none space-y-1">
                </ul>
            </div>
            
            <div class="mt-6 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-lg text-center">
                <p id="dbConfirmation" class="text-xs text-slate-500">Os dados acima foram confirmados diretamente com o banco de dados.</p>
            </div>
        </div>
    </main>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/api.js"></script>
    <script>
        async function exportData() {
            try {
                const data = await EffectaAPI.getExportData();
                
                const blob = new Blob([JSON.stringify(data, null, 4)], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `effecta_export_${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } catch (error) {
                console.error('Erro ao exportar dados:', error);
                showStatus('Erro ao exportar dados. Tente novamente.', 'error');
            }
        }

        async function importData() {
            const fileInput = document.getElementById('importFile');
            const button = document.getElementById('importButton');
            const resultsDiv = document.getElementById('importResults');
            
            if (!fileInput.files.length) {
                showStatus('Por favor, selecione um arquivo para importar.', 'error');
                return;
            }

            const file = fileInput.files[0];
            const reader = new FileReader();

            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Importando...';
            resultsDiv.classList.add('hidden');

            reader.onload = async (e) => {
                try {
                    const data = JSON.parse(e.target.result);
                    const response = await EffectaAPI.importData(data);

                    if (response.success) {
                        showStatus('Processamento concluído!', 'success');
                        displayImportSummary(response.result);
                        fileInput.value = '';
                    } else {
                        showStatus('Erro ao importar dados: ' + (response.error || 'Erro desconhecido'), 'error');
                    }
                } catch (error) {
                    console.error('Erro ao importar dados:', error);
                    showStatus('Erro ao processar o arquivo. Verifique se é um JSON válido.', 'error');
                } finally {
                    button.disabled = false;
                    button.innerHTML = '<i class="fa-solid fa-upload"></i> Importar Dados';
                }
            };

            reader.readAsText(file);
        }

        function displayImportSummary(result) {
            const resultsDiv = document.getElementById('importResults');
            const summaryTable = document.getElementById('summaryTable');
            const logsList = document.getElementById('logsList');
            const logsContainer = document.getElementById('logsContainer');

            resultsDiv.classList.remove('hidden');
            
            summaryTable.innerHTML = `
                <tr class="border-b border-slate-100 dark:border-slate-700">
                    <td class="py-2 font-medium">Projetos</td>
                    <td class="py-2 text-emerald-600 font-bold">${result.projects.added.length}</td>
                    <td class="py-2 text-slate-500">${result.projects.existing}</td>
                    <td class="py-2">-</td>
                </tr>
                <tr class="border-b border-slate-100 dark:border-slate-700">
                    <td class="py-2 font-medium">Pessoas</td>
                    <td class="py-2 text-emerald-600 font-bold">${result.people.added.length}</td>
                    <td class="py-2 text-slate-500">${result.people.existing}</td>
                    <td class="py-2">-</td>
                </tr>
                <tr>
                    <td class="py-2 font-medium">Registros</td>
                    <td class="py-2 text-emerald-600 font-bold">${result.registers.added.length}</td>
                    <td class="py-2 text-slate-500">${result.registers.unchanged}</td>
                    <td class="py-2 text-indigo-600 font-bold">${result.registers.updated.length}</td>
                </tr>
            `;

            let logs = [];
            
            if (result.people.added.length > 0) {
                logs.push(`<strong>Pessoas adicionadas:</strong> ${result.people.added.join(', ')}`);
            }
            if (result.projects.added.length > 0) {
                logs.push(`<strong>Projetos adicionados:</strong> ${result.projects.added.join(', ')}`);
            }
            if (result.registers.added.length > 0) {
                logs.push(`<strong>Atividades novas:</strong> ${result.registers.added.join(', ')}`);
            }
            if (result.registers.updated.length > 0) {
                logs.push(`<strong>Atividades atualizadas:</strong> ${result.registers.updated.join(', ')}`);
            }
            
            if (result.registers.errors && result.registers.errors.length > 0) {
                result.registers.errors.forEach(err => logs.push(`<span class="text-red-500">${err}</span>`));
            }

            if (logs.length > 0) {
                logsContainer.classList.remove('hidden');
                logsList.innerHTML = logs.map(log => `<li class="text-sm text-slate-600 dark:text-slate-400 mb-2 p-2 bg-slate-50 dark:bg-slate-900/30 rounded border-l-4 border-indigo-400">${log}</li>`).join('');
            } else {
                logsContainer.classList.add('hidden');
            }

            // Atualiza o rodapé com os totais reais do banco
            const dbSummary = result.final_db_summary;
            document.getElementById('dbConfirmation').innerHTML = `
                Total no seu perfil agora: <strong>${dbSummary.projects_count}</strong> Projetos, 
                <strong>${dbSummary.people_count}</strong> Pessoas e 
                <strong>${dbSummary.registers_count}</strong> Registros.
            `;
        }

        function showStatus(message, type) {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.textContent = message;
            statusDiv.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-emerald-100', 'text-emerald-700', 'dark:bg-red-900/30', 'dark:text-red-400', 'dark:bg-emerald-900/30', 'dark:text-emerald-400');
            
            if (type === 'error') {
                statusDiv.classList.add('bg-red-100', 'text-red-700', 'dark:bg-red-900/30', 'dark:text-red-400');
            } else {
                statusDiv.classList.add('bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-900/30', 'dark:text-emerald-400');
            }
            
            // Do not hide if it's success to keep the summary visible
            if (type === 'error') {
                setTimeout(() => {
                    statusDiv.classList.add('hidden');
                }, 5000);
            }
        }
    </script>
</body>
</html>
