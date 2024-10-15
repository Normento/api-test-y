<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Migration Paths
    |--------------------------------------------------------------------------
    |
    | 01. By default all sub directories will be registered inside the migrations folders
    | - migrations
    | -- dir1
    | -- dir2
    | ---- dir2.1
    |
    | 02. You can register additional directories outside the migrations folder
    */

    'paths' => [
        database_path('migrations'),
        base_path('src/Modules/Access/Migrations'),
        base_path('src/Modules/Auth/Migrations'),
        base_path('src/Modules/Blog/Migrations'),
        base_path('src/Modules/Employee/Migrations'),
        base_path('src/Modules/FocalPoints/Migrations'),
        base_path('src/Modules/Notification/Migrations'),
        base_path('src/Modules/Partners/Migrations'),
        base_path('src/Modules/Professional/Migrations'),
        base_path('src/Modules/PunctualOrder/Migrations'),
        base_path('src/Modules/RecurringOrder/Migrations'),
        base_path('src/Modules/PunctualService/Migrations'),
        base_path('src/Modules/PunctualService/Migrations'),
        base_path('src/Modules/RecurringService/Migrations'),
        base_path('src/Modules/Trainers/Migrations'),
        base_path('src/Modules/Transaction/Migrations'),
        base_path('src/Modules/Wallet/Migrations'),
        base_path('src/Modules/User/Migrations'),
        base_path('src/Modules/Prospect/Migrations'),
        base_path('src/Modules/Chat/Migrations'),

        base_path('src/Modules/Pricing/Migrations'),
    ],
];
