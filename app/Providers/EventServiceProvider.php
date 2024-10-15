<?php

namespace App\Providers;

use App\Events\FlutterEvent;
use App\Listeners\ProcessFlutterEvent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Ramsey\Uuid\Uuid;
use App\Events\ReceivedEvent;
use App\Listeners\EventListen;
use App\Listeners\ReceivedListener;
use Laravel\Reverb\Events\MessageReceived;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        FlutterEvent::class => [
            ProcessFlutterEvent::class,
        ],

        'Laravel\Reverb\Events\MessageReceived' => [
            'App\Listeners\ActivateListen',
        ],


    ];



    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
	parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    //public function shouldDiscoverEvents(): bool
    //{
    //    return true;
    //}
}
