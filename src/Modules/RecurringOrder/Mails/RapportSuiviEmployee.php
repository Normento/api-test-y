<?php

namespace Core\Modules\RecurringOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Core\Modules\User\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RapportSuiviEmployee extends Mailable
{
    use Queueable, SerializesModels;

    public $year, $suivis, $user, $suivi_type,$ras,$unreachable;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($suivis, User $user, $suivi_type, $ras,$unreachable)
    {
        $this->year = date("Y");
        $this->suivis = $suivis;
        $this->suivi_type = $suivi_type;
        $this->user = $user;
        $this->ras = $ras;
        $this->unreachable = $unreachable;

    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
        ->subject($this->suivi_type == 'client' ? "Rapport de suivi des clients" : "Rapport de suivi des employés" )
        ->view('employee_suivis_rapport');
    }
}
