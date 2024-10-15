<?php

use Core\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Core\Modules\PunctualOrder\Models\PunctualOrder;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/



Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('order', function ($user) {
    return Auth::check();
});


Broadcast::channel('offer-admin', function ($user) {
    return Auth::check();
});

Broadcast::channel('activate', function ($user) {

    return Auth::check();
});

Broadcast::channel('received', function ($data) {
    return true;
});





Broadcast::channel('Offer', function () {

    return true;
});


Broadcast::channel('support.{userId}', function ($user, $userId) {
    if ($user->hasRole('super-admin')) {
        $rrc = User::role('RRC')->first();

        return (string) $rrc->id === (string) $userId;

    }
    return (string) $user->id === (string) $userId;
});


Broadcast::channel('chat', function () {
    return Auth::check();
});

Broadcast::channel('offers.{userId}', function ($user, $userId) {

   return (string) $user->id === (string) $userId;

}, ['guards' => ['api']]);


Broadcast::channel('staff-package.{userId}', function ($user, $userId) {

    return (string) $user->id === (string) $userId;

 });


 Broadcast::channel('employe.{userId}', function ($user, $userId) {

    return (string) $user->id === (string) $userId;

 });
