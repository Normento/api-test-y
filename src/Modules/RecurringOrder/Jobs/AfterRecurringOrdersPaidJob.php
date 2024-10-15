<?php

namespace Core\Modules\RecurringOrder\Jobs;

use Core\ExternalServices\QosService;
use Core\Modules\RecurringOrder\Controllers\RecurringOrderController;
use Core\Modules\Transaction\Models\Transaction;
use Core\Modules\Transaction\TransactionRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AfterRecurringOrdersPaidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $transref, $type, $transactionRepository, $qosService;

    public function __construct($transref, $type, TransactionRepository $transactionRepository, QosService $qosService)
    {
        $this->transref = $transref;
        $this->type = $type;
        $this->transactionRepository = $transactionRepository;
        $this->qosService = $qosService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $transactionData = $this->transactionRepository->findTransactionDatasBy($this->transref);
        $decodeData = json_decode($transactionData->data);

        $successfulTransaction = $this->qosService->verifyTransaction($decodeData->paymentMethod, $this->transref);
        if (is_bool($successfulTransaction)) {
            if ($successfulTransaction) {
                app(RecurringOrderController::class)
                    ->afterRecurringOrdersPaid($transactionData, $this->type);
            } else {
                $transaction = Transaction::make([
                    'status' => 'FAILED',
                    'type' => "Recharge de portefeuille",
                    'payment_method' => $decodeData->paymentMethod == 1 ? 'MTN' : 'Carte visa',
                    'author' => $decodeData->author->full_name,
                    'amount' => $decodeData->amount,
                    "phoneNumber" => $decodeData->phoneNumber
                ]);
                $transaction->transactionData()->associate($transactionData);
                $transaction->save();
            }
        }
    }
}
