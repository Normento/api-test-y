<?php

use Core\Modules\User\Websockets\Handlers\UserUpdatedHandler;
use Illuminate\Support\Facades\Route;
use Core\Modules\Auth\AuthController;
use App\Events\ActivateEvent;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/docs');
})->middleware('api.doc.auth');


Route::get('/docs', function () {
    return view('index');
})->middleware('api.doc.auth');
