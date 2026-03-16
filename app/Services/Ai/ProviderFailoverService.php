<?php

namespace App\Services\Ai;

class ProviderFailoverService
{
    public function chain(string $configKey, ?string $requestedModel = null): array
    {
        $configured = config($configKey, ['gemini', 'openai', 'anthropic', 'groq']);

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        $providers = collect($configured)
            ->map(fn ($provider) => strtolower(trim((string) $provider)))
            ->filter()
            ->unique()
            ->filter(fn (string $provider) => is_array(config("ai.providers.{$provider}")))
            ->filter(fn (string $provider) => $this->isReady($provider))
            ->values();

        if ($providers->isEmpty()) {
            $defaultProvider = strtolower((string) config('ai.default', 'gemini'));

            return [$defaultProvider => $requestedModel ?: null];
        }

        return $providers
            ->mapWithKeys(fn (string $provider, int $index) => [
                $provider => ($index === 0 && filled($requestedModel)) ? $requestedModel : null,
            ])
            ->all();
    }

    public function isReady(string $provider): bool
    {
        if ($provider === 'ollama') {
            return filled((string) config('ai.providers.ollama.url'));
        }

        return filled(config("ai.providers.{$provider}.key"));
    }
}