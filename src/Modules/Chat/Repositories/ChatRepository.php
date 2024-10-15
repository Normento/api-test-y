<?php

namespace Core\Modules\Chat\Repositories;

use DateTime;
use Core\Utils\BaseRepository;
use Core\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Core\Modules\Chat\Models\Message;
use Core\Modules\Chat\Models\Conversation;
use Core\ExternalServices\PushNotificationService;
use Core\Modules\Chat\Jobs\StoreMessageJob;
use Core\Modules\Chat\Events\NewMessageEvent;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChatRepository extends BaseRepository
{
    private Message $messageModel;
    private Conversation $conversationModel;
    private PushNotificationService $pushNotificationService;

    public function __construct(Message $messageModel, Conversation $conversationModel , PushNotificationService $pushNotificationService)
    {
        $this->messageModel = $messageModel;
        $this->conversationModel = $conversationModel;
        $this->pushNotificationService = $pushNotificationService;
        parent::__construct($messageModel);
    }

    //CREER UNE CONVERSATION POUR LE CLIENT A L'ACTIVATION DE SON COMPTE
    public function createConversationForClient(User $user){

        $admin = User::role('RRC')->firstOrFail();
        $conversation = new Conversation();
        $conversation->client()->associate($user);
        $conversation->admin()->associate($admin);
        $conversation->save();

        return $conversation;
    }

    //DASHBOARD RECUPERER LA LISTE DES CONVERSATIONS QUI ONT AU MOINS UN MESSAGE
    public function getActiveConversations()
    {
        return $this->conversationModel::whereHas('messages')
            ->with(['client', 'messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->get()
            ->map(function ($conversation) {
                $lastMessage = $conversation->messages->last();
                $unreadMessagesCount = $conversation->messages->whereNull('read_at')->where('sender_id',$conversation->client_id)->count();

                return [
                    'conversation_id' => $conversation->id,
                    'client' => $conversation->client,
                    'last_message' => $lastMessage,
                    'unread_messages_count' => $unreadMessagesCount,
                    'last_message_created_at' => optional($lastMessage)->created_at,
                ];
            });
    }

    public function getEmptyConversations()
    {
        return $this->conversationModel::whereDoesntHave('messages')
            ->with('client')
            ->get()
            ->map(function ($conversation) {
                return [
                    'conversation_id' => $conversation->id,
                    'client' => $conversation->client,
                ];
            });
    }


    public function storeMessage(string $content, $createdAt, User $user, Conversation $conversation)
    {
        $receiverId = $this->determineReceiverId($user, $conversation);

        $message = new Message([
            'content' => $content,
            'read_at' => null,
            'created_at' => $createdAt,
        ]);
        $message->sender()->associate($user);
        $message->conversation()->associate($conversation);
        $message->save();

        $message['receiver_id'] = $receiverId;

        if ($user->hasRole('RRC')) {

            $this->sendPushNotificationToClient($message, $message['receiver_id']);

        }

        return $message->toArray();
    }

    private function determineReceiverId(User $sender, Conversation $conversation)
    {
        if (!$sender->hasRole('RRC') && !$sender->hasRole('customer')) {
            throw new \Exception('Unauthorized: you must have the role RRC or customer to send a message.', 403);
        }

        return $conversation->client_id === $sender->id ? $conversation->admin_id : $conversation->client_id;
    }


private function sendPushNotificationToClient($message, $receiverId)
{
    $receiver = User::find($receiverId);

    if ($receiver) {
        $token = $receiver->notif_token;

        if ($token) {
            $title = 'Nouveau message du support';
            $body = $message->content;
            $data = [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'created_at' => $message->created_at,
            ];

            $response = PushNotificationService::sendNotification($title, $body, $data, $token);


            // Log the response for debugging purposes
            Log::info('Push Notification Response: ', ['response' => $response]);
        } else {
            Log::warning('User does not have a notification token', ['receiver_id' => $receiverId]);
        }
    } else {
        Log::warning('Receiver not found', ['receiver_id' => $receiverId]);
    }
}



public function markMessagesAsRead(Conversation $conversation, User $user)
{
    if ($user->hasRole('RRC')) {
        $unreadMessages = $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->get();


        foreach ($unreadMessages as $message) {
            $message->read_at = now();
            $message->save();
        }

        return $unreadMessages;
    }

    return false;
}


public function deleteMessage(Message $message, User $user)
{
    if ($message && ($message->sender_id === $user->id)) {
        $conversation = $message->conversation;

        $receiverId = $message->sender_id === $conversation->client_id
            ? $conversation->admin_id
            : $conversation->client_id;

        $message->delete();

        $message['receiver_id'] = $receiverId;

        return $message;
    }

}



public function updateMessage(Message $message, string $newContent, User $user)
{
    if ($message && $message->sender_id === $user->id) {
        $message->content = $newContent;
        $conversation = $message->conversation;

        $receiverId = $message->sender_id === $conversation->client_id
            ? $conversation->admin_id
            : $conversation->client_id;

        $message->save();

        $message['receiver_id'] = $receiverId;

        return $message;
    }

}




    /**
     * Retrieve all messages sent and received by the authenticated user.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getUsersConversation(User $user, Conversation $conversation)
{
    // Vérifier que l'utilisateur fait partie de la conversation
    if ($conversation->client_id !== $user->id && $conversation->admin_id !== $user->id) {
        throw new \Exception('Unauthorized: You are not a participant of this conversation.', 403);
    }

    // Récupérer les messages de la conversation
    $messages = $this->messageModel::withTrashed()
        ->where('conversation_id', $conversation->id)
        ->orderBy('created_at')
        ->get()
        ->map(function ($message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'created_at' => $message->created_at,
                'read_at' => $message->read_at,
                'sender_id' => $message->sender_id,
                'deleted_at' => $message->deleted_at,
                'conversation_id' => $message->conversation_id
            ];
        });

    return $messages;
}


    public function searchConversations(string $name, bool $hasMessage)
{
    $conversationsQuery = $this->conversationModel::with(['client', 'messages' => function ($query) {
        $query->orderBy('created_at', 'asc');
    }]);

    if ($hasMessage) {
        $conversationsQuery->whereHas('messages');
    } else {
        $conversationsQuery->whereDoesntHave('messages');
    }

    $conversations = $conversationsQuery->whereHas('client', function ($query) use ($name) {
        $query->whereRaw('LOWER(first_name) LIKE ?', ["%".strtolower($name)."%"])
              ->orWhereRaw('LOWER(last_name) LIKE ?', ["%".strtolower($name)."%"]);
    })
    ->get()
    ->map(function ($conversation) use ($hasMessage) {
        $lastMessage = $hasMessage ? $conversation->messages->last() : null;
        $unreadMessagesCount = $hasMessage ? $conversation->messages
            ->whereNull('read_at')
            ->where('sender_id', $conversation->client_id)
            ->count() : 0;

        return [
            'conversation_id' => $conversation->id,
            'client' => $conversation->client,
            'last_message' => $lastMessage,
            'unread_messages_count' => $unreadMessagesCount,
            'last_message_created_at' => optional($lastMessage)->created_at,
        ];
    });

    return $conversations;
}



}




