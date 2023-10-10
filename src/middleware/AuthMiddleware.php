<?php
namespace Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // De la solicitud HTTP, obtenemos el encabezado
        $headers = $request->getHeaders();

        // Se valida si existe una cabecera de autorización
        if (!isset($headers['Authorization'])) {
            $response = new \Slim\Psr7\Response();
            return $response->withStatus(401);
        }

        // Se obtiene el token de la cabecera
        $authHeader = explode(' ', $headers['Authorization'][0]);
        $token = $authHeader[1] ?? null;

        // Se valida el token de autorización
        if ($token !== 'TOKEN_SECRETO') {
            // Se crea una respuesta con estado 403 (Prohibido) si el token es inválido
            $response = new \Slim\Psr7\Response();
            return $response->withStatus(403);
        }

        return $handler->handle($request);
    }
}