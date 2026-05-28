<?php
include_once dirname(__DIR__) . '/src/components/head.php';
?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col">

    <?php include_once dirname(__DIR__) . '/src/components/header.php'; ?>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
        <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Central de Feedback</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2">Relate bugs, sugira melhorias ou envie um elogio para a equipe.</p>
            </div>
            
            <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                <button onclick="loadFeedbacks('active')" data-tab="active" class="tab-btn px-4 py-2 text-sm font-medium rounded-lg border-b-2 border-primary text-primary transition-all">
                    Ativos
                </button>
                <button onclick="loadFeedbacks('resolved')" data-tab="resolved" class="tab-btn px-4 py-2 text-sm font-medium rounded-lg border-b-2 border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-all">
                    Resolvidos
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Formulário de Envio (1/4) -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 sticky top-24">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square text-primary"></i> Novo Relato
                    </h2>

                    <form id="feedbackForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo de Feedback</label>
                            <select name="type" required class="w-full form-input">
                                <option value="bug">Erro / Bug (Público)</option>
                                <option value="feature">Ideia / Funcionalidade</option>
                                <option value="improvement">Melhoria</option>
                                <option value="elogio">Elogio</option>
                                <option value="other">Outro</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Assunto Curto</label>
                            <input type="text" name="subject" required placeholder="Ex: Erro ao importar JSON" class="w-full form-input">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descrição Detalhada</label>
                            <textarea name="message" required rows="5" placeholder="Descreva aqui os detalhes..." class="w-full form-input resize-none"></textarea>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                            <i class="fa-solid fa-paper-plane"></i> Enviar Feedback
                        </button>
                    </form>
                </div>
            </div>

            <!-- Visualização de Ativos (Duas Colunas) -->
            <div id="activeView" class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Coluna 1: Meus Feedbacks -->
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-user-tag text-indigo-500"></i> Meus Relatos
                    </h2>
                    <div id="userFeedbackList" class="space-y-4">
                        <!-- JS -->
                    </div>
                </div>

                <!-- Coluna 2: Erros/Bugs Públicos -->
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-bug text-red-500"></i> Mural de Erros (Comunidade)
                    </h2>
                    <div id="bugFeedbackList" class="space-y-4">
                        <!-- JS -->
                    </div>
                </div>
            </div>

            <!-- Visualização de Resolvidos (Coluna Única) -->
            <div id="resolvedView" class="lg:col-span-3 hidden">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-check-double text-emerald-500"></i> Atendidos Recentemente
                </h2>
                <div id="resolvedFeedbackList" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- JS -->
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/components.js"></script>
    <script src="assets/js/feedback.js"></script>
</body>
</html>
