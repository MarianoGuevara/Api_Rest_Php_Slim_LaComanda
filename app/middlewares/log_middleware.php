<?php
    require_once './controllers/log_transacciones_controller.php';
    require_once './middlewares/AutentificadorJWT.php';
    class LogMiddleware
    {
        public static function LogTransaccion($request, $handler)
        {
            $uri = $request->getUri()->getPath();
            
            // Si la ruta es /admin o /sesion, no registrar la transacción
            if ($uri === '/admin' || $uri === '/sesion') {
                return $handler->handle($request);
            }
        
            $cookies = $request->getCookieParams();

            if (isset($cookies['JWT'])) {
                try {
                    $token = $cookies['JWT'];
                    AutentificadorJWT::VerificarToken($token);
                    $datos = AutentificadorJWT::ObtenerData($token);
                    $idUsuario = $datos->id;
                } catch (Exception $e) {
                    $idUsuario = -1;
                }
            }
        
            if ($idUsuario == -1) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode(['error' => 'Token inválido']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
            else
            {
                $response = $handler->handle($request);
                $code = $response->getStatusCode();
                $accion = $request->getUri()->getPath(); 
            
                // Registrar la transacción solo si el usuario es válido
                LogTransaccionesController::InsertarLogTransaccion($idUsuario, $accion, $code);
            
                return $response;   
            }
        }
        public static function SuperUsuario1($request, $handler)
        {
                
        }
    }