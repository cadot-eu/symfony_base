<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeepseekApiService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $deepseekApiKey)
    {
        $this->client = $client;
        $this->apiKey = $deepseekApiKey;
    }

    public function generateMethod(array $data): array
    {
        $payload = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Tu es un assistant expert Symfony 7. Génère du code PHP moderne, ajoute les services manquants par injection si besoin."
                ],
                [
                    'role' => 'user',
                    'content' => $data['prompt'] ?? 'Aucune instruction fournie.'
                ]
            ]
        ];

        $response = $this->client->request('POST', 'https://api.deepseek.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);
        return $response->toArray();
    }
}
