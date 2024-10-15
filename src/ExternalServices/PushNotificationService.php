<?php

namespace Core\ExternalServices;

use Core\Utils\Controller;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\AndroidConfig;

class PushNotificationService
{
    public static function sendNotification($title, $body, $data, $token)
    {
        $firebase = (new Factory)
            ->withServiceAccount(public_path('firebase-auth.json'));
        $messaging = $firebase->createMessaging();


        $androidNotification = [
            'title' => $title,
            'body' => $body,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];


        $message = CloudMessage::withTarget('token', $token)
            ->withNotification([
                'title' => $title,
                'body' => $body,
        ])
        ->withData($data)
        ->withAndroidConfig(AndroidConfig::fromArray([
            'priority' => 'high',
            'notification' => $androidNotification,
        ]));

        $messaging->send($message);

        return response(['message' => 'Notification push envoyée avec succès']);
    }
}
