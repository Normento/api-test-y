<?php

namespace Core\Modules\RecurringOrder\Controllers;

use Carbon\Carbon;
use Core\Utils\Constants;
use App\Events\OrderEvent;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Core\Modules\User\Models\User;
use App\Events\RecurringOrderEvent;
use Core\Utils\Enums\OperationType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Core\ExternalServices\QosService;
use Core\Modules\User\UserRepository;
use Core\Modules\Wallet\Models\Wallet;
use Core\Modules\Wallet\WalletRepository;
use Core\Modules\Pricing\PricingRepository;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\RecurringOrder\Models\Payment;
use Core\Modules\Transaction\Models\Transaction;
use Core\ExternalServices\PushNotificationService;
use Core\Modules\RecurringOrder\Mails\QOSCallback;
use Core\Modules\RecurringOrder\Models\Proposition;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\RecurringOrder\Jobs\AppliedCNSSJob;
use Core\Modules\Transaction\Models\TransactionData;
use Core\Modules\Notification\NotificationRepository;
use Core\Modules\RecurringOrder\Models\RecurringOrder;
use Core\Modules\RecurringOrder\Mails\NewRecurringOrder;
use Core\Modules\RecurringOrder\Requests\AdvanceRequest;
use Core\Modules\RecurringOrder\Requests\PaymentRequest;
use Core\Modules\RecurringOrder\Requests\DecisionRequest;
use Core\Modules\Employee\Repositories\EmployeeRepository;
use Core\Modules\RecurringOrder\Jobs\AfterAvanceSalaryJob;
use Core\Modules\RecurringOrder\Mails\DishiringEmployeeMail;
use Core\Modules\RecurringOrder\Requests\PropositionRequest;
use Core\Modules\RecurringOrder\Mails\NewHiringEmployeesMail;
use Core\Modules\RecurringService\RecurringServiceRepository;
use Core\Modules\RecurringOrder\Repositories\PaymentsRepository;
use Core\Modules\RecurringOrder\Events\StaffPackageAssignedEvent;
use Core\Modules\RecurringOrder\Functions\PaymentSalaryFunctions;
use Core\Modules\RecurringOrder\Jobs\AfterRecurringOrdersPaidJob;
use Core\Modules\RecurringOrder\Mails\EmployeeReceiveAdvanceMail;
use Core\Modules\RecurringOrder\Requests\ConfirmDeploymentRequest;
use Core\Modules\RecurringOrder\Requests\UpdatePropositionRequest;
use Core\Modules\RecurringOrder\Mails\TerminateEmployeeContratMail;
use Core\Modules\RecurringOrder\Requests\StoreRecurringOrderRequest;
use Core\Modules\RecurringOrder\Mails\ConfirmEmployeesDeploymentMail;
use Core\Modules\RecurringOrder\Requests\UpdateRecurringOrderRequest;
use Core\Modules\RecurringOrder\Repositories\RecurringOrderRepository;
use Core\Modules\RecurringOrder\Requests\TerminateEmployeeContractRequest;
use Core\Modules\RecurringOrder\Requests\UnterminateEmployeeContractRequest;
//use Core\Modules\Notification\NotificationService;

#[Route('/recurring-orders', middleware: ['auth:sanctum'])]
class RecurringOrderController extends Controller
{
    private RecurringOrderRepository $repository;
    private RecurringServiceRepository $serviceRepository;
    private PricingRepository $pricingRepository;
    private UserRepository $userRepository;
    private EmployeeRepository $employeeRepository;
    private QosService $qosService;

    private TransactionRepository $transactionRepository;

    private NotificationRepository $notificationRepository;

    private PaymentSalaryFunctions $paymentSalaryFunctions;

    private WalletRepository $walletRepository;
    private PaymentsRepository $paymentRepository;
    private PushNotificationService $notificationService;


    public function __construct(
        RecurringOrderRepository   $repository,
        RecurringServiceRepository $serviceRepository,
        UserRepository             $userRepository,
        EmployeeRepository         $employeeRepository,
        QosService                 $qosService,
        PushNotificationService    $notificationService,
        PaymentSalaryFunctions     $paymentSalaryFunctions,
        PricingRepository          $pricingRepository,
        TransactionRepository      $transactionRepository,
        WalletRepository $walletRepository,
        NotificationRepository      $notificationRepository,
        PaymentsRepository          $paymentRepository,

    ) {
        $this->repository = $repository;
        $this->serviceRepository = $serviceRepository;
        $this->userRepository = $userRepository;
        $this->employeeRepository = $employeeRepository;
        $this->qosService = $qosService;
        $this->notificationService = $notificationService;
        $this->transactionRepository = $transactionRepository;
        $this->notificationRepository = $notificationRepository;
        $this->paymentSalaryFunctions = $paymentSalaryFunctions;
        $this->walletRepository = $walletRepository;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Display a listing of the resource.
     */
    #[Route('/', methods: ['GET'], middleware: ['role_or_permission:customer|create-recurring-order'])]
    public function index(Request $request): Response
    {
        if ($request->filled('user_id')) {
            $user =
                $this->userRepository->findById($request->user_id);

            $loggedUser = Auth::user();
            if ($loggedUser->hasAnyRole(['CO|customer', 'Supervisor'])) {
                $orders = $this->repository->getCustomerOrdersForStaff(staff: $loggedUser, customer: $user);
            } else {
                $orders = $this->repository->getUserOrders($user, type: $request->input('type') ?? 1,is_archived:filter_var($request->is_archived, FILTER_VALIDATE_BOOLEAN));
            }
        } else {
            $user =
                Auth::user();
            if ($user->hasRole('customer')) {
                $orders = $this->repository->getUserOrders($user, isPaid: true, type: $request->input('type') ?? 1);
            } elseif ($user->hasAnyRole(['CO|customer', 'Supervisor'])) {
                $orders = $this->repository->getCustomerOrdersForStaff($user);
            } else {
                $orders = $this->repository->getUsersWithRecurringOrders($request->input('type') ?? 1);
            }
        }

        $response = [
            'message' => "Listes des commandes .",
            'data' => $orders,
        ];

        return response($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Route('/', methods: ['POST'], middleware: ['role_or_permission:customer|create-recurring-order'])]
    public function store(StoreRecurringOrderRequest $request): Response
    {
        // OrderEvent::dispatch('Commande lancée');
        $data = $request->validated();
        $user = $request->filled('user_id') ?
            $this->userRepository->findById($request->user_id) :
            Auth::user();
        // if ($request->filled('user_id')) {

        // }
        $unPaidOrders = $this->repository->getUserOrders($user, false, type: $request->type);
        foreach ($unPaidOrders as $order) {
            $this->repository->delete($order);
        }
        foreach ($data['orders'] as $order) {
            $recurringService = $this->serviceRepository->findById($order['service_id']);
            unset($order['service_id']);
            $order['type'] = $data['type'];
            $this->repository->storeRecurringOrder($order, $recurringService, $user);
        }

        // RecurringOrderEvent::dispatch('Commande lancée par ' . $user);
        return response(['message' => "Commandes sauvegardées avec succès!", 'data' => $this->repository->getUserOrders($user, false, type: $data['type'])], 200);
    }

    /**
     *
     * Display the specified resource.
     *
     */
    #[Route('/{recurringOrder}', methods: ['GET'], middleware: ['permission:view-recurring-order'], wheres: ['recurringOrder' => Constants::REGEXUUID])]
    public function show(RecurringOrder $recurringOrder): Response
    {
        $response['message'] = "Détail d'une commande";
        $recurringOrder->load(['recurringServices', 'user', 'user.co']);
        $recurringOrder->recurringServices->transform(function ($service) {
            $service->image = $this->s3FileUrl($service->image);
            return $service;
        });

        $response['data'] = $recurringOrder;
        return response($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    #[Route('/{recurringOrder}', methods: ['PATCH'], middleware: [/*'permission:update-recurring-order'*/], wheres: ['recurringOrder' => Constants::REGEXUUID])]
    public function update(UpdateRecurringOrderRequest $request, RecurringOrder $recurringOrder): Response
    {
        OrderEvent::dispatch('Commande modifiée');
        $data = $request->validated();
        if ($request->filled('service_id')) {
            $recurringService = $this->serviceRepository->findById($request->service_id);
            $this->repository->associate($recurringOrder, ['recurringService' => $recurringService]);
        }
        if ($request->filled('is_archived')) {
            if ($request->input('is_archived')) {
                $data['archived_date'] = now();
                $this->repository->associate($recurringOrder, ['archivedBy' => Auth::user()]);
                /*
            * Email après aarchivage
            *                     foreach (User::superAdminAndResponsableCommercialUsers() as $user) {
                       Mail::to($user->email)->send(
                           new CAArchiveRecurringOrder($user, $recurringOrder->load(['package.user', 'package.assignTo', 'recurringService']))
                       );
                   }
            */
            } else {
                $data['archived_date'] = null;
                $data['archiving_reason'] = null;
                $this->repository->dissociate($recurringOrder, ['archivedBy']);
            }
        }

        $recurringOrder = $this->repository->update($recurringOrder, $data)->load('recurringService');

        $response['message'] = 'Opération effectuée avec succès';
        $response['data'] = $recurringOrder;

        return response($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Route('/{recurringOrder}', methods: ['DELETE'], middleware: [/*'permission:update-recurring-order'*/], wheres: ['recurringOrder' => Constants::REGEXUUID])]
    public function destroy(RecurringOrder $recurringOrder): Response
    {
        if ($recurringOrder->status == 4) {
            return response(['message' => "Vous ne pouvez pas supprimer une commande active."], 400);
        }
        $response['message'] = 'Commande supprimé avec succès';
        $response['data'] = $this->repository->delete($recurringOrder);
        return response($response, 200);
    }


    /** Get statistics routes */
    #[Route('/statistics', methods: ['GET'])]
    public function recurringordersStatistics(Request $request)
    {
        $statitics = $this->repository->getStatistics($request);
        $response["message"] = "Statistiques";
        $response["data"] = $statitics;
        return response($response, 200);
    }

    /**
     * Pay unpaid user recurring orders.
     */
    #[Route('/pay', methods: ['POST'], middleware: ['role_or_permission:customer|create-recurring-order'])]
    public function payOrders(PaymentRequest $request): Response
    {

        $user = Auth::guest() ? $this->userRepository->findById($request->user_id) : Auth::user();
        $unPaidOrders = $this->repository->getUserOrders($user, false, type: $request->type);
        if (count($unPaidOrders)) {
            $paymentAmount = 0;
            $totalEmployees = 0;
            $discountRate = 0;
            foreach ($unPaidOrders as $order) {
                $totalEmployees += $order->number_of_employees;
            }
            if ($totalEmployees >= 5 && $totalEmployees <= 10) {
                $discountRate = $this->pricingRepository->findBy('slug', "min-placement-fee-rate")?->value;
            } elseif ($totalEmployees > 10) {
                $discountRate = $this->pricingRepository->findBy('slug', "max-placement-fee-rate")?->value;
            }

            if ($request->type == 1) {
                foreach ($unPaidOrders as $order) {
                    $paymentAmount += $order->recurringService->placement_fee * $order->number_of_employees;
                }
            } else {
                $totalAmount = 0;
                foreach ($unPaidOrders as $order) {
                    $totalAmount += $order->employee_salary * $order->number_of_employees;
                }
                $punctualRecruitmentRate = $this->pricingRepository->findBy('slug', 'punctual-recruitment-rate')->value;

                $paymentAmount += max(round($totalAmount * $punctualRecruitmentRate), 10);
            }

            $discountAmount = $paymentAmount * $discountRate;

            $paymentAmount -= $discountAmount;

            if ($discountRate != 0) {
                foreach ($unPaidOrders as $order) {
                    $this->repository->findById($order->id)->update([
                        "discount_applied" => true,
                        "discount_rate" => $discountRate
                    ]);
                }
            }

            $data = [
                "amount" => $paymentAmount,
                "paymentMethod" => $request->payment_method,
                'author' => $user
            ];

            if (in_array($request->payment_method, [1, 2])) {
                $data["phoneNumber"] = $request->phone_number;
            }

            $encode_data = json_encode($data);
            $transactionData = $this->transactionRepository->storeTransactionData(['data' => $encode_data]);

            $transactionResponse = $this->qosService->makeTransaction(
                $request->payment_method,
                $paymentAmount,
                $request->phone_number ?? null, // Provide null if phone number is not set
                $transactionData,
                $user,
                "Paiement des frais de placement"
            );

            if (is_bool($transactionResponse)) {
                if ($request->payment_method == 1) {
                    AfterRecurringOrdersPaidJob::dispatch(
                        $transactionData->id,
                        $request->type,
                        $this->transactionRepository,
                        $this->qosService,
                        $this->userRepository,
                    )->delay(now()->addSeconds(30));
                    $response = ['message' => "Paiement en cours", 'data' => $transactionData];
                } else {
                    $this->afterRecurringOrdersPaid($transactionData, $request->type);
                    $response = ['message' => "Paiement effectué avec succès ! 🥳", 'data' => $transactionData];
                }
            } else {
                return response(['message' => $transactionResponse], 400);
            }
        } else {
            return response(['message' => "Vous n'avez aucune commande en attente de paiement"], 400);
        }
        return response($response, 200);
    }


    public function confirmDeployment(Proposition $proposition, ConfirmDeploymentRequest $request): Response
    {
        $data = $request->validated();

        $proposition = $proposition->load(['recurringOrder.user', "recurringOrder.recurringService"]);
        $recurringOrder = $proposition->recurringOrder;
        $user = $proposition->recurringOrder->user;
        if ($recurringOrder->type == 3) {
            if ($proposition->status == 1) {
                $data['status'] = 1;
                $proposition->update(["status" => 1, "started_date" => $request->date]);
            }
            return response(['message' => "Cette proposition ne peut être déployé"], 400);
        }
        return \response();
    }

    /**
     * Logic after the recruitment payment is paid
     */
    #[Route('/after-recruitment-payment/{transactionData}/{type}', methods: ['GET'])]
    public function afterRecurringOrdersPaid(TransactionData $transactionData, $type): Response
    {
        if (!$transactionData->is_update) {
            $utilsData = json_decode($transactionData->data, true);
            $user = $this->userRepository->findById($utilsData['author']['id'], ['referredBy']);
            $unPaidOrders = $this->repository->getUserOrders($user, false, type: $type);
            if (count($unPaidOrders)) {
                foreach ($unPaidOrders as $order) {
                    $this->repository->findById($order->id)->update([
                        "is_paid" => true,
                    ]);
                }
            }
            $transactionData->is_update = true;
            $transactionData->save();
            $transaction = Transaction::make([
                'status' => 'SUCCESSFUL',
                'type' => "Paiement des frais de placement",
                'payment_method' => $utilsData['paymentMethod'] == 1 ? 'MTN' : 'Carte Visa',
                'author' => $utilsData['author']['first_name'] . " " . $utilsData['author']['last_name'],
                'amount' => $utilsData['amount'],
                "phoneNumber" => $utilsData['phoneNumber']
            ]);
            $transaction->transactionData()->associate($transactionData);
            $transaction->save();

            $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'RO', 'RRC', 'RCM']);
            foreach ($admins as $admin) {
                Mail::to($user->email)->send(new NewRecurringOrder($admin, $unPaidOrders, $user));
            }
            $customerCo = $user->co()->where('co_customer.status', true)->first();
            if (!is_null($customerCo)) {
                Mail::to($customerCo
                    ->email)->send(new NewRecurringOrder($customerCo, $unPaidOrders, $user));
            }
            if (!is_null($user->referredBy)) {
                if ($user->referredBy->hasRole('Supervisor')) {
                    Mail::to($user->referredBy->email)->send(new NewRecurringOrder($user->referredBy, $unPaidOrders, $user));
                }
            }
            return response(['message' => 'Paiement effectué avec succès ! 🥳'], 201);
        }
        return response(['message' => 'Transaction déjà valider'], 422);
    }


    #[Route('/{recurringOrder}/propositions', methods: ['GET'], middleware: [/*'permission:update-recurring-order'*/], wheres: ['recurringOrder' => Constants::REGEXUUID])]
    public function propositions(RecurringOrder $recurringOrder): Response
    {
        $recurringOrder = $recurringOrder->load('recurringService');
        $propositions = $recurringOrder->propositions()
            ->with('employee')
            ->orderBy('created_at')
            ->get();

        $propositions->transform(function ($value) use ($recurringOrder) {
            $newPictures = [];
            // foreach ($value->employee->pictures as $photo) {
            //     $newPictures[] = $this->s3FileUrl($photo);
            // }
            $value->employee->photos = $newPictures;
            $value->employee->profile_image = $this->s3FileUrl($value->employee->profile_image);
            $employeeMetaData = $value->employee->recurringServices()
                ->where('recurring_services.id', $recurringOrder->recurringService->id)
                ->with('pivot.training')->first();
            $value->employee->meta_data = $employeeMetaData->pivot;
            $value->contract_url = $this->s3FileUrl($value->contract);
            return $value;
        });

        $response['message'] = "Liste des propositions effectué ";
        $response['data'] = $propositions;
        return response($response, 200);
    }

    #[Route('/propositions/{proposition}', methods: ['GET'], middleware: [/*'permission:update-recurring-order'*/], wheres: ['proposition' => Constants::REGEXUUID])]
    public function showProposition(Proposition $proposition): Response
    {
        $proposition = $proposition->load('employee');
        $proposition->employee->profile_image = $this->s3FileUrl($proposition->employee->profile_image);


        $response['message'] = "Proposition récupérée avec succès ";
        $response['data'] = $proposition;
        return response($response, 200);
    }

    #[Route('/{recurringOrder}/propositions', methods: ['POST'], middleware: ['role_or_permission:super-admin|CO|customer'], wheres: ['recurringOrder' => Constants::REGEXUUID])]
    public function makePropositions(PropositionRequest $request, RecurringOrder $recurringOrder): Response
    {
        $recurringOrder = $recurringOrder->load(['recurringService', 'user']);
        $employees = [];
        foreach ($request->propositions as $proposition) {
            $employee = $this->employeeRepository->findById($proposition['employee_id']);
            if (!$employee->recurringServices->contains($recurringOrder->recurringService)) {
                return response(['message' => "L'employé {$employee->full_name} n'est pas proposable car il ne fourni pas le service demandé par cette commande"], 403);
            }

            if ($this->repository->checkIfEmployeeIsAlreadyProposed($recurringOrder, $employee)) {
                return response(['message' => "L'employé {$employee->full_name}  est déja proposé pour cette commande."], 403);
            }

            /*  if ($recurringOrder->budget_is_fixed) {
                  if ($recommandation['employee_salary'] >= $recurringOrder->employee_brut_salary) {
                      return response(['message' => "L'employé {$employee->full_name} ne peut pas prendre un salaire supérieure à celui proposé sur la commande,car le budget est fixe."], 400);
                  }
              }*/
            $employees[] = $employee;
        }
        foreach ($request->propositions as $index => $proposition) {
            $this->repository->storeProposition($proposition, $recurringOrder, $employees[$index], Auth::user());
        }
        $this->repository->update($recurringOrder, ['status' => 1]);

        $data = [
            'typeNotification' => 'offer'
        ];

        $notificationBody = [
            "user_id" => $recurringOrder->user->id,
            "title" => "Nouvelle proposition",
            "description" => "Vous avez reçu une nouvelle proposition sur la commande du service ",


        ];

        $fcmToken = $recurringOrder->user->notif_token;
        PushNotificationService::sendNotification($notificationBody["title"], $notificationBody['description'], $data, $fcmToken);
        $this->notificationRepository->createUserNotification($notificationBody);

        $response['message'] = "Profil proposé avec succès.";
        $response['data'] = $recurringOrder->propositions;
        return response($response, 200);
    }

    #[Route('/{recurringOrder}/propositions/{proposition}', methods: ['PATCH'], middleware: ['role_or_permission:super-admin|CO|customer'], wheres: ['recurringOrder' => Constants::REGEXUUID, 'proposition' => Constants::REGEXUUID])]
    public function updatePropositions(UpdatePropositionRequest $request, RecurringOrder $recurringOrder, Proposition $proposition): Response
    {
        if ($request->filled('employee_id')) {
            $employee = $this->employeeRepository->findById($request->employee_id);
            if (!$employee->recurringServices->contains($recurringOrder->recurringService)) {
                return response(['message' => "L'employé {$employee->full_name} n'est pas proposable car il ne fourni pas le service demandé par cette commande"], 403);
            }
            $proposition->employee()->associate($employee);
        }

        if ($request->filled('salary')) {
            $proposition->salary = $request->input('salary');
            $proposition->save();
            $proposition = $proposition->refresh();
            $proposition->load('employee');
            if ($proposition->status == 1 || $proposition->status == 2) {
                $acceptedAndActivePropositions = $this->repository->acceptedOrActivePropositions($recurringOrder);
                $acceptedAndActivePropositions->transform(function ($value) use ($recurringOrder) {
                    $value['budget'] = $this->getCustomerBudget($value->salary, $recurringOrder->cnss)['total'];
                    return $value;
                });

                foreach ($acceptedAndActivePropositions as $value) {
                    $total_budget = $this->getCustomerBudget($value->salary, $recurringOrder->cnss)['total'];
                }
                if (!is_null($recurringOrder->user->signature)) {
                    $userSignature = $this->s3FileUrl($recurringOrder->user->signature);
                    $contract = $this->generateCustomerContract($total_budget, $recurringOrder, $acceptedAndActivePropositions, $userSignature);
                } else {
                    $contract = $this->generateCustomerContract($total_budget, $recurringOrder, $acceptedAndActivePropositions);
                }

                $this->userRepository->update($recurringOrder->user, ['contract' => $contract]);

                $employeeContractName = $this->generateEmployeeContract($recurringOrder, $proposition);
                $proposition->contract = $employeeContractName;
                $proposition->save();
            }
        }


        $response['message'] = "Proposition modifiée avec succès.";
        $response['data'] = $proposition;
        return response($response, 200);
    }

    #[Route('/propositions/{proposition}', methods: ['DELETE'], middleware: ['role_or_permission:super-admin|CO|customer'], wheres: ['proposition' => Constants::REGEXUUID])]
    public function deleteProposition(RecurringOrder $recurringOrder, Proposition $proposition): Response
    {
        $recurringId = $proposition->recurringOrder->id;
        $count = Proposition::where("recurring_order_id", $recurringId)->count();
        if ($count == 1) {
            $recurringOrder->where("id", $recurringId)->update(['status' => 0]);
        }
        $proposition->delete();
        $response['message'] = "Proposition supprimé avec succès";
        return response($response, 200);
    }

    #[Route('/{recurringOrder}/propositions/decision', methods: ['POST'], middleware: ['role_or_permission:super-admin|CO|customer'], wheres: ['recurringOrder' => Constants::REGEXUUID])]
    public function takeDecision(RecurringOrder $recurringOrder, DecisionRequest $request): Response
    {
        $updateField = [];
        $total_budget = 0;
        $hasAcceptAction = false;
        $askInterview = false;
        $hasRejectionAction = false;
        $recurringOrder = $recurringOrder->load('recurringService');
        foreach ($request->propositions as $value) {
            $proposition = Proposition::find($value['id']);
            if (!$recurringOrder->propositions->contains($proposition)) {
                return response(['message' => "Cette proposition n'est pas effectué sur cette commande"], 403);
            }

            if ($value['action'] == 1) {
                $hasAcceptAction = true;
                $updateField['status'] = 1;
                $updateField['is_rejected'] = $proposition->is_rejected ? false : $proposition->is_rejected;
                $updateField['rejection_reason'] = "";
            } else if ($value['action'] == -1) {
                $hasRejectionAction = true;
                $updateField['status'] = -1;
                $updateField['is_rejected'] = true;
                $updateField['rejection_reason'] = $value['rejection_reason'];
            } else {
                $askInterview = true;
                $updateField['status'] = 0;
                $updateField['is_rejected'] = $proposition->is_rejected ? false : $proposition->is_rejected;
                $updateField['rejection_reason'] = "";
                $updateField['interview_location'] = $value['interview_location'];
                $updateField['interview_asked_at'] = $value['interview_asked_at'];
            }
            $this->repository->updateProposition($proposition, $updateField);
        }
        $p = Proposition::find($request->propositions[0]['id']);

        if ($hasAcceptAction) {
            if ($recurringOrder->type == 1) {
                $acceptedPropositions = $recurringOrder->propositions()
                    ->with('employee')
                    ->where('status', 1)
                    ->orderBy('created_at')
                    ->get();
                $acceptedPropositions->transform(function ($value) use ($recurringOrder) {
                    $value['budget'] = $this->getCustomerBudget($value->salary, $recurringOrder->cnss)['total'];
                    return $value;
                });

                foreach ($acceptedPropositions as $value) {
                    $total_budget += $this->getCustomerBudget($value->salary, $recurringOrder->cnss)['total'];
                }

                if ($recurringOrder->user->contract == "" || $recurringOrder->status == -1) {
                    $recurringOrder->status = 2;
                    $recurringOrder->save();
                    $contractName = $this->generateCustomerContract($total_budget, $recurringOrder, $acceptedPropositions);
                    $this->userRepository->update($recurringOrder->user, ['contract' => $contractName]);
                    $contrat_url = $this->s3FileUrl($contractName);
                    $response['data']['customer_contrat_url'] = $contrat_url;
                }
                if($recurringOrder->user->contract != "" && $recurringOrder->user->contract_status == true){
                    $response['data']['customer_contrat_url'] = $this->s3FileUrl($recurringOrder->user->contract);
                }
            } else {
                $recurringOrder->status = 2;
                $recurringOrder->save();
                $response['data'] = $p;
            }
        }
        if ($hasRejectionAction) {

            $response['data'] = $p;
            //Envoie de mail au admin pour mentionner les propositions rejeté
        }
        if ($askInterview) {
            $response['data'] = $p;
            //Envoie de mail au admin pour mentionner les entretien progamer
        }


        $response['message'] = "Action effectué  avec succès.";
        return response($response, 201);

        /*  $propositions = $request->propositons;
         if ($recurringOrder->user_id == Auth::id()) {
               if ($proposition->status == 0 || $proposition->status == -1 || $proposition->status == 3 || $proposition->status == 4) {


                   $employee_contrat_url = $this->contratsPdfService->generateAndGetContractUrlForEmployee($recurringOrder, $proposition);

                      if (!is_null($package->assignTo)) {
                       Mail::to($package->assignTo->email)->send(new CustomerAcceptProposition($package->assignTo, $recurringOrder, $proposition));
                   }

                   foreach (User::superAdminAndResponsableCommercialUsers() as $user) {
                       Mail::to($user->email)->send(new CustomerAcceptProposition($user, $recurringOrder, $proposition));
                   }

                   if ($proposition->recurringOrder->package->client_signature !== "") {
                       foreach (User::superAdminAndAdminRHUsers() as $user) {
                           Mail::to($user->email)
                               ->send(new NotifyRHApproveEmployeeContract($user, $proposition));
                       }

                       if (!is_null($package->load(["rh"])->rh)) {
                           Mail::to($package->load(["rh"])->rh->email)
                               ->send(new NotifyRHApproveEmployeeContract($package->load(["rh"])->rh, $proposition));
                       }
                   }

                   $acceptedProposition = $this->propositionsRepository->numberOfAcceptedPropositions($recurringOrder);
                   if ($acceptedProposition == $recurringOrder->number_of_employees) {
                       $pendingPropositions = $this->propositionsRepository->getProposedPropositions($recurringOrder, 4);
                       foreach ($pendingPropositions as $value) {
                           $this->propositionsRepository->deleteProposition($value);
                       }
                       $interviewedPropositions = $this->propositionsRepository->getProposedPropositions($recurringOrder, 3);
                       foreach ($interviewedPropositions as $value) {
                           $this->propositionsRepository->deleteProposition($value);
                       }
                   }
                   $response['data']['customer_contrat_url'] = $contrat_url ?? [];
                   $response['data']['avenant_contrat_url'] = $avenant_contrat_url ?? [];
                   $response['data']['employee_contrat_url'] = $employee_contrat_url;
                   $response['message'] = "Proposition accepté avec succès.";
                   return response($response, 201);
               } else {
                   return response(['message' => "Seul les propositions en attente d'acceptation peuvent être  accepté."], 404);
               }
           } else {
               return response(['message' => "Vous ne pouvez accepter que les propositions de vos commandes"], 400);
           }*/
    }

    /* public function confirmEmployeeDeployment(Proposition $proposition, ConfirmEmployeeDeploymentRequest $request)
     {
         $proposition = $proposition->load(['recurringOrder.package.user.devices', 'employee.point_focal', "recurringOrder.recurringService"]);
         $recurringOrder = $proposition->recurringOrder;
         $user  = $proposition->recurringOrder->package->user;
         $package =  $proposition->recurringOrder->package;
         $employee = $proposition->employee;
         if ($employee->status == 2 || $employee->status == 5) {
             if ($proposition->status == 1) {
                 if ($package->client_signature == "") {
                     return response(['message' => "Veuillez demander au client de signer son contrat avant tout déploiement.Merci"], 400);
                 }
                 if ($proposition->employee_contrat_signature !== "") {
                     $this->propositionsRepository->updateProposition($proposition, ['status' => 2, "employee_contrat_started_date" => $request->employee_deployment_date]);

                     $employee_contrat_url = $this->contratsPdfService->generateAndGetContractUrlForEmployee($recurringOrder, $proposition, $proposition->employee_contrat_signature);

                     if ($employee->status !== 2) {
                         $employee->status = 2; // Employée is now busy
                         $employee->save();
                     }

                     $propositionActivesByPackage = $this->propositionsRepository->actifsPropositionsByPackage($package->id);
                     $propositionActivesByRecurringOrder = $this->propositionsRepository->getProposedPropositions($recurringOrder, 2);
                     $propositionsAcceptedAndActifs = $this->propositionsRepository->acceptedAndActifsPropositionsByPackage($package->id);
                     $total_budget  = $this->packageSalaryFunctions->getTotalTopaidByPackage($package->id);

                     // changer le statut de la commande
                     if (count($propositionActivesByRecurringOrder) == 1) {
                         $this->recurringOrderRepository->updateRecurringOrder($recurringOrder, ['status' => 4]);
                     }

                     if ((count($propositionActivesByPackage) == 1)  && $package->avenant_contrat_file_name == "") {

                         // envoyer notification
                         if (count($package->user->devices)) {
                             $playerIds = [];
                             foreach ($package->user->devices as $value) {
                                 $playerIds[] = $value->token;
                             }
                             $this->pushNotificationService->sendPushNotification(
                                 $playerIds,
                                 array(
                                     "en" => "Start date of your ylomi direct contract.",
                                     "fr" => "Date de début de votre contrat ylomi direct."
                                 ),
                                 array(
                                     "en" => "Hello {$package->user->first_name}, your Ylomi Direct contract takes effect from this day (the day on which the employee was deployed to you).",
                                     "fr" => " Bonjour Mr/Mme  {$package->user->first_name}, votre contrat Ylomi  prend effet a compter de ce jour (jour auquel l'employé a été déployé chez vous)."
                                 ),
                                 "Voir la demande",
                                 ["recurring_order_id" => $recurringOrder->id, "dif" => 2]
                             );
                         }

                         // enregistrer la date de debut du contrat du package
                         $package->contract_started_date = Carbon::parse($request->employee_deployment_date);
                         $package->save();
                     }
                     // contrat client

                     if ($package->avenant_contrat_file_name == "") {
                         $customer_contrat_url = $this->contratsPdfService->generateAndGetContractUrl($package, $propositionsAcceptedAndActifs,  $total_budget, $package->client_signature == "" ? null : $package->client_signature);
                     } else {
                         $avenant_contrat_url = $this->contratsPdfService->generateAndGetAvenantUrl($package, $propositionsAcceptedAndActifs,  $total_budget);
                     }

                     //affiliation

                     if ((count($propositionActivesByPackage) == 1)  && $package->avenant_contrat_file_name == "") {
                         if (!is_null($user->referred_by)) {
                             //verify if refferedBy user has package(customer)
                             $referredByPackage = $this->packageRepository->findOneBy([["user_id", $user->referred_by]]);
                             if (!is_null($referredByPackage)) {
                                 $this->affiliation($user, $recurringOrder);
                                 $recurringOrder->update(['affiliated' => true, "numbers_of_deployment" => $recurringOrder->numbers_of_deployment += 1]);
                             }
                         }
                     } else {
                         if ($recurringOrder->affiliated  && ($recurringOrder->number_of_employees != $recurringOrder->numbers_of_deployment)) {
                             $this->affiliation($user, $recurringOrder);
                             $recurringOrder->update(["numbers_of_deployment" => $recurringOrder->numbers_of_deployment += 1]);
                         }
                     }
                     // formation fees defalcation
                     $employeeRecurringService = $this->employeeRecurringServiceRepository->findOneBy([["employee_id", $proposition->employee_id], ['recurring_service_id', $proposition->recurringOrder->recurring_service_id]], ['formation']);
                     if ((!is_null($employeeRecurringService) && !is_null($employeeRecurringService->formation)) && (!$employeeRecurringService->formation->formation_fees_is_payed && $employeeRecurringService->formation->practice_formation_received)) {
                         $employeeWallet = EmployeeWallet::where('employee_id', $proposition->employee_id)->first();
                         $oldEmployeeWalletBalance = $employeeWallet->balance;
                         $data = ['amount' =>  1500 * $employeeRecurringService->formation->numbers_days_of_formation, 'balance_before_operation' => $oldEmployeeWalletBalance, "balance_after_operation" => $oldEmployeeWalletBalance - (1500 * $employeeRecurringService->formation->numbers_days_of_formation), "trace" => "Retrait des frais de formation de l'employé {$proposition->employee->full_name} pour le service  de {$recurringOrder->recurringService->name}.", "operation_type" =>  'withdraw'];
                         $this->packageSalaryFunctions->storeEmployeeWalletLog($employeeWallet, $data);
                         $employeeWallet->balance = $oldEmployeeWalletBalance - (1500 * $employeeRecurringService->formation->numbers_days_of_formation);
                         $employeeWallet->save();
                         $formation = $employeeRecurringService->formation;
                         $formation->update(['formation_fees_is_payed' => true, 'formation_fees_paid_date' => now()]);
                     }

                     $detailsOfAmountToBePaid = $recurringOrder->budget_is_fixed ?  $this->packageSalaryFunctions->getFixedBudgetSalaryDetails('deployment-confirmation', $proposition) : $this->packageSalaryFunctions->detailsOfAmountToBePaid('deployment-confirmation', $proposition);
                     $this->packageSalaryFunctions->createPaymentRecord($proposition, $detailsOfAmountToBePaid, 'deployment-confirmation');
                     //applied cnss
                     AppliedCNSSRecurringOrderJob::dispatch($proposition)->delay(Carbon::parse($request->employee_deployment_date)->addMonth(3)->hours("08"));
                     // mail to comptable
                     if ($proposition->recurringOrder->cnss) {
                         foreach (User::superAdminAndComptableUsers() as $user) {
                             Mail::to($user->email)->send(new NewHiringEmployeeMail($user, $proposition));
                         }
                     }
                     $response["message"] = "Vous avez bien confirmé le déploiement de l'employé. Le contrat de l'employé et du client démarre aujourdhui.";
                     $response["employee_contract_url"] = $employee_contrat_url;
                     $response["customer_contrat_url"] = $customer_contrat_url ?? [];
                     $response["avenant_contrat_url"] = $avenant_contrat_url ?? [];

                     foreach (User::superAdminAndResponsableRelationClientAndAdminRHUsers() as $admin) {
                         Mail::to($admin->email)->send(new ConfirmEmployeeDeploymentMail($admin, $user, $employee, $proposition->employee_contrat_started_date, Auth::user()));
                     }
                     return response($response, 201);
                 } else {
                     $response["message"] = "Impossible de confirmer le deploiement, la signature du contrat de l'employé est obligatoire avant déploiement.";
                     return response($response, 400);
                 }
             } else {
                 return response(['message' => "Vous ne pouvez déployer en employé que si son profil est accepté par le client"], 400);
             }
         } else {
             return response(['message' => "Le profil de cet employé est en attente de confirmation par le super admin. Merci de réésayer après sa confirmation."], 400);
         }
     } */


    #[Route('/propositions/{proposition}/confirm-deployment', methods: ['POST'], middleware: ['auth:sanctum','role_or_permission:super-admin|CO|customer'], wheres: ['proposition'=> Constants::REGEXUUID])]
     public function confirmEmployeeDeployment(Proposition $proposition, ConfirmDeploymentRequest $request)
    {

        // Chargement des relations nécessaires
        $proposition->load(['recurringOrder.user', 'employee']);
        $recurringOrder = $proposition->recurringOrder;
        $user = $recurringOrder->user;
        $employee = $proposition->employee;

        // Vérification du statut de l'employé
        if (!in_array($employee->status, [1, 2])) {
            return response(['message' => "Le profil de cet employé n'est pas validé ou l'employé est occupé."], 400);
        }

        // Vérification si l'employé est accepté dans la proposition
        if ($proposition->employee_id != $employee->id) {
            return response(['message' => "Vous ne pouvez déployer un employé que si son profil est accepté par le client."], 400);
        }

        // Vérification du statut de la proposition
        if ($proposition->status != 1) {
            return response(['message' => "Cette proposition n'a pas été accepté par le client."], 400);
        }

        // Vérification de la signature du client
        if (empty($user->signature)) {
            return response(['message' => "Veuillez demander au client de signer son contrat avant tout déploiement."], 400);
        }

        // Vérification de la signature du contrat de l'employé
        if (empty($proposition->signature)) {
            return response(['message' => "La signature du contrat de l'employé est obligatoire avant déploiement."], 400);
        }

        if (Auth::user()->hasRole('CO|customer')) {
            // Vérification si le client a un CO|customer assigné et si ce CO|customer est bien celui connecté
            $isAssigned = $user->co()->where('co_id', Auth::id())->exists();

            if (!$isAssigned) {
                return response(['message' => "Vous n'êtes pas autorisé à confirmer le déploiement des employés affectés pour cette commande."], 400);
            }
        }

        // Mise à jour du statut de la proposition
        $proposition->update([
            'status' => 2,
            'started_date' => Carbon::parse($request->date),
        ]);

        // Mise à jour du statut de l'employé
        $employee->update(['status' => 3]); // Employé occupé

        // Mise à jour du statut de la commande récurrente
        $activePropositions = $recurringOrder->propositions()->where('status', 2)->count();
        if ($activePropositions == 1) {
            $recurringOrder->update(['status' => 4]);
        }

        $detailsOfAmountToBePaid = $this->paymentSalaryFunctions->detailsOfAmountToBePaidBusinessOrder("deployment-confirmation", $proposition);
        $this->paymentSalaryFunctions->createPaymentRecord($proposition, $detailsOfAmountToBePaid, "deployment-confirmation");

        AppliedCNSSJob::dispatch($proposition)->delay(Carbon::parse($request->date)->addMonth(3)->hours("08"));

        // Envoi de notifications
        $data = [
            'order_id' => $recurringOrder->id,
            'user_id' => $user->id,
            'typeNotification' => 'Proposition'
            ];

            $notificationBody = [
                "title" => "DEPLOEMENT EFFECTUE",
                "description" => " Bonjour Mr/Mme  $user->first_name, votre contrat Ylomi  prend effet a compter de ce jour (jour auquel l'employé a été déployé chez vous).",
                "user_id" => $user->id,
                'data' => json_encode($data),

            ];

            $admins1 = $this->userRepository->userWithRole(['super-admin', 'admin', 'accountant']);
            $admins2 = $this->userRepository->userWithRole(['super-admin', 'admin','RCM','RO', 'AA','RRC', 'accountant']);

            // mail to comptable and super-admin
            if ($recurringOrder->cnss) {
                foreach ($admins1 as $user) {
                    Mail::to($user->email)->send(new NewHiringEmployeesMail($user, $employee, $recurringOrder));
                }
            }

            // mail to all responsable
            foreach ($admins2 as $admin) {
                Mail::to($admin->email)->send(new ConfirmEmployeesDeploymentMail($admin, $employee, $recurringOrder, $request->date, Auth::user()));
            }

            $this->notificationRepository->createUserNotification($notificationBody);
            $fcmToken = $user->notif_token;
            PushNotificationService::sendNotification($notificationBody["title"], $notificationBody['description'], $data , $fcmToken);

        return response([
            'message' => "Vous avez bien confirmé le déploiement de l'employé. Le contrat de l'employé et du client démarre aujourd'hui.",
        ], 201);
    }

    #[Route('/propositions/{proposition}/change-deployment-date', methods: ['POST'], middleware: ['auth:sanctum', 'role_or_permission:super-admin|CO|customer'],wheres: ['proposition'=> Constants::REGEXUUID])]
    public function changeEmployeeDeploymentDate(ConfirmDeploymentRequest $request, Proposition $proposition)
    {
        $proposition = $proposition->load(['recurringOrder.package.user', 'employee.point_focal']);
        $recurringOrder = $proposition->recurringOrder;
        if ($proposition->status == 2) {
            $proposition->employee_contrat_started_date = Carbon::parse($request->employee_deployment_date);
            $proposition->save();
            $detailsOfAmountToBePaid =   $recurringOrder->budget_is_fixed ? $this->paymentSalaryFunctions->getFixedBudgetSalaryDetails('change-deployment-date', $proposition) : $this->paymentSalaryFunctions->detailsOfAmountToBePaid('change-deployment-date', $proposition);
            $directSalaryPayment = Payment::where('recurring_order_id', $proposition->recurringOrder->id)->where('employee_id', $proposition->employee->id)->where('latest', true)->first();

            if ($directSalaryPayment) {
                $directSalaryPayment->year =  (date('m') == 12 && (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->endOfMonth()->toDateString() == (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->toDateString()))) ? (Carbon::parse($proposition->employee_contrat_started_date)->year + 1) : (Carbon::parse($proposition->employee_contrat_started_date)->year);
                $directSalaryPayment->month_salary =
                    Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->endOfMonth()->toDateString() == (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->toDateString())  ?
                    Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->addMonths(1)->monthName :
                    Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->monthName;
                $directSalaryPayment->ylomi_direct_fees = $proposition->recurringOrder->applied_cnss ?  $detailsOfAmountToBePaid['ylomi_direct_fees'] : round($detailsOfAmountToBePaid['cnss_employee_amount'] + $detailsOfAmountToBePaid['cnss_customer_amount'] + $detailsOfAmountToBePaid['vps_amount'] + $detailsOfAmountToBePaid['its_amount'] + $detailsOfAmountToBePaid['ylomi_direct_fees']);
                $directSalaryPayment->employee_salary_amount = $detailsOfAmountToBePaid['employee_salary_amount'];
                $directSalaryPayment->total_amount_to_paid = $detailsOfAmountToBePaid['total_amount_to_paid'];
                $directSalaryPayment->cnss_customer_amount = $proposition->applied_cnss ? $detailsOfAmountToBePaid['cnss_customer_amount'] : 0;
                $directSalaryPayment->cnss_employee_amount =  $proposition->applied_cnss ? $detailsOfAmountToBePaid['cnss_employee_amount'] : 0;
                $directSalaryPayment->vps_amount = $proposition->applied_cnss ? $detailsOfAmountToBePaid['vps_amount'] : 0;
                $directSalaryPayment->its_amount = $proposition->applied_cnss  ? $detailsOfAmountToBePaid['its_amount'] : 0;
                $directSalaryPayment->assurance_amount = $detailsOfAmountToBePaid['assurance_amount'];
                $directSalaryPayment->save();
            }
            AppliedCNSSJob::dispatch($proposition)->delay(Carbon::parse($request->date)->addMonth(3)->hours("08"));
            $response["message"] = "Date de déploiement de l'employé modifié avec succès.";
            $response['data']  = $proposition;
            return response($response, 201);
        } else {
            $response["message"] = "La commande doit etre actif pour pouvoir effectué cette action.";
            return response($response, 400);
        }
    }


    #[Route('/propositions/{proposition}/details-salary', methods: ['GET'], middleware: ['auth:sanctum', 'role_or_permission:super-admin|CO|customer'],wheres: ['proposition'=> Constants::REGEXUUID])]
    public function getPropositionsDetailsSalary(Proposition $proposition)
    {
        $proposition = $proposition->load(['recurringOrder']);
        $recurringOrder = $proposition->recurringOrder;
        $response['message'] = "Détails du salaire de la proposition";
        if ($recurringOrder->budget_is_fixed) {
            $response['data']['withoutCnss'] = [
                "cnss" => false,
                "net_salary" => $proposition->salary,
                "customer_budget" => $proposition->recurringOrder->customer_budget,
                "cnss_customer_amount" => 0,
                "cnss_employee_amount" => 0,
                "vps_amount" => 0,
                "its_amount" => 0,
                "assurance_amount" => 0,
                "ylomi_fee" => round($proposition->recurringOrder->customer_budget - $proposition->salary)
            ];
            $response['data']['withCnss'] = [];
        } else {
            $response['data']['withCnss'] = $this->paymentSalaryFunctions->getBudgetPerEmployee($proposition->salary, true);
            $response['data']['withoutCnss'] = $this->paymentSalaryFunctions->getBudgetPerEmployee($proposition->salary, false);
        }

        return response($response, 200);
    }


    #[Route('/propositions/{proposition}/advance-salary', methods: ['POST'], middleware: ['auth:sanctum', 'role:customer'], wheres: ['proposition'=> Constants::REGEXUUID])]
    public function sendAdvanceSalaryToEmployee(AdvanceRequest $request, Proposition $proposition)
    {

        $transactionFee = 0;
        $finalAmount = $request->salary_advance_amount + $transactionFee;
        $data = ['proposition_id' => $proposition->id, "amount" => $request->salary_advance_amount, "transfer_fee" => $transactionFee, "phoneNumber" => $request->phoneNumber , "paymentMethod" => $request->payment_method];
        $encode_data  = json_encode($data);

        if ($proposition->status != 2) {
            $response['message'] = "Cet employé n'est pas occupé";
            return response($response, 422);
        }

        if ($proposition->recurringOrder->user->id != Auth::id()) {
            $response['message'] = "Vous ne pouvez pas faire une avance sur salaire à  un employé qui ne travaille pas chez vous";
            return response($response, 422);
        }
        $salaryPayment = Payment::where('employee_id', $proposition->employee->id)
            ->where('recurring_order_id', $proposition->recurring_order_id)
            ->where('latest', true)
            ->where('status', false)
            ->first();

        if (is_null($salaryPayment)) {
            $response['message'] = "Cet employé n'a aucun salaire non payé";
            return response($response, 422);
        }

        if ($proposition->recurringOrder->cnss) {
            if (!is_null($salaryPayment->salary_advance_amount)) {
                if (($salaryPayment->employee_salary_amount - $salaryPayment->salary_advance_amount) <= $request->salary_advance_amount) {
                    $response['message'] = "Vous ne pouvez que payé une partie du salaire à l'employé";
                    return response($response, 422);
                }
            } else {
                if ($salaryPayment->employee_salary_amount <= $request->salary_advance_amount) {
                    $response['message'] = "Vous ne pouvez que payé une partie du salaire à l'employé";
                    return response($response, 422);
                }
            }
        } else {
            if (!is_null($salaryPayment->salary_advance_amount)) {
                if (($salaryPayment->employee_salary_amount - $salaryPayment->salary_advance_amount - round(($salaryPayment->salary_advance_amount * 3) / 100)) <= $request->salary_advance_amount) {
                    $response['message'] = "Vous ne pouvez que payé une partie du salaire à l'employé";
                    return response($response, 422);
                }
            } else {
                if ($salaryPayment->employee_salary_amount - round(($salaryPayment->salary_advance_amount * 3) / 100) <= $request->salary_advance_amount) {
                    $response['message'] = "Vous ne pouvez que payé une partie du salaire à l'employé";
                    return response($response, 422);
                }
            }
        }

        $transactionData = $this->transactionRepository->storeTransactionData(["data" => $encode_data]);
                $transactionResponse = $this->qosService->makeTransaction(
                $request->payment_method,
                intval($finalAmount),
                $request->phoneNumber,
                $transactionData,
                Auth::user(),
                "Avance sur salaire."
            );

            if (is_bool($transactionResponse)) {
                switch ($request->payment_method) {
                    case 1:
                        AfterAvanceSalaryJob::dispatch($proposition, $transactionData->id, $this->transactionRepository, $this->qosService, $this->paymentSalaryFunctions)->delay(Carbon::now()->addMinutes(1));

                        $response['message'] = "Paiement en cour";
                        $response['data'] = $transactionData;
                        $response['admins'] = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC']);

                        return response($response, 200);
                    case 2:
                        $response= $this->afterAdvanceSend($transactionData, $proposition, 2, false);

                        return response(['message' => "Avance payé avec succès"], 200);
                    default:
                        break;
                }
            }


    }

    public function afterAdvanceSend(TransactionData $transactionData, Proposition $proposition, $saveTransaction = true)
    {

        $decode = json_decode($transactionData->data,true);
        $amount = $decode['amount'];
        Log::info('DECODE'.json_encode($amount));

        $transfer_fee = $decode['transfer_fee'];
        $phoneNumber = $decode['phoneNumber'];
        if (!$transactionData->is_update) {
            $transactionData->is_update = true;
            $transactionData->save();

            $salaryPayment = Payment::where('employee_id', $proposition->employee->id)
                ->where('recurring_order_id', $proposition->recurring_order_id)
                ->where('latest', true)
                ->where('status', 0)->with("employee")
                ->first();

            if ($saveTransaction) {
                $transaction = Transaction::make([
                    'status' => 'SUCCESSFUL',
                    'type' => "Avance sur salaire  {$salaryPayment->month_salary}  {$salaryPayment->year} du client {$proposition->recurringOrder->user->first_name} {$proposition->recurringOrder->user->last_name} à l'employé {$proposition->employee->full_name} ",
                    'payment_method' => 'MTN',
                    'author' => $proposition->recurringOrder->user->first_name . ' ' . $proposition->recurringOrder->user->last_name,
                    'amount' => $amount + $transfer_fee,
                    'phoneNumber' => $phoneNumber
                ]);
                $transaction->transactionData()->associate($transactionData);
                $transaction->save();

            }
            if (!is_null($salaryPayment)) {
                if ($salaryPayment->employee_received_salary_advance) {
                    $salaryPayment->salary_advance_amount += $amount;
                } else {
                    $salaryPayment->employee_received_salary_advance = true;
                    $salaryPayment->salary_advance_amount = $amount;
                }

                $salaryPayment->save();

                $employeeWallet = $salaryPayment->employee->wallet;
                $trace = "Avance sur salaire par le client {$proposition->recurringOrder->user->first_name} {$proposition->recurringOrder->user->last_name} pour le  mois de $salaryPayment->month_salary $salaryPayment->year.";

                if ($employeeWallet) {
                    $this->walletRepository->makeOperation($employeeWallet, OperationType::DEPOSIT,$salaryPayment->salary_advance_amount, $trace);

                }

                //ENVOI DE L4ARGENT PAR MOMO A L'EMPLOYEE

                /* $transref_send_employee_advance = "WM-{$proposition->employee->id}-" . rand(100, 999) . "-dev";
                if ($paymentMethod == 1) {
                    $depositResponse = $this->qosService->sendMoney($amount, $proposition->employee->mtn_number, "MTN", $transref_send_employee_advance);
                    if ($depositResponse['responsecode'] == "00") {
                        $transactionData = [
                            'transref' => $transref_send_employee_advance,
                            'status' => "SUCCESSFUL",
                            'author' => "{$proposition->employee->full_name}",
                            'type' => "Transfert de l'avance sur salaire du mois de {$salaryPayment->month_salary} {$salaryPayment->year} de la part du client {$proposition->recurringOrder->user->full_name} ",
                            'payment_method' => 'MTN',
                            'amount' => $amount,
                            "phoneNumber" => $proposition->employee->mtn_number
                        ];
                        $this->transactionRepository->store($transactionData);
                        foreach (User::superAdminAndAdminRHUsers() as $user) {
                            Mail::to($user->email)->send(new EmployeeReceiveAdvanceMail($user, $salaryPayment, $proposition->employee->mtn_number, $amount, $proposition->recurringOrder->user));
                        }
                        if (!is_null($proposition->recurringOrder->package->rh)) {
                            Mail::to($proposition->recurringOrder->package->rh->email)->send(new EmployeeReceiveAdvanceMail($proposition->recurringOrder->package->rh, $salaryPayment, $proposition->employee->mtn_number, $amount, $proposition->recurringOrder->user));
                        }
                    } else {
                        Mail::to(["contact-lucas@protonmail.com"])->send(new QOSCallback($transref, "Transaction MTN Echoué", $proposition->employee, "Transfert advance $salaryPayment->month_salary $salaryPayment->year"));
                    }
                } else if ($paymentMethod == 2) {
                    $depositResponse = $this->qosService->sendMoney($amount, $proposition->employee->flooz_number, "MOOV", $transref_send_employee_advance);
                    if ($depositResponse['responsecode'] == 0) {
                        $transactionData = [
                            'transref' => $transref_send_employee_advance,
                            'status' => "SUCCESSFUL",
                            'author' => "{$proposition->recurringOrder->package->user->full_nam}",
                            'type' => "Transfert de l'avance sur salaire du mois de {$salaryPayment->month_salary} {$salaryPayment->year}",
                            'payment_method' => 'MOOV',
                            'amount' => $amount,
                            "phoneNumber" =>  $proposition->employee->flooz_number
                        ];
                        $this->transactionsRepository->store($transactionData);

                        foreach (User::superAdminAndAdminRHUsers() as $user) {
                            Mail::to($user->email)->send(new EmployeeReceiveAdvanceMail($user, $salaryPayment, $proposition->employee->flooz_number, $amount, $proposition->recurringOrder->package->user));
                        }
                        if (!is_null($proposition->recurringOrder->package->rh)) {
                            Mail::to($proposition->recurringOrder->package->rh->email)->send(new EmployeeReceiveAdvanceMail($proposition->recurringOrder->package->rh, $salaryPayment, $proposition->employee->flooz_number, $amount, $proposition->recurringOrder->package->user));
                        }
                    } else {
                        Mail::to(["contact-lucas@protonmail.com"])->send(new QOSCallback($transref, "Transaction MTN Echoué", $proposition->employee, "Transfert advance $salaryPayment->month_salary $salaryPayment->year"));
                    }
                }

                $response["message"] = "Avance sur salaire enregistré avec succès";
                $response["data"] = $salaryPayment;
                $utils->is_update = true;
                $utils->save();
                return response($response, 200);
            } else {
                $response["message"] = "Id de direct salary payment est introuvable";
                return response($response, 404);
            } */
        }
        return response(['message' => 'Transaction déjà valider'], 422);
    }}

    #[Route('/employees/terminate-contract', methods: ['POST'], middleware: ['auth:sanctum', 'role:super-admin|CO'])]
    public function  terminateContract(TerminateEmployeeContractRequest $request)
    {
        try {
            $update_employee_status = true;
            if (!$this->verifyDate($request->date)) {
                return response(['message' => "Date incorrect.Vous ne pouvez pas résilier le contrat dans le mois prochain"], 400);
            }

            $proposition = Proposition::getPropositionByid($request->proposition_id);

            if ($proposition->status == 2) {
                if (Carbon::parse($proposition->started_date)->gt(Carbon::parse($request->date))) {

                    return response(['message' => "La date de résiliation du contrat ne peut être inférieure à la date de début du contrat"], 400);
                }

                $proposition = $proposition->load(['employee', 'recurringOrder.user', 'recurringOrder.user.co']);

                $employee = $proposition->employee;

                $recurringOrder = $proposition->recurringOrder;
                $data =
                    [
                        'end_reason' => $request->reason,
                        "status" => -2,
                        "end_date" => Carbon::parse($request->date),
                        "end_type" => $request->type,
                        "is_professional_break" => $request->is_professional_break
                    ];



                Proposition::updatePropositionById($proposition->id, $data);


                $countActifEmploye = Proposition::countActifEmployee($employee->id, 2);

                if ($countActifEmploye > 0) {
                    $update_employee_status = false;
                } else {
                    $otherActivePropositions = Proposition::otherActivePropositions($employee->id, 2);
                    if (!is_null($otherActivePropositions)) {
                        $update_employee_status = false;
                    }
                }
                if ($update_employee_status) {
                    $employee->status = 5;
                    $employee->save();
                }



                $terminatedPropositions = Proposition::terminatedPropositions($recurringOrder);
                if (count($terminatedPropositions) == $recurringOrder->number_of_employees) {
                    $recurringOrder->status = -1;
                    $recurringOrder->save();
                } else {
                    $actifProposition = Proposition::getOneProposition(2);
                    if (!is_null($actifProposition)) {
                        $recurringOrder->status = 4;
                        $recurringOrder->save();
                    } else {
                        $acceptedProposition = Proposition::getOneProposition(1);
                        if (!is_null($acceptedProposition)) {
                            if ($recurringOrder->client->signature == "") {
                                $recurringOrder->status = 2;
                                $recurringOrder->save();
                            } else {
                                $recurringOrder->status = 3;
                                $recurringOrder->save();
                            }
                        } else {
                            $proposedProposition = Proposition::getOneProposition(0);
                            if (!is_null($proposedProposition)) {
                                $recurringOrder->status = 1;
                                $recurringOrder->save();
                            } else {
                                $recurringOrder->status = 0;
                                $recurringOrder->save();
                            }
                        }
                    }
                }


                $payment = Payment::setTerminateContractPayment($recurringOrder->id, $employee->id);

                if (Carbon::parse($request->date)->locale('fr_FR')->toDateString() == (Carbon::parse($proposition->started_date)->locale('fr_FR')->toDateString())) {
                    $payment->delete();
                }

                else {
                    if (
                        $payment->month_salary == Carbon::parse($request->date)
                        ->locale('fr_FR')->monthName
                    ) {
                        $detailsOfCustomerAmount = $recurringOrder->budget_is_fixed ?  $this->paymentSalaryFunctions->getFixedBudgetSalaryDetails('terminate-contrat', $proposition) : $this->paymentSalaryFunctions->detailsOfAmountToBePaid('terminate-contrat', $proposition);
                        $payment->ylomi_direct_fees = $proposition->recurringOrder->cnss ?  $detailsOfCustomerAmount['ylomi_direct_fees'] : round($detailsOfCustomerAmount['cnss_employee_amount'] + $detailsOfCustomerAmount['cnss_customer_amount'] + $detailsOfCustomerAmount['vps_amount'] + $detailsOfCustomerAmount['its_amount'] + $detailsOfCustomerAmount['ylomi_direct_fees']);
                        $payment->employee_salary_amount = $detailsOfCustomerAmount['employee_salary_amount'];
                        $payment->total_amount_to_paid = $detailsOfCustomerAmount['total_amount_to_paid'];
                        $payment->cnss_customer_amount = $proposition->applied_cnss ? $detailsOfCustomerAmount['cnss_customer_amount'] : 0;
                        $payment->cnss_employee_amount =  $proposition->applied_cnss ? $detailsOfCustomerAmount['cnss_employee_amount'] : 0;
                        $payment->vps_amount = $proposition->applied_cnss ? $detailsOfCustomerAmount['vps_amount'] : 0;
                        $payment->its_amount = $proposition->applied_cnss  ? $detailsOfCustomerAmount['its_amount'] : 0;
                        $payment->assurance_amount = $detailsOfCustomerAmount['assurance_amount'];
                        $payment->save();
                    }
                    else {
                        if ($this->paymentSalaryFunctions->compareTwoMonth($payment->month_salary, Carbon::parse($request->date)
                            ->locale('fr_FR')->monthName)) {
                            $payment->update(['next_link' => true]);
                        } else {
                            $payment->delete();
                        }
                    }

                    $propositionsAcceptedAndActifs = Proposition::acceptedAndActifsPropositionsByPackage($recurringOrder->id);
                    if (count($propositionsAcceptedAndActifs)  > 0) {
                        $total_budget = $this->paymentSalaryFunctions->getTotalTopaidByPackage($recurringOrder->id);
                        $customer_avenant_url = $this->generateAndGetAvenantUrl($recurringOrder, $propositionsAcceptedAndActifs, $total_budget);
                    }
                    if ($proposition->recurringOrder->cnss) {
                        $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'accountant']);

                        foreach ($admins as $user) {
                            Mail::to($user->email)->send(new DishiringEmployeeMail($user, $proposition));
                        }
                    }
                    $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'RO']);

                    if (count($admins) > 0) {
                        foreach ($admins as $rh) {
                            Mail::to($rh->email)->send(new TerminateEmployeeContratMail($rh, $proposition));
                        }
                    }
                    /* if (!is_null($recurringOrder->user->co)) {
                        Mail::to($recurringOrder->user->co->email)->send(new TerminateEmployeeContratMail($recurringOrder->co, $proposition));
                    } */
                    $response['message'] = "Le contrat de l'employé a été résilier avec succès.";
                    $response['data'] = $proposition;
                    $response['detailsOfAmountToPaid'] = $detailsOfCustomerAmount ?? [];
                    $response['avenant_contrat_url'] = $customer_avenant_url ?? [];

                }

            }else {
                $response['message'] = "Seul les propositions actives sont résilié";
            }

        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage(),
            ], 422);
        }
        return response($response, 200);

    }


    #[Route('/employees/un-terminate-contract', methods: ['POST'], middleware: ['auth:sanctum', 'role:super-admin|CO'])]
    public function unTerminateEmployeeContract(UnterminateEmployeeContractRequest $request)
    {
        $proposition = Proposition::getPropositionByid($request->proposition_id);
        $proposition = $proposition->load(['recurringOrder', 'employee']);
        $employee = $proposition->employee;
        $recurringOrder = $proposition->recurringOrder;
        $result = [];
        if ($proposition->status == -2) {
            // get action for this proposition
            $employeePayments = Payment::findBy([['recurring_order_id', $recurringOrder->id], ['employee_id', $employee->id]]);

            if (count($employeePayments) == 1) {
                $action = "deployment-confirmation";
            } elseif (count($employeePayments) > 1) {
                $action = "after-salary-payment";
            }
            //   without defalcation
            if (!boolval($request->with_defalcation)) {
                $latestUnPaidPaymentRecord = Payment::setTerminateContractPayment($recurringOrder->id, $employee->id);
                if (!is_null($latestUnPaidPaymentRecord)) {
                    $proposition->update(['status' => 2]);
                    $detailsOfCustomerAmount = $recurringOrder->budget_is_fixed ?  $this->paymentSalaryFunctions->getFixedBudgetSalaryDetails($action, $proposition) : $this->paymentSalaryFunctions->detailsOfAmountToBePaid($action, $proposition);
                    $latestUnPaidPaymentRecord->ylomi_direct_fees = $proposition->recurringOrder->applied_cnss ?  $detailsOfCustomerAmount['ylomi_direct_fees'] : round($detailsOfCustomerAmount['cnss_employee_amount'] + $detailsOfCustomerAmount['cnss_customer_amount'] + $detailsOfCustomerAmount['vps_amount'] + $detailsOfCustomerAmount['its_amount'] + $detailsOfCustomerAmount['ylomi_direct_fees']);
                    $latestUnPaidPaymentRecord->employee_salary_amount = $detailsOfCustomerAmount['employee_salary_amount'];
                    $latestUnPaidPaymentRecord->total_amount_to_paid = $detailsOfCustomerAmount['total_amount_to_paid'];
                    $latestUnPaidPaymentRecord->cnss_customer_amount = $proposition->applied_cnss ? $detailsOfCustomerAmount['cnss_customer_amount'] : 0;
                    $latestUnPaidPaymentRecord->cnss_employee_amount =  $proposition->applied_cnss ? $detailsOfCustomerAmount['cnss_employee_amount'] : 0;
                    $latestUnPaidPaymentRecord->vps_amount = $proposition->applied_cnss ? $detailsOfCustomerAmount['vps_amount'] : 0;
                    $latestUnPaidPaymentRecord->its_amount = $proposition->applied_cnss  ? $detailsOfCustomerAmount['its_amount'] : 0;
                    $latestUnPaidPaymentRecord->assurance_amount = $detailsOfCustomerAmount['assurance_amount'];
                    $latestUnPaidPaymentRecord->save();
                    $result = $detailsOfCustomerAmount;
                } else {

                    $payment = $this->paymentRepository
                        ->getPayment('recurring_order_id', $recurringOrder->id, 'employee_id', $employee->id, "latest", true);
                    if ($payment) {
                        $payment->latest = false;
                        $payment->save();
                    }
                    $detailsOfAmountToBePaid = $proposition->recurringOrder->budget_is_fixed ? $this->paymentSalaryFunctions->getFixedBudgetSalaryDetailsBetweenTwoDate($proposition->employee_contrat_end_date, Carbon::parse($proposition->employee_contrat_end_date)->endOfMonth(), $proposition) : $this->paymentSalaryFunctions->detailsOfAmountToBePaidBetweenTwoDate($proposition->employee_contrat_end_date, Carbon::parse($proposition->employee_contrat_end_date)->endOfMonth(), $proposition);
                    $newPaymentRecord = new Payment();
                    $newPaymentRecord->latest = true;
                    $newPaymentRecord->year =  Carbon::parse($proposition->employee_contrat_end_date)->year;
                    $newPaymentRecord->month_salary = Carbon::parse($proposition->employee_contrat_end_date)->locale('fr_FR')->monthName;
                    $newPaymentRecord->recurringOrder()->associate($recurringOrder);
                    $newPaymentRecord->employee()->associate($employee);
                    $newPaymentRecord->ylomi_direct_fees = $proposition->recurringOrder->applied_cnss ?  $detailsOfAmountToBePaid['ylomi_direct_fees'] : round($detailsOfAmountToBePaid['cnss_employee_amount'] + $detailsOfAmountToBePaid['cnss_customer_amount'] + $detailsOfAmountToBePaid['vps_amount'] + $detailsOfAmountToBePaid['its_amount'] + $detailsOfAmountToBePaid['ylomi_direct_fees']);
                    $newPaymentRecord->employee_salary_amount = $detailsOfAmountToBePaid['employee_salary_amount'];
                    $newPaymentRecord->total_amount_to_paid = $detailsOfAmountToBePaid['total_amount_to_paid'];
                    $newPaymentRecord->cnss_customer_amount = $proposition->applied_cnss ? $detailsOfAmountToBePaid['cnss_customer_amount'] : 0;
                    $newPaymentRecord->cnss_employee_amount =  $proposition->applied_cnss ? $detailsOfAmountToBePaid['cnss_employee_amount'] : 0;
                    $newPaymentRecord->vps_amount = $proposition->applied_cnss ? $detailsOfAmountToBePaid['vps_amount'] : 0;
                    $newPaymentRecord->its_amount = $proposition->applied_cnss  ? $detailsOfAmountToBePaid['its_amount'] : 0;
                    $newPaymentRecord->assurance_amount = $detailsOfAmountToBePaid['assurance_amount'];
                    $newPaymentRecord->save();
                    $result = $detailsOfAmountToBePaid;
                }
            } else {
                $dateStartWorking = Carbon::parse($request->date);
                $paymentInMonthStartWorking = $this->paymentRepository->findOneBy([['month_salary', $dateStartWorking->monthName], ['year', $dateStartWorking->year], ['latest', true], ['status', false], ['employee_id', $employee->id], ['recurring_order_id', $recurringOrder->id]]);

                if (!is_null($paymentInMonthStartWorking)) {
                    $remainingdetailsOfAmountToBePaid = $proposition->recurringOrder->budget_is_fixed ? $this->paymentSalaryFunctions->getFixedBudgetSalaryDetailsBetweenTwoDate($request->date, $dateStartWorking->endOfMonth(), $proposition) : $this->paymentSalaryFunctions->detailsOfAmountToBePaidBetweenTwoDate($request->date, $dateStartWorking->endOfMonth(), $proposition);
                    $paymentInMonthStartWorking->ylomi_direct_fees += $proposition->recurringOrder->applied_cnss ?  $remainingdetailsOfAmountToBePaid['ylomi_direct_fees'] : round($remainingdetailsOfAmountToBePaid['cnss_employee_amount'] + $remainingdetailsOfAmountToBePaid['cnss_customer_amount'] + $remainingdetailsOfAmountToBePaid['vps_amount'] + $remainingdetailsOfAmountToBePaid['its_amount'] + $remainingdetailsOfAmountToBePaid['ylomi_direct_fees']);

                    $paymentInMonthStartWorking->employee_salary_amount += $remainingdetailsOfAmountToBePaid['employee_salary_amount'];

                    $paymentInMonthStartWorking->total_amount_to_paid += $remainingdetailsOfAmountToBePaid['total_amount_to_paid'];

                    $paymentInMonthStartWorking->cnss_customer_amount += $proposition->applied_cnss ? $remainingdetailsOfAmountToBePaid['cnss_customer_amount'] : 0;
                    $paymentInMonthStartWorking->cnss_employee_amount +=  $proposition->applied_cnss ? $remainingdetailsOfAmountToBePaid['cnss_employee_amount'] : 0;
                    $paymentInMonthStartWorking->vps_amount += $proposition->applied_cnss ? $remainingdetailsOfAmountToBePaid['vps_amount'] : 0;
                    $paymentInMonthStartWorking->its_amount += $proposition->applied_cnss  ? $remainingdetailsOfAmountToBePaid['its_amount'] : 0;
                    $paymentInMonthStartWorking->assurance_amount += $remainingdetailsOfAmountToBePaid['assurance_amount'];
                    $paymentInMonthStartWorking->save();

                    $result = $remainingdetailsOfAmountToBePaid;
                } else {

                    $payment = $this->paymentRepository
                        ->getPayment('recurring_order_id', $recurringOrder->id, 'employee_id', $employee->id, "latest", true);
                    if ($payment) {
                        $payment->latest = false;
                        $payment->save();
                    }
                    $detailsOfAmountToBePaid = $proposition->recurringOrder->budget_is_fixed ? $this->paymentSalaryFunctions->getFixedBudgetSalaryDetailsBetweenTwoDate($request->date, $dateStartWorking->endOfMonth(), $proposition) : $this->paymentSalaryFunctions->detailsOfAmountToBePaidBetweenTwoDate($request->date, $dateStartWorking->endOfMonth(), $proposition);

                    $newPaymentRecord = new Payment();
                    $newPaymentRecord->latest = true;
                    $newPaymentRecord->year =  $dateStartWorking->year;
                    $newPaymentRecord->month_salary = $dateStartWorking->locale('fr_FR')->monthName;
                    $newPaymentRecord->recurringOrder()->associate($recurringOrder);
                    $newPaymentRecord->employee()->associate($employee);
                    $newPaymentRecord->ylomi_direct_fees = $proposition->recurringOrder->applied_cnss ?  $detailsOfAmountToBePaid['ylomi_direct_fees'] : round($detailsOfAmountToBePaid['cnss_employee_amount'] + $detailsOfAmountToBePaid['cnss_customer_amount'] + $detailsOfAmountToBePaid['vps_amount'] + $detailsOfAmountToBePaid['its_amount'] + $detailsOfAmountToBePaid['ylomi_direct_fees']);
                    $newPaymentRecord->employee_salary_amount = $detailsOfAmountToBePaid['employee_salary_amount'];
                    $newPaymentRecord->total_amount_to_paid = $detailsOfAmountToBePaid['total_amount_to_paid'];
                    $newPaymentRecord->cnss_customer_amount = $proposition->applied_cnss ? $detailsOfAmountToBePaid['cnss_customer_amount'] : 0;
                    $newPaymentRecord->cnss_employee_amount =  $proposition->applied_cnss ? $detailsOfAmountToBePaid['cnss_employee_amount'] : 0;
                    $newPaymentRecord->vps_amount = $proposition->applied_cnss ? $detailsOfAmountToBePaid['vps_amount'] : 0;
                    $newPaymentRecord->its_amount = $proposition->applied_cnss  ? $detailsOfAmountToBePaid['its_amount'] : 0;
                    $newPaymentRecord->assurance_amount = $detailsOfAmountToBePaid['assurance_amount'];
                    $newPaymentRecord->save();

                    $result = $detailsOfAmountToBePaid;
                }
            }
            $proposition->update(['status' => 2, 'end_date' => null, "end_reason" => null]);

            // update order status
            if ($recurringOrder->status !== 4) {
                $proposition->recurringOrder->status = 4;
                $proposition->recurringOrder->save();
            }

            //update employee status
            if ($employee->status !== 2) {
                $employee->update(['status' => 2]);
            }
            $response["message"] = "Employé réactivé  avec succès.";
            $response['detailsOfAmountToPaid'] = $result;

            return response($response, 200);
        } else {
            $response["message"] = "La commande doit etre résilié pour pouvoir effectué cette action.";
            return response($response, 400);
        }
    }




    public function verifyDate($date)
    {
        $year = Carbon::parse($date)->year;
        if ($year == date("Y")) {
            if (!Carbon::parse($date)->isNextMonth()) {
                return true;
            }
            return false;
        }
        return false;
    }


}
