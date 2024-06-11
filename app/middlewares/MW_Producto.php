<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseClass;

require_once './interfaces/IApiCampos.php';


class MW_Producto implements IApiCampos
{
    public static function ValidarCampos(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();
        $params = $request->getParsedBody();

        if(isset($params["tipo"], $params["sector"], $params["precio"], $params["estado"]))
        {
            $response = $handler->handle($request);
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "Campos invalidos"))); 
        }

        return $response;
    }
    public static function ValidarCodigoNoExistente(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();
        $queryParams = $request->getQueryParams();
        $bodyParams = $request->getParsedBody();
        $params = !empty($queryParams) ? $queryParams : $bodyParams;

        if(Mesa::ObtenerMesa($params["id_producto"]))
        {
            $response = $handler->handle($request);
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "id producto no existente")));
        }

        return $response;
    }

    public static function ValidarTipo(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();

        $params = $request->getParsedBody();

        if($params["tipo"] === "cerveza" || $params["tipo"] === "trago" || $params["tipo"] === "comida")
        {
            $response = $handler->handle($request);
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "tipo de producto invalido"))); 
        }

        return $response;

    }
}