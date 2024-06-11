<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;


interface IApiCampos
{
    public static function ValidarCampos(Request $request, RequestHandler $handler);
    public static function ValidarCodigoNoExistente(Request $request, RequestHandler $handler);

}