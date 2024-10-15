<?php

namespace Core\Modules\User;

use Carbon\Carbon;
use Core\ExternalServices\QosService;
use Core\Modules\Access\Models\Role;
use Core\Modules\RecurringOrder\Repositories\UserRecurringOrderRepository;
// use Core\Modules\RecurringOrder\Repositories\UserRecurringOrderRepository;
// use Core\Modules\User\UserRecurringOrderRepository;
use Core\Modules\Transaction\Models\Transaction;
use Core\Modules\Transaction\Models\TransactionData;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\User\Jobs\AfterWalletIsCreditedJob;
use Core\Modules\User\Mails\DesactivateAccount;
use Core\Modules\User\Mails\NewRoleForUser;
use Core\Modules\User\Mails\WalletIsCredited;
use Core\Modules\User\Models\User;
use Core\Modules\User\Requests\AccountDeletionRequest;
use Core\Modules\User\Requests\CreditWalletRequest;
use Core\Modules\User\Requests\RejectContractRequest;
use Core\Modules\User\Requests\SignContractRequest;
use Core\Modules\User\Requests\UpdateCustomerInfoRequest;
use Core\Modules\User\Requests\UpdateProfileRequest;
use Core\Modules\Wallet\WalletRepository;
use Core\Utils\Constants;
use Core\Utils\Controller;
use Core\Utils\Enums\OperationType;
use Core\Utils\Jobs\GeneratePDF;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use NumberToWords\NumberToWords;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\RecurringOrder\Repositories\RecurringOrderRepository;
use Core\Modules\PunctualOrder\Repositories\PunctualOrderRepository;
use Illuminate\Support\Facades\Storage;

#[Route('/users', middleware:['auth:sanctum'])]
class UserController extends Controller
{
    private UserRepository $userRepository;
    private RecurringOrderRepository $recurringOrderRepository;
    private PunctualOrderRepository $punctualOrderRepository;
    private TransactionRepository $transactionRepository;
    private QosService $qosService;
    private WalletRepository $walletRepository;

    public function __construct(
        UserRepository        $userRepository,
        TransactionRepository $transactionRepository,
        QosService            $qosService,
        WalletRepository      $walletRepository,
        RecurringOrderRepository $recurringOrderRepository,
        PunctualOrderRepository $punctualOrderRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->transactionRepository = $transactionRepository;
        $this->qosService = $qosService;
        $this->walletRepository = $walletRepository;
        $this->recurringOrderRepository =  $recurringOrderRepository;
        $this->punctualOrderRepository =  $punctualOrderRepository;
    }

    #[Route('/customers', methods: ['GET'])]
    public function customerUsers(Request $request): Response
    {
        $response["message"] = "Liste des clients";

        $paginate = $request->query('paginate') == 'false';

        if ($paginate) {
            $customers = $this->userRepository->userWithRole(['customer'], false);
            $customers->map(function ($user) {
                $user->profile_image = $this->s3FileUrl($user->profile_image);
                $user->full_name = $user->first_name . ' ' . $user->last_name;
                $user->id_card = $this->s3FileUrl($user->id_card);
                return $user;
            });
        }else{

            $customers = ($request->query->count() == 0 || $request->has('page')) ?
            $this->userRepository->userWithRole(['customer'], true) :
            $this->userRepository->searchCustomerUsers($request);

            $customers->transform(function ($user) {
            $user->profile_image = $this->s3FileUrl($user->profile_image);
            $user->id_card = $this->s3FileUrl($user->id_card);
            return $user;
        });

        }

        $response['data'] = $customers;

        return response($response, 200);
    }

    /**
     * @throws \Exception
     */
    #[Route('/admins', methods: ['GET'])]
    public function adminUsers(Request $request): Response
    {
        $response["message"] = "Liste des admins";
        $admins = $request->query->count() > 0 ?
            $this->userRepository->searchAdminUsers($request) :
            $this->userRepository->adminUsers();
        $admins->transform(function ($admin) {
            $admin->profile_image = $this->s3FileUrl($admin->profile_image);
            $admin->full_name = $admin->first_name. " ". $admin->last_name;
            return $admin;
        });
        $response['data'] = $admins;
        return response($response, 200);
    }

    #[Route('/{user}', methods: ['GET'], wheres: ['user' => Constants::REGEXUUID])]
    public function show(User $user): Response
    {
        $user->profile_image = $this->s3FileUrl($user->profile_image);
        $user->id_card = $this->s3FileUrl($user->id_card);
        $user = $user->load(['roles.permissions', 'wallet']);
        // $user = $user->load(['roles.permissions', 'wallet', 'devices']);
        return response(['message' => "Détail de l'utilisateur récupéré avec succès", "data" => $user], 200);
    }

    #[Route('/{user}', methods: ['PATCH'], wheres: ['user' => Constants::REGEXUUID])]
    public function updateCustomerInfo(UpdateCustomerInfoRequest $request, User $user): Response
    {
        if ($user->hasRole('customer')) {
            //Assignation du client   à un CO
            if ($request->filled('staff_id')) {

                $staff = $this->userRepository->findBy('id', $request->input('staff_id'));

                if ($staff->hasAnyRole(['CO', 'RO'])) {
                    if (!$this->userRepository->isCOAssignedToCustomer($user, $staff)) {
                        $this->userRepository->attach($staff, $user->id, ["status" => true, "assign_at" => now()], 'customers');
                        //Mail::to($co->email)->send(new EmployeeIsAssigned($co, $employee));
                    } else {
                        return response(['message' => "Ce CO est déjà en charge de ce client"], 400);
                    }
                } else {
                    return response(['message' => "Vous ne pouvez assigner cette commande qu'à un CO ou RO"], 400);
                }
            }
            $response["message"] = "Informations du client modifié avec succès";
            $data = $request->validated();
            if (!$request->is_company) {
                $data['company_name'] = null;
                $data['company_address'] = null;
            }
            $response["data"] = $this->userRepository->update($user, $request->validated());
            return response($response, 201);
        }

        return response([
            'message' => "Vous ne pouvez que modifié le profile des clients."
        ], 422);
    }

    #[Route('/update/profile', methods: ['POST'])]
    public function updateProfile(UpdateProfileRequest $request): Response
    {
        $requestValidatedData = $request->validated();
        $data = Arr::except($requestValidatedData, ['profile_image']);
        if (!$request->is_company) {
            $data['company_name'] = null;
            $data['company_address'] = null;
        }
        $user = $this->userRepository->findById(Auth::id());

        if ($request->hasFile('profile_image')) {
            $profileImagePath = $this->uploadFile($request->file('profile_image'));
            $data['profile_image'] = $profileImagePath;
        }
        $user = $this->userRepository->update($user, $data);
        $response['message'] = "Profil modifié avec succès";
        $user->profile_image = $this->s3FileUrl($user->profile_image);
        $response['data'] = $user->load(['roles.permissions', 'wallet']);
        return response($response, 200);
    }

    #[Route('/{user}/role/{role}', methods: ['PATCH'], wheres: ['user' => Constants::REGEXUUID, 'role' => Constants::REGEXUUID])]
    public function updateAdminRole(User $user, Role $role): Response
    {
        if ($role->name != 'customer') {
            $user->syncRoles($role->name);
            // Mail::to($user->email)->send(new NewRoleForUser($user, Auth::user()));
            $user->profile_image = $this->s3FileUrl($user->profile_image);
            return response(['message' => "Role de l'admin modifié avec succès!", "data" => $user->load(['roles.permissions'])], 201);
        }
        return response(['message' => "Vous ne pouvez pas assigner le role client à un utilisateur"], 422);
    }

    #[Route('/{user}', methods: ['DELETE'], wheres: ['user' => Constants::REGEXUUID])]
    public function delete(AccountDeletionRequest $request, User $user): Response
    {
        if ($user->hasRole('customer')) {
            if (Auth::id() == $user->id) {
                $this->userRepository->update($user, ['delete_account_reason' => $request->input('reason')]);
                if ($this->userRepository->delete($user)) {
                    $user->tokens()->delete();
                    /* foreach (User::superAdminUsers() as $admin) {
                         Mail::to($admin->email)->send(new UserDeleteAccount($user->onlyTrashed()->where('id', $user->id)->first(), $admin));
                     }
                    */
                    return response(['message' => "Compte supprimé avec succès", "data" => $request->reason], 200);
                }
            } else if (!$user->is_activated) {
                if ($this->userRepository->delete($user)) {
                    return response(['message' => "Client supprimé avec succès"], 200);
                }
            } else {
                return response(['message' => "Vous êtes sur le point de supprimé un compte actif"], 400);
            }
        }
        return response(['message' => "Ce compte dont vous ếtes sur le point de supprimé n'est pas celui d'un client"], 400);
    }


    #[Route('/{user}/deactivate', methods: ['GET'], wheres: ['user' => Constants::REGEXUUID])]
    public function deactivateAdminUser(User $user): Response
    {
        if ($user->status) {
            if (!$user->hasRole(['customer', 'super-admin'])) {
                $this->userRepository->update($user, ['status' => false, 'deactivate_date' => Carbon::now()]);
                foreach ($this->userRepository->userWithRole(['admin']) as $admin) {
                    Mail::to($admin->email)->send(new DesactivateAccount($user->load(['roles']), $admin));
                }
                $user->tokens()->delete();
                return response(['message' => "Compte désactivé  avec succès", "data" => $user->refresh()], 200);
            }
            return response(['message' => "Impossible de désactivé un super admin ou un client"], 400);
        }
        return response(['message' => "Compte déjà désactivé"], 400);
    }

    #[Route('/{user}/reactivate', methods: ['GET'], wheres: ['user' => Constants::REGEXUUID])]
    public function reactivateAdminUser(User $user): Response
    {
        if (!$user->status) {
            if ($this->userRepository->update($user, ['status' => true, 'deactivate_date' => null])) {
                $user->tokens()->delete();
                return response(['message' => "Compte réactivé  avec succès", "data" => $user->refresh()], 200);
            }
        }
        return response(['message' => "Compte déjà actif"], 400);
    }


    #[Route('/credit-wallet', methods: ['POST'], wheres: ['user' => Constants::REGEXUUID])]
    public function creditWallet(CreditWalletRequest $request): Response
    {
        $wallet = Auth::user()->wallet;
        $data = [
            'wallet_id' => $wallet->id,
            "amount" => $request->amount,
            "paymentMethod" => $request->payment_method,
            "phoneNumber" => $request->phone_number,
            'author' => Auth::user()
        ];
        $encode_data = json_encode($data);
        $transactionData = $this->transactionRepository->storeTransactionData(['data' => $encode_data]);

        $transactionResponse = $this->qosService->makeTransaction(
            $request->payment_method,
            $request->amount,
            $request->phone_number,
            $transactionData,
            Auth::user(),
            "Recharge de portefeuille"
        );

        if (is_bool($transactionResponse)) {

            if ($request->payment_method == 1) {
                AfterWalletIsCreditedJob::dispatch(
                    $transactionData->id,
                    $this->transactionRepository,
                    $this->qosService,
                    $this->userRepository,
                    $this->walletRepository
                )->delay(Carbon::now()
                    ->addMinutes());
                $response['message'] = "Paiement en cour";
                $response['data'] = $transactionData;
            } else {
                $this->afterWalletIsCredited($transactionData);
                $response['message'] = "Recharge effectué avec succès";
            }
            return response($response, 200);
        } else {
            $response['message'] = "$transactionResponse";
            return response($response, 400);
        }
    }


    #[Route('/after-wallet-recharge/{transactionData}', methods: ['GET'])]
    public function afterWalletIsCredited(TransactionData $transactionData): Response
    {
        if (!$transactionData->is_update) {
            $utilsData = json_decode($transactionData->data, true);

            $wallet = $this->walletRepository->findById($utilsData['wallet_id']);

            $this->walletRepository->makeOperation(
                $wallet,
                OperationType::DEPOSIT,
                $utilsData['amount'],
                'Recharge du portefeuille'
            );

            foreach (User::role(['super-admin', 'admin'])->get() as $user) {
                Mail::to($user->email)->send(new WalletIsCredited($user->full_name, $utilsData['amount'], $wallet->balance, $user));
            }
            $transaction = Transaction::make([
                'status' => 'SUCCESSFUL',
                'type' => "Recharge de portefeuille",
                'payment_method' => $utilsData['paymentMethod'] == 1 ? 'MTN' : 'Carte Visa',
                'author' => $utilsData['author']['last_name'] . " " . $utilsData['author']['first_name'],
                'amount' => $utilsData['amount'],
                "phoneNumber" => $utilsData['phoneNumber']
            ]);
            $transaction->transactionData()->associate($transactionData);
            $transaction->save();

            return response(['message' => 'Recharge effectué avec succès ! 🥳', 'data' => $wallet->refresh()], 201);
        }
        return response(['message' => 'Transaction déjà valider'], 422);
    }

    #[Route('/sign-contract', methods: ['POST'], middleware: ['role:customer'])]
    public function signContract(SignContractRequest $request): Response
    {
        $user = Auth::user();
        $updateField = [];
        $propositions = [];
        $total_budget = 0;

        if ($user->signature !== null) {
            return response(['message' => "Contrat déjà signé!"], 400);
        }
        if ($user->contract == "") {
            return response(['message' => "Aucun contrat à approuvé!"], 400);
        }
        $signaturePath = $this->uploadFile($request->file('signature'));
        $updateField['signature'] = $signaturePath;
        $updateField['contract_status'] = true;

        $orders = $user->recurringOrders()
            ->whereHas('propositions', function ($query) {
                $query->where('propositions.status', 1)
                    ->orWhere('propositions.status', 2);
            })
            ->with('propositions.employee')
            ->with('recurringService')
            ->orderBy('created_at')
            ->get();
        foreach ($orders as $value) {
            foreach ($value->propositions as $proposition) {

                $total_budget += $this->getCustomerBudget($proposition->salary, $value->cnss)['total'];
                if ($proposition->status == 1) {
                    $employeeContractName = $this->generateEmployeeContract($value,$proposition);

                    $proposition->contract = $employeeContractName;
                    $proposition->save();
                }
                $propositions[] = $proposition;
            }
        }

        $orders->transform(function ($value) {
            $value->propositions->transform(function ($proposition) use ($value) {
                $proposition['budget'] = $this->getCustomerBudget($proposition->salary, $value->cnss)['total'];
                return $proposition;
            });
            return $value;
        });
        $signature = $this->s3FileUrl($signaturePath);
        sleep(5);
        $contractName = $this->generateCustomerContract($total_budget,$orders[0],$propositions,$signature);
        $userModel = $this->userRepository->findById($user->id);
        $user = $this->userRepository->update($userModel, $updateField);
        $user->contract = Storage::temporaryUrl($contractName,now()->addDays(7));

        /*$userModel->contract = $contractName;
        $userModel->save();*/


        /**
         * EMail après apropation de contrat et modifier le status des commande sur contrat approuvé
         */

        /*  $recurringOrdersHavePropositionsAccepted = $this->recurringOrderRepository->recurringOrdersHavingPropositionsAccepted($user->id);
          foreach ($recurringOrdersHavePropositionsAccepted as $value) {
              if ($value->propositions_count >= 1) {
                  $value->status = 3;
                  $value->save();
              }
          }*/
        // $total_budget = $this->packageSalaryFunctions->getTotalTopaidByPackage($user->id);
        //$propositionsActifsAndAccepted = $this->propositionsRepository->acceptedAndActifsPropositionsByPackage($user->id);
        // $customer_contrat_url = $this->contratPdfServices->generateAndGetContractUrl($user->load(["user"]), $propositionsActifsAndAccepted, $total_budget, $client_signature_s3_url);

        /* foreach (User::superAdminAndResponsableCommercialUsers() as $user) {
             Mail::to($user->email)
                 ->send(new ClientApprouveContract($user, $user->load(['user']), $customer_contrat_url));
         }
         if (!is_null($user->load(['assignTo'])->assignTo)) {
             Mail::to($user->load(['assignTo'])->assignTo->email)->send(new ClientApprouveContract($user, $user->load(['user']), $customer_contrat_url));
         }

         foreach ($propositionsActifsAndAccepted as $propositions) {
             if ($propositions->employee_contrat_signature == "") {
                 foreach (User::superAdminAndAdminRHUsers() as $user) {
                     Mail::to($user->email)
                         ->send(new NotifyRHApproveEmployeeContract($user, $propositions));
                 }

                 if (!is_null($user->load(["rh"])->rh)) {
                     Mail::to($user->load(["rh"])->rh->email)
                         ->send(new NotifyRHApproveEmployeeContract($user->load(["rh"])->rh, $propositions));
                 }
             }
         }

         Mail::to($user->load('user')->user->email)->send(new AfterContratApprouve($user->load(['user', 'assignTo'])));*/
        $response['data'] = $userModel->load(['wallet', 'roles.permissions','clientConversations']);
        $response['message'] = "Contrat approuvé avec sucès";
        return response($response, 200);
    }

    #[Route('/reject-contract', methods: ['POST'], middleware: ['role:customer'])]
    public function rejectContract(RejectContractRequest $request): Response
    {
        $user = Auth::user();
        if ($user->contract_status === false) {
            return response(["message" => "Le contrat à déja été désapprouvé"], 400);
        }
        $data = $request->validated();

        $data['contract_status'] = false;
        $userModel = $this->userRepository->findById($user->id);
        $user = $this->userRepository->update($userModel, $data);
        $user->contract = $this->s3FileUrl($user->contract);
        $response['data'] = $userModel->load(['wallet', 'roles.permissions','clientConversations']);
        $response["message"] = "Contrat désapprouvé avec succès";

        /* foreach (User::superAdminAndResponsableCommercialUsers() as $user) {
             Mail::to($user->email)->send(
                 new ClientDisapproveContract($user, $response["data"])
             );
         }

         $package = $package->load(['assignTo']);
         if (!is_null($package->assign_to)) {
             $chargeDaffaire = User::where('id', $package->assign_to)->where('role_id', Role::chargeDaffaire()->id)->first();
             if ($chargeDaffaire) {
                 Mail::to($chargeDaffaire->email)->send(new ClientDisapproveContract($chargeDaffaire, $response["data"]));
             }
         }*/

        return response($response, 201);
    }

    #[Route('/customers/statistics', methods: ['GET'])]
    public function statistics(): Response
    {
        $response["message"] = "Statistiques sur les clients";

        $count = $this->recurringOrderRepository->getUsersWithRecurringOrder(1);
        $count1 = $this->punctualOrderRepository->getAllPunctualOrder();
        $totalClients = $this->userRepository->getStatistics()['totalClients'];
        $activeClients = $this->userRepository->getStatistics()['activeClients'];
        $inactiveClients = $this->userRepository->getStatistics()['inactiveClients'];
        $total= $count + $count1;

        $response["data"] = [
            "totalClients" => $totalClients,
            "activeClients" => $activeClients,
            "inactiveClients" => $inactiveClients,
            "userWithOrders" => $total,
        ];

        // $response["data"] = $this->userRepository->getStatistics();
        return response($response, 200);
    }


}
