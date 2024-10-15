<?php

use Core\Modules\RecurringService\RecurringServiceController;
use Core\Utils\Constants;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Command API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Command API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api/command" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:sanctum')->group(function () {
    Route::get('', [RecurringServiceController::class, 'index']);

    Route::post('', [RecurringServiceController::class, 'create']);
    Route::get('/{service}', [RecurringServiceController::class, 'show'])->where('service', Constants::REGEXUUID);
    Route::delete('/{service}', [RecurringServiceController::class, 'delete'])->where('service', Constants::REGEXUUID);
    Route::post('/{service}', [RecurringServiceController::class, 'update'])->where('service', Constants::REGEXUUID);

});
