<?php

namespace Core\Modules\RecurringOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmEmployeesDeploymentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $year, $admin, $employeesAssigned, $businessRecurringOrder, $contrat_started_date, $co;
    public function __construct($admin, $employeesAssigned, $businessRecurringOrder, $contrat_started_date, $co)
    {
        $this->admin = $admin;
        $this->year = date("Y");
        $this->employeesAssigned = $employeesAssigned;
        $this->businessRecurringOrder = $businessRecurringOrder;
        $this->co = $co;

        $this->contrat_started_date = $contrat_started_date;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Confirmation du déploiement des employés  chez un client pour une commande Business récurrente")
            ->view('confirm_employees_deployment');
    }
}
