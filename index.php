<?php
// Roteamento de Páginas (Front Controller)
$page = $_GET['page'] ?? 'registros';

// Sanitiza o nome do parâmetro de página para evitar Directory Traversal
if (!preg_match('/^[a-zA-Z0-9_]+$/', $page)) {
    $page = 'registros';
}

$pageFile = __DIR__ . '/pages/' . $page . '.php';

if (file_exists($pageFile)) {
    include_once $pageFile;
} else {
    // Fallback para a página de registros caso a página não exista
    include_once __DIR__ . '/pages/registros.php';
}