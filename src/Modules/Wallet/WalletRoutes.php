<?php

use Core\Modules\Wallet\WalletController;
use Core\Utils\Constants;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/{wallet}/after/deposit', [WalletController::class, 'afterDepositInWallet'])->whereUuid('wallet');
    Route::get('/{wallet}', [WalletController::class, 'show'])->whereUuid('wallet');
    Route::match(['get', 'post'], '/{wallet}/logs', [WalletController::class, 'walletLogs'])->whereUuid('wallet');
    Route::post('/{wallet}/make/operation', [WalletController::class, 'makeOperation'])->whereUuid('wallet');
});

