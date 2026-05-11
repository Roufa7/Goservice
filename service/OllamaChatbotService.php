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