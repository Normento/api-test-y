<?php

namespace Core\Modules\PunctualOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendCompletingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public  $order, $user, $admin, $offer;
    public string $year;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user,$order)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->order = $order;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Commande terminée")
            ->view('emails.send_completing_mail');
    }
}
