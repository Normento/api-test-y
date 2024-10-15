<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Core\Utils\Controller;
use Kreait\Firebase\Messaging\AndroidConfig;

class PushNotificationController extends Controller
{
    //
    public static function sendNotification($title,$body,$data, $token)
    {
        $firebase = (new Factory)
            ->withServiceAccount(storage_path('app/firebase-auth.json'));

        $messaging = $firebase->createMessaging();

        $message = CloudMessage::withTarget('token', $token)
        ->withNotification([
                'title' => $title,
                'body' => $body,
                'data' =>$data,
        ])
        ->withAndroidConfig(
            AndroidConfig::new()
            ->withMaximalNotificationPriority()
        );

        $messaging->send($message);

        return response()->json(['message' => 'Notification push envoyée avec succès']);
    }
}
