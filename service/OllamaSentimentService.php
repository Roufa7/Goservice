<?php

class OllamaSentimentService
{
    private string $url = 'http://localhost:11434/api/generate';
    private string $model = 'qwen2.5:7b';

    public function analyser(string $texte): array
    {
        $texte = trim($texte);

        if ($texte === '') {
            return $this->defaultResult();
        }

        $lower = mb_strtolower($texte);

        $positifs = [
            'farha','farhana','farhaana',
            'barcha jaw','jaw',
            'benna','tayara','mabrouk',
            '3jebni','nheb',
            'excellent','bravo','super','awesome',
            'top','wow','cool','nice',
            'love','great', 'amazing','fantastic','wonderful','best','perfect',
            '❤️','🔥','😍','😊','😄'
        ];

        $negatifs = [
            'khayeb',
            'ma3ejbnich',
            'ma 3jebnich',
            'jaime pas',
            "j'aime pas",
            'mauvais',
            'trop mauvais',
            'nul',
            'horrible',
            'hate','dislike',
            'bad','terrible','awful','worst'
        ];

        $toxiques = [
            'stupid','idiot','dumb','fool','bastard','asshole','jerk','moron','loser','imbecile','dumb',
            'idiot','imbécile','crétin','con','abruti',
            'hate you',
            'ta gueule','nique ta gueule','nique ta mère',
            'salaud','connard','enculé','putain','merde','bordel','salope','pute',
        ];

        foreach($toxiques as $mot){
            if (preg_match('/\b' . preg_quote($mot, '/') . '\b/u', $lower)) {
                return [
                    'sentiment'=>'negatif',
                    'score'=>-1,
                    'toxicite'=>1,
                    'raison'=>'Expression toxique détectée'
                ];
            }
        }

        foreach($negatifs as $mot){
            if (preg_match('/\b' . preg_quote($mot, '/') . '\b/u', $lower)) {
                return [
                    'sentiment'=>'negatif',
                    'score'=>-0.9,
                    'toxicite'=>0,
                    'raison'=>'Expression négative détectée'
                ];
            }
        }

        foreach($positifs as $mot){
            if (preg_match('/\b' . preg_quote($mot, '/') . '\b/u', $lower)) {
                return [
                    'sentiment'=>'positif',
                    'score'=>0.9,
                    'toxicite'=>0,
                    'raison'=>'Expression positive détectée'
                ];
            }
        }

       $prompt = "
You are an advanced multilingual sentiment analysis AI.

Your ONLY task:
Analyze the emotional sentiment of the text.

RULES:
- Happiness, compliments, satisfaction, love, excitement, positivity, admiration and enjoyment => positif
- Sadness, anger, disappointment, hate, criticism, frustration => negatif
- Neutral ONLY if there is absolutely no emotion

The text may contain:
- French
- English
- Arabic
- Tunisian dialect
- Arabizi
- emojis
- mixed languages

IMPORTANT:
- Return ONLY valid JSON
- No explanations
- No markdown
- No code block
- No extra text

VALID JSON FORMAT:
{
  \"sentiment\":\"positif|negatif|neutre\",
  \"score\":0.0,
  \"toxicite\":0,
  \"raison\":\"short explanation\"
}

Examples:

Input:
ce site est incroyable jaime trop
Output:
{
  \"sentiment\":\"positif\",
  \"score\":0.9,
  \"toxicite\":0,
  \"raison\":\"positive appreciation\"
}

Input:
je suis triste et déçu
Output:
{
  \"sentiment\":\"negatif\",
  \"score\":-0.8,
  \"toxicite\":0,
  \"raison\":\"sadness and disappointment\"
}

Input:
what time is the event
Output:
{
  \"sentiment\":\"neutre\",
  \"score\":0,
  \"toxicite\":0,
  \"raison\":\"neutral informational sentence\"
}

TEXT:
".$texte;

       $payload = [
    'model' => $this->model,
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'temperature' => 0.2
    ]
];

        $ch = curl_init($this->url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);
            return $this->defaultResult();
        }

        curl_close($ch);

        $data = json_decode($response, true);

        if (!isset($data['response'])) {
            return $this->defaultResult();
        }
        $json = json_decode($data['response'], true);

        if (!is_array($json)) {
            return $this->defaultResult();
        }

        $sentiment = strtolower(trim($json['sentiment'] ?? 'neutre'));
$score = (float)($json['score'] ?? 0);

if ($sentiment === 'négatif') {
    $sentiment = 'negatif';
}

if ($sentiment === 'negative') {
    $sentiment = 'negatif';
}

if ($sentiment === 'positive') {
    $sentiment = 'positif';
}

if (!in_array($sentiment, ['positif','negatif','neutre'], true)) {
    $sentiment = 'neutre';
}

if ($sentiment === 'negatif') {
    $score = -abs($score ?: 0.8);
}

if ($sentiment === 'positif') {
    $score = abs($score ?: 0.8);
}

if ($sentiment === 'neutre') {
    $score = 0;
}

return [
    'sentiment' => $sentiment,
    'score' => $score,
    'toxicite' => !empty($json['toxicite']) ? 1 : 0,
    'raison' => $json['raison'] ?? ''
];
    }

    private function defaultResult(): array
    {
        return [
            'sentiment'=>'neutre',
            'score'=>0,
            'toxicite'=>0,
            'raison'=>'Analyse indisponible'
        ];
    }
}