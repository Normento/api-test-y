<?php

namespace Core\Modules\Email;

use Carbon\Carbon;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Message;
use Core\ExternalServices\ImapService;
use Exception;
use Illuminate\Http\Request;
use Webklex\PHPIMAP\Exceptions\ResponseException;

class EmailRepository
{

    public function __construct()
    {
    }

    public function countMessageByFolder(string $folderName): ?int
    {
        $connection = ImapService::connection();
        $folder = $connection->getFolder($folderName);
        return $folder->messages()->all()->count();
    }

    public function getfolders()
    {
        $connection = ImapService::connection();
        $folders = $connection->getFolders(false);

        $folderDetails = [];
        foreach ($folders as $folder) {
        $messageCount = $folder->messages()->all()->count();
        $folderDetails[] = [
            'name' => $folder->name,
            'message_count' => $messageCount
        ];
    }
        return $folderDetails;
    }

    public function getMailsByFolder($folderName, $page, $limit)
    {
        $connection = ImapService::connection();
        $folder = $connection->getFolder($folderName);

        $messages = $folder->messages()->all()->fetchOrderDesc()->limit($limit, intval($page))->setFetchBody(false)->get();

        $messageList = [];
        foreach ($messages as $message) {
            $date = Carbon::parse($message->getDate());
            $flags = $message->getFlags()->toArray();

            $fullMessage = $folder->messages()->getMessageByUid($message->uid);

            $attachmentsCount = $fullMessage->getAttachments()->count();

            $messageList[] = [
                'uid' => $message->uid,
                'id' => $message->getMessageId()[0],
                'is_seen' => in_array('Seen', $flags) ? true : false,
                'to' => $message->getTo()[0],
                'subject' => $message->getSubject()[0],
                'from' => $message->getFrom()[0],
                'attachments_count' => $attachmentsCount,
                'date' => $date->format('Y-m-d H:i:s'),
            ];
        }
        $connection->disconnect();

        return array_reverse($messageList);
    }
    public function decodeMimeStr($string)
    {
        $elements = imap_mime_header_decode($string);
        $decoded = '';

        foreach ($elements as $element) {
            $decoded .= $element->text;
        }

        return $decoded;
    }
    public function foldersName()
    {
        $connection = ImapService::connection();
        $folders = [];
        $inboxFolders = $connection->getFolders();
        foreach ($inboxFolders as $folder) {
            $folders[] = $folder->name;
        }
        $connection->disconnect();
        return $folders;

    }

    public function getMailDetail($message_uid, $folderName)
    {
        $connection = ImapService::connection();
        $folder = $connection->getFolder($folderName);
        $message = $folder->messages()->getMessageByUid((int) $message_uid);
        $flags = $message->getFlags()->toArray();

        $attachments = [];
        foreach ($message->getAttachments() as $index => $attachment) {
            $attachments[] = [
                'name' => $attachment->name,
                'size' => $attachment->size,
                'content' => base64_encode($attachment->content),
                "content_type" => $attachment->getAttributes()['content_type'],
            ];
        }

        $subject = $this->decodeMimeStr($message->getSubject()[0]);
        $date = Carbon::parse($message->getDate());


        $cleanHtmlBody = htmlspecialchars_decode($message->getHTMLBody(), ENT_HTML5);
        $body = $this->decodeMimeStr($cleanHtmlBody);

        $tab = [
            "id" => $message->getMessageId()[0],
            "is_seen" => in_array("Seen", $flags) ? true : false,
            "subject" => $subject,
            'from' => $message->getFrom()[0],
            "to" => $message->getTo()[0],
            "date" =>  $date,
            "attachements_count" => $message->getAttachments()->count(),
            "attachements" => $attachments,
            "html" => $body,
        ];
        $connection->disconnect();
        return $tab;
    }


    public function  emptyTrashFolder()
    {
        $folderName = "Trash";
        $connection = ImapService::connection();
        $folder = $connection->getFolder($folderName);
        $messages =  $folder->messages()->all()->setFetchBody(false)->get();

        $deletedCount = 0;
        foreach ($messages as $message) {
            $message->delete();
            $deletedCount++;
        }
        return ['message' => "$deletedCount messages vidées de la corbeille "];
    }


    public function getMail($folderName, $messageId): Message| bool
    {
        $connection = ImapService::connection();
        $folder = $connection->getFolder($folderName);
        $message =   $folder->query()->getMessage($messageId);
        return $message;
    }

    public function move(string $fromFolderName, $message_uid, string $toFolderName)
    {
        $connection = ImapService::connection();
        $fromFolder = $connection->getFolder($fromFolderName);
        $message = $fromFolder->messages()->getMessageByUid($message_uid);
        $toFolder = $connection->getFolder($toFolderName);
        if (is_null($toFolder)) {
            $connection->createFolder($toFolderName, true);
        }
        $newMessage  =  $message->move($toFolderName, true);

        return [
            'new_uid' => $newMessage->getAttributes()['uid'],
            'message' => "Message déplacé dans le dossier '$toFolderName' avec succès."
        ];

    }

    public function deleteMail($message_uid, string $folderName)
    {
        $connection = ImapService::connection();
        $folder = $connection->getFolder($folderName);
        $message = $folder->messages()->getMessageByUid($message_uid);

        if ($message) {
            $trashFolder = $connection->getFolder('Trash');
            if (is_null($trashFolder)) {
                $connection->createFolder('Trash', true);
                $trashFolder = $connection->getFolder('Trash');
            }

            $newMessage = $message->move('Trash', true);

            return[
                'new_uid' => $newMessage->getAttributes()['uid'],
                'message' => 'Message supprimé avec succès.'
            ];
        }
    }


    public function markMessageAs($seen, $message_uid, $folderName)
    {
        $connection = ImapService::connection();
        $folder = $connection->getFolder($folderName);
        $message = $folder->messages()->getMessageByUid($message_uid);
        $flags = $message->getFlags()->toArray();
        $isSeen =   in_array("Seen", $flags) ? true : false;
        if (boolval($seen)) {
            return  $isSeen ? false : $message->setFlag('SEEN');
        } else {
            return !boolval($isSeen) ? false : $message->unsetFlag('SEEN');
        }
    }
}
