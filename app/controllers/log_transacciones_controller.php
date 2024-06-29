<?php

    require_once("./models/class_log_transaccion.php");
    require_once("./models/Usuario.php");

    class LogTransaccionesController {

        public function GetTransacciones($request, $response, $args)
        {
            $transacciones = LogTransaccion::TraerTodo();

            $payload = json_encode(["transacciones" => $transacciones]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }

        public static function InsertarLogTransaccion($idUsuario, $accion, $code)
        {
            $logTransaccion = new LogTransaccion();
            $logTransaccion->idUsuario = $idUsuario;
            $logTransaccion->code = $code;
            $logTransaccion->accion = $accion;

            $logTransaccion->Insertar();
        }

        public function CalcularCantidadOperaciones($request, $response, $args)
        {
            $parametros = $request->getQueryParams();
            $sectores = Usuario::ObtenerSectores();

            $horaActual = date('H:i:s');
            $parametros["fecha"] .= ' ' . $horaActual;    

            $transacciones = LogTransaccion::TraerTodoFecha($parametros["fecha"]);

            foreach ($transacciones as $transaccion)
            {
                $usuario = Usuario::obtenerUsuario($transaccion->idUsuario);
                $sectores[$usuario->rol] += 1;
            }

            $payload = json_encode(["cantidadOperaciones" => $sectores]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }

        public function CalcularCantidadOperacionesUsuarios($request, $response, $args)
        {
            $parametros = $request->getQueryParams();
            $sectores = [];

            $horaActual = date('H:i:s');
            $parametros["fecha"] .= ' ' . $horaActual;    

            $transacciones = LogTransaccion::TraerTodoFecha($parametros["fecha"]);

            foreach ($transacciones as $transaccion)
            {
                $usuario = Usuario::obtenerUsuario($transaccion->idUsuario);

                if (isset($usuario->rol) && $usuario->rol != false && $usuario->rol != null)
                {
                    if (isset($sectores[$usuario->rol][$usuario->id]))
                        $sectores[$usuario->rol][strval($usuario->id)] += 1;
                    else
                        $sectores[$usuario->rol][strval($usuario->id)] = 1;
                }
                
            }

            $payload = json_encode(["cantidadOperaciones" => $sectores]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }

        public function CalcularCantidadOperacionesUno($request, $response, $args)
        {
            $parametros = $request->getQueryParams();
            $arrayFinal[$parametros["idUsuario"]] = 0;

            $horaActual = date('H:i:s');
            $parametros["fecha"] .= ' ' . $horaActual;    

            $transacciones = LogTransaccion::TraerTodoFecha($parametros["fecha"]);

            foreach ($transacciones as $transaccion)
            {
                $usuario = Usuario::obtenerUsuario($transaccion->idUsuario);
                $usuario2 = Usuario::obtenerUsuario($parametros["idUsuario"]);
                if ($usuario->id == $usuario2->id)
                {
                    $arrayFinal[$parametros["idUsuario"]] += 1;
                }
            }

            $payload = json_encode(["cantidadOperacionesUser" => $arrayFinal]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }

        public static function MesaMasUsada($request, $response, $args){
            $arrayFinal = [];
            $transacciones = LogTransaccion::TraerTodo();
    
            for ($i=0; $i<count($transacciones); $i++)
            {
                
            }
    
            $payload = json_encode(array("mensaje" => $arrayFinal));
            $response->getBody()->write($payload);        
    
            return $response->withHeader('Content-Type', 'application/json');
        }
    }