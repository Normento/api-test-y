<?php

namespace Core\Modules\Employee\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeIsValidate  extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Employée Validé")
            ->view('employee_is_validate');
    }
}
