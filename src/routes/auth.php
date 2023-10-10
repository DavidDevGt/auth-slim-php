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
        $app->post('/register', function (Request $request, Response $response) use ($pdo) {
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
        $app->post('/login', function (Request $request, Response $response) use ($pdo) {
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
        // Recibir el correo del usuario
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';

        // Validar el email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $response->withStatus(400)->withJson(['error' => 'Email inválido']);
        }

        // Generar un token para el usuario
        $token = bin2hex(random_bytes(32));

        // Guardar el token en la base de datos con una validez de 15 minutos
        $validUntil = new DateTime();
        $validUntil->modify('+15 minutes'); // Añadir 15 minutos a la hora actual
        $formattedValidUntil = $validUntil->format('Y-m-d H:i:s'); // Formatear para MySQL datetime

        $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_token_valid_until = :valid_until WHERE email = :email");
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':valid_until', $formattedValidUntil);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Enviar el token al usuario vía correo electrónico
        $to = $email;
        $subject = "Restablecimiento de Contraseña";
        $message = "Tu token para restablecer la contraseña es: $token. Este token es válido por 15 minutos.";
        $headers = "From: webmaster@example.com";

        if (mail($to, $subject, $message, $headers)) {
            return $response->withJson(['message' => 'Token generado y enviado por correo electrónico.']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo enviar el correo.']);
        }
    });

    // Ruta para cambiar la contraseña con el token de restablecimiento
    $app->post('/password-reset/{token}', function (Request $request, Response $response, array $args) use ($pdo) {
        // Recibir el nuevo password y el token desde la ruta
        $data = $request->getParsedBody();
        $newPassword = $data['password'] ?? '';
        $token = $args['token'] ?? '';

        // Validar los datos
        if (empty($newPassword) || empty($token)) {
            return $response->withStatus(400)->withJson(['error' => 'Datos inválidos']);
        }

        // Encontrar al usuario por el token
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :token");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si el usuario no se encuentra, retornar un error
        if (!$user) {
            return $response->withStatus(400)->withJson(['error' => 'Token inválido']);
        }

        // Actualizar la contraseña del usuario
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL WHERE id = :id");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $user['id']);
        $stmt->execute();

        return $response->withJson(['message' => 'Contraseña actualizada.']);
    });
};