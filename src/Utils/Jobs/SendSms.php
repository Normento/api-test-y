<?php

namespace Core\Utils\Jobs;

use Core\Utils\Constants;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $receiver;
    protected $message;


    /**
     * Create a new job instance.
     */
    public function __construct($receiver, $message)
    {
        $this->receiver = $receiver;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = Constants::url;
        $apiKey = Constants::apiKey;
        $clientId = Constants::clientId;
        $smsData = array(
            'from' => 'Ylomi',
            'to' => '' . $this->receiver . '',
            'type' => 0,
            'message' => $this->message,
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("APIKEY: " . $apiKey, "CLIENTID:" . $clientId));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $smsData);
        curl_exec($ch);
        curl_close($ch);
    }

}
