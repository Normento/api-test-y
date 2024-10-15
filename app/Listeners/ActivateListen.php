<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Reverb\Events\MessageReceived;
use App\Events\OrderEvent;
use App\Events\ActivateEvent;


class ActivateListen
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {

        //
        $message = json_decode($event->message);
        $data = $message->data;

        if($message->event == 'TestEvent'){
            ActivateEvent::dispatch("REVERB_RESPONSE");
            //OrderEvent::dispatch("REVERBS");
        }
    }

}

