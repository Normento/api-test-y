<?php

namespace Core\Modules\Wallet;

use Core\Modules\Transaction\TransactionRepository;
use Core\Modules\User\Mails\WalletIsCredited;
use Core\Modules\User\Models\User;
use Core\Modules\Wallet\Models\Wallet;
use Core\Modules\Wallet\Requests\MakeOperationRequest;
use Core\Modules\Wallet\Requests\WalletLogsRequest;
use Core\Utils\Controller;
use Core\Utils\Enums\OperationType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

class WalletController extends Controller
{
    protected  WalletRepository $walletRepository;
    protected  TransactionRepository $transactionRepository;

    public function __construct(WalletRepository $walletRepository, TransactionRepository $transactionRepository)
    {
        $this->walletRepository = $walletRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function walletLogs(Wallet $wallet, WalletLogsRequest $request)
    {
        if ($request->query->count() == 0 || $request->has('page')) {
            $data = $wallet->logs()->paginate(20);
        } else {
            $data = $this->walletRepository->searchWalletLog($wallet->id, $request);
        }
        return response(['message' => "Wallet récupéré avec succès", "data" => $data], 200);

    }

    public function makeOperation(Wallet $wallet, MakeOperationRequest $request): Response
    {

        $this->walletRepository->makeOperation($wallet, $request->operation_type == 0 ?
            OperationType::WITHDRAW :
            OperationType::DEPOSIT,
            $request->amount,
            $request->trace);
        return response(['message' => "Opération effectué avec succès", "data" => $wallet], 200);

    }

    /**
     * Display the specified resource.
     */
    public function show(Wallet $wallet)
    {
        $response['message'] = "Détail d'un portefeuille";
        $response['data'] = $wallet;
        return response($response, 200);
    }

    public function afterDepositInWallet(Request $request, Wallet $wallet, $saveTransaction = true)
    {
        if (!is_null($request->query("transref"))) {
            $transref = $request->query("transref");
        } else {
            $transref = $request->transref;
        }

        $transaction_data = $this->transactionRepository->findTransactionDatasBy($transref);
        $decode = json_decode($transaction_data->data);
        $amount = $decode->amount;
        $phoneNumber = $decode->phoneNumber;
        $author = $decode->author;
        if (!$transaction_data->is_update) {
            $this->transactionRepository->updateTransactionData(['is_update' => true], $transaction_data);
            $wallet = $this->walletRepository->findBy('id', $wallet->id);
            if (!is_null($wallet)) {
                $oldWalletBalance = $wallet->balance;
                //update wallet
                $this->walletRepository->update($wallet, ['balance' => $oldWalletBalance + $amount]);

                $walletLogData = [
                    "balance_before_operation" => $oldWalletBalance,
                    "balance_after_operation" => $wallet->balance,
                    "amount" => $amount,
                    "operation_type" => 'deposit',
                    "trace" => "Approvisionnement du portefeuille"
                ];
                $this->walletRepository->storeWalletLog($wallet, $walletLogData);

                foreach (User::role(['super-admin', 'admin'])->get() as $admin) {
                    Mail::to($admin->email)->send(new WalletIsCredited($author, $amount, $wallet->balance, $admin));
                }

                if ($saveTransaction) {
                    $transaction_datas = [
                        'transref' => $transaction_data,
                        'status' => 'SUCCESSFUL',
                        'type' => "Recharge du portefeuille YLOMI",
                        'payment_method' => 'MTN',
                        'author' => $author,
                        'amount' => $amount,
                        'phoneNumber' => $phoneNumber

                    ];
                    $this->transactionRepository->store($transaction_datas);
                }
                $response["message"] = "Recharge effectuée avec succès";
                $response["data"] = $wallet->load('logs');
                return response($response, 201);
            }
        }
    }
}
