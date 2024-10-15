<?php

namespace App\Listeners;

use App\Events\FlutterEvent;
 

class ProcessFlutterEvent
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
    public function handle(FlutterEvent $event): void
    {
        $out = new \Symfony\Component\Console\Output\ConsoleOutput();
        $out->writeln("Hello from Terminal Event OrderEven");
    }
}
