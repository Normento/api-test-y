<?php

namespace Core\Modules\RecurringOrder\Jobs;

use Illuminate\Bus\Queueable;
use Core\Utils\Enums\OperationType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Core\ExternalServices\QosService;
use Core\Modules\User\UserRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Core\Modules\Wallet\WalletRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Core\Modules\RecurringOrder\Models\Payment;
use Core\Modules\Transaction\Models\Transaction;
use Core\Modules\RecurringOrder\Mails\QOSCallback;
use Core\Modules\RecurringOrder\Models\Proposition;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\Transaction\Models\TransactionData;
use Core\Modules\RecurringOrder\Functions\PaymentSalaryFunctions;
use Core\Modules\RecurringOrder\Mails\EmployeeReceiveAdvanceMail;
use Core\Modules\RecurringOrder\Controllers\RecurringOrderController;

class AfterAvanceSalaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public  $proposition, $transref, $transactionsRepository, $paymentSalaryFunctions, $qosService;
    public function __construct(Proposition $proposition, $transref, TransactionRepository $transactionsRepository, QosService $qosService, PaymentSalaryFunctions $paymentSalaryFunctions)
    {
        $this->proposition = $proposition;
        $this->transref = $transref;
        $this->paymentSalaryFunctions = $paymentSalaryFunctions;
        $this->transactionsRepository = $transactionsRepository;

        $this->qosService = $qosService;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(WalletRepository $walletRepository, UserRepository $userRepository)
    {
        $transactionData = TransactionData::find($this->transref);

        $decodeData = json_decode($transactionData->data);
        $successfulTransaction = $this->qosService->verifyTransaction($decodeData->paymentMethod, $this->transref);
        $salaryPayment = Payment::where('employee_id', $this->proposition->employee->id)
                    ->where('recurring_order_id', $this->proposition->recurring_order_id)
                    ->where('latest', true)
                    ->where('status', 0)->with("employee")
                    ->first();

        $amount = $decodeData->amount;
        $transfer_fee = $decodeData->transfer_fee;
        $phoneNumber = $decodeData->phoneNumber;

        if (is_bool($successfulTransaction)) {

            if ($successfulTransaction) {
                app(RecurringOrderController::class)
                    ->afterAdvanceSend($transactionData, $this->proposition);
            }else{
                $transaction = Transaction::make([
                    'status' => 'SUCCESSFUL',
                    'type' => "Avance sur salaire  {$salaryPayment->month_salary}  {$salaryPayment->year} du client {$this->proposition->recurringOrder->user->first_name} {$this->proposition->recurringOrder->user->last_name} à l'employé {$this->proposition->employee->full_name} ",
                    'payment_method' => 'MTN',
                    'author' => $this->proposition->recurringOrder->user->first_name . ' ' . $this->proposition->recurringOrder->user->last_name,
                    'amount' => $amount + $transfer_fee,
                    'phoneNumber' => $phoneNumber
                ]);
                $transaction->transactionData()->associate($transactionData);
                $transaction->save();
            }



            if (!$transactionData->is_update) {
                $transactionData->is_update = true;
                $transactionData->save();

                if (!is_null($salaryPayment)) {

                    if ($salaryPayment->employee_received_salary_advance) {
                        $salaryPayment->salary_advance_amount += $amount;
                    } else {
                        $salaryPayment->employee_received_salary_advance = true;
                        $salaryPayment->salary_advance_amount = $amount;
                    }

                    $salaryPayment->save();

                    $employeeWallet = $salaryPayment->employee->wallet;
                    $trace = "Avance sur salaire par le client {$this->proposition->recurringOrder->user->first_name} {$this->proposition->recurringOrder->user->last_name} pour le  mois de $salaryPayment->month_salary $salaryPayment->year.";

                if ($employeeWallet) {
                    $walletRepository->makeOperation($employeeWallet, OperationType::DEPOSIT,$salaryPayment->salary_advance_amount, $trace);

                }

                $transref_send_employee_advance = "WM-{$this->proposition->employee->id}-" . rand(100, 999) . "-dev";

                    /* $depositResponse = $this->qosService->sendMoney($amount, $this->proposition->employee->mtn_number, "MTN", $transref_send_employee_advance);
                    if ($depositResponse['responsecode'] == "00") {
                        $transactionData = [
                            'transref' => $transref_send_employee_advance,
                            'status' => "SUCCESSFUL",
                            'author' => "{$this->proposition->employee->full_nam}",
                            'type' => "Transfert de l'avance sur salaire du mois de {$salaryPayment->month_salary} {$salaryPayment->year} de la part du client {$this->proposition->recurringOrder->user->full_name}",
                            'payment_method' => 'MTN',
                            'amount' => $amount,
                            "phoneNumber" => $this->proposition->employee->mtn_number
                        ];
                        $this->transactionsRepository->store($transactionData);
                        $admins = $userRepository->userWithRole(['super-admin', 'admin', 'accountant']);

                        foreach ($admins as $user) {
                            Mail::to($user->email)->send(new EmployeeReceiveAdvanceMail($user, $salaryPayment, $this->proposition->employee->mtn_number, $amount, $this->proposition->recurringOrder->user));
                        }
                        if (!is_null($this->proposition->recurringOrder->user->co)) {
                            Mail::to($this->proposition->recurringOrder->user->co->email)->send(new EmployeeReceiveAdvanceMail($this->proposition->recurringOrder->user->co, $salaryPayment, $this->proposition->employee->mtn_number, $amount, $this->proposition->recurringOrder->user));
                        }
                    } else {
                        Mail::to(["contact-lucas@protonmail.com"])->send(new QOSCallback($this->transref, "Transaction MTN Echoué", $this->proposition->employee, "Transfert advance $salaryPayment->month_salary $salaryPayment->year"));
                    }

                    $transaction_data = [
                        'transref' => $this->transref,
                        'status' => 'SUCCESSFUL',
                        'type' => "Avance sur salaire de {$salaryPayment->month_salary}  {$salaryPayment->year} du client  {$this->proposition->recurringOrder->package->user}  à l'employé {$this->proposition->employee->full_name}",
                        'payment_method' => 'MTN',
                        'author' => $this->proposition->recurringOrder->user->first_name . ' ' . $this->proposition->recurringOrder->user->last_name,
                        'amount' => $amount + $transfer_fee,
                        'phoneNumber' => $phoneNumber
                    ];
                    $this->transactionsRepository->store($transaction_data);
                } */
            }
        }
        }
    }
}
