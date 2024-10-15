<?php

namespace Core\Modules\PunctualOrder\Jobs;

use App\Events\OrderEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Core\ExternalServices\QosService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Core\Modules\Transaction\Models\Transaction;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\PunctualOrder\Controllers\PunctualOrderController;

class AfterPunctualOrderPaid implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public $transref, $transactionRepository, $qosService;

    public function __construct($transref, TransactionRepository $transactionRepository, QosService $qosService)
    {
        $this->transref = $transref;
        $this->transactionRepository = $transactionRepository;
        $this->qosService = $qosService;
    }

    public function handle(): void
    {
        $transaction_data = $this->transactionRepository->findTransactionDatasBy($this->transref);
        $orderData = json_decode($transaction_data->data, true);
        $successfulTransaction = $this->qosService->verifyTransaction($orderData['requestData']['payment_method'], $this->transref);
        $phoneNumber = $orderData['requestData']['phoneNumber'];

        if (is_bool($successfulTransaction)) {
            if ($successfulTransaction) {
                app(PunctualOrderController::class)
                ->afterPunctualOrdersPaid($transaction_data);

            } else {
                $transaction = Transaction::make([
                    'status' => 'FAILED',
                    'type' => "Paiement de 20% du budget d'une commande ponctuelle",
                    'payment_method' => 'MTN',
                    'author' => $orderData['author']['last_name'] . " " . $orderData['author']['first_name'],
                    'amount' => $orderData['amount'],
                    "phoneNumber" => $phoneNumber
                ]);
                $transaction->transactionData()->associate($transaction_data);
                $transaction->save();
                $this->transactionRepository->updateTransactionData(['is_update' => true], $transaction_data);
            }
        }
    }
}
