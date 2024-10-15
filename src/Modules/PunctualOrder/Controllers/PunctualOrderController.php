<?php

namespace Core\Modules\PunctualOrder\Controllers;

use App\Events\FinishedOrderEvent;
use App\FcmToken;
use Carbon\Carbon;
use Core\Utils\Constants;
use App\Events\OrderEvent;
use Core\Utils\Controller;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Core\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Core\ExternalServices\QosService;
use Core\Modules\User\UserRepository;
use App\Notifications\PushNotification;
use Illuminate\Support\Facades\Storage;
use Core\Modules\Wallet\WalletRepository;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\Transaction\Models\Transaction;
use Core\ExternalServices\PushNotificationService;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\PunctualOrder\Models\PunctualOrder;
use Core\Modules\Transaction\Models\TransactionData;
use Core\Modules\Notification\NotificationRepository;
use Core\Modules\PunctualOrder\Mails\NewPunctualOrder;
use Core\Modules\PunctualOrder\Mails\SendCompletingMail;
use Core\Modules\PunctualOrder\Requests\StoreNoteRequest;
use Core\Modules\PunctualOrder\Requests\StoreOrderRequest;
use Core\Modules\PunctualOrder\Events\AfterOrderNotedEvent;
use Core\Modules\PunctualOrder\Events\ProNotedEvent;
use Core\Modules\PunctualOrder\Jobs\AfterPunctualOrderPaid;
use Core\Modules\PunctualOrder\Mails\NewOrderNoted;
use Core\Modules\PunctualOrder\Repositories\NoteRepository;
use Core\Modules\PunctualService\PunctualServiceRepository;
use Core\Modules\PunctualOrder\Requests\UpdatePunctualOrder;
use Core\Modules\PunctualOrder\Repositories\PunctualOrderRepository;


#[Route('/punctual-order', middleware: ['auth:sanctum'])]
class PunctualOrderController extends Controller
{
    private PunctualOrderRepository $punctualOrderRepository;
    protected readonly PunctualServiceRepository $punctualServiceRepository;
    protected readonly NoteRepository $noteRepository;
    protected TransactionRepository $transactionRepository;
    protected QosService $qosService;
    protected WalletRepository $walletRepository;
    protected UserRepository $userRepository;

    protected NotificationRepository $notificationRepository;


    public function __construct(
        PunctualOrderRepository   $punctualOrderRepository,
        PunctualServiceRepository $punctualServiceRepository,
        NoteRepository            $noteRepository,
        TransactionRepository     $transactionRepository,
        QosService                $qosService,
        WalletRepository          $walletRepository,
        UserRepository            $userRepository,
        NotificationRepository    $notificationRepository,

    )
    {
        $this->punctualOrderRepository = $punctualOrderRepository;
        $this->punctualServiceRepository = $punctualServiceRepository;
        $this->noteRepository = $noteRepository;
        $this->transactionRepository = $transactionRepository;
        $this->qosService = $qosService;
        $this->walletRepository = $walletRepository;
        $this->userRepository = $userRepository;
        $this->notificationRepository = $notificationRepository;
    }


    #[Route('/', methods: ['POST'])]
    public function store(StoreOrderRequest $request)
    {
        $requestValidated = $request->validated();
        $data = [];
        $data['author'] = Auth::user();
        if (array_key_exists('pictures', $requestValidated)) {
            $picturesPath = [];
            foreach ($requestValidated['pictures'] as $file) {
                $picturesPath[] = $this->uploadFile($file);
            }
            $requestValidated = Arr::except($requestValidated, 'pictures');

            $data['requestData'] = $requestValidated;
            $data['picturesPath'] = $picturesPath;
        } else {
            $data['requestData'] = $requestValidated;
        }
        $amount = round($request->budget * 0.20);
        $data['amount'] = $amount;
        $encode_data = json_encode($data);
        $transactionData = $this->transactionRepository->storeTransactionData(["data" => $encode_data]);
        $transactionResponse = $this->qosService->makeTransaction(
            $request->payment_method,
            $amount,
            $request->phoneNumber,
            $transactionData,
            Auth::user(),
            "Paiement de 20% du budget de la commande ponctuelle."
        );

        if (is_bool($transactionResponse)) {
            switch ($request->payment_method) {
                case 1:
                    AfterPunctualOrderPaid::dispatch(
                        $transactionData->id,
                        $this->transactionRepository,
                        $this->qosService,
                    )->delay(Carbon::now()
                        ->addSeconds(45));
                    $response['message'] = "Paiement en cour";
                    $response['data'] = $transactionData;
                    $response['admins'] = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC']);

                    return response($response, 200);
                case 2:
                    $response = $this->afterPunctualOrdersPaid($transactionData);

                    return response(['message' => "Commande ponctuelle lancée avec succès"], 200);
                default:
                    break;
            }
        } else {
            $response['message'] = "$transactionResponse";

        }
    }

    public function savePunctualOrder($orderData): \Illuminate\Database\Eloquent\Model
    {

        $service = $this->punctualServiceRepository->findById($orderData['requestData']['service_id']);
        $order = $this->punctualOrderRepository->make($orderData['requestData']);
        $service->image = $this->s3FileUrl($service->image);
        $user = $this->userRepository->findById($orderData['author']['id']);
        $saveOrder = $this->punctualOrderRepository->associate($order, ['user' => $user, 'service' => $service]);
        if (array_key_exists('picturesPath', $orderData)) {
            $order->update(['pictures' => json_encode($orderData['picturesPath'])]);
        }
        return $saveOrder;
    }

    #[Route('/after-payment/{transactionData}', methods: ['GET'])]
    public function afterPunctualOrdersPaid(TransactionData $transactionData): Response
    {

        if (!$transactionData->is_update) {
            $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC']);
            $utilsData = json_decode($transactionData->data, true);
            $order = $this->savePunctualOrder($utilsData);
            $transactionData->is_update = true;
            $transactionData->save();

            $transaction = Transaction::make([
                'status' => 'SUCCESSFUL',
                'type' => "Paiement de 20% du budget d'une commande ponctuelle",
                'payment_method' => 'MTN',
                'author' => $utilsData['author']['last_name'] . " " . $utilsData['author']['first_name'],
                'amount' => $utilsData['amount'],
                "phoneNumber" => $utilsData['requestData']['phoneNumber']
            ]);

            //ENVOYER UN EVENEMENT

            $message = "Nouvelle commande ponctuelle lancée avec succès";
            $userAuth = Auth::user();
            broadcast(new OrderEvent($message, $order));
            $transaction->transactionData()->associate($transactionData);
            $transaction->save();

             foreach ($admins as $admin) {
                 Mail::to($admin->email)->send(new NewPunctualOrder(Auth::user(), $admin, $order));
             }
            return response(['message' => 'Paiement effectué avec succès ! 🥳', 'data' => $order], 201);

        }
        return response(['message' => 'Transaction déjà valider'], 422);

    }


    #[Route('/user/{user}', methods: ['GET'], wheres: ['user' => Constants::REGEXUUID])]
    public function getUserPunctualOrders(User $user)
    {

        // Récupération des commandes d'un utilisateur
        $data = $user->load(['orders', 'orders.service','orders.offers','orders.note']);

        $response["message"] = "Liste des commandes ponctuelles";
        $data->orders->transform(function ($order) {
            $order->service->image = Storage::temporaryUrl($order->service->image, now()->addDay(7));
            $order->offers_count = $order->offers->count();
            return $order;


        });


        $response["data"] = $data;

        return response($response, 200);
    }

    #[Route('/', methods: ['GET'])]
    public function index(Request $request)
    {
        if ($request->query->count() == 0 || $request->has('page')) {
            $orderList = $this->punctualOrderRepository->getAllPunctualOrders(
                Auth::user()->hasRole('customer') ? Auth::user() : null
            );
        } else {
            $orderList = $this->punctualOrderRepository->filterOrders($request);
        }

        if (!empty($orderList)) {
            $response["message"] = "Liste des commandes ponctuelles";
            $orderList->transform(function ($value) {
                $value->orders->transform(function ($order) {
                    $order->service->image = Storage::temporaryUrl($order->service->image, now()->addDay(7));
                    return $order;
                });
                return $value;
            });
            $response["data"] = $orderList;
            return response($response, 200);
        } else {
            $response["message"] = "Aucune commande ponctuelle";
            $response["data"] = $orderList;
            return response($response, 200);
        }
    }

    #[Route('/{order}', methods: ['GET'], wheres: ['order' => Constants::REGEXUUID])]
    public function show(PunctualOrder $order)
    {
        $order->load(['service', 'user', 'note']);
        // Gérer les images
        if (!is_null($order->pictures)) {
            $pictureLinks = $order->pictures;
            $temporaryLinks = [];
            foreach ($pictureLinks as $pictureLink) {
                $temporaryLinks[] = Storage::temporaryUrl($pictureLink, now()->addDays(7));
            }
            $order->pictures = json_encode($temporaryLinks);
        }

        return response(['message' => "Détail de la commande ponctuelle", "data" => $order], 200);
    }

    #[Route('/{order}', methods: ['POST'], wheres: ['order' => Constants::REGEXUUID])]
    public function update(UpdatePunctualOrder $request, PunctualOrder $order)
    {
        $user = $order->user;
        $offer = $order->offers;
        $data = $request->validated();
        /**************** Marquer une commande comme terminée ******************/
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status == 3) {
                if ($order->status === 2 && $order->acceptedOffer->remaining_order_price == 0) {
                    $modifyOrder = $this->punctualOrderRepository->update($order, $data);
                    if ($order->service->image != "") {
                        $order->service->image = Storage::temporaryUrl($order->service->image, now()->addDay(7));
                    }



                    //order completed
                    $data = [
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                        'typeNotification' => 'order completed'
                    ];

                    $notificationBody = [
                        "title" => "Commande terminé avec succès.",
                        "description" => "Bonjour Mr/Mme $user->first_name $user->last_name votre commande est terminée veuillez s'il vous plaît noté le profersionnel." ,
                        "user_id" => $user->id,
                        'data' => json_encode($data),

                    ];
                    $message = "Commande terminé avec succès.";
                    $userId = $user->id;

                    broadcast(new FinishedOrderEvent($message, $order, $userId));

                    Mail::to($user->email)->send(new SendCompletingMail($user, $order));

                    $fcmToken = $user->notif_token;
                    PushNotificationService::sendNotification($notificationBody["title"], $notificationBody['description'], $data , $fcmToken);

                    $this->notificationRepository->createUserNotification($notificationBody);

                    //Mail::to($user->email)->send(new SendCompletingMail($user, $order));
                    return response(['message' => "Commande terminée avec succès", "data" => $modifyOrder], 200);
                } else {
                    return response(['message' => "La commande ne peut être marquée comme terminée", "data" => $order], 400);
                }
            }
        }


        /**************** Modifier une commande ******************/
        // Vérification du status de la commande
        if ($order->status === 2 || $order->status === 3) {
            $response["message"] = "Commande non modifiable";
            return response($response, 403);
        }

        /* Vérification si le service est modifieé */
        if ($request->filled('service_id')) {
            return response(['message' => "Le service n'est pas modifiable"], 403);
            // $existingService = $this->punctualServiceRepository->findById($request->service_id);
            // if ($existingService != "") {
            //     //$order = $this->punctualOrderRepository->update($order, $data);
            //     $modifyOrder = $this->punctualOrderRepository->associate($order, ['service' => $existingService]);
            //     if ($order->service->image != "") {
            //         $order->service->image = Storage::temporaryUrl($order->service->image, now()->addDay(7));
            //     }
            //     return response(['message' => "Commande ponctuelle modifiée avec succès", "data" => $modifyOrder], 200);
            // }
        }

        if ($request->filled('budget')) {
            $orderService = $this->punctualServiceRepository->findById($order->service_id);
            if ($orderService->fixed_price !== true) {
                return response(['message' => "Le budget n'est pas modifiable"], 403);
            }
        }

        $modifyOrder = $this->punctualOrderRepository->update($order, $data);
        if ($order->service->image != "") {
            $order->service->image = Storage::temporaryUrl($order->service->image, now()->addDay(7));
        }

        return response(['message' => "Commande ponctuelle modifiée avec succès", "data" => $modifyOrder], 200);
    }

    #[Route('/{order}', methods: ['DELETE'], wheres: ['order' => Constants::REGEXUUID])]
    public function destroy(PunctualOrder $order)
    {
        // Vérification du status de la commande
        if ($order->status === 1) {
            $response["message"] = "Impossible d'annuler la commande";
            return response($response, 403);
        }
        // Mise à jour de la commande
        $cancelOrder = $this->punctualOrderRepository->delete($order);
        if ($cancelOrder) {
            $response["message"] = "Commande ponctuelle suprimée avec succès.";
            return response($response, 200);
        } else {
            $response["message"] = "Echec de l'annulation de la commande";
            return response($response, 500);
        }
    }

    #[Route('/statistics', methods: ['GET'])]
    public function orderStatistics()
    {
        $statitics = $this->punctualOrderRepository->getStatistics();
        $response["message"] = "Statistiques";
        $response["data"] = $statitics;
        return response($response, 200);
    }

    #[Route('/{order}/note', methods: ['POST'], wheres: ['order' => Constants::REGEXUUID])]
    public function notePro(StoreNoteRequest $request, PunctualOrder $order)
    {
        if (is_null($order->note_id)) {
            $data = $request->validated();

            if (($request->filled('tags'))) {

                $data['tags'] = $request->tag;
            }

            if (($request->filled('comment'))) {

                $data['comment'] = $request->comment;
            }

            $note = $order->note()->create($data);
            $this->punctualOrderRepository
                ->associate($order, ['note' => $note]);

                $message = "Une commande a été noté.";
                $userId = auth()->id();
                broadcast(new ProNotedEvent($message, $order->load(['note','service']), $userId));

                   $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC']);
                   foreach ($admins as $admin) {
                       Mail::to($admin->email)->send(new NewOrderNoted(Auth::user(), $admin, $order->load(['note','user','service','acceptedOffer.professionals'])));
                   }

            return response(['message' => "Note envoyée avec succès", "data" => $note], 200);
        }
        return response(['message' => "Note déjà envoyée"], 403);
    }


    #[Route('/{order}/reorder', methods: ['POST'], wheres: ['order' => Constants::REGEXUUID])]
    public function reorder(PunctualOrder $order, Request $request){
        if ($order->status == 3) {
            $payload = [
               'service_id' => $order->service_id,
               'budget' => $order->budget,
               'desired_date' => Carbon::now(),
               'address' => $order->address,
               'description' => $order->description,
               'phoneNumber' => $request->phoneNumber,
               'payment_method' => $request->payment_method,
            ];

            $amount = round($order->budget * 0.20);
            $data = [];
            $data['author'] = Auth::user();
            $data['requestData'] = $payload;
            $data['amount'] = $amount;
            $encode_data = json_encode($data);
            $transactionData = $this->transactionRepository->storeTransactionData(["data" => $encode_data]);
                $transactionResponse = $this->qosService->makeTransaction(
                $request->payment_method,
                $amount,
                $request->phoneNumber,
                $transactionData,
                Auth::user(),
                "Paiement de 20% du budget de la commande ponctuelle."
            );

            if (is_bool($transactionResponse)) {
                switch ($request->payment_method) {
                    case 1:
                        AfterPunctualOrderPaid::dispatch(
                            $transactionData->id,
                            $this->transactionRepository,
                            $this->qosService,
                        )->delay(Carbon::now()
                            ->addSeconds(45));
                        $response['message'] = "Paiement en cour";
                        $response['data'] = $transactionData;
                        $response['admins'] = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC']);

                        return response($response, 200);
                    case 2:
                        $response = $this->afterPunctualOrdersPaid($transactionData);

                        return response(['message' => "Commande ponctuelle lancée avec succès"], 200);
                    default:
                        break;
                }
            } else {
                $response['message'] = "$transactionResponse";
            }

        }else {
            $response['message'] = "Vous ne pouvez pas re-commander cette commande";
            return response($response, 403);
        }

    }

}
