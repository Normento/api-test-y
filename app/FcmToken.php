<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class FcmToken extends Model
{
    use HasFactory, Notifiable;

    protected $fcmToken;

    public function __construct($fcmToken)
    {
        $this->fcmToken = $fcmToken;
    }

    public function routeNotificationForFcm($notification)
    {
        return $this->fcmToken;
    }
}
