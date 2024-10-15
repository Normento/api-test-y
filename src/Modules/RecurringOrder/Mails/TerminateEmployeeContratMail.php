<?php

namespace Core\Modules\RecurringOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TerminateEmployeeContratMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $year, $proposition, $user;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $proposition)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->proposition = $proposition;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Désistement d'un employé")
            ->view('dishiring_employee');
    }

}
