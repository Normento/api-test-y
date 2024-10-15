<?php

namespace Core\Modules\PunctualOrder\Controllers;

use App\FcmToken;
use Carbon\Carbon;
use Core\Utils\Constants;
use App\Events\OfferEvent;
use Core\Utils\Controller;
use Illuminate\Http\Response;
use Core\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Core\ExternalServices\QosService;
use Core\Modules\User\UserRepository;
use App\Notifications\PushNotification;
use Illuminate\Support\Facades\Storage;
use Core\Modules\PunctualOrder\Models\Offer;
use Illuminate\Support\Facades\Notification;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\Transaction\Models\Transaction;
use Core\ExternalServices\PushNotificationService;
use Core\Modules\Professional\Models\Professional;
use Core\Modules\PunctualOrder\Mails\OfferRejected;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\PunctualOrder\Models\PunctualOrder;
use Core\Modules\Transaction\Models\TransactionData;
use Core\Modules\Notification\NotificationRepository;
use Core\Modules\Professional\ProfessionalRepository;
use Core\Modules\PunctualOrder\Events\AcceptedOfferEvent;
use Core\Modules\PunctualOrder\Jobs\AfterAccetOfferPayment;
use Core\Modules\PunctualOrder\Requests\StoreOffersRequest;
use Core\Modules\PunctualService\PunctualServiceRepository;
use Core\Modules\PunctualOrder\Events\RejectedtedOfferEvent;
use Core\Modules\PunctualOrder\Mails\SendNegotiationOnOffer;
use Core\Modules\PunctualOrder\Repositories\OffersRepository;
use Core\Modules\PunctualOrder\Events\AfterSucesPaiementEvent;
use Core\Modules\PunctualOrder\Requests\StrorePaidAcceptedOffer;
use Core\Modules\PunctualOrder\Requests\UpdateStoreOffersRequest;
use Core\Modules\PunctualOrder\Requests\StroreRejectedOfferRequest;
use Core\Modules\PunctualOrder\Repositories\PunctualOrderRepository;
use Core\Modules\PunctualOrder\Mails\SendNotificationCustomerNewOffer;

#[Route('/punctual-order', middleware: ['auth:sanctum'])]
class OffersController extends Controller
{
    protected readonly OffersRepository $offersPunctualOrderRepository;
    protected readonly ProfessionalRepository $professionalRepository;
    protected readonly PunctualOrderRepository $punctualOrderRepository;
    protected readonly PunctualServiceRepository $punctualServiceRepository;
    protected TransactionRepository $transactionRepository;
    protected QosService $qosService;
    protected UserRepository $userRepository;

    protected NotificationRepository $notificationRepository;


    public function __construct(
        PunctualOrderRepository   $punctualOrderRepository,
        OffersRepository          $OffersPunctualOrderController,
        ProfessionalRepository    $professionalRepository,
        PunctualServiceRepository $punctualServiceRepository,
        TransactionRepository     $transactionRepository,
        QosService                $qosService,
        UserRepository            $userRepository,
        NotificationRepository $notificationRepository,
    ) {
        $this->punctualOrderRepository = $punctualOrderRepository;
        $this->offersPunctualOrderRepository = $OffersPunctualOrderController;
        $this->professionalRepository =  $professionalRepository;
        $this->punctualServiceRepository = $punctualServiceRepository;
        $this->transactionRepository = $transactionRepository;
        $this->qosService = $qosService;
        $this->userRepository = $userRepository;
        $this->notificationRepository = $notificationRepository;
    }

    #[Route('/{order}/offers', methods:['POST'], wheres: ['order' => Constants::REGEXUUID])]
    public function store(StoreOffersRequest $request, PunctualOrder $order)
    {
        //$order = PunctualOrder::find($order->id);
        $service = $this->punctualServiceRepository->findById($order->service_id);
        //$user = $order->user;

	$user = $this->userRepository->findById($order->user_id);

        //Vérification du status de la commande
        if ($order->status === 2 || $order->status === 3) {
            $response["message"] = "Impossible de soumettre une offre";
            return response($response, 403);
        }

        $data = $request->validated();
        $offers = $request->offers;
        foreach ($request->offers as $offer) {
            $professionalId = $offer["professional_id"];
            $professional = $this->professionalRepository->findById($professionalId);
            $proName = $professional->full_name;
            // Vérification de la concordance entre le service de la commande et celui fourni par le pro
            $matchingService = $this->offersPunctualOrderRepository->getPunctualServicePro($order, $professional);
            if (!$matchingService) {
                return response(["message" => "Le professionnel " . $proName . " ne fournit pas le service demandé."], 403);
            }
            // Vérification de l'existence de l'offre
            $existingPro = $this->offersPunctualOrderRepository->professionalAlreadyAssigned($order, $professional);
            if ($existingPro) {
                return response(["message" => "Le professionnel " . $proName . "  est déjà enrégistré pour cette commande."], 403);
            }
        }

        foreach ($offers as $offer) {
            $professionalId = $offer["professional_id"];
            $professionalService = $this->professionalRepository->findByProfessionalAndService($professionalId,$service->id);
            $professional = $this->professionalRepository->findById($professionalId);

            //Verification du type de service afin de déduire le montant restant à payer de l'offre
            if (!$service->fixed_price) {
                $amount = $offer['price'] - (round(0.20 * $order->budget));
                $offer['remaining_order_price']  =  $amount;
            }else{
                $professionalPrice = $professionalService->price;
                $offer['price'] != $professionalPrice ?
                $professionalService->update(['price' => $offer['price']]) : "";
            }

             //Création de l'offre
            $offerInstance = $this->offersPunctualOrderRepository->make($data);
            // Remplir l'instance avec les données de $offer
            $offerInstance->fill($offer);
            // Associer l'offre à la commande et au professionnel en utilisant les relations Eloquent
            $offerInstanceSave = $this->offersPunctualOrderRepository->associate($offerInstance, ['orders' => $order, 'professionals' => $professional]);
            if ($offerInstanceSave) {
                 Mail::to($user->email)->send(new SendNotificationCustomerNewOffer(Auth::user(), $order, $offers, $professional));
             }
        }

        //Mise à du status du client
        $order->update(['status' => 1]);
        $userId = $order->user_id;
        OfferEvent::dispatch($userId, "Vous avez reçu une nouvelle offre !");

        $data = [
        'order_id' => $order->id,
        'user_id' => $userId,
        'typeNotification' => 'offer'
        ];

        $notificationBody = [
            "title" => "NOUVELLE OFFRE",
            "description" => "Vous avez reçu une nouvelle offre sur la commande du service " . $service->name,
            "user_id" => $userId,
            'data' => json_encode($data),

        ];

        $this->notificationRepository->createUserNotification($notificationBody);
        $fcmToken = $user->notif_token;
        PushNotificationService::sendNotification($notificationBody["title"], $notificationBody['description'], $data , $fcmToken);
        return response(["message" => "Offre envoyée avec succès.", "data" => $offerInstanceSave], 200);
    }

    #[Route('/{order}/offers', methods:['GET'], wheres: ['order' => Constants::REGEXUUID])]
    public function index(PunctualOrder $order)
    {
        $offers  =  $order->offers;
        $offers->load(['professionals', 'orders','professionals.services']);
        if (!empty($offers)) {
            $response["message"] = "Liste des offres de la commande";
            foreach ($offers as $offer) {
                $proImage = $offer->professionals->profile_image;
                if ($proImage != "") {
                    $offer->professionals->profile_image =  Storage::temporaryUrl($proImage, now()->addDay(7));
                }
                $offer->professionals['metaData'] = $this->offersPunctualOrderRepository->getProNote($offer->professionals);
            }
            $response["data"] = $offers;
            return response($response, 200);
        } else {
            $response["message"] = "Aucune offre";
            $response["data"] = $offers;
            return response($response, 404);
        }
    }



    #[Route('/offers/{offer}', methods:['GET'], wheres: ['offer' => Constants::REGEXUUID])]
    public function show(Offer $offer)
    {
        $offer->load(['orders', 'professionals']);
        //Récupération de l'image du professionnel
        $professionalImage = $offer->professionals->profile_image;
        // Générez le lien temporaire pour l'image du professionnel
        $professionalImagePath = Storage::temporaryUrl($professionalImage, now()->addDay(7));
        // Ajout du lien temporaire à l'objet professionals
        $offer->professionals->profile_image = $professionalImagePath;

        return response([
            'message' => "Détails de l'offre de la commande", "data" => $offer
        ], 200);
    }

    #[Route('/offers/{offer}', methods:['POST'], wheres: ['offer' => Constants::REGEXUUID])]
    public function update(UpdateStoreOffersRequest $request, Offer $offer)
    {
        $data = $request->validated();
        $order = $offer->orders;
        /**************** Négociation d'une offre ******************/
        if ($offer->status === 0 &&  (is_null($order->accepted_offer_id))) {
            if ($request->filled(['status', 'negotiation']) && $request->filled(['status', 1])) {
                $modifyOffer = $this->offersPunctualOrderRepository->update($offer, $data);
                $admins = User::SuperAdminAndCOP();
                /*  foreach ($admins as $admin) {
                    Mail::to($admin->email)->send(new SendNegotiationOnOffer(Auth::user(), $admin, $order, $offer, $modifyOffer));
                } */
                return response(["message" => "Négotiation envoyé avec succès. ", "data" => $modifyOffer], 200);
            };
        } else {
            return response(["message" => "Offre déjà acceptée/négociée. "], 403);
        }

        /**************** Modification d'une offre ******************/
        if ($offer->status === 2) {
            $response["message"] = "Une offre accéptée n'est pas modifiable";
            return response($response, 403);
        }

        if ($request->filled('professional_id')) {
            $professionalId = $data["professional_id"];
            $professional = $this->professionalRepository->findById($professionalId);
            $proName = $professional->full_name;
            // Vérification du pro s'il est modifié
            if ($professionalId != $offer->professional_id) {
                // Vérification de la concordance entre le service de la commande et celui fourni par le pro
                $matchingService = $this->offersPunctualOrderRepository->getPunctualServicePro($order, $professional);
                if (!$matchingService) {
                    return response(["message" => "Le professionnel " . $proName . " ne fournit pas le service demandé."], 200);
                }

                // Vérification de l'existence de l'offre
                $existingPro = $this->offersPunctualOrderRepository->professionalAlreadyAssigned($order, $professional);
                if ($existingPro) {
                    return response(["message" => "Le professionnel " . $proName . "  est déjà enrégistré pour cette commande."], 403);
                }
            }

            $this->offersPunctualOrderRepository->update($offer, $data);
            $modifyOffer =  $this->offersPunctualOrderRepository->associate($offer, ['professionals' => $professional]);
            if ($offer->professionals->profile_image != "") {
                $offer->professionals->profile_image =  Storage::temporaryUrl($offer->professionals->profile_image, now()->addDay(7));
            }
            return response(["message" => "Offre modifiéé avec succès.", "data" => $modifyOffer], 200);
        }

        if ($request->filled('price')) {
            $orderService = $this->punctualServiceRepository->findById($order->service_id);
            if ($orderService->fixed_price !== true) {
                $remainingAmount = $request->price - (0.20 * $order->budget);
                $data['remaining_order_price'] = $remainingAmount;
            } else {
                return response(["message" => "Le prix ne peut être modifié."], 403);
            }
        }
        $modifyOffer = $this->offersPunctualOrderRepository->update($offer, $data);
        if ($offer->professionals->profile_image != "") {
            $offer->professionals->profile_image =  Storage::temporaryUrl($offer->professionals->profile_image, now()->addDay(7));
        }
        return response(["message" => "Offre modifiéé avec succès.", "data" => $modifyOffer], 200);
    }

    #[Route('/offers/{offer}', methods:['DELETE'], wheres: ['offer' => Constants::REGEXUUID])]
    public function destroy(Offer $offer)
    {
        // Annulation de la suppression de l'offre
        if ($offer->status === 2) {
            $response["message"] = "L'offre ne peut-être suprrimée";
            return response($response, 403);
        }
        //Suppression de l'offre
        $this->offersPunctualOrderRepository->delete($offer);
        //Récupération des offres restantes
        $offers = $this->offersPunctualOrderRepository->destroyOffer($offer);
        $order = $offer->orders;
        $userId = $order->user_id;
        $offers->load('orders');
        if (count($offers) == 0) {
            //Mis à jour du statut de la commande après suppression de toutes les offres
            $order->update(['status' => 0]);
            OfferEvent::dispatch($userId, "Cette Offre a été supprimée avec succès !");
            return response(["message" => "Offre supprimée avec succès.", "data" => $offers], 200);
        } else {
            //Association du pro et de son service à l'offre
            foreach ($offers as $remainingOffers) {
                $remainingOffers->load(['professionals', 'professionalServices']);
                if ($remainingOffers->professionals->profile_image != "") {
                    $remainingOffers->professionals->profile_image =  Storage::temporaryUrl($remainingOffers->professionals->profile_image, now()->addDay(7));
                }
            }
            OfferEvent::dispatch($userId, "Cette Offre a été supprimée avec succès !");
            return response(["message" => "Offre supprimée avec succès.", "data" => $offers], 200);
        }
    }


    #[Route('/offers/{offer}/accepted', methods:['POST'], wheres: ['offer' => Constants::REGEXUUID])]
    public function acceptedOffer(StrorePaidAcceptedOffer $request, Offer $offer)
    {
        // Récupérer le service et le prix fixe associé
    $orderService = $offer->orders->service->fixed_price;
    $requestValidated = $request->validated();


    // Déterminer le montant total à payer
    $totalAmount = $orderService ? $orderService : $offer->remaining_order_price;
    // Vérifier si le montant total est déjà payé
    if ($offer->remaining_order_price == 0) {
        return response(["message" => "Le montant total est déjà payé."], 403);
    }

    // Vérifier le type de paiement (0 pour paiement total, 1 pour paiement échelonné)
    $paymentType = $requestValidated['payment_type'];

    if ($paymentType == 0) {
        // Paiement total
        $amount = $totalAmount;
        $requestValidated['amount'] = $amount;
    } elseif ($paymentType == 1) {
        // Paiement échelonné
        $amount = $request->amount;
        $requestValidated['amount'] = $amount;
        if ($amount > $totalAmount || $amount <= 0) {
            return response(["message" => "Le montant à payer est incorrect."], 403);
        }
    }

        $data = ['author' => Auth::user()->full_name, "requestData" => $requestValidated, "offer" => $offer, "order" => $offer->orders];
        $encode_data = json_encode($data);
        $transactionData = $this->transactionRepository->storeTransactionData(["data" => $encode_data]);
        $transactionResponse = $this->qosService->makeTransaction(
            $request->payment_method,
            $amount,
            $request->phoneNumber,
            $transactionData,
            Auth::user(),
            "Paiement après acceptation d'une offre."
        );

        if (is_bool($transactionResponse)) {
            switch ($requestValidated['payment_method']) {
                case 1: // MTN
                    AfterAccetOfferPayment::dispatch(
                        $transactionData->id,
                        $this->transactionRepository,
                        $this->qosService,
                        $this->punctualOrderRepository,
                        $this->offersPunctualOrderRepository,
                    )->delay(Carbon::now()
                        ->addSeconds(45));
                    $response['message'] =  "Paiement en cour avec MTN";
                    $response['data'] = $transactionData;
                    return response($response, 200);

                case 2: // MOOV
                    $this->afterOffersPaid($transactionData);

                default:
                    return response(['message' => "Veuillez choisir un moyen de paiement"], 403);
                    //break;
            }
        } else {
            $response['message'] = "$transactionResponse";
            return response($response, 400);
        }
    }


    #[Route('/offers/{offer}/rejected', methods:['POST'], wheres: ['offer' => Constants::REGEXUUID])]
    public function rejectedOffer(StroreRejectedOfferRequest $request, Offer $offer)
    {

        $offer->update(array_merge($request->validated(), ['status' => -1]));
        $user = Auth::user();
        $title = 'Offre rejetté';
        $content = 'L\'utilisateur ' . $user->last_name . ' a rejetté une offre du professionel'.$offer->professionals->full_name;

        $message = [
            'title' => $title,
            'content' => $content,
        ];

        broadcast(new RejectedtedOfferEvent($offer, $user->id, json_encode($message)));


        $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC']);
                   foreach ($admins as $admin) {
                       Mail::to($admin->email)->send(new OfferRejected(Auth::user(), $admin, $offer->load(['orders.user','professionals'])));
                   }
        return response(['message' => 'Offre rejeté avec succès ! 🥳'], 200);
    }

    public function savePunctualOffer($offerData): \Illuminate\Database\Eloquent\Model{

        $offer = $this->offersPunctualOrderRepository->findById($offerData['offer']['id']);
        $order = $this->punctualOrderRepository->findById($offerData['order']['id'],['service']);
        $amount = round($offerData['requestData']['amount']);


        if ($offer) {
            // Mise à jour de l'offre
            if ($order->service->fixed_price !== true && $offer->remaining_order_price !== 0) {
                $offer->remaining_order_price -= $amount;
            }
            if ($offer->status != 2) {
                $offer->status = 2;
            }
            $offer->save();
        }

        if ($order) {

            // Mise à jour de la commande
            if ($order->status != 2) {
                $order->status = 2;
                $this->punctualOrderRepository->associate($order, ['acceptedOffer' => $offer]);
                $order->save();
            }
        }
        return $offer;

    }

    #[Route('/after-offer-payment/{transactionData}', methods: ['GET'])]
    public function afterOffersPaid(TransactionData $transactionData): Response
    {

        if (!$transactionData->is_update) {
            $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC']);
            $utilsData = json_decode($transactionData->data, true);
            $payementData = json_decode($transactionData->data);

            $offer = $this->savePunctualOffer($utilsData);
            $this->transactionRepository->updateTransactionData(['is_update' => true], $transactionData);

            $transaction = Transaction::make([
                'status' => 'SUCCESSFUL',
                'type' => "Paiement après acceptation d'une offre.",
                'payment_method' => 'MTN',
                'author' => $payementData->author,
                'amount' => round($payementData->requestData->amount),
                "phoneNumber" => $payementData->requestData->phoneNumber,
            ]);
            $transaction->transactionData()->associate($transactionData);
            $transaction->save();
            $order = $offer->orders;

            $user = Auth::user();
            $order->load('acceptedOffer.professionals','service');
            $pro = $order->acceptedOffer->professionals;
            //$imageKey = $pro->profile_image;
            //$image = $this->offersPunctualOrderRepository->s3FileUrl($imageKey);
            $order->acceptedOffer->professionals['metaData'] = $this->offersPunctualOrderRepository->getProNote($pro);
            //$order->professionals['profile_image'] = $image;

            if ($order->acceptedOffer->professionals->profile_image != "") {
                $order->acceptedOffer->professionals->profile_image =  Storage::temporaryUrl($order->acceptedOffer->professionals->profile_image, now()->addDay(7));
            }


            Log::info('ORDER'.json_encode($order));

                //ENVOYER UN EVENEMENT

            $title = 'Offre accepté';
            $content = 'L\'utilisateur ' . $order->user->last_name . ' a accepté une offre ';

            $message = [
                'title' => $title,
                'content' => $content,
            ];

            Log::info("ORDER".$order);

            broadcast(new AfterSucesPaiementEvent(json_encode($order), $order->user->id, json_encode($message)));

            broadcast(new AcceptedOfferEvent(json_encode($order), $order->user->id, json_encode($message)));

            //  foreach ($admins as $admin) {
            //      Mail::to($admin->email)->send(new NewPunctualOrder(Auth::user(), $admin, $order));
            //  }

            return response(['message' => 'Paiement effectué avec succès ! 🥳'], 201);

        }
        return response(['message' => 'Transaction déjà valider'], 422);

    }
}
