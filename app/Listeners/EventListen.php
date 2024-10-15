<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\ReceivedEvent;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\ConsoleOutput;
use Laravel\Reverb\Events\MessageReceived;


// use Laravel\Reverb\Events\MessageReceived;


class EventListen
{
    // use InteractsWithQueue;
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

        $message = json_decode($event->message);
        $data = $message->data;

        if($message->event == 'ReceivedEvent'){
            ReceivedEvent::dispatch("Dispatch ReceivedEvent ");
        }



    }
}
