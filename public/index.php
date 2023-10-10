<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

// Crear la aplicación
$app = AppFactory::create();

// Agregar rutas
(require __DIR__ . '/../routes/auth.php')($app);

// Ejecutar la aplicación
$app->run();
