<?php

namespace Core\Modules\PunctualOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderNoted extends Mailable implements  ShouldQueue
{
    use Queueable, SerializesModels;
    public $year, $order, $user, $admin;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user,$admin,$order)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->order = $order;
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
            ->subject("Une commande a été noté")
            ->view('new_order_noted');
    }
}
