<?php

namespace Core\Modules\Blog;

use GuzzleHttp\Client;
use Core\Utils\Constants;
use Core\Utils\Controller;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Core\Modules\Blog\Models\Post;
use Core\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\Process\Process;
use Core\Modules\Blog\Mail\NewBlogMail;
use Core\Modules\Blog\Models\Postviews;
use Core\Modules\Blog\Mail\PostPublished;
use Core\ExternalServices\FacebookService;
use Core\ExternalServices\LinkedinService;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\Blog\Mail\AfterBlogValidated;
use Core\Modules\Blog\Requests\StorePostRequest;
use Core\Modules\Blog\Requests\UpdatePostRequest;
use Core\ExternalServices\PushNotificationService;
use Core\Modules\Blog\Requests\ShareLinkedInRequest;
use Core\Modules\Blog\Requests\StorePostviewsRequest;

#[Route('/posts')]
class PostController extends Controller
{
    private PostRepository $postRepository;
    private PushNotificationService $pushNotificationService;
    private PostviewsRepository $postviewsRepository;
    private LinkedinService $linkedinService;
    private FacebookService $facebookService;


    public function __construct(PostRepository $postRepository,LinkedinService $linkedinService, PushNotificationService $pushNotificationService,PostviewsRepository $postviewsRepository, FacebookService $facebookService)
    {
        $this->postRepository = $postRepository;
        $this->pushNotificationService = $pushNotificationService;
        $this->postviewsRepository = $postviewsRepository;
        $this->linkedinService = $linkedinService;
        $this->facebookService = $facebookService;
    }

    #[Route('/', methods: ['GET'], middleware: ['optional-auth'])]
    public function index(Request $request): Response
    {
        $response["message"] = "Liste des articles de blog";
        // users see only  published blog
        if (Auth::guest()) {
            $posts = $this->postRepository->findBy("status", 2, relations: ['author:id,first_name,last_name'], collection: true, paginate: true);
            $posts->transform(function ($post) {
                $post->image = $this->s3FileUrl($post->image);
                return $post;
            });
        } else {

            $posts = ($request->query->count() == 0 || $request->has('page')) ? $this->postRepository->all(relations: ['author:id,first_name,last_name,profile_image'],
                    paginate: true) : $this->postRepository->searchPost($request);

            $posts->transform(function ($post) use ($request) {
                $post->image = $this->s3FileUrl($post->image);

                if(!is_null($post->author->profile_image) && $request->query->count() == 0 ){
                    $post->author->profile_image = $post->author->profile_image;
                };
                if(!is_null($post->author->profile_image) && $request->query->count() > 0 && $request->input('page') != 1){
                    $post->author->profile_image = $this->s3FileUrl($post->author->profile_image);
                };
                return $post;
            });


        }
        $response['data'] = $posts;
        return response($response, 200);
    }

    #[Route('/{post}', methods: ['GET'], middleware: ['optional-auth'], wheres: ['post' => Constants::REGEXUUID])]
    public function show(Request $request, Post $post): Response
    {
        $response["message"] = "Détails d'un article ";
        $post = $post->load(['author:id,first_name,last_name,profile_image']);

        // Increment view with user logic
        // $postViewCookie = 'post_viewed_' . $post->id;
        $post->author->profile_image = $this->s3FileUrl($post->author->profile_image);
        $post->image = $this->s3FileUrl($post->image);
        $response["data"] = $post;
        $response["ip"] = $request->getClientIp();
        $response["ip2"] = $request->ip();

        return response($response, 200);
    }

    #[Route('/post-facebook', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function post_on_facebook(ShareLinkedInRequest $request)
    {
        $data = Arr::except($request->validated(), ['title','content','post_image','post_id',]);
        $data['title'] = $request->input('title');
        $data['content'] = $request->input('content');
        $data['post_image'] = $request->input('post_image');
        $data['post_id'] = $request->input('post_id');

        $response = $this->facebookService->publishOnFacebook($data);
        return response()->json(json_decode((string) $response->getBody()));
    }

    #[Route('/post-linkedin', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function post_on_linkedin(ShareLinkedInRequest $request)
    {

        $data = Arr::except($request->validated(), ['title','content','post_image','post_id',]);
        $data['title'] = $request->input('title');
        $data['content'] = $request->input('content');
        $data['post_image'] = $request->input('post_image');
        $data['post_id'] = $request->input('post_id');
        $response = $this->linkedinService->publishOnLinkedin($data);
        return response()->json(json_decode((string) $response->getBody()), $response->getStatusCode());

    }


    #[Route('/post-views', methods: ['POST'], middleware: ['optional-auth'])]
    public function incrementPostView(StorePostviewsRequest $request) : Response
    {
        $data = Arr::except($request->validated(), ['fingerprint','post_id']);
        $data['fingerprint'] = $request->input('fingerprint');
        $data['post_id'] = $request->input('post_id');
        $userViews =  $this->postviewsRepository->countPostViewsUser($data);
        if($userViews == 0){
            $this->postviewsRepository->incrementViews($request);
        }
        $response["views"] =$userViews;
        $response["message"] = "User views on this post";
        return response($response, 200);
    }





    #[Route('/', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function store(StorePostRequest $request): Response
    {
        $data = Arr::except($request->validated(), ['image']);
        $s3PostImagePath = $this->uploadFile($request->file('image'));

        $data['image'] = $s3PostImagePath;
        $data['slug'] = $data['title'];

        $post = $this->postRepository->make($data);
        $post = $this->postRepository->associate($post, ['author' => Auth::user()]);

        if (!Auth::user()->hasRole(['super-admin', 'admin'])) {
            foreach (User::role('admin')->get() as $user) {
                Mail::to($user->email)->send(new NewBlogMail($user, $post));
            }
        } else {
            $this->postRepository->update($post, ['status' => 1]);
        }
        $post = $post->refresh();
        $post->image = $this->s3FileUrl($post->image);

        $response["data"] = $post;
        $response["message"] = "Article de blog enregistré avec succès";
        return response($response, 201);
    }


    #[Route('/test', methods: ['GET'])]
    public function test () {
        broadcast(new \App\Events\ActivateEvent("YO"));
        return response("Success", 201);
    }

    #[Route('/{post}', methods: ['POST'], middleware: ['auth:sanctum'], wheres: ['post' => Constants::REGEXUUID])]
    public function update(UpdatePostRequest $request, Post $post): Response
    {
        $data = Arr::except($request->validated(), ['image']);
        if ($request->hasFile('image')) {
            $s3PostImagePath = $this->uploadFile($request->file('image'));
            $data['image'] = $s3PostImagePath;
        }
        $post = $this->postRepository->update($post, $data);
        $post->image = $this->s3FileUrl($postImagePath ?? $post->image);
        $post->load('author');
        $response["data"] = $post;
        $response["message"] = "Article de blog modifié avec succès";
        return response($response, 201);
    }


    #[Route('/', methods: ['DELETE'], middleware: ['auth:sanctum'])]
    public function delete(Post $post): Response
    {
        $this->postRepository->delete($post);
        $response["message"] = "Article de blog suprimé avec succès.";
        return response($response, 200);
    }




    #[Route('/{post}/processed', methods: ['GET'], middleware: ['auth:sanctum'], wheres: ['post' => Constants::REGEXUUID])]
    public function processedPost(Post $post): Response
    {
        if ($post->status == 0) {
            if (Auth::user()->hasRole(['super-admin', 'admin'])) {

                $this->postRepository->update($post, ['status' => 1, 'validation_date' => now()]);
                Mail::to($post->author->email)->send(new AfterBlogValidated($post, Auth::user()));
                $post->load('author');
                $post->image = $this->s3FileUrl($post->image);

                return response(['message' => "Article validé avec succès", 'data' => $post], 200);
            } else {
                return response(['message' => "Seul le super admin peut valider un article"], 400);
            }
        } elseif ($post->status == 1) {
            $deviceIds = [];
            $this->postRepository->update($post, ['status' => 2, 'published_date' => now()]);
            $this->postRepository->associate($post, ['publishedBy' => Auth::user()]);

          /*  foreach (User::role('customer')->get() as $customer) {
                if (!is_null($customer->devices)) {
                    foreach ($customer->devices as $device) {
                        $deviceIds[] = $device->token;
                    }
                }
                Mail::to($customer->email)->send(new PostPublished($customer, $post));
            }*/
            
            $post->load('author');
            $post->image = $this->s3FileUrl($post->image);
            return response(['message' => "Article publié avec succès", 'data' => $post], 200);
        } else {
            $this->postRepository->update($post, ['status' => 1]);
            $post->load('author');

            $post->image = $this->s3FileUrl($post->image);

            return response(['message' => "Publication de l'article annulée avec succès", 'data' => $post], 200);
        }
    }
}
