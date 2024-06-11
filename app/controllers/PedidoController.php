<?php
require_once './models/Pedido.php';
require_once './interfaces/IApiUsable.php';

class PedidoController extends Pedido implements IApiUsable
{
    public function TraerUno($request, $response, $args)
    {
        $id_pedido = $args['id_pedido'];
        $pedido_final = Pedido::obtenerPedido($id_pedido);
        $payload = json_encode($pedido_final);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Pedido::obtenerTodos();
        $payload = json_encode(array("listaPedido" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        
        $codigo = $parametros['codigo'];
        $nombre_cliente = $parametros['nombre_cliente'];
        $id_mesa = $parametros['id_mesa'];
        $estado = $parametros['estado'];
        $tiempo_estimado = $parametros['tiempo_estimado'];
        $precio_final = $parametros['precio_final'];
        $fecha_baja = null;

        $pedido = new Pedido();
        $pedido->codigo = $codigo;
        $pedido->nombre_cliente = $nombre_cliente;
        $pedido->id_mesa = $id_mesa;
        $pedido->estado = $estado;
        $pedido->tiempo_estimado = $tiempo_estimado;
        $pedido->precio_final = $precio_final;
        $pedido->fecha_baja = $fecha_baja;

        $pedido->crearPedido();

        $payload = json_encode(array("mensaje" => "Pedido creado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $pedido = Pedido::obtenerPedido($parametros['id_pedido']);

        $pedido->codigo = $parametros['codigo'];
        $pedido->nombre_cliente = $parametros['nombre_cliente'];
        $pedido->id_mesa = $parametros['id_mesa'];
        $pedido->estado = $parametros['estado'];
        $pedido->tiempo_estimado = $parametros['tiempo_estimado'];
        $pedido->precio_final = $parametros['precio_final'];
        $pedido->fecha_baja = $parametros['fecha_baja'];

        Pedido::modificarPedido($pedido);

        $payload = json_encode(array("mensaje" => "Pedido modificado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $pedidoId = $parametros['id_pedido'];
        Pedido::borrarPedido($pedidoId);

        $payload = json_encode(array("mensaje" => "Pedido borrado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}