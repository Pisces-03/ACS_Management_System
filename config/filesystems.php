<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        // * 'serve' is off: Laravel's built-in signed /storage/{path} route would otherwise
        // * replace the route in routes/web.php that serves maintenance photos and videos
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
