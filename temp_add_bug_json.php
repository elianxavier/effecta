<?php
$file = 'data/feedbacks.json';
if (file_exists($file)) {
    $feedbacks = json_decode(file_get_contents($file), true) ?: [];
    $feedbacks[] = [
        'id' => time(),
        'user_id' => 3, // Dev
        'type' => 'bug',
        'subject' => 'Bug JSON Externo',
        'message' => 'Este e um bug de teste para o modo JSON.',
        'status' => 'pendente',
        'archived' => 0,
        'likes' => 0,
        'reports' => 0,
        'hidden_by_reports' => 0,
        'viewed_by_dev' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($file, json_encode($feedbacks, JSON_PRETTY_PRINT));
    echo "Bug JSON inserido.\n";
} else {
    echo "Arquivo feedbacks.json nao encontrado.\n";
}
