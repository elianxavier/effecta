<?php
// Inclui o head comum
include_once dirname(__DIR__) . '/src/components/head.php';
?>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col justify-center items-center p-4">

    <!-- Container do Card de Login -->
    <div class="w-full max-w-md bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xl p-8 flex flex-col gap-6 transition-all duration-300 hover:shadow-2xl">
        
        <!-- Logo e Titulo -->
        <div class="flex flex-col items-center text-center gap-2">
            <div class="h-12 w-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-indigo-500/20">
                <i class="fa-solid fa-bolt"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2">Acesse sua conta</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Insira suas credenciais do Effecta Tracker</p>
        </div>

        <!-- Formulário de Login -->
        <form id="loginForm" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-xs font-semibold text-slate-500 dark:text-slate-400">Email *</label>
                <div class="relative">
                    <i class="fa-regular fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="email" id="email" name="email" required placeholder="exemplo@effecta.com" class="w-full pl-9 form-input">
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-xs font-semibold text-slate-500 dark:text-slate-400">Senha *</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full pl-9 form-input">
                </div>
            </div>

            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-250 flex items-center justify-center gap-2 mt-2">
                <i class="fa-solid fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <!-- Divisor -->
        <div class="relative flex items-center justify-center my-1">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
            </div>
            <span class="relative bg-white dark:bg-slate-800 px-3 text-xs text-slate-400 dark:text-slate-500">ou entre com</span>
        </div>

        <!-- Login com Google -->
        <div class="flex justify-center w-full">
            <!-- Google Identity Services -->
            <script src="https://accounts.google.com/gsi/client" async defer></script>
            
            <div id="g_id_onload"
                 data-client_id="610767295925-66chlec1n5935cpkicqvfknei9jt1qdt.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleGoogleLogin"
                 data-auto_prompt="false">
            </div>

            <div class="g_id_signin w-full flex justify-center"
                 data-type="standard"
                 data-shape="rectangular"
                 data-theme="outline"
                 data-text="signin_with"
                 data-size="large"
                 data-logo_alignment="left"
                 data-width="320">
            </div>
        </div>

    </div>

    <!-- Scripts utilitários de autenticação -->
    <script src="assets/js/components.js"></script>
    <script>
        // Handler do Formulário de Login Tradicional
        document.getElementById("loginForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;
            
            const btn = e.target.querySelector('button[type="submit"]');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Entrando...';
            btn.disabled = true;

            try {
                const res = await fetch("api/index.php?action=login", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ email, password })
                });

                const data = await res.json();
                if (res.ok) {
                    saveSessionAndRedirect(data);
                } else {
                    showToast(data.error || "Erro ao fazer login.", "error");
                }
            } catch (err) {
                showToast("Erro ao conectar com a API.", "error");
            } finally {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        });

        // Handler do Google Login Callback
        async function handleGoogleLogin(response) {
            try {
                const res = await fetch("api/index.php?action=google_login", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id_token: response.credential })
                });

                const data = await res.json();
                if (res.ok) {
                    saveSessionAndRedirect(data);
                } else {
                    showToast(data.error || "Erro no Login com Google.", "error");
                }
            } catch (err) {
                showToast("Erro de conexao na autenticacao do Google.", "error");
            }
        }

        // Helper para salvar tokens e redirecionar
        function saveSessionAndRedirect(data) {
            // Guarda tokens no localStorage
            localStorage.setItem("access_token", data.access_token);
            localStorage.setItem("user_name", data.user.name);
            localStorage.setItem("user_role", data.user.role);

            // Grava cookie do access_token de forma que o PHP consiga ler no redirecionamento
            // Expira em 15 minutos (900 segundos)
            document.cookie = `access_token=${data.access_token}; max-age=900; path=/`;

            showToast("Acesso autorizado! Redirecionando...", "success");
            setTimeout(() => {
                window.location.href = "index.php?page=registros";
            }, 1000);
        }
    </script>
</body>
</html>
