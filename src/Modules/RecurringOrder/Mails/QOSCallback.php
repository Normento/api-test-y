<?php

namespace Core\Modules\RecurringOrder\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QOSCallback extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $transref;
    public $status;
    public $user;
    public $type;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($transref, $status, $user, $type)
    {
        $this->transref = $transref;
        $this->status = $status;
        $this->user = $user;
        $this->type = $type;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Transaction QOS.")
            ->view('qoscallback');
    }
}
