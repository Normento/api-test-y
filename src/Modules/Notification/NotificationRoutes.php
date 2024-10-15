
<?php

use Core\Modules\Notification\NotificationController;
use Core\Utils\Constants;
use Illuminate\Support\Facades\Route;



Route::get('/{notification}', [NotificationController::class, 'show'])->where('notification', Constants::REGEXUUID)->middleware('optional-auth');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/', [NotificationController::class, 'store']);
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/usernotifications/get',[NotificationController::class,'getUserNotifications']);
    Route::post('/{notification}', [NotificationController::class, 'update'])->where('notification', Constants::REGEXUUID);
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->where('notification', Constants::REGEXUUID);
    Route::get("/{notification}/send", [NotificationController::class, "sendNotification"])->where('notification', Constants::REGEXUUID);
});
