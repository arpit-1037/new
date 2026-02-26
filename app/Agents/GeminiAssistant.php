<?php

namespace App\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

#[Provider('gemini')]
class GeminiAssistant implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a concise and helpful assistant for a Laravel application.';
    }
}
