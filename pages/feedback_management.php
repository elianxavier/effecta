<?php
include_once dirname(__DIR__) . '/src/components/head.php';
?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col">
    <?php include_once dirname(__DIR__) . '/src/components/header.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Gerenciamento de Feedbacks</h1>
            <div id="alertsBadge" class="hidden bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 px-4 py-2 rounded-full font-bold animate-pulse">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i> Feedbacks Críticos!
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Tipo/Assunto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Mensagem</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Reports</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="feedbackTableBody" class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        <!-- Conteúdo injetado via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/components.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", loadFeedbackStats);

        async function loadFeedbackStats() {
            try {
                const data = await EffectaAPI.request('api/index.php?action=get_feedback_stats');
                renderFeedbacks(data.all);
                
                if (data.alerts_count > 0) {
                    const badge = document.getElementById('alertsBadge');
                    badge.classList.remove('hidden');
                    badge.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-2"></i> ${data.alerts_count} Feedbacks Críticos (>5 denúncias)!`;
                }
            } catch (err) {
                console.error(err);
                showToast("Erro ao carregar feedbacks.", "error");
            }
        }

        function renderFeedbacks(feedbacks) {
            const tableBody = document.getElementById("feedbackTableBody");
            tableBody.innerHTML = "";

            feedbacks.forEach(f => {
                const row = tableBody.insertRow();
                row.className = f.reports > 5 ? 'bg-red-50 dark:bg-red-900/10' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50';

                const typeBadge = `<span class="px-2 py-1 text-xs font-bold rounded ${f.type === 'bug' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}">${f.type.toUpperCase()}</span>`;
                row.insertCell(0).innerHTML = `<div class="font-semibold">${f.subject}</div>${typeBadge}`;
                row.insertCell(1).className = "px-6 py-4 text-sm max-w-xs truncate";
                row.cells[1].textContent = f.message;
                
                row.insertCell(2).innerHTML = `<select onchange="updateFeedbackStatus(${f.id}, this.value)" class="text-sm border-none bg-transparent focus:ring-0">
                    <option value="pendente" ${f.status === 'pendente' ? 'selected' : ''}>Pendente</option>
                    <option value="analise" ${f.status === 'analise' ? 'selected' : ''}>Em Análise</option>
                    <option value="concluido" ${f.status === 'concluido' ? 'selected' : ''}>Concluído</option>
                </select>`;

                row.insertCell(3).innerHTML = `<div class="flex items-center gap-2">
                    <span class="font-bold ${f.reports > 5 ? 'text-red-600' : ''}">${f.reports}</span>
                    ${f.hidden_by_reports ? '<i class="fa-solid fa-eye-slash text-slate-400" title="Oculto automaticamente"></i>' : ''}
                </div>`;

                const actionsCell = row.insertCell(4);
                actionsCell.className = "px-6 py-4 text-right space-x-2";
                actionsCell.innerHTML = `
                    <button onclick="markAsViewed(${f.id})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400" title="Marcar como Visto">
                        <i class="fa-solid ${f.viewed_by_dev ? 'fa-check-double' : 'fa-check'}"></i>
                    </button>
                    <button onclick="toggleHidden(${f.id}, ${f.hidden_by_reports})" class="text-slate-600 hover:text-slate-900 dark:text-slate-400" title="Alternar Visibilidade">
                        <i class="fa-solid ${f.hidden_by_reports ? 'fa-eye' : 'fa-eye-slash'}"></i>
                    </button>
                `;
            });
        }

        async function updateFeedbackStatus(id, status) {
            try {
                await EffectaAPI.request('api/index.php?action=admin_update_feedback', {
                    method: 'POST',
                    body: JSON.stringify({ id, status })
                });
                showToast("Status atualizado.", "success");
            } catch (err) { showToast("Erro ao atualizar status.", "error"); }
        }

        async function markAsViewed(id) {
            try {
                await EffectaAPI.request('api/index.php?action=admin_update_feedback', {
                    method: 'POST',
                    body: JSON.stringify({ id, viewed_by_dev: 1 })
                });
                loadFeedbackStats();
            } catch (err) { showToast("Erro ao atualizar.", "error"); }
        }

        async function toggleHidden(id, currentHidden) {
            try {
                await EffectaAPI.request('api/index.php?action=admin_update_feedback', {
                    method: 'POST',
                    body: JSON.stringify({ id, hidden_by_reports: !currentHidden })
                });
                loadFeedbackStats();
            } catch (err) { showToast("Erro ao atualizar.", "error"); }
        }
    </script>
</body>
</html>