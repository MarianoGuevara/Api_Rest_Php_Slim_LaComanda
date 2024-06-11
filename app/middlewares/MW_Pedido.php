<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseClass;

require_once './interfaces/IApiCampos.php';
require_once './models/Producto.php';
require_once './models/Pedido.php';

class MW_Pedido implements IApiCampos
{
    public static function ValidarCampos(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();

        $params = $request->getParsedBody();

        if(isset($params["id_mesa"], $params["id_pedido"], $params["estado"], $params["nombre_cliente"],
        $params["tiempo_preparacion"] ))
        {
            $response = $handler->handle($request);
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "campos invalidos"))); 
        }

        return $response;
    }

    
    public static function ValidarCodigoNoExistente(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();
        $queryParams = $request->getQueryParams();
        $bodyParams = $request->getParsedBody();
        $params = !empty($queryParams) ? $queryParams : $bodyParams;

        if(Pedido::obtenerPedido($params["id_pedido"]))
        {
            $response = $handler->handle($request);
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "id de pedido no existente")));
        }

        return $response;
    }

    public static function ValidarProductosListos(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();
        parse_str(file_get_contents("php://input"), $params);

        $id_pedido = $params["id_pedido"];

        $pedido = Pedido::obtenerPedido($id_pedido);
        $id_mesa = $pedido->id_mesa;

        $productos = Producto::ObtenerTodos();

        $flag = true;
        foreach($productos as $producto)
        {
            if($producto->id_mesa == $id_mesa)
            {
                if($producto->estado_producto !== "listo")
                {
                    $flag = false;
                    break;
                }
            }
        }

        if($flag)
        {
            $pedido->estado_pedido = "listo para servir";
            $response = $handler->handle($request);
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "los productos aun no estan listos para servir")));
        }

        return $response;

    }
}