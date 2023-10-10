<?php

use Slim\Factory\AppFactory;
use DI\Container;

return function ($app, $container) {
    // Configuración de base de datos
    $container = new Container();
    AppFactory::setContainer($container);

    // Configuraciones
    $container->set('settings', function () {
        return [
            'displayErrorDetails' => true,
            'addContentLengthHeader' => false,
            'db' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'auth-users',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_general_ci',
                'prefix' => '',
            ],
        ];
    });

    // Añadimos PDO al contenedor para que pueda ser inyectado.
    $container->set('pdo', function () use ($container) {
        // Accedemos a settings usando $container en vez de $this
        $settings = $container->get('settings')['db'];
        $pdo = new PDO(
            "mysql:host=" . $settings['host'] . ";dbname=" . $settings['database'],
            $settings['username'],
            $settings['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    });

    $app->setBasePath("/auth-slim-php/public");
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware(true, true, true);
};
