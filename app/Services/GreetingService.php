<?php

namespace App\Services;

class GreetingService
{
    public function sayHello($name)
    {
        return "Hello, " . ucfirst($name);
    }
}