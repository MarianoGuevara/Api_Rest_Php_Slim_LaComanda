<?php
require_once './models/Pedido.php';
require_once './models/Producto.php';
require_once './models/Mesa.php';
require_once 'MesaController.php';
require_once './interfaces/IApiUsable.php';
class PedidoController extends Pedido implements IApiUsable{
    public function TraerUno($request, $response, $args){
        $parametros = $request->getParsedBody();
        $pedido = Pedido::obtenerPedido($parametros['codigoPedido']);
        $payload = json_encode($pedido);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args){
        $lista = Pedido::obtenerTodos();
        $payload = json_encode(array("listaPedidos" => $lista));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerFueraDeTiempo($request, $response, $args){
        $lista = Pedido::obtenerTodos();
        $listaFueraTiempo = [];
        foreach ($lista as $pedido)
        {
            if (isset($pedido->fechaCierre))
            {
                $producto = Producto::obtenerProducto($pedido->idProducto);
                $inicio = new DateTime($pedido->fechaInicio);
                $cierre = new DateTime($pedido->fechaCierre);
                $diferencia = $inicio->diff($cierre);
                $minutos = $diferencia->days * 24 * 60;
                $minutos += $diferencia->h * 60;
                $minutos += $diferencia->i;
                if ($minutos >= $producto->tiempoPreparacion)
                    $listaFueraTiempo[] = $pedido;
            }
        }
        $payload = json_encode(array("listaPedidosFueraTiempo" => $listaFueraTiempo));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function CargarUno($request, $response, $args){
        $parametros = $request->getParsedBody();
        $mesa = MesaController::obtenerMesaCodigoMesa($parametros["codigoMesa"]);

        $producto = Producto::obtenerProducto($parametros['idProducto']);

        $pedido = new Pedido();
        $pedido->codigoPedido = self::generarCodigoPedido();
        $pedido->idMesa = $mesa->id;
        $pedido->idProducto = $parametros['idProducto'];
        $pedido->sector = self::ChequearSector($producto->tipo);
        $pedido->cantidad = $parametros['cantidad'];
        $pedido->nombreCliente = $parametros['nombreCliente'];
        $pedido->importe = $parametros['cantidad'] * $producto->precio;
        $pedido->tiempoPreparacion = null;

        $pedido->crearPedido();
        $payload = json_encode(array("mensaje" => "Pedido creado con exito"));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args){
        $parametros = $request->getParsedBody();
        if(isset($parametros['id'])){
            $pedido = Pedido::obtenerPedidoIndividual($parametros['id']);
            Pedido::borrarPedido($pedido);
            $payload = json_encode(array("mensaje" => "Pedido borrado con exito"));
        }
        else{
            $payload = json_encode(array("mensaje" => "Debe ingresar un id de pedido valido"));
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args){
        $parametros = $request->getParsedBody();
        $pedido = Pedido::obtenerPedidoIndividual($parametros['id']);
        if(isset($parametros['nombreCliente'])){
            $pedido->nombreCliente = $parametros['nombreCliente'];
        }
        if(isset($parametros['cantidad'])){
            $pedido->cantidad = $parametros['cantidad'];
            $producto = Producto::obtenerProducto($parametros['idProducto']);
            $pedido->importe = $producto->precio * $parametros['cantidad'];
        }
        if(isset($parametros['idProducto'])){
            Pedido::modificarPedido($pedido, $parametros['idProducto']);
        }
        if(isset($parametros['tiempoPreparacion'])){
            $producto->tiempoPreparacion = $parametros['tiempoPreparacion'];
        }
        else{
            Pedido::modificarPedido($pedido, false);
        }
        $payload = json_encode(array("mensaje" => "Pedido modificado con exito"));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function generarCodigoPedido(){
        $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $longitud = 5;
        $codigo = '';
        for ($i = 0; $i < $longitud; $i++) {
            $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        return $codigo;
    }

    public static function TraerTodosPorSector($request, $response, $args) {
        $cookie = $request->getCookieParams();
        if(isset($cookie['JWT'])){
            $token = $cookie['JWT'];
            $datos = AutentificadorJWT::ObtenerData($token);

            if($datos->rol == 'cocinero'){
                $lista = Pedido::obtenerTodosPorSector('cocina');
            }
            if($datos->rol == 'bartender'){
                $lista = Pedido::obtenerTodosPorSector('barra');
            }
            if($datos->rol == 'cervecero'){
                $lista = Pedido::obtenerTodosPorSector('cervezas');
            }
            if($datos->rol == 'candyman'){
                $lista = Pedido::obtenerTodosPorSector('candybar');
            }
            if ($datos->rol == 'socio' || $datos->rol == 'mozo')
            {
                $lista = ["Los socios y mozos no pueden traer por sector"];
            }
            $payload = json_encode(array("listaPedidos" => $lista));
        }
        else{
            $payload = json_encode(array("listaPedidos" => 'No hay pedidos para tu sector'));
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function RecibirPedidos($request, $response, $args) {
        $parametros = $request->getQueryParams();
        $cookie = $request->getCookieParams();

        if (isset($parametros["tiempoPreparacion"]))
        {
            if(isset($cookie['JWT'])){
                $token = $cookie['JWT'];
                $datos = AutentificadorJWT::ObtenerData($token);

                $idPedido = $args['idPedido'];
                $pedido = Pedido::obtenerPedidoIndividual($idPedido);

                $vale = false;
                if($datos->rol == 'cocinero' && $pedido->sector == "cocina"){
                    $vale = true;
                }
                if($datos->rol == 'bartender' && $pedido->sector == "barra"){
                    $vale = true;
                }
                if($datos->rol == 'candyman' && $pedido->sector == "candybar"){
                    $vale = true;
                }
                if($datos->rol == 'cervecero' && $pedido->sector == "cerveza"){
                    $vale = true;
                }
                
                if ($vale)
                {
                    $mesa = Mesa::obtenerMesa($pedido->idMesa);
                    $mesa->estado = "en uso";
                    Pedido::updatePedidoPendiente($pedido, $parametros["tiempoPreparacion"]);
                    Mesa::modificarMesa($mesa);
                    $payload = json_encode(array("mensaje" => 'Comenzo la preparacion del pedido'));
                }
                else
                {
                    $payload = json_encode(array("mensaje" => 'Ud. no es del sector para agarrar el pedido'));
                }
            }
            else
            {
                $payload = json_encode(array("mensaje" => 'No esta iniciado sesion'));
            }
        }
        else    
        {
            $payload = json_encode(array("mensaje" => 'No se envia tiempo de preparacion'));
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function PrepararPedido($request, $response, $args) {
        $idPedido = $args['idPedido'];
        $pedido = Pedido::obtenerPedidoIndividual($idPedido);
        if ($pedido->estado == "en preparacion")
        {
            Pedido::updatePedidoEnPreparacion($pedido);
            $payload = json_encode(array("mensaje" => 'Finalizo la preparacion del pedido'));
        }
        else
        {
            $payload = json_encode(array("mensaje" => 'Pedido debe estar en preparacion antes'));
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function EntregarPedidoFinalizado($request, $response, $args) {
        $idPedido = $args['idPedido'];
        $pedido = Pedido::obtenerPedidoIndividual($idPedido);
        if ($pedido != null && $pedido->estado == "preparado")
        {
            Pedido::LlevarPedido($idPedido);
            $payload = json_encode(array("mensaje" => 'Pedido entregado. Que lo goze'));
        }
        else
        {
            $payload = json_encode(array("mensaje" => 'Pedido no existe o no está listo para entregar'));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function DescargarCSV($request, $response, $args) {
        $pedidos = Pedido::obtenerTodosFinalizados('completado');
        $fecha = new DateTime(date('Y-m-d'));
        $path = date_format($fecha, 'Y-m-d').'pedidos_completados.csv';
        $archivo = fopen($path, 'w');
        $encabezado = array('id','codigoPedido','idMesa','idProducto','nombreCliente','sector','estado','importe','cantidad','fechaInicio','fechaCierre','tiempoPreparacion');
        fputcsv($archivo, $encabezado);
        foreach($pedidos as $pedido){
            $linea = array($pedido->id, $pedido->codigoPedido, $pedido->idMesa, $pedido->idProducto, $pedido->nombreCliente, $pedido->sector, $pedido->estado, $pedido->importe, $pedido->cantidad, $pedido->fechaInicio, $pedido->fechaCierre, $pedido->tiempoPreparacion);
            fputcsv($archivo, $linea);
        }
        $payload = json_encode(array("mensaje" => 'Archivo de Pedidos del dia de la fecha creado exitosamente'));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function ChequearSector($tipo){
        if($tipo === 'comida'){
            return 'cocina';
        }
        else if($tipo === 'bebida' || $tipo === 'trago'){
            return 'barra';
        }
        else if($tipo === 'postre'){
            return 'candybar';
        }
        else return "cerveza";
    }

    public function CalcularPromedioIngresos30Dias($request, $response, $args)
    {
        $fechaActual = date("Y-m-d H:i:s");  // Fecha y hora actuales en formato "YYYY-MM-DD HH:mm:ss"
        $fechaActualObj = new DateTime($fechaActual);
        $fechaLimite = $fechaActualObj->modify('-30 days');
        $pedidos = Pedido::obtenerTodosFinalizados("completado");
        $acumulador = 0;
        foreach ($pedidos as $pedido)
        {
            $fechaCierre = new DateTime($pedido->fechaCierre);
            if($fechaCierre >= $fechaLimite)
            {
                $acumulador += $pedido->importe;
            }
        }
        $promedio = $acumulador / 30;

        $payload = json_encode(array("mensaje" => "El importe promedio en los ultimos 30 dias fue de: " . $promedio));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ListarProductosPorVentas($request, $response, $args)
    {   
        $contadorProductos = [];
        $pedidos = Pedido::obtenerTodos();
        $productos = Producto::obtenerTodos();
        foreach ($pedidos as $pedido)
        {
            $producto = Producto::obtenerProducto($pedido->idProducto);
            if (isset($contadorProductos[strval($producto->id)]))
                $contadorProductos[strval($producto->id)] += 1;
            else
                $contadorProductos[strval($producto->id)] = 1;
        }
        usort($productos, function($a, $b) use ($contadorProductos) {
            $cantidad_a = isset($contadorProductos[$a->id]) ? $contadorProductos[$a->id] : 0;
            $cantidad_b = isset($contadorProductos[$b->id]) ? $contadorProductos[$b->id] : 0;
            return $cantidad_b - $cantidad_a;
        });

        $payload = json_encode(array("listaProductoOrdenada" => $productos));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ListarPedidosListos($request, $response, $args)
    {   
        $arrayFinal = [];
        $pedidos = Pedido::obtenerTodos();
        foreach ($pedidos as $pedido)
        {
            if ($pedido->estado == "preparado")
            {
                array_push($arrayFinal, $pedido);
            }
        }

        $payload = json_encode(array("lista pedidos listos para servir" => $arrayFinal));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function PedidosTiempos($request, $response, $args){
        $arrayFinal = [];
        $pedidos = Pedido::obtenerTodos();

        for ($i=0; $i<count($pedidos); $i++)
        {
            $arrayFinal[$pedidos[$i]->codigoPedido] = $pedidos[$i]->tiempoPreparacion;
        }

        $payload = json_encode(array("mensaje" => $arrayFinal));
        $response->getBody()->write($payload);        

        return $response->withHeader('Content-Type', 'application/json');
    }
}