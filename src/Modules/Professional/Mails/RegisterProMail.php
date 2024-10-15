<?php

namespace Core\Modules\Professional\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegisterProMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $year, $pro, $user, $admin;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($pro, $admin, $user = null)
    {
        $this->year = date("Y");
        $this->pro = $pro;
        $this->user = $user;
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
            ->subject("Nouvelle inscription de prestataire")
            ->view('emails.register_pro');
    }
}
