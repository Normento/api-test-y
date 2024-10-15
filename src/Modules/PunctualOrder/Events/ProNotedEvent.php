<?php

namespace Core\Modules\PunctualOrder\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ProNotedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public $userId;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct($message,$order,$userId)
    {
        //
        $this->message = $message;
        $this->order = $order;
        $this->userId = $userId;
    }

    public function broadcastAs(){
        return 'order-note-event';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return [

            new PrivateChannel('offers.' . $this->userId)
        ];
    }
}
