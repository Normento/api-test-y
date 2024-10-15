<?php

namespace Core\Modules\Blog;

use Core\Modules\Blog\Models\Post;
use Core\Modules\Blog\Models\Postviews;
use Core\Utils\BaseRepository;
use Illuminate\Http\Request;
use Normalizer;

class PostviewsRepository extends BaseRepository
{
    protected Postviews $postviews;

    public function __construct(Postviews $postviews)
    {
        $this->postviews = $postviews;
        parent::__construct($postviews);
    }
    public function store($data)
    {

        $this->postviews->insert([
            'browser_fingerprint' => $data['fingerprint'],
            'post_id' => $data['post_id']
        ]);

    }
    public function incrementViews(Request $request)
    {
        Post::where('id',$request->input('post_id'))->increment('views');
    }

    public function countPostViewsUser($data)
    {
      $user_views = 0;
      $user_views = $this->postviews->where([
        ['browser_fingerprint', $data['fingerprint']],
        ['post_id', $data['post_id']],
      ])->count();
      if($user_views == 0){
        $this->store($data);
      }
      return $user_views;

    }


}
