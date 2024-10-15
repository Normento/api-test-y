<?php

namespace Core\Modules\Email\Mails;

use Core\ExternalServices\ImapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Webklex\IMAP\Facades\Client;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $data, $attachedFilesPaths, $html;
    public function __construct($data, array $attachedFilesPaths = [], $is_tagged, $customers = [])
    {
        $this->data = $data;
        $this->attachedFilesPaths = $attachedFilesPaths;
        if ($is_tagged) {
            $this->html = $this->replaceTags($data['body'], $customers);
        } else {
            $this->html = $data['body'];
        }
    }


    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail =  $this->view('emails.send')->from('infos@ylomi.net', 'Ylomi')->subject($this->data["subject"]);
        if (array_key_exists("cc", $this->data)  && count($this->data['cc']) > 0) {
            $mail->cc($this->data['cc']);
        }
        if (count($this->attachedFilesPaths) != 0) {
            foreach ($this->attachedFilesPaths as $attachement) {
                $mail->attachFromStorageDisk('s3', $attachement);
            }
        }
        return $mail;
    }

    private function replaceTags($body, $customers)
    {
        foreach ($customers as $customer) {
            $tagValues = [
                '@full_name' => $customer->first_name . $customer->last_name,
                '@first_name' => $customer->first_name,
                "@last_name" => $customer->last_name
                // Ajoute d'autres correspondances de tags et de valeurs ici
            ];

            $body = str_replace(array_keys($tagValues), array_values($tagValues), $body);
        }

        return $body;
    }
}
