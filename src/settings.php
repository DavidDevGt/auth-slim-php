<?php

return [
    'settings' => [
        'displayErrorDetails' => true, // muestra error detallado en pantalla
        'addContentLengthHeader' => false,
        'db' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'database_name',
            'username' => 'username',
            'password' => 'pass',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
    ],
];