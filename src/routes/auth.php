<?php

use Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use PDO;

return function (App $app) {

    $container = $app->getContainer();

    $dbSettings = $container->get('settings')['db'];
    $pdo = new PDO("mysql:host=" . $dbSettings['host'] . ";dbname=" . $dbSettings['database'], $dbSettings['username'], $dbSettings['password']);

    $app->group('', function (App $app) use ($pdo) {
        // Ruta para el registro de usuarios
        $app->post('/register', function (Request $request, Response $response )use ($pdo) {
            // Recibir los datos del usuario (como mínimo, email y contraseña)
            $data = $request->getParsedBody();
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
                return $response->withStatus(400)->withJson([
                    'message' => 'Datos inválidos'
                ]);
            }

            // Hasheamos la contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->execute();

            return $response->withJson([
                'message' => 'Usuario registrado exitosamente'
            ]);
        });
    
        // Ruta para iniciar sesión
        $app->post('/login', function (Request $request, Response $response) use ($pdo){
            // Datos de inicio de sesion
            $data = $request->getParsedBody();
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
                return $response->withStatus(400)->withJson([
                    'message' => 'Datos inválidos'
                ]);
            }

            // Revisar las credenciales en la base de datos
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                return $response->withStatus(401)->withJson([
                    'message' => 'Credenciales inválidas'
                ]);
            }
            // Aquí se debe crear un token o una sesión si las credenciales son válidas y retornar una respuesta
            return $response->withJson([
                'message' => 'Inicio de sesión exitoso'
            ]);
        });
    })->add(new AuthMiddleware()); // Aquí se aplica el middleware de autenticación a las rutas del grupo
    
    // Ruta para restablecer la contraseña
    $app->post('/password-reset', function (Request $request, Response $response) use ($pdo) {
        // Recibir el correo electrónico del usuario
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';

        // Validar el correo electrónico
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $response->withStatus(400)->withJson([
                'message' => 'Correo inválido'
            ]);
        }

        // Generar un token para el restablecimiento de la contraseña
        $token = bin2hex(random_bytes(32));
        
        // Normalmente, esto implicará enviar un correo electrónico al usuario con un token
        // que luego pueda usar para cambiar su contraseña
        return $response;
    });
    
    // Ruta para cambiar la contraseña con el token de restablecimiento
    $app->post('/password-reset/{token}', function (Request $request, Response $response, array $args) {
        // Implementar la lógica de cambio de contraseña aquí
        // Aquí deberás permitir al usuario establecer una nueva contraseña, utilizando el token
        // que ha recibido por correo electrónico para verificar su identidad
        return $response;
    });
};