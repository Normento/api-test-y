<?php

use Core\Modules\Trash\TrashController;
use Illuminate\Support\Facades\Route;

Route::post('', [TrashController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {

});
