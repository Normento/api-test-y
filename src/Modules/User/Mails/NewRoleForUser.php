<?php

namespace Core\Modules\User\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Core\Modules\User\Models\User;

class NewRoleForUser extends Mailable implements ShouldQueue
{
    public User $user;
    public  User $admin;
    public $year;
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user,User $admin)
    {
        $this->year = date("Y");
        $this->user = $user;
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
            ->subject("Attribution d'un nouveau rôle")
            ->view('new_role_for_user');
    }
}
