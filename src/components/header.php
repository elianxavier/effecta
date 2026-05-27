<?php
$userName = "Usuario";
$userInitials = "US";
$currentPage = $_GET['page'] ?? 'registros'; // Get current page from URL

if (isset($_COOKIE['access_token'])) {
    $payload = SimpleJWT::decode($_COOKIE['access_token']);
    if ($payload && isset($payload['name'])) {
        $userName = $payload['name'];
        $words = explode(" ", $userName);
        $userInitials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
    }
}
?>
<header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center gap-8">
                <div class="flex-shrink-0 flex items-center gap-2 text-primary dark:text-indigo-400">
                    <i class="fa-solid fa-bolt text-2xl"></i>
                    <span class="font-bold text-xl tracking-tight">Effecta</span>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="index.php?page=registros" class="<?= ($currentPage === 'registros') ? 'border-b-2 border-primary text-slate-900 dark:text-white' : 'border-b-2 border-transparent hover:border-primary text-slate-700 dark:text-slate-300' ?> px-1 pt-1 text-sm font-medium">Registros</a>
                    <a href="index.php?page=import_export" class="<?= ($currentPage === 'import_export') ? 'border-b-2 border-primary text-slate-900 dark:text-white' : 'border-b-2 border-transparent hover:border-primary text-slate-700 dark:text-slate-300' ?> px-1 pt-1 text-sm font-medium">Importar/Exportar</a>
                    <a href="index.php?page=management" class="<?= ($currentPage === 'management') ? 'border-b-2 border-primary text-slate-900 dark:text-white' : 'border-b-2 border-transparent hover:border-primary text-slate-700 dark:text-slate-300' ?> px-1 pt-1 text-sm font-medium">Gerenciar Entidades</a>
                    <?php if (isset($payload['role']) && $payload['role'] === 'admin'): ?>
                        <a href="index.php?page=users" class="<?= ($currentPage === 'users') ? 'border-b-2 border-primary text-slate-900 dark:text-white' : 'border-b-2 border-transparent hover:border-primary text-slate-700 dark:text-slate-300' ?> px-1 pt-1 text-sm font-medium">Gerenciamento de Usuários</a>
                    <?php endif; ?>
                    <a href="index.php?page=profile" class="<?= ($currentPage === 'profile') ? 'border-b-2 border-primary text-slate-900 dark:text-white' : 'border-b-2 border-transparent hover:border-primary text-slate-700 dark:text-slate-300' ?> px-1 pt-1 text-sm font-medium">Meu Perfil</a>
                </nav>
            </div>
            <div class="flex items-center gap-4">
                <span class="hidden sm:inline text-xs font-semibold text-slate-500 dark:text-slate-400">Olá, <strong class="text-slate-700 dark:text-slate-200"><?= htmlspecialchars($userName) ?></strong></span>

                <button id="themeToggle" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors text-slate-500 dark:text-slate-400" title="Alternar Tema">
                    <i class="fa-solid fa-moon text-lg dark:hidden"></i>
                    <i class="fa-solid fa-sun text-lg hidden dark:block"></i>
                </button>

                <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-primary dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-700" title="<?= htmlspecialchars($userName) ?>">
                    <?= htmlspecialchars($userInitials) ?>
                </div>

                <button onclick="handleLogout()" class="p-2 rounded-full hover:bg-red-50 dark:hover:bg-red-950/20 text-red-500 dark:text-red-400 transition-colors" title="Sair do Sistema">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
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

        // Limpa tokens
        localStorage.removeItem("access_token");
        localStorage.removeItem("user_name");
        localStorage.removeItem("user_role");

        // Deleta cookie
        document.cookie = "access_token=; max-age=0; path=/";

        // Redireciona
        window.location.href = "index.php?page=login";
    }
</script>