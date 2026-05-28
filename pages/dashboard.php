<?php
include_once dirname(__DIR__) . '/src/components/head.php';
?>

<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col">
    <?php include_once dirname(__DIR__) . '/src/components/header.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8 max-w-7xl">
        <!-- INICIO ALTERACAO: Titulo com gradiente e alinhamento melhorado -->
        <div class="mb-10 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl sm:text-4xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-500 dark:from-white dark:to-slate-400 tracking-tight mb-2">Dashboard</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm sm:text-base">Bem-vindo de volta! Aqui está o resumo do seu impacto.</p>
            </div>
        </div>
        <!-- FIM ALTERACAO -->

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- INICIO ALTERACAO: Cards refatorados sem @apply e com design premium -->
            <div class="bg-white dark:bg-slate-800/80 p-6 rounded-3xl shadow-sm hover:shadow-xl border border-slate-200/60 dark:border-slate-700 flex items-center gap-5 transition-all duration-300 hover:-translate-y-1 group backdrop-blur-sm">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-solid fa-folder-tree"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Total Projetos</p>
                    <h2 id="totalProjects" class="text-3xl font-black text-slate-800 dark:text-white leading-none">--</h2>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800/80 p-6 rounded-3xl shadow-sm hover:shadow-xl border border-slate-200/60 dark:border-slate-700 flex items-center gap-5 transition-all duration-300 hover:-translate-y-1 group backdrop-blur-sm">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Total Registros</p>
                    <h2 id="totalRegisters" class="text-3xl font-black text-slate-800 dark:text-white leading-none">--</h2>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800/80 p-6 rounded-3xl shadow-sm hover:shadow-xl border border-slate-200/60 dark:border-slate-700 flex items-center gap-5 transition-all duration-300 hover:-translate-y-1 group backdrop-blur-sm">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl bg-sky-50 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">No Prazo (Mês)</p>
                    <h2 id="onTimeMonth" class="text-3xl font-black text-slate-800 dark:text-white leading-none">--</h2>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800/80 p-6 rounded-3xl shadow-sm hover:shadow-xl border border-slate-200/60 dark:border-slate-700 flex items-center gap-5 transition-all duration-300 hover:-translate-y-1 group backdrop-blur-sm">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl bg-rose-50 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Atrasados (Mês)</p>
                    <h2 id="delayedMonth" class="text-3xl font-black text-slate-800 dark:text-white leading-none">--</h2>
                </div>
            </div>
            <!-- FIM ALTERACAO -->
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 p-8 rounded-3xl shadow-sm border border-slate-200/60 dark:border-slate-700 relative overflow-hidden">
                <div class="flex items-center justify-between mb-8 relative z-10">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Comparativo de Desempenho</h3>
                    <span class="text-xs font-bold text-slate-500 bg-slate-100 dark:bg-slate-700/50 px-3 py-1.5 rounded-full uppercase tracking-wider">Geral</span>
                </div>
                <div class="h-64 relative z-10">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            <!-- INICIO ALTERACAO: Card lateral com gradiente novo e botão moderno -->
            <div class="bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-700 rounded-3xl p-8 text-white flex flex-col justify-between shadow-xl shadow-indigo-200/50 dark:shadow-none overflow-hidden relative group">
                <div class="absolute -right-4 -top-4 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-purple-500/30 rounded-full blur-3xl"></div>

                <i class="fa-solid fa-rocket absolute -right-4 -bottom-4 text-9xl text-white/10 -rotate-12 group-hover:-rotate-0 transition-all duration-500 group-hover:scale-110"></i>

                <div class="relative z-10">
                    <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold tracking-wider mb-4 border border-white/20">DICA</span>
                    <h3 class="text-2xl font-bold mb-3 leading-tight">Mantenha o Foco!</h3>
                    <p class="text-indigo-100 text-sm leading-relaxed mb-8 font-medium">Você está progredindo. Lembre-se de registrar suas horas gastas para manter as estatísticas precisas.</p>
                </div>
                <a href="index.php?page=registros" class="bg-white/90 backdrop-blur-sm text-indigo-700 font-extrabold py-3.5 px-6 rounded-2xl text-center hover:bg-white hover:scale-[1.02] transition-all duration-200 relative z-10 shadow-lg flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus text-sm"></i> Novo Registro
                </a>
            </div>
            <!-- FIM ALTERACAO -->
        </div>
    </main>

    <!-- INICIO ALTERACAO: Bloco de estilo removido pois quebrava sem build process -->
    <!-- FIM ALTERACAO -->

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/components.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>

</html>