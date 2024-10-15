<?php

namespace Core\Modules\RecurringOrder\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class StaffPackageAssignedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $staff;
    public $message;
    public $customer;
    public $orders;

    /**
     * Create a new event instance.
     */
    public function __construct($staff, $customer, $orders,$message)
    {
        //
        $this->message = $message;
        $this->staff = $staff;
        $this->customer = $customer;
        $this->orders = $orders;
    }

    public function broadcastAs(){
        return 'staff-package-event';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        
        return [
            new PrivateChannel('staff-package.' . $this->staff->id)
        ];
    }
}
