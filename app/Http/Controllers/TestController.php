<?php

namespace App\Http\Controllers;

use App\Traits\GreetingTrait;

class TestController extends Controller
{
    use GreetingTrait;

    public function index()
    {
        return $this->greet('arpit patel');
    }
}