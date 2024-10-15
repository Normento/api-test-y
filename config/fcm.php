<?php

return [
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', config_path('firebase_credentials.json')),
    ],
];
