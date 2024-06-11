<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as ResponseMw;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class Auth
{
    private $perfil = ""; // paramestros para hacer mas reutilizable

    public function __construct($perfil) {
        $this->perfil = $perfil;
    }
    public function __invoke(Request $request, RequestHandler $requestHandler) 
    {
        // para poder llamar desde afuera a un metodo de la clase esta
        return $this->auth($request, $requestHandler);
    }
    function auth(Request $request, RequestHandler $requestHandler)
    {
        $response = new ResponseMw();
        $params = $request->getQueryParams();

        if (isset($params["credenciales"]))
        {
            $credenciales = $params["credenciales"];
            if ($credenciales === $this->perfil) 
            {
                $response = $requestHandler->handle($request);
            }
            else 
            {
                $response->getBody()->write(json_encode(array("error" => "No sos".$this->perfil)));
            }
        }
        else
        {
            $response->getBody()->write(json_encode(array("error" => "No envio credenciales")));
        }
        
        return $response;
    }
}