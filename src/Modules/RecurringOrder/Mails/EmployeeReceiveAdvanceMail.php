<?php

namespace Core\Modules\RecurringOrder\Mails;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeReceiveAdvanceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $year, $payment, $number, $balance, $user, $admin;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($admin, $payment, $number, $balance, $user)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->balance = $balance;
        $this->payment = $payment;
        $this->number = $number;
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
            ->subject("Transfert d'avance de salaire à un employé.")
            ->view('employee_received_advance_salaray');
    }
}
