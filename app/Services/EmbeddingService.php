<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected string $ollamaUrl;
    protected string $embeddingModel;
    
    public function __construct()
    {
        $this->ollamaUrl = config('services.ollama.url', 'http://localhost:11434');
        $this->embeddingModel = config('services.ollama.embedding_model', 'nomic-embed-text');
    }
    
    /**
     * Generate embedding vector for a text string
     * 
     * @param string $text Text to embed
     * @return array|null Vector embedding (array of floats) or null on failure
     */
    public function embed(string $text): ?array
    {
        try {
            $response = Http::timeout(30)->post("{$this->ollamaUrl}/api/embeddings", [
                'model' => $this->embeddingModel,
                'prompt' => $text,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['embedding'] ?? null;
            }
            
            Log::error('Embedding API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Embedding generation failed', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);
            
            return null;
        }
    }
    
    /**
     * Generate embeddings for multiple texts in batch
     * 
     * @param array $texts Array of strings to embed
     * @return array Array of embeddings (same order as input)
     */
    public function embedBatch(array $texts): array
    {
        $embeddings = [];
        
        foreach ($texts as $text) {
            $embedding = $this->embed($text);
            $embeddings[] = $embedding;
            
            // Small delay to avoid overwhelming Ollama
            usleep(100000); // 100ms
        }
        
        return $embeddings;
    }
    
    /**
     * Calculate cosine similarity between two vectors
     * 
     * @param array $vector1 First embedding vector
     * @param array $vector2 Second embedding vector
     * @return float Similarity score (0-1, higher is more similar)
     */
    public function cosineSimilarity(array $vector1, array $vector2): float
    {
        if (count($vector1) !== count($vector2)) {
            throw new \InvalidArgumentException('Vectors must be same length');
        }
        
        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;
        
        for ($i = 0; $i < count($vector1); $i++) {
            $dotProduct += $vector1[$i] * $vector2[$i];
            $magnitude1 += $vector1[$i] * $vector1[$i];
            $magnitude2 += $vector2[$i] * $vector2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }
        
        return $dotProduct / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Get embedding model info
     * 
     * @return array|null Model information
     */
    public function getModelInfo(): ?array
    {
        try {
            $response = Http::timeout(10)->get("{$this->ollamaUrl}/api/show", [
                'name' => $this->embeddingModel,
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get embedding model info', [
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
    
    /**
     * Check if Ollama is running and embedding model is available
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->ollamaUrl}/api/tags");
            
            if (!$response->successful()) {
                return false;
            }
            
            $models = $response->json()['models'] ?? [];
            
            foreach ($models as $model) {
                // Check if model name contains our embedding model (handles :latest suffix)
                if (str_contains($model['name'], $this->embeddingModel)) {
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Format knowledge entry for embedding
     * Creates a rich text representation suitable for semantic search
     * 
     * @param array $knowledge Knowledge entry from database
     * @param string $type Type of knowledge (companion, rotation, calendar)
     * @return string Formatted text for embedding
     */
    public function formatKnowledgeForEmbedding(array $knowledge, string $type): string
    {
        switch ($type) {
            case 'companion':
                return sprintf(
                    "Companion planting: %s works well with %s. %s %s Timing: %s. %s",
                    $knowledge['primary_crop'] ?? '',
                    $knowledge['companion_plant'] ?? '',
                    $knowledge['benefits'] ?? '',
                    $knowledge['planting_notes'] ?? '',
                    $knowledge['planting_timing'] ?? '',
                    $knowledge['seasonal_considerations'] ?? ''
                );
                
            case 'rotation':
                return sprintf(
                    "Crop rotation: After growing %s, %s to plant %s. %s %s Soil consideration: %s",
                    $knowledge['previous_crop'] ?? '',
                    $knowledge['relationship'] === 'avoid' ? 'avoid' : 'good',
                    $knowledge['following_crop'] ?? '',
                    $knowledge['benefits'] ?? '',
                    $knowledge['risks'] ?? '',
                    $knowledge['soil_consideration'] ?? ''
                );
                
            case 'calendar':
                return sprintf(
                    "UK planting calendar: %s (%s variety). Sow: %s-%s. Transplant: %s-%s. Harvest: %s-%s. %s Hardy: %s (%s). %s %s",
                    $knowledge['crop_name'] ?? '',
                    $knowledge['variety_type'] ?? 'general',
                    $knowledge['sow_indoors_start'] ?? $knowledge['sow_outdoors_start'] ?? 'N/A',
                    $knowledge['sow_indoors_end'] ?? $knowledge['sow_outdoors_end'] ?? 'N/A',
                    $knowledge['transplant_start'] ?? 'N/A',
                    $knowledge['transplant_end'] ?? 'N/A',
                    $knowledge['harvest_start'] ?? 'N/A',
                    $knowledge['harvest_end'] ?? 'N/A',
                    $knowledge['frost_hardy'] ? 'Frost' : 'Not frost',
                    $knowledge['frost_hardy'] ? 'yes' : 'no',
                    $knowledge['hardiness_zone'] ?? '',
                    $knowledge['timing_notes'] ?? '',
                    $knowledge['uk_specific_advice'] ?? ''
                );
                
            default:
                return json_encode($knowledge);
        }
    }
}
