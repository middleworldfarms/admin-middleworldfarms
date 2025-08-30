<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SymbiosisAIService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = null; // Not needed for local Ollama
        $this->baseUrl = 'http://127.0.0.1:8005/api'; // Using custom Ollama port
    }

    /**
     * Process a chat completion request using phi3:mini via Ollama
     */
    public function chat(array $messages, array $options = []): array
    {
        try {
            // Convert messages to a single prompt for Ollama
            $prompt = $this->convertMessagesToPrompt($messages);
            
            $response = Http::timeout(60)->post($this->baseUrl . '/generate', [
                'model' => 'phi3:mini',
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => $options['temperature'] ?? 0.7,
                    'num_predict' => $options['max_tokens'] ?? 1000,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'choices' => [
                        [
                            'message' => [
                                'content' => $data['response'] ?? 'No response generated.'
                            ]
                        ]
                    ]
                ];
            }

            throw new \Exception('phi3:mini/Ollama API request failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('SymbiosisAI phi3:mini chat error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Convert OpenAI-style messages to a single prompt for Ollama
     */
    private function convertMessagesToPrompt(array $messages): string
    {
        $prompt = '';
        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';
            
            if ($role === 'system') {
                $prompt .= "System: {$content}\n\n";
            } elseif ($role === 'user') {
                $prompt .= "User: {$content}\n\n";
            } elseif ($role === 'assistant') {
                $prompt .= "Assistant: {$content}\n\n";
            }
        }
        
        return trim($prompt);
    }

    /**
     * Generate farming insights based on data
     */
    public function generateFarmingInsights(array $farmData): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an expert agricultural AI assistant specializing in sustainable farming practices, crop planning, and farm management. Provide practical, actionable insights.'
            ],
            [
                'role' => 'user',
                'content' => 'Analyze this farm data and provide insights: ' . json_encode($farmData)
            ]
        ];

        $response = $this->chat($messages);
        return $response['choices'][0]['message']['content'] ?? 'Unable to generate insights.';
    }

    /**
     * Crop planning assistance
     */
    public function suggestCropPlanning(array $conditions): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a crop planning specialist. Analyze soil, weather, and market conditions to suggest optimal crop planning strategies.'
            ],
            [
                'role' => 'user',
                'content' => 'Based on these conditions, suggest a crop planning strategy: ' . json_encode($conditions)
            ]
        ];

        $response = $this->chat($messages);
        return $response['choices'][0]['message']['content'] ?? 'Unable to generate crop planning suggestions.';
    }

    /**
     * Pest and disease identification
     */
    public function identifyPestOrDisease(string $description, array $images = []): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an agricultural pathology expert. Help identify pests and diseases based on descriptions and suggest treatment options.'
            ],
            [
                'role' => 'user',
                'content' => "Help identify this pest or disease issue: {$description}"
            ]
        ];

        $response = $this->chat($messages);
        return $response['choices'][0]['message']['content'] ?? 'Unable to identify the issue.';
    }
}
