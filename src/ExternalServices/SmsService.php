<?php

namespace Core\ExternalServices;

use Core\Utils\Constants;
use Illuminate\Support\Facades\Http;

class SmsService
{


    public function sendSms($to, $content)
    {
        $url = Constants::url; //url du serveur
        $apiKey  = Constants::apiKey; //remplacez par votre api key
        $clientId = Constants::clientId; //Remplacez par votre client Id

        $smsData = array(
            'from' => 'Ylomi', //l'expediteur
            'to' => '' . $to . '', //destination au format international sans "+" ni "00". Ex: 22966413718
            'type' => 0, //type de message text et flash
            'message' => urlencode($content), //le contenu de votre sms
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("APIKEY: " . $apiKey, "CLIENTID:" . $clientId));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,   $smsData);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
