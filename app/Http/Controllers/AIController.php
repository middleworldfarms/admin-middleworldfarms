<?php

namespace App\Http\Controllers;

use App\Services\AI\SymbiosisAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIController extends Controller
{
    protected $aiService;

    public function __construct(SymbiosisAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * General chat endpoint
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => 'required|array',
            'options' => 'sometimes|array'
        ]);

        try {
            $response = $this->aiService->chat(
                $request->input('messages'),
                $request->input('options', [])
            );

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Farming insights endpoint
     */
    public function farmingInsights(Request $request): JsonResponse
    {
        $request->validate([
            'farm_data' => 'required|array'
        ]);

        try {
            $insights = $this->aiService->generateFarmingInsights($request->input('farm_data'));

            return response()->json([
                'success' => true,
                'insights' => $insights
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crop planning endpoint
     */
    public function cropPlanning(Request $request): JsonResponse
    {
        $request->validate([
            'conditions' => 'required|array'
        ]);

        try {
            $suggestions = $this->aiService->suggestCropPlanning($request->input('conditions'));

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pest/Disease identification endpoint
     */
    public function identifyIssue(Request $request): JsonResponse
    {
        $request->validate([
            'description' => 'required|string',
            'images' => 'sometimes|array'
        ]);

        try {
            $identification = $this->aiService->identifyPestOrDisease(
                $request->input('description'),
                $request->input('images', [])
            );

            return response()->json([
                'success' => true,
                'identification' => $identification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
