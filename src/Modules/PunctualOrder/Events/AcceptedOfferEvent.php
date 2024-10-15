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

class AcceptedOfferEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $userId;

    public $message;


    /**
     * Create a new event instance.
     */
    public function __construct($order,$userId, $message)
    {
        $this->order = $order;
        $this->userId = $userId;
        $this->message = $message;
    }

    // public function broadcatsWith(){

    //     return [$this->order];
    // }


    public function broadcastAs(){
        return 'accepted-offer-event';
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {

        return [new PrivateChannel('offer-admin')];
    }

}
