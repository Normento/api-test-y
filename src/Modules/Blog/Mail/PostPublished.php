<?php

namespace Core\Modules\Blog\Mail;

use Core\Modules\Blog\Models\Post;
use Core\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PostPublished extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $year;
    public User $customer;
    public Post $post;
    public string $postUrl;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $customer, Post $post)
    {
        $this->year = date('Y');
        $this->customer = $customer;
        $this->post  = $post;
        $this->postUrl = "https://ylomi.net/blog/" . $post->slug;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->from('infos@ylomi.net', 'Ylomi')
            ->subject("Un nouvel article de blog vient d'etre publié sur YLOMI.")
            ->view('post_published');
    }
}
