<?php
    require_once './models/Producto.php';
    class AutenticadorProductos{
        public static function ValidarCamposProductos($request, $handler){
            $parametros = $request->getParsedBody();
            if(isset($parametros['nombre']) || isset($parametros['tipo']) || isset($parametros['precio'])){
                return $handler->handle($request);
            }
            else throw new Exception('Campos Invalidos');
        }
        
    }

?>