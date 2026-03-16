<?php

namespace App\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

#[Provider('gemini')]
class TranslationAssistant implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a multilingual translation assistant for a Laravel application. Preserve meaning exactly, do not add explanations, and return only valid JSON when requested.';
    }
}