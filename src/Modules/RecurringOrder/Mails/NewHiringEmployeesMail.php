<?php

namespace Core\Modules\RecurringOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewHiringEmployeesMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $employeesAssigned, $businessRecurringOrder, $year, $user;
    public function __construct($user, $employeesAssigned, $businessRecurringOrder)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->employeesAssigned = $employeesAssigned;
        $this->businessRecurringOrder = $businessRecurringOrder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Avis d'embauchage des employés d'une commande reccurente")
            ->view('new_hiring_employees');
    }
}
