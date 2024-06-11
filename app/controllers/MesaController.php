<?php
require_once './models/Mesa.php';
require_once './interfaces/IApiUsable.php';

class MesaController extends Mesa implements IApiUsable
{
    public function TraerUno($request, $response, $args)
    {
        $mesa_id = $args['id_mesa'];
        $mesa_final = Mesa::obtenerMesa($mesa_id);
        $payload = json_encode($mesa_final);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Mesa::obtenerTodos();
        $payload = json_encode(array("listaMesa" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        
        $codigo = $parametros['codigo'];
        $estado = $parametros['estado'];
        $fecha_baja = null;

        $mesa = new Mesa();
        $mesa->codigo = $codigo;
        $mesa->estado = $estado;
        $mesa->fecha_baja = $fecha_baja;

        $mesa->crearMesa();

        $payload = json_encode(array("mensaje" => "Mesa creada con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $mesa = Mesa::obtenerMesa($parametros['id_mesa']);

        $mesa->codigo = $parametros['codigo'];
        $mesa->estado = $parametros['estado'];
        $mesa->fecha_baja = $parametros['fecha_baja'];

        Mesa::modificarMesa($mesa);

        $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $mesaId = $parametros['id_mesa'];
        Mesa::borrarMesa($mesaId);

        $payload = json_encode(array("mensaje" => "Mesa borrada con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}