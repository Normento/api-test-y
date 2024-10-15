<?php

use Core\Modules\Partners\PartnersController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [PartnersController::class, "index"]);
    Route::post('/', [PartnersController::class, "store"]);
    Route::get('/{partner}', [PartnersController::class, "show"])->whereUuid('partner');
    Route::patch('/{partner}', [PartnersController::class, "update"])->whereUuid('partner');
    Route::delete('/{partner}', [PartnersController::class, "destroy"])->whereUuid('partner');
});
