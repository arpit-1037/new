<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Agents\GeminiAssistant;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Api\AiGenerateController;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\TranslationController;

Route::get('/support-chat', [SupportChatController::class, 'index'])->name('support.chat');
Route::post('/support-chat/start', [SupportChatController::class, 'start'])->name('support.chat.start');
Route::post('/support-chat/send', [SupportChatController::class, 'send'])
    ->name('support.chat.send')
    ->middleware('throttle:20,1');

Route::get('/translation', [TranslationController::class, 'index'])->name('translation.index');
Route::post('/translation', [TranslationController::class, 'generate'])->name('translation.generate');

Route::view('/ai', 'ai');
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