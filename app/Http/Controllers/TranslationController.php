<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TranslationController extends Controller
{
    public function __construct(
        protected TranslationService $translationService
    ) {
    }

    public function index()
    {
        return view('translation');
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'min:1', 'max:4000'],
            'model' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $result = $this->translationService->generate(
                $validated['text'],
                $validated['model'] ?? null
            );

            return view('translation', [
                'inputText' => $validated['text'],
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('TRANSLATION CONTROLLER ERROR', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'text' => 'Translation failed. Please try again.',
                ])
                ->with('errorMessage', $e->getMessage());
        }
    }
}
