<?php

namespace App\Listeners;


use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use Laravel\Reverb\Events\MessageReceived;
use App\Events\ReceivedEvent;

class ReceivedListen
{

    // use Dispatchable, InteractsWithQueue;
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
        // $message = $event->message;
        // ReceivedEvent::dispatch("Dispatch ReceivedEvent ");

        // // $data = $event->data;

        // echo "Received data from frontend: " . $message;

        $message = json_decode($event->message);
        $data = $message->data;

        if($message->event == 'ReceivedEvent'){
            ReceivedEvent::dispatch("REVERB");
        }
    }
}
