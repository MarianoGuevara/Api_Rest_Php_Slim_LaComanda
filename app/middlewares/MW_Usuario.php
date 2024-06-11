<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseClass;

require_once './models/Producto.php';
class MW_Usuario
{
    private $perfil = '';
    public function __construct($perfil) {$this->perfil = $perfil;}
    public function __invoke(Request $request, RequestHandler $handler){return $this->VerificarRol($request, $handler);}
    public function VerificarRol(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();
        $queryParams = $request->getQueryParams();
        $bodyParams = $request->getParsedBody();
        $params = !empty($queryParams) ? $queryParams : $bodyParams;

        if($params["rol"] !== $this->perfil)
        {
            $response->getBody()->write(json_encode(array("Error" => "Debe ser del tipo ". $this->perfil)));
        }
        else $response = $handler->handle($request);

        return $response;
    }
    public static function ValidarCambioEstadoProducto(Request $request, RequestHandler $handler)
    {
        parse_str(file_get_contents("php://input"), $params);

        $response = new ResponseClass();
        $rol = $params["rol"];

        $producto = Producto::ObtenerProducto($params["id_producto"]);
        $tipo_producto = $producto->tipo;
        $flag = false;

        switch($tipo_producto)
        {
            case "comida":
                if($rol != "cocinero")
                {
                    $response->getBody()->write(json_encode(array("error" => "No podes modificar el tipo")));
                }
                else
                {
                    $producto->estado = "listo";
                    $flag = true;
                    $response = $handler->handle($request);
                }
                break;
            case "trago":
                    if($rol != "bartender")
                    {
                        $response->getBody()->write(json_encode(array("error" => "No podes modificar el tipo")));
                    }
                    else
                    {
                        $flag = true;
                        $producto->estado = "listo";
                        $response = $handler->handle($request);
                    }
                    break;
            default:
                if($rol != "cervecero")
                {
                    $response->getBody()->write(json_encode(array("error" => "No podes modificar el tipo")));
                }
                else
                {
                    $flag = true;
                    $producto->estado = "listo";
                    $response = $handler->handle($request);
                }
                break;
        }
        return $response;
    }
    public static function ValidarRol(Request $request, RequestHandler $handler)
    {
        $response = new ResponseClass();

        $params = $request->getParsedBody();


        if(isset($params["rol"]))
        {
            $rol = $params["rol"];

            if($rol == "socio" || $rol == "bartender" || $rol == "mozo" ||
            $rol == "cocinero" || $rol == "cliente" || $rol == "cervecero")
            {
                $response = $handler->handle($request);
            }
            else
            {
                $response->getBody()->write(json_encode(array("error" => "El rol mandado es incorrecto")));
            }
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "No se envía el rol")));
        }

        return $response;
    }
}