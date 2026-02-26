<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\TestController;
use App\Agents\GeminiAssistant;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Api\AiGenerateController;

Route::view('/ai', 'ai'); // UI page

Route::post('/ai/generate', function (Request $request) {
    $validated = $request->validate([
        'prompt' => ['required', 'string', 'min:1', 'max:8000'],
        'model'  => ['nullable', 'string', 'max:100'],
    ]);

    try {
        $response = GeminiAssistant::make()->prompt(
            $validated['prompt'],
            model: $validated['model'] ?? null
        );

        return response()->json([
            'provider' => $response->meta->provider,
            'model'    => $response->meta->model,
            'text'     => $response->text,
        ]);
    } catch (\Throwable $e) {
        Log::error('GEMINI AI ERROR', [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
        ]);

        return response()->json([
            'error' => get_class($e),
            'message' => $e->getMessage(),
        ], 500);
    }
})->name('ai.generate');

Route::post('/ai/generate', AiGenerateController::class)->name('ai.generate');

Route::get('/ai-test', function (Request $request) {
    $prompt = $request->string('prompt')->toString() ?: 'Reply with: AI working with Gemini';
    $model = $request->string('model')->toString() ?: null;

    try {
        $response = GeminiAssistant::make()->prompt(
            $prompt,
            model: $model
        );

        return response()->json([
            'provider' => $response->meta->provider,
            'model' => $response->meta->model,
            'text' => $response->text,
        ]);
    } catch (\Throwable $e) {
        Log::error('GEMINI AI ERROR', [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
        ]);

        return response()->json([
            'error' => get_class($e),
            'message' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/trait-test', [TestController::class, 'index']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return greet_user('arpit');
});
