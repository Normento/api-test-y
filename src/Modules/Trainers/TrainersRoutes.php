<?php

use Core\Modules\Trainers\TrainersController;
use Core\Utils\Constants;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [TrainersController::class, "index"]);
    Route::post('/', [TrainersController::class, "store"]);
    Route::get('/{trainer}', [TrainersController::class, "show"])->where('trainer', Constants::REGEXUUID);
    Route::post('/{trainer}', [TrainersController::class, "update"])->where('trainer', Constants::REGEXUUID);
    Route::patch('/{trainer}', [TrainersController::class, "validateTrainer"])->where('trainer', Constants::REGEXUUID);
    Route::delete('/{trainer}', [TrainersController::class, "destroy"])->where('trainer', Constants::REGEXUUID);

    Route::delete('/{trainer}/services/{service}', [TrainersController::class, "removeService"])->where('trainer', Constants::REGEXUUID);
    Route::post('/{trainer}/services', [TrainersController::class, "addServices"])->where('trainer', Constants::REGEXUUID);
    Route::patch('/{trainer}/services/{service}', [TrainersController::class, "updateService"])->where('trainer', Constants::REGEXUUID);

    Route::get('/training-records', [TrainersController::class, "trainingRecords"]);
    Route::post('/training-records/{trainer}', [TrainersController::class, "recordTraining"])->where('trainer', Constants::REGEXUUID);;
    Route::patch('/training-records/{trainingRegistry}', [TrainersController::class, "validateTrainingRecord"])->where('trainingRegistry', Constants::REGEXUUID);

});
