<?php
$userName = "Usuario";
$userInitials = "US";
$currentPage = $_GET['page'] ?? 'registros';
$userRole = null;

if (isset($_COOKIE['access_token'])) {
    $payload = SimpleJWT::decode($_COOKIE['access_token']);
    if ($payload && isset($payload['name'])) {
        $userName = $payload['name'];
        $words = explode(" ", $userName);
        $userInitials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
        $userRole = $payload['role'] ?? null;
    }
}

$navItems = [
    'registros' => ['label' => 'Registros', 'roles' => null],
    'import_export' => ['label' => 'Importar/Exportar', 'roles' => null],
    'management' => ['label' => 'Entidades', 'roles' => null],
    'feedbacks' => ['label' => 'Feedback', 'roles' => null],
];
?>

<header class="sticky top-0 z-50 bg-white/90 dark:bg-slate-900/90 backdrop-blur-lg border-b border-slate-200/80 dark:border-slate-800 shadow-sm transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <div class="flex items-center gap-8">
                <div class="flex-shrink-0 flex items-center gap-2 text-primary dark:text-indigo-400 cursor-pointer hover:opacity-80 transition-opacity">
                    <div class="bg-primary/10 dark:bg-indigo-500/20 p-2 rounded-lg">
                        <i class="fa-solid fa-bolt text-xl"></i>
                    </div>
                    <span class="font-bold text-xl tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-primary to-indigo-600 dark:from-indigo-400 dark:to-purple-400">Effecta</span>
                </div>

                <nav class="hidden lg:flex space-x-1">
                    <?php foreach ($navItems as $key => $item): ?>
                        <?php
                        $isActive = ($currentPage === $key);
                        $baseClasses = "px-4 py-2 rounded-full text-sm font-medium transition-all duration-200";
                        $activeClasses = "bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-white shadow-sm";
                        $inactiveClasses = "text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50";
                        ?>
                        <a href="index.php?page=<?= $key ?>" class="<?= $baseClasses ?> <?= $isActive ? $activeClasses : $inactiveClasses ?>">
                            <?= $item['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <?php if ($userRole === 'dev'): ?>
                    <div id="globalDevAlert" class="hidden flex items-center justify-center h-10 w-10 rounded-full bg-red-50 dark:bg-red-900/20 text-red-500 animate-pulse cursor-help" title="Feedbacks Críticos!">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                <?php endif; ?>

                <button id="themeToggle" class="p-2 h-10 w-10 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 flex items-center justify-center" title="Alternar Tema">
                    <i class="fa-solid fa-moon text-lg dark:hidden"></i>
                    <i class="fa-solid fa-sun text-lg hidden dark:block"></i>
                </button>

                <!-- Modificado: Perfil e Opções agora ficam num Menu Dropdown -->
                <div class="relative ml-1" id="userMenuContainer">
                    <button id="userMenuBtn" class="flex items-center gap-2 focus:outline-none" title="Menu do Usuário">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-indigo-100 to-primary/20 dark:from-indigo-900 dark:to-purple-900 flex items-center justify-center text-primary dark:text-indigo-300 font-bold border-2 border-white dark:border-slate-800 shadow-sm ring-2 ring-transparent hover:ring-primary/30 transition-all">
                            <?= htmlspecialchars($userInitials) ?>
                        </div>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 hidden sm:block"></i>
                    </button>

                    <div id="userDropdown" class="hidden absolute right-0 mt-2 w-52 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-2 transform opacity-0 scale-95 transition-all duration-200 origin-top-right">
                        <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700/50 mb-2">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate"><?= htmlspecialchars($userName) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 capitalize"><?= htmlspecialchars($userRole ?? 'Usuário') ?></p>
                        </div>

                        <a href="index.php?page=profile" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-indigo-400 transition-colors">
                            <i class="fa-solid fa-user w-5 text-center mr-1"></i> Meu Perfil
                        </a>

                        <?php if (in_array($userRole, ['admin', 'dev'])): ?>
                            <a href="index.php?page=users" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-indigo-400 transition-colors">
                                <i class="fa-solid fa-users-gear w-5 text-center mr-1"></i> Usuários
                            </a>
                        <?php endif; ?>

                        <?php if ($userRole === 'dev'): ?>
                            <a href="index.php?page=feedback_management" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-primary dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-indigo-400 transition-colors">
                                <i class="fa-solid fa-bug w-5 text-center mr-1"></i> Feedbacks ADM
                            </a>
                        <?php endif; ?>

                        <div class="border-t border-slate-100 dark:border-slate-700/50 mt-2 pt-2">
                            <button onclick="handleLogout()" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/10 transition-colors">
                                <i class="fa-solid fa-right-from-bracket w-5 text-center mr-1"></i> Sair do Sistema
                            </button>
                        </div>
                    </div>
                </div>

                <button class="lg:hidden ml-1 p-2 h-10 w-10 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 transition-colors flex items-center justify-center">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
            </div>

        </div>
    </div>
</header>

<script>
    async function handleLogout() {
        try {
            await fetch("api/index.php?action=logout", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                }
            });
        } catch (err) {}

        localStorage.removeItem("access_token");
        localStorage.removeItem("user_name");
        localStorage.removeItem("user_role");
        document.cookie = "access_token=; max-age=0; path=/";
        window.location.href = "index.php?page=login";
    }

    // Modificado: Lógica pra abrir e fechar o dropdown lindão
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
            setTimeout(() => {
                userDropdown.classList.toggle('opacity-0');
                userDropdown.classList.toggle('scale-95');
            }, 10);
        });

        document.addEventListener('click', (e) => {
            if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.add('opacity-0', 'scale-95');
                setTimeout(() => userDropdown.classList.add('hidden'), 200);
            }
        });
    }
</script>