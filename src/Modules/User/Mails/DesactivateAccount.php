<?php

namespace Core\Modules\User\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DesactivateAccount extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $user, $admin, $year;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $admin)
    {
        $this->user = $user;
        $this->admin = $admin;
        $this->year = date("Y");
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Retrait d'un admin du dashboard")
            ->view('desactivate_account');
    }
}
