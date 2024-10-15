<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    |
    */

    'paths' => [
        realpath(base_path("src/Utils/Views")),

        realpath(base_path('src/Modules/User/Views')),
        realpath(base_path('src/Modules/Professional/Views')),
        realpath(base_path('src/Modules/Employee/Views')),
        realpath(base_path('src/Modules/Wallet/Views')),
        realpath(base_path("src/Modules/apiDoc/Views")),
        realpath(base_path("src/Modules/Email/Views")),
        realpath(base_path("src/Modules/Auth/Views")),
        realpath(base_path("src/Modules/PunctualOrder/Views")),
        realpath(base_path("src/Modules/RecurringOrder/Views")),
        realpath(base_path("src/Modules/Blog/Views")),
        realpath(base_path("src/Modules/Notification/Views")),
        realpath(base_path("src/Modules/Views")),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
