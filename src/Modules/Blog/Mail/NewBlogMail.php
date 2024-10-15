<?php

namespace Core\Modules\Blog\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewBlogMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $year;
    public $user;
    public $post;
    public $postDetailsUrl;



    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $post)
    {
        $this->year = date("Y");
        $this->user = $user;
        $this->post = $post;
        $this->postDetailsUrl = "https://administration.ylomi.net/blog/detail/" . $post->slug . "-" . $post->id . "";
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Un nouveau article de blog vient d'etre crée.")
            ->view('new_blog');
    }
}
