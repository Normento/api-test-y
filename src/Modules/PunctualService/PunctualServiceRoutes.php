<?php

use Core\Modules\PunctualService\PunctualServiceController;
use Core\Utils\Constants;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(
    function () {
        Route::get('', [PunctualServiceController::class, 'index']);
        Route::delete('/{service}', [PunctualServiceController::class, 'delete'])->where('service', Constants::REGEXUUID);
        Route::post('/{service}', [PunctualServiceController::class, 'update'])->where('service', Constants::REGEXUUID);
        Route::post('', [PunctualServiceController::class, 'store']);
        Route::get('/{service}', [PunctualServiceController::class, 'show'])->where('service', Constants::REGEXUUID);

    }
);
