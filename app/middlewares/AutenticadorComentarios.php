<?php
    require_once './models/Comentario.php';
    require_once './models/Mesa.php';
    require_once './models/Pedido.php';
    class AutenticadorComentarios{
        public static function ValidarCamposComentario($request, $handler){
            $parametros = $request->getParsedBody();
            if(isset($parametros['idPedido']) && isset($parametros['idMesa'])&& isset($parametros['comentario']) &&
            isset($parametros['puntajeResto'])&& isset($parametros['puntajeMesa']) &&
            isset($parametros['puntajeMozo'])&& isset($parametros['puntajeComida'])){
                if ($parametros['puntajeResto'] > 0 && $parametros['puntajeResto'] < 11 && 
                $parametros['puntajeMesa'] > 0 && $parametros['puntajeMesa'] < 11 && 
                $parametros['puntajeMozo'] > 0 && $parametros['puntajeMozo'] < 11 && 
                $parametros['puntajeComida'] > 0 && $parametros['puntajeComida'] < 11)
                    return $handler->handle($request);
            }
            throw new Exception('Campos Invalidos');
        }

        public static function ValidarBindeo($request, $handler){
            $parametros = $request->getParsedBody();
            $cookie = $request->getCookieParams();
            $token = $cookie['JWT'];
            $datos = AutentificadorJWT::ObtenerData($token);

            $mesa = Mesa::obtenerMesa($parametros['idMesa']);
            $pedido = Pedido::obtenerPedidoIndividual($parametros['idPedido']);
            if($mesa && $pedido)
            {
                // $pedido->nombreCliente == $datos->nombre
                if ($pedido->idMesa == $mesa->id) return $handler->handle($request);
                else throw new Exception('El pedido no es de esa mesa');
            }
            else
            {
                throw new Exception('la mesa y/o pedido no existe');
            }
        }
    }