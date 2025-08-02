<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class AIController extends Controller
{
    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'context' => 'nullable|string',
        ]);

        $aiUrl = env('AI_SERVICE_URL', 'http://localhost:8001/ask');
        $response = Http::post($aiUrl, [
            'question' => $request->input('question'),
            'context' => $request->input('context'),
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json([
                'error' => 'AI service unavailable',
                'details' => $response->body(),
            ], 500);
        }
    }
}
