<?php

namespace Core\Modules\Chat;

use Core\Utils\Constants;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Core\Modules\Chat\Models\Message;
use Core\Modules\Chat\Models\Conversation;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\Chat\Events\NewMessageEvent;
use Core\Modules\Chat\Events\DeleteMessageEvent;
use Core\Modules\Chat\Events\UpdateMessageEvent;
use Core\Modules\Chat\Repositories\ChatRepository;
use Core\Modules\Chat\Requests\StoreMessageRequest;
use Core\Modules\Chat\Events\MarkMessageAsReadEvent;
use Core\Modules\Chat\Requests\UpdateMessageRequest;


#[Route('/chat')]

class ChatController extends Controller
{
    protected $chatRepository;

    public function __construct(ChatRepository $chatRepository)
    {
        $this->chatRepository = $chatRepository;
    }

    /**
     * Liste de toutes les conversation coté dashboard.
     */

     #[Route('/conversations', methods: ['GET'], middleware: ['auth:sanctum','checkRRC'])]
     public function index(Request $request): Response
     {
         $response['message'] = 'Liste des conversations';

         $hasMessage = filter_var($request->query('hasMessage', 'true'), FILTER_VALIDATE_BOOLEAN);
         $name = $request->query('name', '');

         if (!empty($name)) {
             $data = $this->chatRepository->searchConversations($name, $hasMessage);
         } else {
             if ($hasMessage) {
                 $data = $this->chatRepository->getActiveConversations();
             } else {
                 $data = $this->chatRepository->getEmptyConversations();
             }
         }

         $response['data'] = $data;
         return response($response, 200);
     }

    /**
     * Envoyer un message.
     */
#[Route('/conversation/{conversation}', methods: ['POST'], wheres: ['conversation' => Constants::REGEXUUID], middleware: ['auth:sanctum'])]
public function store(StoreMessageRequest $request, Conversation $conversation)
{
    $user = $request->user();
    $content = $request->input('content');
    $createdAt = $request->input('created_at', now());


    $message = $this->chatRepository->storeMessage($content, $createdAt, $user, $conversation);

    broadcast(new NewMessageEvent($message))->toOthers();

    return response([
        'message' => 'Message stored successfully.',
        'data' => $message
    ], 201);

}

    /**
     * Details d'une conversation coté dashboard.
     */
    #[Route('/conversation/{conversation}', methods: ['GET'], wheres: ['conversation' => Constants::REGEXUUID], middleware: ['auth:sanctum', 'role:RRC'])]
    public function show(Conversation $conversation)
    {

        $data = $conversation->load(['messages' => function ($query) {
            $query->withTrashed()->orderBy('created_at', 'asc');
        }]);
        $response["message"] = "Liste des messages de la conversation";
        $response["data"] = $data;
        return $response;

    }


    /**
     * Modifier un message coté dashboard.
     */
    #[Route('/message/{message}', methods: ['PUT'], wheres: ['message' => Constants::REGEXUUID], middleware: ['auth:sanctum', 'role:RRC'])]
    public function update(UpdateMessageRequest $request, Message $message)
    {

        $user = $request->user();
        $newContent = $request->newContent;

        $message = $this->chatRepository->updateMessage($message, $newContent, $user);

        broadcast(new UpdateMessageEvent($message));

        return response([
            'message' => 'Message updated successfully.',
            'data' => $message,
        ], 200);


    }



    /**
     * Supprimer un message coté dashboard.
     */
    #[Route('/message/{message}', methods: ['DELETE'], wheres: ['message' => Constants::REGEXUUID], middleware: ['auth:sanctum', 'role:RRC'])]
    public function delete(Request $request, Message $message)
    {
        $user = $request->user();

        $message = $this->chatRepository->deleteMessage($message, $user);

        broadcast(new DeleteMessageEvent($message));

        return response([
            'message' => 'Message deleted successfully.',
            'data' => $message,
        ], 200);



    }


    /**
     * Marqué les message d'une conversation comme lu coté dashboard.
     */
    #[Route('/conversation/{conversation}/mark-as-read', methods: ['POST'], wheres: ['conversation' => Constants::REGEXUUID], middleware: ['auth:sanctum', 'role:RRC'])]
    public function markMessagesAsRead(Request $request, Conversation $conversation)
    {
        $user = $request->user();


        $message = $this->chatRepository->markMessagesAsRead($conversation, $user);

        if ($message) {
            broadcast(new MarkMessageAsReadEvent($message));

            return response(['message' => 'Messages marked as read.'], 200);
        } else {
            return response(['message' => 'Failed to mark messages as read.'], 403);
        }



    }

    /**
     * Recuperer tout les message envoyé comme reçu d'un client coté mobile.
     */
    #[Route('/conversation/{conversation}/messages', methods: ['GET'], wheres: ['conversation' => Constants::REGEXUUID], middleware: ['auth:sanctum', 'role:customer'])]
    public function getUserConversation(Request $request, Conversation $conversation)
    {
        $user = $request->user();


        $messages = $this->chatRepository->getUsersConversation($user, $conversation);

        return response([
            'message' => 'Messages retrieved successfully.',
            'data' => $messages
        ], 200);


    }








}
