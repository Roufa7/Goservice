<?php

class OllamaChatbotService
{
    private string $url = 'http://localhost:11434/api/chat';
    private string $model = 'qwen2.5:7b';

    public function chat(array $messages): string
    {
        $system = [
            'role' => 'system',
            'content' => "
Tu es l'assistant officiel du site GoService.
Tu réponds comme dans une vraie discussion.
GoService est une plateforme avec forum, services, catégories, contrats, réservations et prestataires.
Réponds en français, naturellement, court et utile.
"
        ];

        $data = [
            'model' => $this->model,
            'messages' => array_merge([$system], $messages),
            'stream' => false
        ];

        $ch = curl_init($this->url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 120
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return "Ollama ne répond pas. Vérifie qu'il est lancé.";
        }

        $result = json_decode($response, true);

        return trim($result['message']['content'] ?? "Je n'ai pas reçu de réponse.");
    }
}

// POST handler for AJAX chat requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['reply' => "Veuillez écrire un message.", 'error' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Initialize chat history in session
    if (!isset($_SESSION['chatbot_history'])) {
        $_SESSION['chatbot_history'] = [];
    }
    
    // Add user message to history
    $_SESSION['chatbot_history'][] = [
        'role' => 'user',
        'content' => $message
    ];
    
    // Keep only last 10 messages for context
    if (count($_SESSION['chatbot_history']) > 10) {
        $_SESSION['chatbot_history'] = array_slice($_SESSION['chatbot_history'], -10);
    }
    
    $service = new OllamaChatbotService();
    $reply = $service->chat($_SESSION['chatbot_history']);
    
    // Add assistant response to history
    $_SESSION['chatbot_history'][] = [
        'role' => 'assistant',
        'content' => $reply
    ];
    
    echo json_encode(['reply' => $reply, 'error' => false], JSON_UNESCAPED_UNICODE);
    exit;
}