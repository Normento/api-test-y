<?php

namespace Core\Modules\PunctualOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeNotedMail extends Mailable implements  ShouldQueue
{
    use Queueable, SerializesModels;
    public $year, $notes, $user, $admin, $paymentMonth,$paymentYear;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user,$admin,$notes,$paymentMonth,$paymentYear)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->notes = $notes;
        $this->admin = $admin;
        $this->paymentMonth = $paymentMonth;
        $this->paymentYear = $paymentYear;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Un Employe a été noté")
            ->view('employee_noted');
    }
}
