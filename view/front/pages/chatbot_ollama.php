<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../service/OllamaChatbotService.php';

$messages = json_decode($_POST['messages'] ?? '[]', true);

if (!is_array($messages) || empty($messages)) {
    echo json_encode([
        'success' => false,
        'reply' => 'Message vide.'
    ]);
    exit;
}

$chatbot = new OllamaChatbotService();

echo json_encode([
    'success' => true,
    'reply' => $chatbot->chat($messages)
]);