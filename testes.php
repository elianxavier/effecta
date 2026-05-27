<?php
// Testes de Integracao do EffectaORM
echo "=== Iniciando Bateria de Testes do EffectaORM ===\n";

require_once __DIR__ . '/src/EffectaORM.php';

$configFile = __DIR__ . '/src/config/database.php';
if (!file_exists($configFile)) {
    echo "Erro: Arquivo src/config/database.php nao encontrado.\n";
    exit(1);
}

$config = require $configFile;
$storageType = $config['storage_type'] ?? 'json';
echo "Modo ativo: " . strtoupper($storageType) . "\n\n";

$orm = new EffectaORM($storageType);

$testPersonId = null;
$testProjectId = null;
$testRegisterId = null;

try {
    // ----------------------------------------------------
    // TESTE 1: Insercao de Pessoa (Autor)
    // ----------------------------------------------------
    echo "Testando insercao de pessoa... ";
    $personData = $orm->insert('people', [
        'name' => 'Testador Automatizado CLI'
    ]);
    if (isset($personData['id']) && $personData['name'] === 'Testador Automatizado CLI') {
        $testPersonId = $personData['id'];
        echo "\033[32m[PASSOU]\033[0m\n";
    } else {
        throw new Exception("Falha ao retornar dados corretos da pessoa inserida.");
    }

    // ----------------------------------------------------
    // TESTE 2: Insercao de Projeto
    // ----------------------------------------------------
    echo "Testando insercao de projeto... ";
    $projectData = $orm->insert('projects', [
        'name' => 'Projeto de Teste CLI'
    ]);
    if (isset($projectData['id']) && $projectData['name'] === 'Projeto de Teste CLI') {
        $testProjectId = $projectData['id'];
        echo "\033[32m[PASSOU]\033[0m\n";
    } else {
        throw new Exception("Falha ao retornar dados corretos do projeto inserido.");
    }

    // ----------------------------------------------------
    // TESTE 3: Insercao de Registro
    // ----------------------------------------------------
    echo "Testando insercao de registro... ";
    $registerPayload = [
        'projeto' => 'Projeto de Teste CLI',
        'atividade' => 'Implementando testes de ORM',
        'tipo_prazo' => 'horas',
        'horas_trabalhadas' => 4.5,
        'horas_gastas' => 4.0,
        'meta' => 'Verificar estabilidade do codigo',
        'contribuicao' => 'Cobertura de testes automatizados adicionada',
        'impacto' => 'Seguranca e estabilidade na integracao continua',
        'treinamentos' => 'Nenhum',
        'stakeholders' => 'Elian, Equipe',
        'autor_feedback' => 'Testador Automatizado CLI',
        'feedbacks' => 'Excelente trabalho!'
    ];

    $registerData = $orm->insert('registers', $registerPayload);
    if (isset($registerData['id']) && !isset($registerData['o_que_fiz']) && $registerData['atividade'] === 'Implementando testes de ORM') {
        $testRegisterId = $registerData['id'];
        echo "\033[32m[PASSOU]\033[0m\n";
    } else {
        throw new Exception("Falha ao retornar dados do registro inserido.");
    }

    // ----------------------------------------------------
    // TESTE 4: Leitura de todos os Registros (getAll)
    // ----------------------------------------------------
    echo "Testando recuperacao de todos os registros... ";
    $allRegisters = $orm->getAll('registers');
    $found = false;
    foreach ($allRegisters as $reg) {
        if ($reg['id'] === $testRegisterId) {
            $found = true;
            break;
        }
    }
    if ($found) {
        echo "\033[32m[PASSOU]\033[0m\n";
    } else {
        throw new Exception("Registro de teste recem-inserido nao foi listado no getAll.");
    }

    // ----------------------------------------------------
    // TESTE 5: Busca por Termo (search)
    // ----------------------------------------------------
    echo "Testando busca por termo... ";
    $searchResults = $orm->search('registers', 'testes de ORM');
    $foundInSearch = false;
    foreach ($searchResults as $reg) {
        if ($reg['id'] === $testRegisterId) {
            $foundInSearch = true;
            break;
        }
    }
    if ($foundInSearch) {
        echo "\033[32m[PASSOU]\033[0m\n";
    } else {
        throw new Exception("A busca pelo termo 'testes de ORM' nao localizou o registro de teste.");
    }

    echo "\n\033[32m=== TODOS OS TESTES PASSARAM COM SUCESSO! ===\033[0m\n\n";

} catch (Exception $e) {
    echo "\n\033[31m[FALHA NO TESTE]: " . $e->getMessage() . "\033[0m\n\n";
} finally {
    // ----------------------------------------------------
    // LIMPEZA DOS REGISTROS DE TESTE
    // ----------------------------------------------------
    echo "Limpando registros de teste...\n";
    if ($storageType === 'mysql') {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            if ($testPersonId) {
                $pdo->exec("DELETE FROM `people` WHERE `id` = '{$testPersonId}'");
            }
            if ($testProjectId) {
                $pdo->exec("DELETE FROM `projects` WHERE `id` = '{$testProjectId}'");
            }
            if ($testRegisterId) {
                $pdo->exec("DELETE FROM `registers` WHERE `id` = '{$testRegisterId}'");
            }
            echo "Banco de dados limpo.\n";
        } catch (PDOException $e) {
            echo "Erro ao limpar banco de dados: " . $e->getMessage() . "\n";
        }
    } else {
        // Modo JSON
        $dataDir = __DIR__ . '/data';
        
        if ($testPersonId && file_exists("$dataDir/people.json")) {
            $data = json_decode(file_get_contents("$dataDir/people.json"), true) ?: [];
            $data = array_filter($data, function($item) use ($testPersonId) { return $item['id'] !== $testPersonId; });
            file_put_contents("$dataDir/people.json", json_encode(array_values($data), JSON_PRETTY_PRINT));
        }

        if ($testProjectId && file_exists("$dataDir/projects.json")) {
            $data = json_decode(file_get_contents("$dataDir/projects.json"), true) ?: [];
            $data = array_filter($data, function($item) use ($testProjectId) { return $item['id'] !== $testProjectId; });
            file_put_contents("$dataDir/projects.json", json_encode(array_values($data), JSON_PRETTY_PRINT));
        }

        if ($testRegisterId && file_exists("$dataDir/registers.json")) {
            $data = json_decode(file_get_contents("$dataDir/registers.json"), true) ?: [];
            $data = array_filter($data, function($item) use ($testRegisterId) { return $item['id'] !== $testRegisterId; });
            file_put_contents("$dataDir/registers.json", json_encode(array_values($data), JSON_PRETTY_PRINT));
        }
        echo "Arquivos JSON limpos.\n";
    }
}
echo "=== Testes finalizados ===\n";
