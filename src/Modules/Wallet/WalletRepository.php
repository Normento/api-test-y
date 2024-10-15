<?php

namespace Core\Modules\Wallet;

use Core\Modules\Wallet\Models\Wallet;
use Core\Modules\Wallet\Models\WalletLog;
use Core\Utils\BaseRepository;
use Core\Utils\Enums\OperationType;

class WalletRepository extends BaseRepository
{

    private $walletModel, $walletLogModel;

    public function __construct(Wallet $walletModel, WalletLog $walletLogModel)
    {
        parent::__construct($walletModel);
        $this->walletModel = $walletModel;
        $this->walletLogModel = $walletLogModel;
        parent::__construct($walletModel);
    }

    public function storeWalletLog($wallet, $logData)
    {
        $walletLog = $this->walletLogModel->make($logData);
        $walletLog->wallet()->associate($wallet);
        $walletLog->save();
        return $walletLog;
    }

    public function getWalletLogs($walletId)
    {
        return $this->walletLogModel->where('wallet_id', $walletId)
            ->orderBy('created_at', 'desc')->paginate(20);
    }

    public function searchWalletLog($walletId, $request)
    {
        $result = [];

        if ($request->has('operation_type') && !$request->has(['start_date', 'end_date'])) {
            $result = $this->walletLogModel->where('wallet_id', $walletId)->where('operation_type', $request->input('operation_type'))->orderBy('created_at', 'desc')->get();
        }

        if (!$request->has('operation_type') && $request->has(['start_date', 'end_date'])) {
            $result = $this->walletLogModel->where('wallet_id', $walletId)
                ->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])
                ->orderBy('created_at', 'desc')->get();
        }
        if ($request->has(['start_date', 'end_date', 'operation_type'])) {
            $result = $this->walletLogModel->where('wallet_id', $walletId)
                ->where('operation_type', $request->input('operation_type'))
                ->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')])
                ->orderBy('created_at', 'desc')->get();
        }
        return $result;
    }

    public function makeOperation(Wallet $wallet, OperationType $type, int $amount, string $trace)
    {
        switch ($type) {
            case OperationType::WITHDRAW:
                if ($amount > $wallet->balance) {
                    return -1;
                } else {
                    $oldWalletBalance = $wallet->balance;
                    $this->update($wallet, ['balance' => $oldWalletBalance - $amount]);
                    $walletLogData = [
                        "balance_before_operation" => $oldWalletBalance,
                        "balance_after_operation" => $wallet->balance,
                        "amount" => $amount,
                        "operation_type" => "withdraw",
                        'trace' => $trace,
                    ];
                    $this->storeWalletLog($wallet, $walletLogData);
                }
                break;
            case OperationType::DEPOSIT:
                $oldWalletBalance = $wallet->balance;
                $this->update($wallet, ['balance' => $oldWalletBalance + $amount]);
                $walletLogData = [
                    "balance_before_operation" => $oldWalletBalance,
                    "balance_after_operation" => $wallet->balance,
                    "amount" => $amount,
                    "operation_type" => "deposit",
                    'trace' => $trace,
                ];
                $this->storeWalletLog($wallet, $walletLogData);
        }

    }
}
