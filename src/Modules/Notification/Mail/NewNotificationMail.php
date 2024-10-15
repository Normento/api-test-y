<?php

namespace Core\Modules\Notification\Mail;

use Core\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $year;
    public $user, $createdBy;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, User $createdBy)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->createdBy = $createdBy;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Nouvelle Notification client crée")
            ->view('emails.new_notification');
    }
}
