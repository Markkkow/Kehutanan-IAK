<?php

return [

    'paths' => [
        resource_path('views'),
    ],

    // Di Vercel, storage dipindah ke /tmp oleh bootstrap/app.php, jadi storage_path() sudah benar.
    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views')) ?: storage_path('framework/views')),

];
