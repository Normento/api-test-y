<?php

use Core\Modules\FocalPoints\FocalPointsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [FocalPointsController::class, "index"]);
    Route::post('/', [FocalPointsController::class, "store"]);
    Route::get('/{focalPoint}', [FocalPointsController::class, "show"])->whereUuid('focalPoint');
    Route::patch('/{focalPoint}', [FocalPointsController::class, "update"])->whereUuid('focalPoint');
    Route::delete('/{focalPoint}', [FocalPointsController::class, "destroy"])->whereUuid('focalPoint');
});
