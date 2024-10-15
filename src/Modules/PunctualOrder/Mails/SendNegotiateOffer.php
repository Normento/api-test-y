<?php

namespace Core\Modules\PunctualOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendNegotiationOnOffer extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public  $order, $user, $admin, $offer, $modifyOffer;
    public string $year;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user,$admin,$order, $offer, $modifyOffer)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->order = $order;
        $this->admin = $admin;
        $this->offer = $offer;
        $this->modifyOffer = $modifyOffer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Négociation sur l'offre de la commande")
            ->view('emails.send_negotiate_offer');
    }
}
