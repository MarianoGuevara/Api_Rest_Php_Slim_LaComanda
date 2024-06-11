<?php
require_once './models/Producto.php';
require_once './interfaces/IApiUsable.php';

class ProductoController extends Producto implements IApiUsable
{
    public function TraerUno($request, $response, $args)
    {
        $id_producto = $args['id_producto'];
        $producto_final = Producto::obtenerProducto($id_producto);
        $payload = json_encode($producto_final);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Producto::obtenerTodos();
        $payload = json_encode(array("listaProducto" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        
        $precio = $parametros['precio'];
        $tipo = $parametros['tipo'];
        $sector = $parametros['sector'];
        $fecha_baja = null;

        $producto = new Producto();
        $producto->precio = $precio;
        $producto->tipo = $tipo;
        $producto->sector = $sector;
        $producto->fecha_baja = $fecha_baja;

        $producto->crearProducto();

        $payload = json_encode(array("mensaje" => "Producto creado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $producto = Producto::obtenerProducto($parametros['id_producto']);

        $producto->precio = $parametros['precio'];
        $producto->tipo = $parametros['tipo'];
        $producto->sector = $parametros['sector'];
        $producto->fecha_baja = $parametros['fecha_baja'];

        Producto::modificarProducto($producto);

        $payload = json_encode(array("mensaje" => "Producto modificado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $productoId = $parametros['id_producto'];
        Producto::borrarProducto($productoId);

        $payload = json_encode(array("mensaje" => "Producto borrado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
