<?php

namespace Core\Modules\Email;

use Core\Utils\Controller;
use Illuminate\Support\Facades\Mail;
use Core\Modules\User\UserRepository;
use Core\ExternalServices\ImapService;
use Core\Modules\Email\Mails\SendMail;
use Core\ExternalServices\MailFolderService;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\Email\Requests\SendMailRequest;
use Core\Modules\Email\Requests\MoveMailsRequest;
use Core\Modules\Email\Requests\DeleteMailRequest;
use Core\Modules\Email\Requests\RestoreMailsRequest;
use Core\Modules\Email\Requests\MarkMessageAsRequest;
use Core\Modules\Email\Requests\GetMailDetailsRequest;
use Core\Modules\Email\Requests\GetMailsByFolderRequest;



#[Route('/mails')]
class EmailController extends Controller
{

    protected readonly EmailRepository $emailRepository;
    protected readonly UserRepository $userRepository;
    protected readonly MailFolderService $mailFolderService;
    public function __construct(EmailRepository $emailRepository, UserRepository $userRepository, MailFolderService $mailFolderService)
    {
        $this->emailRepository = $emailRepository;
        $this->userRepository = $userRepository;
        $this->mailFolderService = $mailFolderService;
    }


    #[Route('/send', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function sendMail(SendMailRequest $request)
    {

        $attachments = [];
        if ($request->attachments) {

            if (count($request->attachments) > 0) {
                foreach ($request->attachments as $attachment) {
                    $path = $attachment->store(
                        'uploadedFile',
                    );
                    $attachments[] = $path;
                }
            }

        }

        if ($request->is_tagged) {
            $customers = [];
            foreach ($request->recipients as $recipient) {
                $customers[] = $this->userRepository->findOneBy([["email", $recipient]]);
            }
        }


        $mail = new SendMail($request->only("cc", "subject", "body"), $attachments, $request->is_tagged, $request->is_tagged ? $customers : []);
        $message = Mail::to($request->recipients)->send($mail);
        $connection = ImapService::connection();
        $folder = $connection->getFolder("Sent");

        $m = $folder->appendMessage($message->getSymfonySentMessage()->toString(), ['\Seen'], now()->format("d-M-Y h:i:s O"));

        dd($m);

        return response(["message" => "Mail envoyé avec succès"], 200);
    }

    #[Route('/folders', methods: ['GET'], middleware: ['auth:sanctum'])]
    public function getFolder()
    {
        $data = $this->emailRepository->getfolders();

        if (empty($data)) {
            return response(['message' => "Aucun dossier n'a été trouvé"], 404);
        }

        return response(["message"=> 'dossiers recuperés avec sucess',"data" => $data], 200);

    }

    public function verifyFolderName(string $folderName): bool
    {
        $foldersName = $this->emailRepository->foldersName();
        if (!in_array($folderName, $foldersName, true)) {
            return false;
        }
        return true;
    }


    #[Route('/{folder}/messages', methods: ['GET'], middleware: ['auth:sanctum'])]
    public function getMailsByFolder(GetMailsByFolderRequest $request, $folder)
    {
        $limit = $request->query('limit', 5);
        $folderName = $folder;
        $page = $request->query('page', 1);

        if (!$this->verifyFolderName($folderName)) {
            return response(['message' => "Votre nom de dossier est incorrect. Veuillez choisir INBOX pour les mails reçus, Sent pour les mails envoyés, Drafts pour les brouillons, Trash pour les mails supprimés, ou Spam pour les spams"], 400);
        }

        $totalMessages = $this->emailRepository->countMessageByFolder($folderName);
        $lastPage = ceil($totalMessages / $limit);

        if ($page < 1 || $page > $lastPage) {
            return response(['message' => "Vous avez dépassé la limite de page de mail dans ce dossier"], 400);
        }

        $messages = $this->emailRepository->getMailsByFolder($folderName, $page, $limit);

        if (empty($messages)) {
            return response(['message' => "Aucun message n'a été trouvé"], 404);
        }

        $response = [
            'message' => 'Messages récupérés avec succès',
            'data' => [
                'mails' => $messages,
                'pagination' => [
                    'total_messages' => $totalMessages,
                    'total_pages' => $lastPage,
                    'current_page' => $page,
                    'limit' => $limit
                ]
            ]
        ];

        return response($response, 200, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
    }

    #[Route('/{folder}/{uid}/details', methods: ['GET'], middleware: ['auth:sanctum'])]
    public function getMailDetails($folder , $uid)
    {

        $folderName = $folder;
        $message_uid = $uid;

        if (!$this->verifyFolderName($folderName)) {
            return response(['message' => "Votre nom de dossier est incorrect. Veuillez choisir INBOX pour les mails reçu , Sent pour les mails envoyé , Drafts pour mails brouillons , Trash pour les mails supprimé , Spam pour les spam"], 400);
        }

        $messages = $this->emailRepository->getMailDetail($message_uid, $folderName);

        if (empty($messages)) {
            return response(['message' => "Aucun mail n'a été trouvé"], 404);
        }

        return response(["message"=> 'Mail recuperés avec sucess',"data" => $messages], 200,['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],
        JSON_UNESCAPED_UNICODE);
    }


    #[Route('/move', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function moveMails(MoveMailsRequest $request)
    {
        $fromFolderName = $request->from;
        $toFolderName = $request->to;
        $message_uid = $request->message_uid;

        if (!$this->verifyFolderName($fromFolderName) && !$this->verifyFolderName($toFolderName)) {
            return response(['message' => "Nom de dossier  incorrect. Veuillez choisir INBOX pour les mails reçu , Sent pour les mails envoyé , Drafts pour mails brouillons , Trash pour les mails supprimé , Spam pour les spam"], 400);
        }
        if (($fromFolderName == $toFolderName)) {
            return response(['message' => "Vous ne pouvez pas déplacer un mail dans un meme dossier"], 400);
        }
        foreach ($message_uid as $messageId) {
            $newMailUid =  $this->emailRepository->move($fromFolderName, $messageId, $toFolderName);

            // store mails folders in json file
            //$this->mailFolderService->setFolder($newMailUid, $fromFolderName);
        }

        return response([
            "message" => "Mails déplacé avec succès",

        ], 200);
    }


    #[Route('/restore', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function restoreMails(RestoreMailsRequest $request)
{
    $foldersName = ["Archive", "Spam", "Trash"];
    $folderName = 'Trash';
    $toFolderName = 'INBOX';
    $messagesId = $request->message_uid;

    if (!$this->verifyFolderName($folderName)) {
        return response(['message' => "Nom de dossier incorrect. Veuillez choisir INBOX pour les mails reçus, Sent pour les mails envoyés, Drafts pour les brouillons, Trash pour les mails supprimés, Spam pour les spams"], 400);
    }

    if (!in_array($folderName, $foldersName)) {
        return response(['message' => "Vous ne pouvez restaurer que les mails des dossiers Archive, Spam et Trash vers leurs anciens dossiers"], 400);
    }

    // Parcourir les IDs des messages et restaurer les mails
    foreach ($messagesId as $messageId) {

        $this->emailRepository->move($folderName, $messageId, $toFolderName);

    }

    return response([
        "message" => "Mails restaurés avec succès",
    ], 200);
}


#[Route('/trash/empty', methods: ['DELETE'], middleware: ['auth:sanctum'])]
    public function emptyTrashFolder()
    {
        if ($data = $this->emailRepository->emptyTrashFolder()) {
            return response([
                $data['message'],
            ], 200);
        }
    }


    #[Route('/delete', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function deleteMail(DeleteMailRequest $request)
    {
        $message_uid = $request->message_uid;
        $folderName = $request->folder;

        if (!$this->verifyFolderName($folderName)) {
            return response(['message' => "Nom de dossier incorrect. Veuillez choisir INBOX pour les mails reçus, Sent pour les mails envoyés, Drafts pour les brouillons, Trash pour les mails supprimés, Spam pour les spams"], 400);
        }

        foreach ($message_uid as $messageId) {
           $data = $this->emailRepository->deleteMail($messageId, $folderName);
        }
        return response([
            "data" => $data,

        ], 200);
    }


    #[Route('/mark-as', methods: ['POST'], middleware: ['auth:sanctum'])]
    public function markMessageAs(MarkMessageAsRequest $request)
    {
        $message_uid = $request->message_uid;
        $folderName= $request->folder;
        $seen = $request->seen;

        if (!$this->verifyFolderName($folderName)) {
            return response(['message' => "Votre nom de dossier est incorrect. Veuillez choisir INBOX pour les mails reçu , Sent pour les mails envoyé , Drafts pour mails brouillons , Trash pour les mails supprimé , Spam pour les spam"], 400);
        }
        if (is_null($request->seen)) {
            return response(['message' => "Veuillez ajouté le query seen"],400);
        }

        foreach ($message_uid as $messageId) {
            $response = $this->emailRepository->markMessageAs($seen, $messageId, $folderName);
         }


        if (!$response) {
            return response([
                "message" => boolval($seen) ? "Mail déja marqué lu" : "Mail déja marqué non lu"
            ], 400);
        }
        return response([
            "message" => $seen ? "Mail  marqué lu avec succès" : "Mail  marqué non lu avec succès"
        ], 200);
    }
}
