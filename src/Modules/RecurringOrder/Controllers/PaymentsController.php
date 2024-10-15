<?php

namespace Core\Modules\RecurringOrder\Controllers;

use App\Helpers\Helper;
use Carbon\Carbon;
use Core\Utils\Constants;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Core\Utils\Enums\OperationType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Core\ExternalServices\QosService;
use Core\Modules\User\UserRepository;
use Core\Modules\Wallet\Models\Wallet;
use Core\Modules\Wallet\WalletRepository;
use SmashedEgg\LaravelRouteAnnotation\Route;
use Core\Modules\RecurringOrder\Models\Payment;
use Core\Modules\Transaction\Models\Transaction;
use Core\Modules\RecurringOrder\Mails\FactureMail;
use Core\Modules\RecurringOrder\Models\Proposition;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\Transaction\Models\TransactionData;
use Core\Modules\PunctualOrder\Mails\EmployeNotedMail;
use Core\Modules\RecurringOrder\Models\RecurringOrder;
use Core\Modules\Employee\Repositories\EmployeeRepository;
use Core\Modules\RecurringOrder\Jobs\AfterSalaryPaymentJob;
use Core\Modules\RecurringOrder\Requests\SalaryPaymentRequest;
use Core\Modules\RecurringOrder\Repositories\PaymentsRepository;
use Core\Modules\PunctualOrder\Requests\StoreEmployeeNoteRequest;
use Core\Modules\RecurringOrder\Functions\PaymentSalaryFunctions;
use Core\Modules\RecurringOrder\Requests\PayEmployeeSalaryRequest;
use Core\Modules\RecurringOrder\Requests\GetEmployeesSalaryRequest;
use Core\Modules\RecurringOrder\Repositories\RecurringOrderRepository;
use Core\Modules\RecurringOrder\Requests\StatisticStartDateAndEndDateRequest;


#[Route('/payment', middleware: ['auth:sanctum'])]
class PaymentsController extends Controller
{
    private PaymentsRepository $paymentsRepository;
    private QosService $qosService;
    private TransactionRepository $transactionsRepository;
    private PaymentSalaryFunctions $paymentSalaryFunctions;
    private WalletRepository $walletRepository;

    private UserRepository $userRepository;

    private RecurringOrderRepository $recurringOrderRepository;

    private EmployeeRepository $employeeRepository;


    public function __construct(
        PaymentsRepository $paymentsRepository,
        QosService $qosService,
        TransactionRepository $transactionsRepository,
        PaymentSalaryFunctions $paymentSalaryFunctions,
        WalletRepository $walletRepository,
        RecurringOrderRepository $recurringOrderRepository,
        EmployeeRepository $employeeRepository,
        UserRepository $userRepository
    ) {
        $this->paymentsRepository = $paymentsRepository;
        $this->qosService = $qosService;
        $this->transactionsRepository = $transactionsRepository;
        $this->paymentSalaryFunctions = $paymentSalaryFunctions;
        $this->walletRepository = $walletRepository;
        $this->recurringOrderRepository = $recurringOrderRepository;
        $this->userRepository = $userRepository;
        $this->employeeRepository = $employeeRepository;
    }



    #[Route('/', methods: ['GET', 'POST'])]
    public function salaryPayments(Request $request)
    {

        $response["message"] = "Salaires payé et non payé";

        $filter_obj = Helper::filterPaymentDefaultvalue($request->filter);

        $perPage = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        //$filter = $request->filter;
        $user = auth()->user();  // Récupérer l'utilisateur authentifié
        $role = $user->getRoleNames()->first();  // Récupérer le rôle de l'utilisateur

        $response = [];

        switch ($role) {
            case 'super-admin':
            case 'accountant':
            case 'AA':
            case 'RRC':
                // Appel à la méthode getFilterListPayment pour les rôles admin
                $payments = Payment::getFilterListPayment($perPage, $page, $filter_obj);

                // Transformer la collection pour ajouter 'full_name'
                $payments->getCollection()->transform(function ($payment) {
                    $payment->customer_full_name = "{$payment->recurringOrder->user->first_name} {$payment->recurringOrder->user->last_name}";
                    return $payment;
                });

                // Préparer la réponse
                $response['data'] = $payments;
                break;

            case 'customer':
                // Gestion spécifique pour les clients
                $package = $this->recurringOrderRepository->getPackageBy('user_id', $user->id);
                $response['data'] = $package ? $this->paymentsRepository->getPaymentsByPackages($package) : [];
                break;

            case 'CO':
                // Appel à la méthode getFilterListPayment avec filtre spécifique pour CO
                $response['data'] = Payment::getFilterListPayment($perPage, $page, $filter_obj);
                break;

            default:
                // Si l'utilisateur n'a aucun des rôles
                return response()->json([
                    'message' => 'Accès ou opération interdit.'
                ], 403);
        }

        return response()->json($response);
    }

    #[Route('/{payment}/cancel', methods: ['POST'], middleware: ['role:super-admin'], wheres: ['payment' => Constants::REGEXUUID])]
    public function cancelPayment(Payment $payment)
    {
        if (Auth::user()->hasAnyRole(['super-admin', 'accountant', 'AA'])) {
            if ($payment->status) {
                return response(['message' => "Ce lien de paiement a déjà été payé, vous ne pouvez plus l'annuler"], 400);
            }
            if (!is_null($payment->deleted_at)) {
                return response(['message' => "Ce lien de paiement a déjà été annulé"], 400);
            }
            if ($this->paymentsRepository->delete($payment)) {
                Payment::onlyTrashed()->where('id', $payment->id)->update(['deleted_by' => Auth::id()]);
            }
            $response["message"] = "Paiement annulé avec succès.";
            return response($response, 200);
        }

        return response()->json([
            'message' => 'Accès ou opération uniquement réservée aux super admins, comptables ou assistantes administratives.'
        ], 403);
    }



    #[Route('/{payment}/comfirm', methods: ['POST'], middleware: ['role:super-admin'], wheres: ['payment' => Constants::REGEXUUID])]
    public function confirmPayment(Payment $payment)
    {
        if (Auth::user()->hasAnyRole(['super-admin', 'accountant'])) {
            if ($payment->status) {
                return response(['message' => "Ce lien de paiement a déjà été payé, vous ne pouvez plus le confirmer"], 400);
            }

            $data = $this->paymentSalaryFunctions->afterSalaryPayment($payment->id);


            $response["message"] = "Paiement confirmé avec succès.";
            $response['data'] = $data;

            return response($response, 200);
        }

        return response()->json([
            'message' => 'Accès ou opération uniquement réservé aux super admins et comptables.'
        ], 403);
    }


    #[Route('/{payment}', methods: ['GET'], middleware: ['role:super-admin|customer'], wheres: ['payment' => Constants::REGEXUUID])]
    public function getPayment(Payment $payment)
    {
        $response['data'] =  $this->paymentsRepository->findPayment($payment->id);
        $response["message"] = "Détail du paiement {$payment->id}";
        return response($response, 200);
    }


    #[Route('/{payment}/block', methods: ['POST'], middleware: ['role:super-admin'], wheres: ['payment' => Constants::REGEXUUID])]
    public function blockPayment(Payment $payment)
    {
        if (Auth::user()->hasAnyRole(['super-admin', 'accountant'])) {
            if (!$payment->auto_send) {
                return response(['message' => "Ce lien de paiement a déjà été bloqué."], 400);
            }

            if ($payment->status) {
                return response(['message' => "L'employé a déjà reçu le salaire, vous ne pouvez plus le bloquer."], 400);
            }

            $this->paymentsRepository->update($payment, ['auto_send' => false]);

            $response["message"] = "Paiement bloqué avec succès.";
            return response($response, 200);
        }

        return response()->json([
            'message' => 'Accès ou opération uniquement réservé aux super admins et comptables.'
        ], 403);
    }



    #[Route('/{payment}/unblock', methods: ['POST'], middleware: ['role:super-admin'], wheres: ['payment' => Constants::REGEXUUID])]
    public function unBlockPayment(Payment $payment)
    {
        if (Auth::user()->hasAnyRole(['super-admin', 'accountant'])) {
            if ($payment->auto_send) {
                return response(['message' => "Ce lien de paiement a déjà été débloqué."], 400);
            }

            $this->paymentsRepository->update($payment, ['auto_send' => true]);

            $response["message"] = "Paiement débloqué avec succès.";
            return response($response, 200);
        }

        return response()->json([
            'message' => 'Accès ou opération uniquement réservé aux super admins et comptables.'
        ], 403);
    }




    #[Route('/after-salary-payment/{transactionData}', methods: ['GET'])]
    public function afterSalaryPayment(TransactionData $transactionData)
    {

        $utilsData = json_decode($transactionData->data, true);

        //$amount = $utilsData->amount;
        $phoneNumber = $utilsData['phoneNumber'];
        $date = $utilsData['date'];
        $amount = $utilsData['amount'];
        if (!$transactionData->is_update) {
            $paymentsIds = $utilsData['payment'];

            $p = Payment::whereIn('id', $paymentsIds)->get();

            foreach ($p as $payment) {

                $this->paymentSalaryFunctions->afterSalaryPayment($payment->id, $utilsData['payment_method']);

                if ($date) {

                    $this->paymentsRepository->update($payment, ['auto_send' => false, 'salary_paid_date' => $date]);

                    // JOB POUR PAYER LE SALAIRE PLUS TARD ICI
                    //ENVOI DE MAIL POUR NOTIFIER QUE UN SALIRE VA ETRE PAYE PLUS TARD


                }
            }

            $this->transactionsRepository->updateTransactionData(['is_update' => true], $transactionData);

            $transaction = Transaction::make([
                'status' => "SUCCESSFUL",
                'author' => "{$payment->recurringOrder->user->first_name} {$payment->recurringOrder->user->last_name}",
                'type' => "Paiement des frais de prestation du mois de {$payment->month_salary} {$payment->year}.",
                'payment_method' => $utilsData['payment_method'] == 1 ? 'MTN' : 'Carte Visa',
                'amount' => $amount,
                'phoneNumber' => $phoneNumber
            ]);

            $transaction->transactionData()->associate($transactionData);
            $transaction->save();
        }

        $response["message"] = "Frais de prestation payé avec  succès.";

        return response($response, 200);
    }








    #[Route('/pay-salary', methods: ['POST'], middleware: ['role:customer'])]

    public function payEmployeeSalary(PayEmployeeSalaryRequest $request)
    {
        $p = $request->payment;
        $date = $request->date;
        $payments = Payment::whereIn('id', $p)->get();

        $paymentCount = [];

        $total_to_pay = 0;

        foreach ($payments as $payment) {
            $paymentCount[] = $payment->id;


            // Vérifier si le paiement a déjà été effectué
            if ($payment->status) {
                return response(['message' => "le salaire de l'employé {$payment->employee->full_name} est déjà payé. Merci"], 400);
            }

            $payment = $payment->load(['recurringOrder.user.wallet', 'employee']);
            $package = $payment->recurringOrder;

            // Calcul du montant total à payer en tenant compte des avances de salaire
            $current_total_to_paid = $payment->employee_received_salary_advance
                ? ($payment->total_amount_to_paid - $payment->salary_advance_amount)
                : $payment->total_amount_to_paid;

            $reductionMin = 10;
            $ylomi_fee = $payment->ylomi_direct_fees;
            $numberOfActifEmployee = Proposition::numberOfActifEmployee($package->user_id);

            // Appliquer la réduction
            if (5 <= $numberOfActifEmployee && $numberOfActifEmployee <= 10) {
                $current_total_to_paid -= ($ylomi_fee * $reductionMin) / 100;
                $payment->discount_applied = true;
                $payment->discount_rate = $reductionMin;
            } elseif ($payment->recurringOrder->discount_applied && $payment->discount_rate) {
                $current_total_to_paid -= ($ylomi_fee * $payment->recurringOrder->discount_rate) / 100;
                $payment->discount_applied = true;
                $payment->discount_rate = $payment->recurringOrder->discount_rate;
            }

            $total_to_pay += $current_total_to_paid;

            // Préparer les données de transaction pour chaque paiement


        }

        $data = [
            //"payment" => $payment->id,
            //'package_id' => $package->id,
            "month_salary" => $payment->month_salary,
            "year" => $payment->year,
            //"cnss" => $payment->cnss,
            'amount' => round($total_to_pay),
            'phoneNumber' => ($request->payment_method == 3 || $request->payment_method == 4)
                ? Auth::user()->phone_number
                : $request->phoneNumber,
            'total_to_paid' => round($total_to_pay),
            'transfer_fee' => 0,
            'payment_method' => $request->payment_method,
            'date' => $date,
        ];

        $data['payment'] = $paymentCount;
        // Encoder les données pour la transaction
        $encode_data = json_encode($data);
        $transactionsData = $this->transactionsRepository->storeTransactionData(['data' => $encode_data]);

        // Faire la transaction avec le montant total cumulé
        $transactionResponse = $this->qosService->makeTransaction(
            $request->payment_method,
            round($total_to_pay),
            $request->phoneNumber,
            $transactionsData,
            Auth::user(),
            "Paiement des frais de prestation de tous les employés."
        );

        // Vérification et retour de la réponse
        if (is_bool($transactionResponse)) {
            if ($request->payment_method == 1) {
                AfterSalaryPaymentJob::dispatch(
                    $paymentCount,
                    $transactionsData->id,
                    $this->paymentSalaryFunctions,
                    $this->paymentsRepository,
                    $this->transactionsRepository,
                    $this->qosService,
                    $request->payment_method
                )->delay(now()->addSeconds(30));
                return response(['message' => "Paiement en cours", 'data' => $transactionsData]);
            } elseif ($request->payment_method == 2) {
                $this->afterSalaryPayment($transactionsData);
                return response(['message' => "Paiement effectué avec succès ! 🥳", 'data' => $transactionsData]);
            }
        } else {
            return response(['message' => "Échec du paiement", 'data' => $transactionsData], 400);
        }
    }




    #[Route('/history', methods: ['GET', 'POST'], middleware: ['role:super-admin'], wheres: ['payment' => Constants::REGEXUUID])]
    public function PaymentsHistory(GetEmployeesSalaryRequest $request)
    {
        if (Auth::user()->hasAnyRole(['super-admin', 'RRC', 'accountant'])) {
            $response['data'] = $this->paymentsRepository->paymentsHistory($request->isMethod("post") ? $request->validated() : null);
            $response['message'] = "Historique de paiement du salaire";
            return response($response, 200);
        }

        if (Auth::user()->hasRole('CO')) {
            $response['data'] = $this->paymentsRepository->paymentsHistory($request->isMethod("post") ? $request->validated() : null, Auth::id());
            $response['message'] = "Historique de paiement du salaire";
            return response($response, 200);
        }

        return response()->json([
            'message' => 'Accès interdit.'
        ], 403);
    }



    #[Route('/filter-employee-cnss', methods: ['POST'], middleware: ['role:super-admin'], wheres: ['payment' => Constants::REGEXUUID])]
    public function filterEmployeeCnss(StatisticStartDateAndEndDateRequest $request)
    {
        $user = Auth::user();

        switch (true) {
            case $user->hasRole('super-admin'):
            case $user->hasRole('accountant'):
            case $user->hasRole('AA'):
            case $user->hasRole('RRC'):
            case $user->hasRole('CO'):
                $response['message'] = "Filtre des employés déclarés à la CNSS";
                $response['data'] = $this->paymentsRepository->filterEmployeeCnss($request->start_date, $request->end_date);
                return response($response, 200);

            default:
                return response([
                    'message' => "Accès ou opération uniquement réservé aux rôles autorisés."
                ], 403);
        }
    }


    #[Route('/{payment}/auto-generate', methods: ['GET'], middleware: ['role:super-admin'], wheres: ['payment' => Constants::REGEXUUID])]
    public function autoGenerateSalaryPayments()
    {
        $actifsPropositions = Proposition::where('status', 2)->with(['recurringOrder.package', 'employee'])->get();

        $terminatedPropositions = Proposition::where('status', -2)->whereMonth('employee_contrat_end_date', Carbon::now()->month)->with(['recurringOrder', 'employee'])->get();
        foreach ($actifsPropositions as $proposition) {
            $lastDayOfPreviousMonth = Carbon::now()->endOfMonth()->locale('fr_FR')->toDateString();
            if (Carbon::parse($proposition->employee_contrat_started_date)->locale('fr_FR')->toDateString() != $lastDayOfPreviousMonth) {
                $existPayment = Payment::where('employee_id', $proposition->employee_id)->where('recurring_order_id', $proposition->recurring_order_id)->first();
                if (!is_null($existPayment)) {
                    $action = 'after-salary-payment';
                } else {
                    $action = "deployment-confirmation";
                }

                $detailsOfAmountToBePaid = $this->paymentSalaryFunctions->detailsOfAmountToBePaid($action, $proposition);
                $payment =  $this->paymentSalaryFunctions->createPaymentRecord($proposition, $detailsOfAmountToBePaid, $action);
            }
        }
        foreach ($terminatedPropositions as $proposition) {
            $detailsOfAmountToBePaid = $this->paymentSalaryFunctions->detailsOfAmountToBePaid('terminate-contrat', $proposition);
            $payment =  $this->paymentSalaryFunctions->createPaymentRecord($proposition, $detailsOfAmountToBePaid, 'terminate-contrat');
        }
    }



    #[Route('/note', methods: ['POST'], middleware: ['auth:sanctum', 'role:customer'])]
    public function noteEmployee(StoreEmployeeNoteRequest $request)
    {
        $data = $request->validated();


        $notes = [];
        $paymentMonth = null;
        $paymentYear = null;

        foreach ($data['payments'] as $paymentData) {
            $payment = Payment::find($paymentData['payment_id']);

            if (!$payment) {
                return response(['message' => "Le paiement avec l'identifiant {$paymentData['payment_id']} n'existe pas."], 404);
            }

            if (!$payment->status) {
                return response(['message' => "Impossible de noter l'employé {$payment->employee->full_name} car son paiement n'a pas encore été réglé."], 400);
            }

            // Créer une note pour chaque paiement
            $noteData = [
                'payment_id' => $paymentData['payment_id'],
                'note' => $paymentData['note'],
                'comment' => $paymentData['comment'],
            ];

            $note = $this->employeeRepository->noteEmployee($noteData);

            // Ajouter les détails à la liste des notes
            $notes[] = [
                'employee_name' => $payment->employee->full_name,
                'note' => $note->note,
                'comment' => $note->comment,
                'service' => $payment->employee->recurringService->name,
            ];

            // Collecter les informations du paiement pour l'email
            $paymentMonth = $payment->month_salary;
            $paymentYear = $payment->year;
        }

        // Envoi des emails
        $admins = $this->userRepository->userWithRole(['super-admin', 'admin', 'RRC']);
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new EmployeNotedMail(Auth::user(), $admin, $notes, $paymentMonth, $paymentYear));
        }

        return response(['message' => "Notes envoyées avec succès", "data" => $notes], 200);
    }
}
