<?php

namespace App\Providers;

use App\View\Components\layout;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Listeners\ActivateListen;
use Laravel\Reverb\Events\MessageReceived;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
	Event::listen(
            MessageReceived::class,
            ActivateListen::class,
        );
    }
}
