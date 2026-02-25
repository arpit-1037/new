<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GreetingService;

class AppServiceProvider extends ServiceProvider
{
   

public function register(): void
{
    $this->app->singleton('greeting', function () {
        return new GreetingService();
    });
}
    
    public function boot(): void
    {
        //
    }
}
