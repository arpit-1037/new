<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Agents\GeminiAssistant;

class AiGenerateController extends Controller
{
    public function __invoke(Request $request)
    {
        // 1) Validate request (stateless generation)
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:1', 'max:8000'],
            'model'  => ['nullable', 'string', 'max:100'],
        ]);

        $prompt = $validated['prompt'];
        $model  = $validated['model'] ?? null;

        try {
            $response = GeminiAssistant::make()->prompt(
                $prompt,
                model: $model
            );

            return response()->json([
                'provider' => $response->meta->provider ?? 'gemini',
                'model'    => $response->meta->model ?? $model,
                'text'     => $response->text ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI GENERATE ERROR', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return response()->json([
                'error'   => get_class($e),
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}