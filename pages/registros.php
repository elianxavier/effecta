<?php
// Inclui o cabeçalho comum de metatags e bibliotecas
include_once dirname(__DIR__) . '/src/components/head.php';
?>

<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col font-sans">

    <?php
    // Inclui o cabeçalho visual superior (opcional)
    include_once dirname(__DIR__) . '/src/components/header.php';
    ?>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <!-- Header da Página -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-8">
            <div>
                <h1 class="text-3xl sm:text-4xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-500 dark:from-white dark:to-slate-400 tracking-tight mb-2">Seus Registros</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm sm:text-base">Acompanhe seu progresso e impacto gerado nos projetos.</p>
            </div>

            <div class="flex flex-col sm:flex-row w-full md:w-auto gap-3">
                <div class="relative w-full sm:w-64 md:w-72 group">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    <input type="text" id="searchInput" placeholder="Buscar em tudo..." class="w-full pl-11 pr-4 py-2.5 border-2 border-slate-200 dark:border-slate-700/80 rounded-xl bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm focus:bg-white dark:focus:bg-slate-800 focus:ring-0 focus:border-indigo-500 outline-none transition-all dark:text-white shadow-sm placeholder:text-slate-400 text-sm">
                </div>
                <button onclick="openModal()" class="flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-600 to-primary hover:from-indigo-700 hover:to-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-indigo-200 dark:shadow-none hover:shadow-xl hover:-translate-y-0.5 whitespace-nowrap border border-indigo-500">
                    <i class="fa-solid fa-plus text-sm"></i> Novo Registro
                </button>
            </div>
        </div>

        <!-- Barra de Filtros Refatorada -->
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-md p-5 rounded-2xl shadow-sm border border-slate-200/60 dark:border-slate-700 mb-8 flex flex-wrap gap-5 items-end relative z-10">
            <div class="flex items-center gap-2 w-full mb-2">
                <i class="fa-solid fa-filter text-slate-400 text-sm"></i>
                <span class="text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Filtros</span>
            </div>

            <div class="flex-1 min-w-[220px] relative group" id="filterProjetoContainer">
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 ml-1">Projeto</label>
                <input type="hidden" id="filterProjeto">
                <div class="relative">
                    <input type="text" id="filterProjetoSearch" placeholder="Todos os Projetos..." class="w-full form-input bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 rounded-lg py-2 pl-3 pr-8 text-sm focus:border-indigo-500 cursor-pointer transition-colors" autocomplete="off">
                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors"></i>
                </div>
                <div id="filterProjetoDropdown" class="hidden absolute z-20 mt-2 w-full bg-white dark:bg-slate-800 shadow-xl rounded-xl border border-slate-100 dark:border-slate-700 max-h-56 overflow-y-auto custom-scrollbar transform opacity-0 scale-95 transition-all duration-200 origin-top">
                    <ul id="filterProjetoList" class="py-2 text-sm text-slate-700 dark:text-slate-300"></ul>
                </div>
            </div>

            <div class="flex-1 min-w-[220px] relative group" id="filterAutorContainer">
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 ml-1">Autor do Feedback</label>
                <input type="hidden" id="filterAutor">
                <div class="relative">
                    <input type="text" id="filterAutorSearch" placeholder="Todos os Autores..." class="w-full form-input bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 rounded-lg py-2 pl-3 pr-8 text-sm focus:border-indigo-500 cursor-pointer transition-colors" autocomplete="off">
                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors"></i>
                </div>
                <div id="filterAutorDropdown" class="hidden absolute z-20 mt-2 w-full bg-white dark:bg-slate-800 shadow-xl rounded-xl border border-slate-100 dark:border-slate-700 max-h-56 overflow-y-auto custom-scrollbar transform opacity-0 scale-95 transition-all duration-200 origin-top">
                    <ul id="filterAutorList" class="py-2 text-sm text-slate-700 dark:text-slate-300"></ul>
                </div>
            </div>

            <div class="flex-1 min-w-[220px] relative group" id="filterStatusContainer">
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 ml-1">Status</label>
                <input type="hidden" id="filterStatus">
                <div class="relative">
                    <input type="text" id="filterStatusSearch" placeholder="Todos os Status..." class="w-full form-input bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 rounded-lg py-2 pl-3 pr-8 text-sm focus:border-indigo-500 cursor-pointer transition-colors" autocomplete="off" readonly>
                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors"></i>
                </div>
                <div id="filterStatusDropdown" class="hidden absolute z-20 mt-2 w-full bg-white dark:bg-slate-800 shadow-xl rounded-xl border border-slate-100 dark:border-slate-700 max-h-56 overflow-y-auto custom-scrollbar transform opacity-0 scale-95 transition-all duration-200 origin-top">
                    <ul id="filterStatusList" class="py-2 text-sm text-slate-700 dark:text-slate-300"></ul>
                </div>
            </div>
        </div>

        <!-- Área de Resultados (Grid de Cards) -->
        <div id="results" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <!-- Os cards serão injetados aqui via JS. Certifique-se de atualizar o app.js para gerar cards com design premium (rounded-3xl, sombras suaves, etc) -->
        </div>
    </main>

    <!-- Modal Refatorado -->
    <div id="recordModal" class="modal hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Conteúdo do Modal -->
            <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-slate-200/50 dark:border-slate-700">

                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white" id="modal-title">
                            Registrar Progresso
                        </h3>
                    </div>
                    <button type="button" onclick="closeModal()" class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-100 dark:bg-slate-700 text-slate-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form id="effectaForm" class="px-8 py-6">
                    <input type="hidden" name="id" id="recordIdHidden">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Seção Principal -->
                        <div class="md:col-span-2 space-y-5">
                            <div class="relative" id="projetoSelectContainer">
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Projeto <span class="text-red-500">*</span></label>
                                <input type="hidden" name="projeto_id" id="projetoHidden" required>
                                <div class="relative">
                                    <input type="text" id="projetoSearch" placeholder="Pesquise ou adicione projeto..." class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 pl-4 pr-10 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm" autocomplete="off" required>
                                    <i class="fa-solid fa-search absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                </div>
                                <div id="projetoDropdown" class="hidden absolute z-30 mt-2 w-full bg-white dark:bg-slate-800 shadow-xl rounded-xl border border-slate-100 dark:border-slate-700 max-h-48 overflow-y-auto custom-scrollbar">
                                    <ul id="projetoList" class="py-2 text-sm text-slate-700 dark:text-slate-300"></ul>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Atividade Realizada <span class="text-red-500">*</span></label>
                                <input type="text" name="atividade" placeholder="Ex: Refatoração do módulo de relatórios" required class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm">
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="md:col-span-2 my-2 border-t border-dashed border-slate-200 dark:border-slate-700"></div>

                        <!-- Seção Tempo/Prazos -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Controle de Tempo e Prazo <span class="text-red-500">*</span></label>
                            <div class="flex flex-wrap gap-4 mb-4 p-1 bg-slate-100 dark:bg-slate-900/50 rounded-lg inline-flex">
                                <label class="flex-1 relative">
                                    <input type="radio" name="tipo_prazo" value="horas" checked class="peer sr-only" onchange="togglePrazoInput()">
                                    <div class="px-4 py-2 text-sm text-center font-medium rounded-md cursor-pointer transition-all text-slate-500 hover:text-slate-700 dark:text-slate-400 peer-checked:bg-white dark:peer-checked:bg-slate-700 peer-checked:text-indigo-600 dark:peer-checked:text-indigo-400 peer-checked:shadow-sm">
                                        Horas Trabalhadas
                                    </div>
                                </label>
                                <label class="flex-1 relative">
                                    <input type="radio" name="tipo_prazo" value="data" class="peer sr-only" onchange="togglePrazoInput()">
                                    <div class="px-4 py-2 text-sm text-center font-medium rounded-md cursor-pointer transition-all text-slate-500 hover:text-slate-700 dark:text-slate-400 peer-checked:bg-white dark:peer-checked:bg-slate-700 peer-checked:text-indigo-600 dark:peer-checked:text-indigo-400 peer-checked:shadow-sm">
                                        Data Limite
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div id="inputHorasContainer" class="w-full">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Estimativa (H:M)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fa-regular fa-clock text-slate-400"></i>
                                </div>
                                <input type="text" name="horas_trabalhadas" placeholder="Ex: 04:30" class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 pl-10 pr-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm">
                            </div>
                        </div>

                        <div id="inputDataContainer" class="hidden w-full">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Data Limite Planejada</label>
                            <input type="date" name="prazo" class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm">
                        </div>

                        <div id="entregaHorasContainer" class="w-full">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tempo Gasto (H:M)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-stopwatch text-slate-400"></i>
                                </div>
                                <input type="text" name="horas_gastas" placeholder="Ex: 06:15" class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 pl-10 pr-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm">
                            </div>
                        </div>

                        <div id="entregaDataContainer" class="hidden w-full">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Data de Entrega Real</label>
                            <input type="date" name="data_entrega" class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm">
                        </div>

                        <!-- Divider -->
                        <div class="md:col-span-2 my-2 border-t border-dashed border-slate-200 dark:border-slate-700"></div>

                        <!-- Seção Detalhes -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Meta Relacionada</label>
                            <input type="text" name="meta" placeholder="Qual objetivo isso atende?" class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Contribuição Relevante</label>
                            <textarea name="contribuicao" rows="2" placeholder="Descreva brevemente sua contribuição..." class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm resize-none"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Impacto Gerado</label>
                            <textarea name="impacto" rows="2" placeholder="Qual foi o resultado prático disso?" class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm resize-none"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Treinamentos Aplicados</label>
                            <textarea name="treinamentos" rows="2" placeholder="Cursos, workshops..." class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm resize-none"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Stakeholders Envolvidos</label>
                            <textarea name="stakeholders" rows="2" placeholder="Separe por vírgula (Ex: João, Maria)" class="w-full form-input bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-600 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm resize-none"></textarea>
                        </div>

                        <!-- Seção Feedback Box -->
                        <div class="md:col-span-2 mt-4 bg-indigo-50/50 dark:bg-indigo-900/10 p-5 rounded-2xl border border-indigo-100 dark:border-indigo-800/30">
                            <h4 class="text-sm font-bold mb-4 text-indigo-800 dark:text-indigo-300 flex items-center gap-2">
                                <i class="fa-solid fa-comment-medical"></i> Adicionar Feedback Recebido
                            </h4>

                            <div class="grid grid-cols-1 gap-4">
                                <div class="relative" id="autorSelectContainer">
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Autor do Feedback</label>
                                    <input type="hidden" name="pessoa_feedback_id" id="autorFeedbackHidden">
                                    <div class="relative">
                                        <input type="text" id="autorFeedbackSearch" placeholder="Pesquise ou adicione novo autor..." class="w-full form-input bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-600 rounded-xl py-2 pl-4 pr-10 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm" autocomplete="off">
                                        <i class="fa-solid fa-user-pen absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    </div>

                                    <div id="autorDropdown" class="hidden absolute z-30 mt-2 w-full bg-white dark:bg-slate-800 shadow-xl rounded-xl border border-slate-100 dark:border-slate-700 max-h-48 overflow-y-auto custom-scrollbar">
                                        <ul id="autorList" class="py-2 text-sm text-slate-700 dark:text-slate-300"></ul>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Conteúdo do Feedback</label>
                                    <textarea name="feedbacks" rows="2" placeholder="Escreva o feedback que você recebeu sobre esta atividade..." class="w-full form-input bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-600 rounded-xl py-2 px-4 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm resize-none"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-700/50 flex justify-end gap-3">
                        <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-bold transition-colors shadow-sm">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-primary hover:from-indigo-700 hover:to-indigo-600 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-200 dark:shadow-none hover:-translate-y-0.5 flex items-center gap-2 border border-indigo-500">
                            <i class="fa-solid fa-floppy-disk"></i> Salvar Registro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Adicione isso para animar os dropdowns. Pode colocar no seu CSS ou num arquivo separado futuramente. -->
    <style>
        /* Ajuste do scrollbar para os dropdowns */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            /* slate-300 */
            border-radius: 20px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #475569;
            /* slate-600 */
        }
    </style>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/components.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        // Lógica visual básica para os dropdowns de filtro (ajuste conforme seu JS existente)
        document.addEventListener('DOMContentLoaded', () => {
            const setups = [{
                    input: 'filterProjetoSearch',
                    drop: 'filterProjetoDropdown'
                },
                {
                    input: 'filterAutorSearch',
                    drop: 'filterAutorDropdown'
                },
                {
                    input: 'filterStatusSearch',
                    drop: 'filterStatusDropdown'
                }
            ];

            setups.forEach(({
                input,
                drop
            }) => {
                const i = document.getElementById(input);
                const d = document.getElementById(drop);
                if (i && d) {
                    i.addEventListener('focus', () => {
                        d.classList.remove('hidden');
                        setTimeout(() => {
                            d.classList.remove('opacity-0', 'scale-95');
                        }, 10);
                    });

                    // Simple click outside to close (seu app.js provavelmente já trata isso melhor)
                    document.addEventListener('click', (e) => {
                        if (!i.contains(e.target) && !d.contains(e.target)) {
                            d.classList.add('opacity-0', 'scale-95');
                            setTimeout(() => {
                                d.classList.add('hidden');
                            }, 200);
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>