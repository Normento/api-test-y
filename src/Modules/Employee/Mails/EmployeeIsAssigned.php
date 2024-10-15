<?php

namespace Core\Modules\Employee\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeIsAssigned extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public  $co, $employee;

    public function __construct($co, $employee)
    {
        $this->co = $co;
        $this->employee = $employee;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Un employée vous est assigné")
            ->view('employee_is_assigned');
    }

}
