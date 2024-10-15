<?php

use App\Events\Hello;
use Core\Utils\Controller;
use Illuminate\Http\Request;
use App\Events\ActivateEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Core\ExternalServices\QosService;
use Core\Modules\Auth\AuthController;
use Core\Modules\Blog\PostController;
use Core\Modules\Chat\ChatController;
use Core\Modules\User\UserController;
use Core\Modules\User\UserRepository;
use Illuminate\Support\Facades\Route;
use Core\Modules\Email\EmailController;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Core\Modules\Chat\MessageController;
use Core\Modules\Access\AccessController;
use Illuminate\Support\Facades\Broadcast;
use Core\Modules\Pricing\PricingController;
use App\Http\Controllers\JobOfferController;
use Core\Modules\Chat\ConversationController;
use Core\Modules\Employee\EmployeeController;
use Core\Modules\Prospect\ProspectController;
use App\Http\Controllers\ApplyForJobController;
use Core\Modules\Transaction\TransactionController;
use Core\Modules\Professional\ProfessionalController;
use Core\Modules\PunctualService\Models\PunctualService;
use Core\Modules\PunctualOrder\Controllers\TagController;

use Core\Modules\RecurringService\Models\RecurringService;
use Core\Modules\PunctualService\PunctualServiceRepository;
use Core\Modules\PunctualOrder\Controllers\OffersController;
use Core\Modules\RecurringOrder\Controllers\SuivisController;
use Core\Modules\RecurringService\RecurringServiceRepository;
use Core\Modules\RecurringOrder\Controllers\PaymentsController;
use Core\Modules\PunctualOrder\Controllers\PunctualOrderController;
use Core\Modules\RecurringOrder\Controllers\RecurringOrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::annotation(AccessController::class);
Route::annotation(AuthController::class);
Route::annotation(PostController::class);
Route::annotation(UserController::class);
Route::annotation(EmployeeController::class);
Route::annotation(PunctualOrderController::class);
Route::annotation(RecurringOrderController::class);
Route::annotation(OffersController::class);
Route::annotation(ProfessionalController::class);
Route::annotation(ProspectController::class);
Route::annotation(PricingController::class);
Route::annotation(ChatController::class);
Route::annotation(EmailController::class);
Route::annotation(TagController::class);
Route::annotation(TransactionController::class);
Route::annotation(PaymentsController::class);
Route::annotation(SuivisController::class);

Route::get('file/{key}/url', function ($key) {
    if (Storage::exists($key)) {
        $url = Storage::temporaryUrl($key, now()->addDay(7));
        return response(['fileUrl' => $url], 200);
    }
    return response(["message" => "key not found"], 404);
})->where('key', '(.*)');


Route::post('account-info', function (Request $request) {
    $response = (new QosService())->getAccountInfo(
        $request->phone_number,
        $request->mobile_network,
    );
    return response(["data" => $response]);
});

Route::post('verify-transaction', function (Request $request) {
    $response = (new QosService())->verifyTransaction(
        $request->payment_method,
        $request->trans_ref,
    );
    return response(["data" => $response]);
});


Route::post('support', function (Request $request) {
    $users = app(UserRepository::class)->userWithRole(['super-admin', 'admin']);
    foreach ($users as $user) {
        //Mail::to($user)->send(new SendContact($new_contact, $recipient));
    }
    return response(["message" => "Nous vous remercions pour votre intérêt. notre service clientèle prendra contact avec vous dans les plus brefs délais"]);
});


Route::get('services', function (Request $request) {
    $request->merge(["is_archived" => false]);
    $response["message"] = "Fussion des services ponctuelles et récurrentes";
    $punctual = app(PunctualServiceRepository::class)->filterServices($request);
    $recurring = app(RecurringServiceRepository::class)->filterServices($request);
    $services = $recurring->merge($punctual);
    $services->transform(function ($service) {
        $service->image = app(Controller::class)->s3FileUrl($service->image);
        return $service;
    });
    $response['data'] = $services;
    return response($response, 200);
});


Route::get('services/most-requested', function (Request $request) {
    $recurring = DB::table('recurring_orders')
        ->join('recurring_services', 'recurring_services.id', '=', 'recurring_orders.recurring_service_id')
        ->select('recurring_services.name', DB::raw('COUNT(*) as total_request'), "recurring_services.id", "recurring_services.image", "recurring_services.placement_fee")
        ->where("recurring_services.is_archived", false)
        ->groupBy('recurring_services.name', 'recurring_services.id', "recurring_services.image", "recurring_services.placement_fee")
        ->orderBy('total_request', 'desc')
        ->limit(5)
        ->get();
    $punctual = DB::table('punctual_orders')
        ->join('punctual_services', 'punctual_services.id', '=', 'punctual_orders.service_id')
        ->select('punctual_services.name', DB::raw('COUNT(*) as total_request'), "punctual_services.id", "punctual_services.image", "punctual_services.fixed_price")
        ->where("punctual_services.is_archived", false)
        ->groupBy('punctual_services.name', 'punctual_services.id', "punctual_services.image")
        ->orderBy('total_request', 'desc')
        ->limit(5)
        ->get();
    $services = $recurring->merge($punctual);
    $services->transform(function ($service) {
        $service->image = app(Controller::class)->s3FileUrl($service->image);
        return $service;
    });
    $response['data'] = $services;
    return response($response, 200);
});

Route::get('services/highlighted', function (Request $request) {
    $recurring = RecurringService::where('is_highlighted', true)->limit(4)
        ->get();
    $punctual = PunctualService::where('is_highlighted', true)->limit(4)
        ->get();
    $services = $recurring->merge($punctual);
    $services->transform(function ($service) {
        $service->image = app(Controller::class)->s3FileUrl($service->image);
        return $service;
    });
    $response['data'] = $services;
    return response($response, 200);
});


Route::get('activity-log', function (Request $request) {
    return response(["data" => Activity::all()]);
});

// Route::post('/login', [AuthController::class, 'login']);

Route::get('/socket', function () {
    ActivateEvent::dispatch("HELLO");
});


//Authorizing Private Broadcast Channels With Sanctum RESTful API
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::middleware('auth:sanctum')->get('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});;

// Gestion des routes pour les offres d'emploie

Route::middleware('auth:sanctum')->post('/create/job',[JobOfferController::class,'create']);
Route::middleware('auth:sanctum')->post('/edit/job',[JobOfferController::class,'edit']);
Route::post('/show/job',[JobOfferController::class,'show']);
Route::middleware('auth:sanctum')->post('/delete/job',[JobOfferController::class,'destroy']);
Route::get('/jobs',[JobOfferController::class,'index']);

Route::post('/apply', [ApplyForJobController::class, 'store']);
Route::get('/allusers/apply/for/job/{id}', [ApplyForJobController::class, 'allUsersApply']);
