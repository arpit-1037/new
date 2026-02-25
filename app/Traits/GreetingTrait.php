<?php

namespace App\Traits;

trait GreetingTrait
{
    public function greet($name)
    {
        return "Hello, " . ucfirst($name);
    }
}