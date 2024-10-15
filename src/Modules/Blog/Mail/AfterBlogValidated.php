<?php

namespace Core\Modules\Blog\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AfterBlogValidated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $year;
    public $post;
    public $validatedBy;
    public $blogDetailsUrl;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($post, $validatedBy)
    {
        $this->year = date("Y");
        $this->post = $post;
        $this->validatedBy = $validatedBy;
        $this->blogDetailsUrl = "https://administration.ylomi.net/blog/detail/" . $post->slug . "-" . $post->id . "";
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Un nouveau article de blog vient d'être validé.")
            ->view('after_blog_is_validated');
    }
}
