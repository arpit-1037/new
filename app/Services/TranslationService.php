<?php

namespace App\Services;

use App\Agents\TranslationAssistant;
use App\Services\Ai\ProviderFailoverService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TranslationService
{
    public function __construct(
        protected ProviderFailoverService $providerFailoverService
    ) {
    }

    public function generate(string $text, ?string $requestedModel = null): array
    {
        $providerChain = $this->providerFailoverService->chain(
            'ai.translation.provider_failover',
            $requestedModel
        );

        $prompt = $this->buildPrompt($text);

        $attemptErrors = [];

        foreach ($providerChain as $provider => $model) {
            try {
                $startedAt = microtime(true);

                $response = TranslationAssistant::make()->prompt(
                    $prompt,
                    provider: [$provider => $model]
                );

                $rawText = trim((string) ($response->text ?? ''));
                $parsed = $this->parseJsonResponse($rawText);
                $normalized = $this->normalizeResult($parsed);

                $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

                Log::info('TRANSLATION SUCCESS', [
                    'provider' => $response->meta->provider ?? $provider,
                    'model' => $response->meta->model ?? $model ?? 'default',
                    'duration_ms' => $durationMs,
                    'input_length' => mb_strlen($text),
                ]);

                return [
                    'original_english' => $text,
                    'simplified_english' => $normalized['simplified_english'],
                    'hindi' => $normalized['hindi'],
                    'gujarati' => $normalized['gujarati'],
                    'french' => $normalized['french'],
                    'spanish' => $normalized['spanish'],
                    'provider' => $response->meta->provider ?? $provider,
                    'model' => $response->meta->model ?? $model ?? 'default',
                ];
            } catch (Throwable $e) {
                $attemptErrors[] = [
                    'provider' => $provider,
                    'model' => $model,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ];

                Log::warning('TRANSLATION ATTEMPT FAILED', [
                    'provider' => $provider,
                    'model' => $model,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
            }
        }

        Log::error('TRANSLATION FAILED ALL PROVIDERS', [
            'attempts' => $attemptErrors,
            'input_length' => mb_strlen($text),
        ]);

        throw new RuntimeException('All translation providers failed.');
    }

    protected function buildPrompt(string $text): string
    {
        return <<<PROMPT
You are a translation engine.

Task:
Given the English text below, return:
1. simplified_english
2. hindi
3. gujarati
4. french
5. spanish

Rules:
- Preserve the original meaning exactly.
- Do not add explanations.
- Do not add markdown.
- Do not add labels outside JSON.
- Keep the wording natural and suitable for customer-facing product text.
- Preserve numbers, dates, URLs, emails, codes, and product names unless they clearly should be translated.
- Return only valid JSON.
- The JSON must have exactly these keys:
  simplified_english, hindi, gujarati, french, spanish

English text:
{$text}
PROMPT;
    }

    protected function parseJsonResponse(string $rawText): array
    {
        $decoded = json_decode($rawText, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $rawText, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('Model did not return valid JSON.');
    }

    protected function normalizeResult(array $data): array
    {
        $requiredKeys = [
            'simplified_english',
            'hindi',
            'gujarati',
            'french',
            'spanish',
        ];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new RuntimeException("Missing required key: {$key}");
            }

            if (!is_string($data[$key])) {
                throw new RuntimeException("Invalid value for key: {$key}");
            }

            $data[$key] = trim($data[$key]);

            if ($data[$key] === '') {
                throw new RuntimeException("Empty value for key: {$key}");
            }
        }

        return [
            'simplified_english' => $data['simplified_english'],
            'hindi' => $data['hindi'],
            'gujarati' => $data['gujarati'],
            'french' => $data['french'],
            'spanish' => $data['spanish'],
        ];
    }
}
