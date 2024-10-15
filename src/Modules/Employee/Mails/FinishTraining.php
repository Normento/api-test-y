<?php

namespace Core\Modules\Employee\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FinishTraining extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $admin, $employee;

    public function __construct($admin, $employee)
    {
        $this->admin = $admin;
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
            ->subject("Formation terminée")
            ->view('finish_training');
    }
}
