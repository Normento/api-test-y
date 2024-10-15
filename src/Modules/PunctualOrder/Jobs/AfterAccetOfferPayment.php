<?php

namespace Core\Modules\PunctualOrder\Jobs;

use AWS\CRT\HTTP\Request;
use Core\Modules\PunctualOrder\Repositories\OffersRepository;
use Illuminate\Bus\Queueable;
use PhpParser\Node\Stmt\Return_;
use Core\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Core\ExternalServices\QosService;
use Core\Modules\User\UserRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use BeyondCode\LaravelWebSockets\Apps\App;
use Core\Modules\PunctualOrder\Controllers\OffersController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Core\Modules\PunctualOrder\Models\Offer;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Core\Modules\Transaction\Models\Transaction;
use Illuminate\Support\Facades\App as FacadesApp;
use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\PunctualOrder\Models\PunctualOrder;
use Core\Modules\PunctualOrder\Mails\NewPunctualOrder;
use Core\Modules\PunctualOrder\Requests\StoreOrderRequest;
use Core\Modules\PunctualOrder\Events\AfterSucesPaiementEvent;
use Core\Modules\PunctualOrder\Controllers\PunctualOrderController;
use Core\Modules\PunctualOrder\Repositories\PunctualOrderRepository;

class AfterAccetOfferPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    public  $transref, $transactionRepository, $qosService, $punctualOrderRepository, $offersRepository;
    public function __construct($transref, TransactionRepository $transactionRepository, QosService $qosService, PunctualOrderRepository $punctualOrderRepository, OffersRepository $offersRepository)
    {
        $this->transref = $transref;
        $this->transactionRepository = $transactionRepository;
        $this->qosService = $qosService;
        $this->punctualOrderRepository = $punctualOrderRepository;
        $this->offersRepository = $offersRepository;
    }

    public function handle()
    {
        $transaction_data = $this->transactionRepository->findTransactionDatasBy($this->transref);
        $paymentData = json_decode($transaction_data->data);
        $successfulTransaction = $this->qosService->verifyTransaction($paymentData->requestData->payment_method, $this->transref);
        $phoneNumber = $paymentData->requestData->phoneNumber;
        $amount = round($paymentData->requestData->amount);
        if (is_bool($successfulTransaction)) {
            if ($successfulTransaction) {
                app(OffersController::class)
                ->afterOffersPaid($transaction_data);

                $transaction = Transaction::make([
                    'status' => 'SUCCESSFUL',
                    'type' => "Paiement après acceptation d'une offre.",
                    'payment_method' => 'MTN',
                    'author' => $paymentData->author,
                    'amount' => $amount,
                    "phoneNumber" => $phoneNumber
                ]);
                $transaction->transactionData()->associate($transaction_data);
                $transaction->save();


                // foreach (User::superAdminUsers() as $admin) {
                //     Mail::to(Auth::user()->email)->send(new PaidAfterAccetOfferEmail($paymentData->author, $amount, $admin));
                // }

            } else {
                $transaction = Transaction::make([
                    'status' => 'FAILED',
                    'type' => "Paiement après acceptation d'une offre.",
                    'payment_method' => 'MTN',
                    'author' => $paymentData->author,
                    'amount' => $amount,
                    "phoneNumber" => $phoneNumber
                ]);
                $transaction->transactionData()->associate($transaction_data);
                $transaction->save();
            }
        }
    }

}
