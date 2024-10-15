<?php

use Core\Modules\Transaction\TransactionController;
use Core\Utils\Constants;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Package API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register utils API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api/transactions" middleware group. Enjoy building your API!
|
*/


Route::get('/verify/{transref}', [TransactionController::class, 'verifyQosTransaction'])->where('transref', Constants::REGEXINT);




Route::middleware('auth:sanctum')->group(function () {
});
