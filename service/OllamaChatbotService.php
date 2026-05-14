<?php

require_once __DIR__ . '/../config.php';

class OllamaChatbotService
{
    private string $url;
    private string $model;

    public function __construct()
    {
        $baseUrl = rtrim((string) config::env('OLLAMA_BASE_URL', 'http://localhost:11434'), '/');
        $this->url = $baseUrl . '/api/generate';
        $this->model = (string) config::env('OLLAMA_MODEL', 'qwen2.5:7b');
    }

    public function chat(array $messages): string
    {
        $prompt = $this->buildPrompt($messages);

        if ($prompt === '') {
            return "Veuillez écrire un message.";
        }

        $data = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
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

        return trim((string) ($result['response'] ?? $result['message']['content'] ?? "Je n'ai pas reçu de réponse."));
    }

    private function buildPrompt(array $messages): string
    {
        $lastUserMessage = '';
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            $role = strtoupper(trim((string) ($message['role'] ?? 'user')));
            $content = trim((string) ($message['content'] ?? ''));

            if ($content === '' || $role !== 'USER') {
                continue;
            }

            $lastUserMessage = $content;
        }

        if ($lastUserMessage === '') {
            return '';
        }

        return implode("\n", [
            "Tu es l'assistant officiel du site GoService.",
            'Réponds en français, naturellement, court et utile.',
            'Message utilisateur: ' . $lastUserMessage,
            'Réponse:',
        ]);
    }
}

// POST handler for AJAX chat requests
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
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