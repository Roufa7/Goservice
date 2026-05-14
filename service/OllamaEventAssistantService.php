<?php

class OllamaEventAssistantService
{
    private array $baseUrls;
    private string $model;

    public function __construct()
    {
        $configuredUrl = rtrim((string) config::env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'), '/');
        $this->model = 'qwen2.5:7b';
        $this->baseUrls = array_values(array_unique(array_filter([
            $configuredUrl,
            'http://127.0.0.1:11434',
            'http://localhost:11434',
            'http://[::1]:11434',
        ])));
    }

    public function model(): string
    {
        return $this->model;
    }

    public function generate(string $mode, array $eventDraft): array
    {
        $prompt = $this->buildPrompt($mode, $eventDraft);

        if ($prompt === '') {
            return [
                'success' => false,
                'available' => true,
                'text' => '',
                'message' => 'Les informations saisies sont insuffisantes pour utiliser l\'assistant IA.',
            ];
        }

        $lastError = '';

        foreach ($this->baseUrls as $baseUrl) {
            $result = $this->postToOllama($baseUrl, [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => in_array($mode, ['generate_promo'], true) ? 0.45 : 0.3,
                ],
            ]);

            if (!$result['ok']) {
                $lastError = $result['error'];
                continue;
            }

            $payload = json_decode($result['body'], true);
            $text = trim((string) ($payload['response'] ?? ''));

            if ($text === '') {
                return [
                    'success' => false,
                    'available' => true,
                    'text' => '',
                    'message' => 'L\'assistant IA n\'a retourne aucun texte exploitable.',
                ];
            }

            return [
                'success' => true,
                'available' => true,
                'text' => $this->normalizeForMode($mode, $text),
                'message' => 'Texte genere avec succes par l\'assistant IA.',
            ];
        }

        return [
            'success' => false,
            'available' => false,
            'text' => '',
            'message' => 'Ollama n\'est pas joignable depuis PHP. Verifiez qu\'Ollama est lance et que qwen2.5:7b existe. Derniere erreur: ' . $lastError,
        ];
    }

    private function postToOllama(string $baseUrl, array $payload): array
    {
        $url = rtrim($baseUrl, '/') . '/api/generate';
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $body,
                'timeout' => 90,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $error = error_get_last();
            return ['ok' => false, 'body' => '', 'error' => ($error['message'] ?? 'Unknown PHP stream error') . ' on ' . $baseUrl];
        }

        $statusLine = $http_response_header[0] ?? 'HTTP/1.1 200 OK';
        if (!preg_match('/\s(\d{3})\s/', $statusLine, $match) || (int) $match[1] >= 400) {
            return ['ok' => false, 'body' => $response, 'error' => $statusLine . ' on ' . $baseUrl];
        }

        return ['ok' => true, 'body' => $response, 'error' => ''];
    }
    private function buildPrompt(string $mode, array $eventDraft): string
    {
        $title = trim((string) ($eventDraft['titre'] ?? ''));
        $description = trim((string) ($eventDraft['description'] ?? ''));

        if ($mode === 'suggest_title' && $description === '') {
            return '';
        }

        if (in_array($mode, ['improve_description', 'generate_promo'], true) && $description === '') {
            return '';
        }

        if ($mode === 'analyze_event' && $title === '' && $description === '') {
            return '';
        }

        $context = implode("\n", [
            'Contexte plateforme : GoService est une plateforme de services pour des domaines comme plomberie, electricite, jardinage et prestations similaires.',
            'Type d\'evenement : ' . ((string) ($eventDraft['type_evenement'] ?? '') ?: 'non precise'),
            'Titre actuel : ' . ($title !== '' ? $title : 'non precise'),
            'Lieu : ' . ((string) ($eventDraft['lieu'] ?? '') ?: 'non precise'),
            'Date de debut : ' . ((string) ($eventDraft['date_debut'] ?? '') ?: 'non precise'),
            'Date de fin : ' . ((string) ($eventDraft['date_fin'] ?? '') ?: 'non precise'),
            'Nombre de places : ' . ((string) ($eventDraft['nb_places'] ?? '') ?: 'non precise'),
            'Statut : ' . ((string) ($eventDraft['statut'] ?? '') ?: 'non precise'),
            'Description actuelle : ' . ($description !== '' ? $description : 'non precise'),
        ]);

        $instruction = match ($mode) {
            'suggest_title' => "Tache : propose un meilleur titre en francais, clair, professionnel et concis.\nRetourne uniquement le nouveau titre, sans guillemets ni explication.",
            'generate_promo' => "Tache : reecris la description en francais dans un style plus attractif et promotionnel pour une page evenement.\nRetourne uniquement le nouveau texte, en 2 ou 3 phrases maximum.",
            'analyze_event' => "Tache : analyse rapidement la fiche evenement pour un administrateur.\nInterdiction absolue d'inventer ou de demander un prix, un organisme, une ville supplementaire, une duree supplementaire, un public cible ou toute autre donnee absente du formulaire.\nBase ton retour uniquement sur la clarte du titre, la qualite de la description, la coherence date/lieu/statut/capacite, la valeur percue et l'appel a l'action.\nRetourne uniquement le resultat suivant, en francais et sans phrase d'introduction :\nPoints forts :\n- ...\nPoints a renforcer :\n- ...\nRecommandation :\n- ...",
            default => "Tache : ameliore la description en francais pour qu'elle soit professionnelle, naturelle et adaptee a une page evenement.\nRetourne uniquement le nouveau texte, sans liste ni commentaire.",
        };

        return implode("\n\n", [
            'Tu es un assistant de redaction pour un module evenement en PHP.',
            'Utilise uniquement les informations fournies ci-dessous.',
            'N\'invente aucun fait, aucun prix, aucun organisme, aucune duree supplementaire, aucune ville non mentionnee et aucune information externe.',
            'Si une information manque, ne la compense pas.',
            $context,
            $instruction,
        ]);
    }

    private function normalizeForMode(string $mode, string $text): string
    {
        $text = $this->cleanup($text);

        if ($mode === 'suggest_title') {
            $lines = preg_split('/\n+/', $text) ?: [$text];
            $text = trim((string) ($lines[0] ?? $text), " \t\n\r\0\x0B\"'");
            return rtrim($text, '.');
        }

        return $text;
    }

    private function cleanup(string $text): string
    {
        $text = preg_replace('/^```(?:text)?/m', '', $text) ?? $text;
        $text = preg_replace('/```$/m', '', $text) ?? $text;
        $text = trim($text);
        return preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
    }
}