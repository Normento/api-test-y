<?php

namespace Core\Modules\Auth\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountActivation extends Mailable implements  ShouldQueue
{
    use Queueable, SerializesModels;
    public $year, $code, $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $code)
    {
        $this->year = date("Y");
        $this->code = $code;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Activation de compte")
            ->view('account_activation');
    }
}
