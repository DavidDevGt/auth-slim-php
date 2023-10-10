<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Crear contenedor de dependencias
$container = new \DI\Container(); // Aquí estoy suponiendo que usas PHP-DI, pero ajusta según tu implementación
AppFactory::setContainer($container);
$app = AppFactory::create();

// Cargar configuraciones
$settings = require __DIR__ . '/../src/settings.php';
$settings($app, $container); // Pasar el contenedor aquí también si es necesario

// Agregar Middleware de error para manejar los errores de la aplicación
$app->addErrorMiddleware(true, true, true);

// Ruta de prueba para verificar que la aplicación está funcionando
$app->get('/test', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("¡Hola, PHP está funcionando!");
    return $response;
});

// Agregar rutas
$routes = require __DIR__ . '/../src/routes/auth.php';
$routes($app, $container); 

// Ejecutar la aplicación
$app->run();
