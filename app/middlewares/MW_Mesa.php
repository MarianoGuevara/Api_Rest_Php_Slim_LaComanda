<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseClass;

require_once './models/Mesa.php';
require_once './models/Pedido.php';
require_once './interfaces/IApiCampos.php';

class MW_Mesa
{
    public static function ValidarCampos(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();
        $params = $request->getParsedBody();
        if(isset($params["codigo"], $params["estado"]))
        {
            $response = $handler->handle($request);
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "campos invalidos"))); 
        }

        return $response;
    }


    public static function CambiarEstadoMesa(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();
        $params = $request->getParsedBody();

        $codigo_mesa = $params["codigo_mesa"];
        $codigo_pedido = $params["codigo_pedido"];
        $estado_mesa = $params["estado"];

        $mesa = Mesa::ObtenerMesa($codigo_mesa);
        $pedido = Pedido::obtenerPedido($codigo_pedido);

        if($pedido->codigo_mesa != $codigo_mesa)
        {
            $response->getBody()->write(json_encode(array("error" => "ese pedido no pertenece a esa mesa"))); 
        }
        else
        {
            if($mesa->estado == "cerrada" && $estado_mesa == "con cliente esperando pedido")
            {
                $response = $handler->handle($request);
            }
            else if($mesa->estado == "con cliente esperando pedido" && $estado_mesa == "con cliente comiendo" )
            {
                $pedido->tiempo_estimado = $params["tiempo_estimado"];
                Pedido::modificarPedido($pedido);
                $response = $handler->handle($request);
            }
            else
            {
                $response->getBody()->write(json_encode(array("error" => "verifique el estado mesa ingresado"))); 
            }
        }
        return $response;
    }

    public static function ValidarEstadoMesa(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();

        $params = $request->getParsedBody();
        $codigo_mesa = $params["codigo_mesa"];

        $mesa = Mesa::ObtenerMesa($codigo_mesa);

        if($mesa->estado == "con cliente esperando pedido" || $mesa->estado == "con cliente comiendo")
        {
            $response = $handler->handle($request);
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "esa mesa no espera un pedido"))); 
        }

        return $response;
    }

    public static function ValidarCodigoExistente(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();
        $params = $request->getParsedBody();

        if (!isset($params["codigo"]))
        {
            $response->getBody()->write(json_encode(array("error" => "no se envia codigo")));
        }
        else
        {
            if(Mesa::ObtenerMesa($params["codigo"]))
            {
                $response->getBody()->write(json_encode(array("error" => "esa mesa ya existe")));
            }
            else
            {
                $response = $handler->handle($request);
            }
        }

        return $response;
    }

    // public static function ValidarCodigoNoExistente(Request $request, RequestHandler $handler)
    // {
    //     $response = new ResponseClass();
    //     $queryParams = $request->getQueryParams();
    //     $bodyParams = $request->getParsedBody();
    //     $params = !empty($queryParams) ? $queryParams : $bodyParams;


    //     if(isset($params["codigo_mesa"]) && Mesa::ObtenerMesa($params["codigo_mesa"]))
    //     {
    //         $response = $handler->handle($request);
    //     }
    //     else
    //     {
    //         $response->getBody()->write(json_encode(array("error" => "codigo de mesa no existente o no enviado")));
    //     }

    //     return $response;
    // }
}