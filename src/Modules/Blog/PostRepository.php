<?php

namespace Core\Modules\Blog;

use Core\Modules\Blog\Models\Post;
use Core\Utils\BaseRepository;
use Core\Utils\Constants;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Normalizer;

class PostRepository extends BaseRepository
{
    protected Post $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
        parent::__construct($post);
    }

    public function searchPost($request)
    {
        $result = collect();
        if ($request->has('title') && !$request->has(['start_date', 'end_date', 'status'])) {
            $filter = $request->input('title');
            $normalizedFilter = mb_strtolower(normalizer_normalize($filter, Normalizer::FORM_D));
            $result = $this->post->whereRaw('lower(unaccent(title)) ilike ?', ['%' . $normalizedFilter . '%'])->orderBy('created_at', 'desc')->get();
        }

        if ($request->has('status') && !$request->has(['title', 'start_date', 'end_date'])) {
            $result = $this->post->where('status', $request->input('status'))->orderBy('created_at', 'desc')->get();
        }

        if ($request->has(['status', 'end_date', 'start_date']) && !$request->has('title')) {
            $dateFrom = date($request->input('start_date'));
            $dateTo = date($request->input('end_date'));
            $result = $this->post->where('status', $request->input('status'))->whereBetween($request->input('status') == 0 ? 'created_at' : ($request->input('status') == 1 ? 'validation_date' : 'published_date'), [$dateFrom, $dateTo])->orderBy('created_at', 'desc')->get();
        }

        if ($request->has(['end_date', 'start_date']) && !$request->has(['title', 'status'])) {
            $result = $this->post->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])->orderBy('created_at', 'desc')->get();
        }

        return  $result ;
    }


}
