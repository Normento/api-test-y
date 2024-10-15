<?php

namespace Core\Modules\PunctualOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendNotificationCustomerNewOffer extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $year, $user, $order, $offers, $pro;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $order, $offers, $pro)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->order = $order;
        $this->offers = $offers;
        $this->pro = $pro;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Une offre pour votre commande")
            ->view('emails.send_notification_customer_new_offer');
    }
}
