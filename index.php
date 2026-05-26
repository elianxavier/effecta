<?php
require_once 'orm.php';

$orm = new EffectaORM('json');
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'add_person') {
        echo json_encode($orm->insert('people', ['name' => $input['name']]));
        exit;
    }

    if ($action === 'add_project') {
        echo json_encode($orm->insert('projects', ['name' => $input['name']]));
        exit;
    }

    echo json_encode($orm->insert('logs', $input));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action) {
    if ($action === 'get_people') {
        echo json_encode($orm->getAll('people'));
        exit;
    }
    if ($action === 'get_projects') {
        echo json_encode($orm->getAll('projects'));
        exit;
    }
    if ($action === 'search') {
        echo json_encode(array_values($orm->search('logs', $_GET['term'] ?? '')));
        exit;
    }
    if ($action === 'get_logs') {
        echo json_encode($orm->getAll('logs'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Effecta - Seu Tracker de Impacto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    },
                    colors: {
                        primary: '#4f46e5'
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .form-input {
                @apply px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-md bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-shadow shadow-sm text-sm;
            }
        }
    </style>
</head>

<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col">

    <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-8">
                    <div class="flex-shrink-0 flex items-center gap-2 text-primary dark:text-indigo-400">
                        <i class="fa-solid fa-bolt text-2xl"></i>
                        <span class="font-bold text-xl tracking-tight">Effecta</span>
                    </div>
                    <nav class="hidden md:flex space-x-8">
                        <a href="#" class="border-b-2 border-primary text-slate-900 dark:text-white px-1 pt-1 text-sm font-medium">Dashboard</a>
                    </nav>
                </div>
                <div class="flex items-center gap-4">
                    <button id="themeToggle" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors text-slate-500 dark:text-slate-400">
                        <i class="fa-solid fa-moon text-lg dark:hidden"></i>
                        <i class="fa-solid fa-sun text-lg hidden dark:block"></i>
                    </button>
                    <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-primary dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-700">
                        EL
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Seus Registros</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Acompanhe seu progresso e impacto gerado.</p>
            </div>
            <div class="flex w-full md:w-auto gap-4">
                <div class="relative w-full md:w-64">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="searchInput" placeholder="Buscar em tudo..." class="w-full pl-10 pr-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all dark:text-white shadow-sm">
                </div>
                <button onclick="openModal()" class="flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-md hover:shadow-lg whitespace-nowrap">
                    <i class="fa-solid fa-plus"></i> Novo
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 mb-8 flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px] relative" id="filterProjetoContainer">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Filtrar por Projeto</label>
                <input type="hidden" id="filterProjeto">
                <input type="text" id="filterProjetoSearch" placeholder="Todos os Projetos..." class="w-full form-input cursor-pointer" autocomplete="off">
                <div id="filterProjetoDropdown" class="hidden absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 shadow-lg rounded-md border border-slate-200 dark:border-slate-600 max-h-48 overflow-y-auto custom-scrollbar">
                    <ul id="filterProjetoList" class="py-1 text-sm text-slate-700 dark:text-slate-300"></ul>
                </div>
            </div>
            <div class="flex-1 min-w-[200px] relative" id="filterAutorContainer">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Filtrar por Autor Feedback</label>
                <input type="hidden" id="filterAutor">
                <input type="text" id="filterAutorSearch" placeholder="Todos os Autores..." class="w-full form-input cursor-pointer" autocomplete="off">
                <div id="filterAutorDropdown" class="hidden absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 shadow-lg rounded-md border border-slate-200 dark:border-slate-600 max-h-48 overflow-y-auto custom-scrollbar">
                    <ul id="filterAutorList" class="py-1 text-sm text-slate-700 dark:text-slate-300"></ul>
                </div>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Status de Entrega</label>
                <select id="filterStatus" class="w-full form-input appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E')] bg-[length:12px_auto] bg-no-repeat bg-[position:right_12px_center] pr-8 cursor-pointer">
                    <option value="">Todos</option>
                    <option value="no_prazo">No Prazo / Concluído</option>
                    <option value="atrasado">Atrasado</option>
                </select>
            </div>
        </div>

        <div id="results" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        </div>
    </main>

    <div id="recordModal" class="modal hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-semibold text-slate-900 dark:text-white" id="modal-title">
                        Registrar Progresso
                    </h3>
                    <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300 transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <form id="effectaForm" class="px-6 py-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2 relative" id="projetoSelectContainer">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Projeto *</label>
                            <input type="hidden" name="projeto" id="projetoHidden" required>
                            <input type="text" id="projetoSearch" placeholder="Pesquise ou adicione projeto..." class="w-full form-input" autocomplete="off" required>

                            <div id="projetoDropdown" class="hidden absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 shadow-lg rounded-md border border-slate-200 dark:border-slate-600 max-h-48 overflow-y-auto custom-scrollbar">
                                <ul id="projetoList" class="py-1 text-sm text-slate-700 dark:text-slate-300"></ul>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Atividade *</label>
                            <input type="text" name="atividade" required class="w-full form-input">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tipo de Esforço/Prazo *</label>
                            <div class="flex gap-4 mb-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="tipo_prazo" value="horas" checked class="text-primary focus:ring-primary" onchange="togglePrazoInput()">
                                    <span class="text-sm dark:text-slate-300">Horas Trabalhadas</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="tipo_prazo" value="data" class="text-primary focus:ring-primary" onchange="togglePrazoInput()">
                                    <span class="text-sm dark:text-slate-300">Data Limite</span>
                                </label>
                            </div>
                        </div>

                        <div id="inputHorasContainer" class="w-full">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Planejado (Ex: 6:40) *</label>
                            <input type="text" name="horas_trabalhadas" placeholder="6:40" class="w-full form-input" required>
                        </div>

                        <div id="inputDataContainer" class="hidden w-full">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Prazo Planejado *</label>
                            <input type="date" name="prazo" class="w-full form-input">
                        </div>

                        <div id="entregaDataContainer" class="hidden w-full">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Data de Entrega</label>
                            <input type="date" name="data_entrega" class="w-full form-input">
                        </div>

                        <div id="entregaHorasContainer" class="w-full">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Horas Gastas (Ex: 6:40)</label>
                            <input type="text" name="horas_gastas" placeholder="6:40" class="w-full form-input">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Meta</label>
                            <input type="text" name="meta" class="w-full form-input">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">O que fiz</label>
                            <textarea name="o_que_fiz" rows="2" class="w-full form-input"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contribuição relevante</label>
                            <textarea name="contribuicao" rows="2" class="w-full form-input"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Impacto Gerado</label>
                            <textarea name="impacto" rows="2" class="w-full form-input"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Treinamentos</label>
                            <textarea name="treinamentos" rows="2" class="w-full form-input"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Stakeholders</label>
                            <textarea name="stakeholders" rows="2" placeholder="Separe por vírgula..." class="w-full form-input"></textarea>
                        </div>

                        <div class="md:col-span-2 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                            <h4 class="text-sm font-semibold mb-3 text-slate-800 dark:text-slate-200"><i class="fa-regular fa-comment-dots mr-1"></i> Feedback Recebido</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2 relative" id="autorSelectContainer">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Autor do Feedback</label>
                                    <input type="hidden" name="autor_feedback" id="autorFeedbackHidden">
                                    <input type="text" id="autorFeedbackSearch" placeholder="Pesquise ou adicione..." class="w-full form-input" autocomplete="off">

                                    <div id="autorDropdown" class="hidden absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 shadow-lg rounded-md border border-slate-200 dark:border-slate-600 max-h-48 overflow-y-auto custom-scrollbar">
                                        <ul id="autorList" class="py-1 text-sm text-slate-700 dark:text-slate-300"></ul>
                                    </div>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Conteúdo do Feedback</label>
                                    <textarea name="feedbacks" rows="2" class="w-full form-input"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium transition-colors shadow-sm">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors shadow-md flex items-center gap-2">
                            <i class="fa-solid fa-check"></i> Salvar Registro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>