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

class RejectedtedOfferEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $offer;
    public $userId;

    public $message;


    /**
     * Create a new event instance.
     */
    public function __construct($offer,$userId, $message)
    {
        $this->offer = $offer;
        $this->userId = $userId;
        $this->message = $message;
    }

    public function broadcatsWith(){

        return [$this->offer];
    }


    public function broadcastAs(){
        return 'rejected-offer-event';
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
