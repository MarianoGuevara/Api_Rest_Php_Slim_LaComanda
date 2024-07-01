<?php
    class AutenticadorUsuario{

        public static function VerificarUsuario($request, $handler){
            $cookies = $request->getCookieParams();
            $token = $cookies['JWT'];
            AutentificadorJWT::VerificarToken($token);
            $datos = AutentificadorJWT::ObtenerData($token);
            if(self::ValidarRolUsuario($datos->rol)){
                return $handler->handle($request);
            }
            else{
                throw new Exception('No autorizado');
            }
        }

        public static function ValidarPermisosDeRol($request, $handler, $rol = false){
            $cookies = $request->getCookieParams();
            $token = $cookies['JWT'];
            AutentificadorJWT::VerificarToken($token);
            $datos = AutentificadorJWT::ObtenerData($token);
            if((!$rol && $datos->rol == 'socio') || $rol && $datos->rol == $rol || $datos->rol == 'socio'){
                return $handler->handle($request);
            }
            throw new Exception('Acceso denegado');
        }

        public static function ValidarPermisosDeRolCliente($request, $handler, $rol = false){
            $cookies = $request->getCookieParams();
            $token = $cookies['JWT'];
            AutentificadorJWT::VerificarToken($token);
            $datos = AutentificadorJWT::ObtenerData($token);
            if(!$rol && $datos->rol == 'cliente'){
                return $handler->handle($request);
            }
            throw new Exception('Acceso denegado');
        }
        public static function ValidarPermisosDeRolDoble($request, $handler, $rol1 = false, $rol2 = false){
            $cookies = $request->getCookieParams();
            $token = $cookies['JWT'];
            AutentificadorJWT::VerificarToken($token);
            $datos = AutentificadorJWT::ObtenerData($token);
            if((!$rol1 && $datos->rol == 'socio') || ($rol1 && $datos->rol == $rol1) || ($rol2 && $datos->rol == $rol2) || ($datos->rol == 'socio' || $datos->rol == 'mozo')){
                return $handler->handle($request);
            }
            throw new Exception('Acceso denegado');
        }
        
        public static function ValidarCampos($request, $handler){
            $parametros = $request->getParsedBody();
            if(isset($parametros['nombre']) || isset($parametros['email']) || isset($parametros['clave']) || isset($parametros['rol']) || isset($parametros['estado'])){
                return $handler->handle($request);
            }
            throw new Exception('Campos Invalidos');
        }

        public static function ValidarCampoIdUsuario($request, $handler){
            $parametros = $request->getQueryParams();
            if(isset($parametros['idUsuario'])){
                return $handler->handle($request);
            }
            throw new Exception('Campos Invalidos');
        }

        public static function ValidarRolUsuario($rol){
            if($rol !== null){
                if(empty($rol) || $rol != 'socio' && $rol != 'bartender' && $rol != 'cocinero' && $rol != 'mozo' && $rol != 'candyman' && $rol != 'cervecero'){
                    return false;
                }
            }
            return true;
        }

        public static function ValidarFecha($request, $handler, $rol = false){
            $parametros = $request->getQueryParams();
            if(isset($parametros["fecha"]) && AutenticadorUsuario::verificarFecha($parametros["fecha"])){
                
                return $handler->handle($request);
            }
            throw new Exception('Fecha invalida');
        }

        private static function verificarFecha($fecha)
        {
            $bool = false;

            if ($fecha!=null)
            {
                $patron = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/'; // regex q ve fecha
                if (preg_match($patron, $fecha)) $bool = true;
            }
            return $bool;
        }
    }
?>