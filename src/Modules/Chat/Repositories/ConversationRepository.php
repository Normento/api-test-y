<?php

namespace Core\Modules\Chat\Repositories;

use Core\Utils\BaseRepository;
use Illuminate\Support\Facades\Auth;
use Core\Modules\Chat\Models\Message;
use Core\Modules\Chat\Models\Conversation;

class ConversationRepository extends BaseRepository
{
    private Conversation $conversationModel;

    public function __construct(Conversation $conversationModel)
    {
        $this->conversationModel = $conversationModel;
        parent::__construct($conversationModel);
    }

    /**
     * Retrieve all conversations.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getConversations()
    {
        $user = Auth::user();
        if (!$user->hasRole(['super-admin', 'RRC'])) {
            throw new \Exception('Unauthorized');
        }

        $conversations = $this->conversationModel::with(['client', 'messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])
        ->get()
        ->map(function ($conversation) use ($user) {
            $lastMessage = $conversation->messages->last();
            $unreadMessagesCount = $conversation->messages->whereNull('read_at')->where('sender_id', '!=', $user->id)->count();

            return [
                'conversation_id' => $conversation->id,
                'client' => $conversation->client,
                'last_message' => $lastMessage,
                'unread_messages_count' => $unreadMessagesCount,
                'last_message_created_at' => optional($lastMessage)->created_at,
            ];
        })
        ->sortByDesc('last_message_created_at')
        ->values();

        return $conversations;
    }


    /**
     * Retrieve all conversations and messages for a specific client.
     *
     * @param string $clientId
     * @return array
     */

     public function filterConversationById(string $conversationId)
     {
         // Vérifier si l'utilisateur authentifié a le rôle 'admin' ou 'RRC'
         $user = Auth::user();
         if (!$user->hasRole(['super-admin', 'RRC'])) {
             throw new \Exception('Unauthorized');
         }

         // Récupérer la conversation spécifique par ID
         $conversation = $this->conversationModel::where('id', $conversationId)
             ->with(['messages' => function ($query) {
                $query->withTrashed()
                ->orderBy('created_at', 'asc');
             }])
             ->first();

         // Vérifier si la conversation existe
         if (!$conversation) {
             throw new \Exception('Conversation not found');
         }

         // Récupérer les messages de la conversation
         $messages = $conversation->messages->map(function ($message) {
             return [
                 'id' => $message->id,
                 'content' => $message->content,
                 'created_at' => $message->created_at,
                 'read_at' => $message->read_at,
                 'sender_id' => $message->sender_id,
                 'deleted_at' => $message->deleted_at,
             ];
         });

         return $messages->toArray();
     }


}
