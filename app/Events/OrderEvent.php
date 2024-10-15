<?php

namespace App\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public $userId;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct($message,$order)
    {
        //
        $this->message = $message;
        $this->order = $order;
    }

    public function broadcastAs(){
        return 'order-event';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        Log::info("ORDER EVENT MESSAGE SENT FROM EVENT FILE".$this->message);
        Log::info("ORDER EVENT ORDER SENT FROM EVENT FILE".$this->order);
        return [
            new PrivateChannel('order')
        ];
    }
}
