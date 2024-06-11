<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseClass;

require_once './models/Mesa.php';
require_once './models/Pedido.php';
require_once './interfaces/IApiCampos.php';

class MW_Mesa implements IApiCampos
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

        $id_mesa = $params["id_mesa"];
        $id_pedido = $params["id_pedido"];
        $estado_mesa = $params["estado"];

        $mesa = Mesa::ObtenerMesa($id_mesa);
        $pedido = Pedido::obtenerPedido($id_pedido);

        if($pedido->id_mesa != $id_mesa)
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
        $id_mesa = $params["id_mesa"];

        $mesa = Mesa::ObtenerMesa($id_mesa);

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

        if(Mesa::ObtenerMesa($params["id_mesa"]))
        {
            $response->getBody()->write(json_encode(array("error" => "esa mesa ya existe")));
        }
        else
        {
            $response = $handler->handle($request);
        }

        return $response;
    }

    public static function ValidarCodigoNoExistente(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();
        $queryParams = $request->getQueryParams();
        $bodyParams = $request->getParsedBody();
        $params = !empty($queryParams) ? $queryParams : $bodyParams;

        if(Mesa::ObtenerMesa($params["id_mesa"]))
        {
            $response = $handler->handle($request);
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "codigo de mesa no existente")));
        }

        return $response;
    }
}