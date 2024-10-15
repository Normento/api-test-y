<?php

namespace Core\Modules\PunctualOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OfferRejected extends Mailable implements  ShouldQueue
{
    use Queueable, SerializesModels;
    public $year, $offer, $user, $admin;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user,$admin,$offer)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->offer = $offer;
        $this->admin = $admin;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Une offre a été rejeté")
            ->view('offer_rejected');
    }
}
