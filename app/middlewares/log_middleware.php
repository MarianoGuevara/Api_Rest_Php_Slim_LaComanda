<?php
    require_once './controllers/log_transacciones_controller.php';
    require_once './middlewares/AutentificadorJWT.php';
    class LogMiddleware
    {
        public static function LogTransaccion($request, $handler)
        {
            $uri = $request->getUri()->getPath();
    
            // Si la ruta es /admin, no registrar la transacción
            if ($uri === '/admin' || $uri === '/sesion') {
                return $handler->handle($request);
            }

            $coockies = $request->getCookieParams();
            if (isset($coockies['JWT']))
            {
                $token = $coockies['JWT'];
                AutentificadorJWT::VerificarToken($token);
                $datos = AutentificadorJWT::ObtenerData($token);
                $idUsuario = $datos->id;
            }
            else
                $idUsuario = -1;

            $response = $handler->handle($request);

            $code = $response->getStatusCode();
            $accion = $request->getUri()->getPath(); 
            
            if ($idUsuario != -1)
            {
                LogTransaccionesController::InsertarLogTransaccion($idUsuario, $accion, $code);
            }

            return $response;
        }
        public static function SuperUsuario1($request, $handler)
        {
                
        }
    }