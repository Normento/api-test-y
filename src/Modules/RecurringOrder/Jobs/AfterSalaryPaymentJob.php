<?php

namespace Core\Modules\RecurringOrder\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Core\ExternalServices\QosService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Core\Modules\RecurringOrder\Models\Payment;
use Core\Modules\Transaction\Models\Transaction;
use Core\Modules\RecurringOrder\Mails\FactureMail;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\RecurringOrder\Controllers\PaymentsController;
use Core\Modules\RecurringOrder\Repositories\PaymentsRepository;
use Core\Modules\RecurringOrder\Functions\PaymentSalaryFunctions;

class AfterSalaryPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public  $payment, $transref, $paymentSalaryFunctions, $transactionsRepository, $paymentsRepository, $qosService, $payment_method;
    public function __construct(Array $payment, $transref, PaymentSalaryFunctions $paymentSalaryFunctions, PaymentsRepository $paymentsRepository, TransactionRepository $transactionsRepository, QosService $qosService, $payment_method)
    {
        $this->payment = $payment;
        $this->transref = $transref;
        $this->paymentSalaryFunctions = $paymentSalaryFunctions;
        $this->paymentsRepository = $paymentsRepository;
        $this->transactionsRepository = $transactionsRepository;
        $this->qosService = $qosService;
        $this->payment_method = $payment_method;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $transactionData = $this->transactionsRepository->findTransactionDatasBy($this->transref);
        $decodeData = json_decode($transactionData->data);

        $paymentsCount = count($this->payment);

        $paymentsIds = $this->payment;

        //Log::info("PAYMENT ID FROM JOB".json_encode($paymentsIds));


        for ($i = 0; $i < $paymentsCount; $i++) {

            $paymentId = $paymentsIds[$i];
            $payment = Payment::find($paymentId);

            //Log::info("PAYMENT FROM JOB".json_encode($payment));

                $payment = $payment->load('recurringOrder.user', 'employee');
                $package = $payment->recurringOrder;
                $amount = $payment->employee_salary_amount;
                $phoneNumber = $decodeData->phoneNumber;
                $month = $decodeData->month_salary;
                $year = $decodeData->year;
                $cnss = $payment->cnss;
                $transfer_fee = $decodeData->transfer_fee;
                $total_to_paid =  $payment->employee_salary_amount;




            //MTN
            if ($this->payment_method == 1) {
                $successfulTransaction = $this->qosService->verifyTransaction($this->payment_method, $this->transref);
                if (is_bool($successfulTransaction)) {
                    if ($successfulTransaction) {
                        app(PaymentsController::class)
                            ->afterSalaryPayment($transactionData);
                    }else{
                        $transaction = Transaction::make([
                            'transref' => $this->transref,
                            'status' => "FAILED",
                            'author' => "{$package->user->first_name}" . "         {$package->user->last_name}",
                            'type' => "Paiement des frais de prestation du mois de $month $year   par le client {$package->user->first_name}  {$package->user->last_name}.",
                            'payment_method' => 'MTN',
                            'amount' => $amount,
                            "phoneNumber" => $phoneNumber
                        ]);
                    }

                    if (!$transactionData->is_update) {

                        $this->paymentSalaryFunctions->afterSalaryPayment($payment->id, $this->payment_method);

                        $transaction = Transaction::make([
                            //'transref' => $this->transref,
                            'status' => "SUCCESSFUL",
                            'author' => "{$package->user->first_name}" . "         {$package->user->last_name}",
                            'type' => "Paiement des frais de prestation du mois de $month $year   par le client {$package->user->first_name}  {$package->user->last_name}.",
                            'payment_method' => 'MTN',
                            'amount' => $amount,
                            "phoneNumber" => $phoneNumber
                        ]);
                        $transaction->transactionData()->associate($transactionData);
                        $transaction->save();

                        //Log::info("TRANSACTION".json_encode($transaction));




                        $factureTab = ['amount' => $total_to_paid, "transfer_fee" => $transfer_fee, "mobile_money" => "MTN Mobile Money", "subject" => "Paiement des frais de prestation du mois de $month $year de l'employé {$payment->employee->full_name}", 'transref' => $this->transref];
                        Mail::to($package->user->email)->send(new FactureMail($package->user, $factureTab));
                        $transactionData->is_update = true;
                        $transactionData->save();
                    }
                }
            }
        }





        // CARTE BANCAIRE
        /* elseif ($this->payment_method == 4) {
            $responsePayment = $this->qosService->getCardTransactionStatus($this->transref);
            if ($responsePayment['data']['status'] === 'SUCCESS') {
                $data = explode("-", $this->transref);
                $utils = Utils::find($data[2]);
                $decode = json_decode($utils->data);
                $amount = $decode->amount;
                $phoneNumber = $decode->phoneNumber;
                $month_salary = $decode->month_salary;
                $year = $decode->year;
                $cnss = $decode->cnss;
                $transfer_fee = $decode->transfer_fee;
                $total_to_paid =  $decode->total_to_paid;
                if (!$utils->is_update) {
                    $this->paymentSalaryFunctions->afterSalaryPayment($payment->id, $this->payment_method);

                    $transactionData = [
                        'transref' => $this->transref,
                        'status' => "SUCCESSFUL",
                        'author' => "{$package->user->first_name}" . "         {$package->user->last_name}", 'author' => "{$package->user->first_name}" . "         {$package->user->last_name}",
                        'type' => "Paiement des frais de prestation du mois de {$this->month} {$this->year} de l'employé {$payment->employee->full_name} par le client {$package->user->first_name}  {$package->user->last_name}.",
                        'payment_method' => 'CARD',
                        'amount' => $amount,
                        "phoneNumber" => $phoneNumber
                    ];
                    $factureTab = ['amount' => $total_to_paid, "transfer_fee" => $transfer_fee, "mobile_money" => "CARTE BANCAIRE", "subject" => "Paiement des frais de prestation du mois de {$this->month} {$this->year} de l'employé {$payment->employee->full_name}", 'transref' => $this->transref];
                    Mail::to($package->user->email)->send(new FactureMail($package->user, $factureTab));
                    $this->transactionsRepository->store($transactionData);

                    $utils->is_update = true;
                    $utils->save();
                }
            }
        } */
    }
}
