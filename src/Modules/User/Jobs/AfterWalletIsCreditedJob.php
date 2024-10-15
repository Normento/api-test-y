<?php

namespace Core\Modules\User\Jobs;

use Core\ExternalServices\QosService;
use Core\Modules\PunctualOrder\Controllers\PunctualOrderController;
use Core\Modules\Transaction\Models\Transaction;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\User\Mails\WalletIsCredited;
use Core\Modules\User\Models\User;
use Core\Modules\User\UserController;
use Core\Modules\User\UserRepository;
use Core\Modules\Wallet\WalletRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class AfterWalletIsCreditedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
                $this->transactionRepository->updateTransactionData(['is_update' => true], $transactionData);
                app(UserController::class)
                    ->afterWalletIsCredited($transactionData);
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
