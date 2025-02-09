<?php
require_once './models/Pedido.php';
require_once './models/Producto.php';
require_once './models/Mesa.php';
require_once './models/DetallePedido.php';
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
        $parametros = $request->getQueryParams();
        
        $horaActual = date('H:i:s');
        $parametros["fecha"] .= ' ' . $horaActual;    
        $lista = Pedido::obtenerTodosFecha($parametros["fecha"]);

        $listaFueraTiempo = [];
        foreach ($lista as $pedido)
        {
            if (isset($pedido->fechaCierre))
            {
                $inicio = new DateTime($pedido->fechaInicio); // veo fechas de inicio y cierre-> cuando arrancó y se completo el pedido
                $cierre = new DateTime($pedido->fechaCierre);
                $diferencia = $inicio->diff($cierre); // calculo la diferencia entre ambas y la llevo a minutos
                $minutos = $diferencia->days * 24 * 60;
                $minutos += $diferencia->h * 60;
                $minutos += $diferencia->i; 
                if ($minutos >= $pedido->tiempoPreparacion) // si la diferencia entre el tiempo acumulado entre fechaInicio y fechaCierre es mayor a tiempoPreparacion, se entregó fuera de time
                    $listaFueraTiempo[] = $pedido;
            }
        }
        $payload = json_encode(array("listaPedidosFueraTiempo" => $listaFueraTiempo));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function CargarUno($request, $response, $args){
        $cookies = $request->getCookieParams();
        $token = $cookies["JWT"];
        $datos = AutentificadorJWT::ObtenerData($token);

        $parametros = $request->getParsedBody();
        $mesa = MesaController::obtenerMesaCodigoMesa($parametros["codigoMesa"]);

        $pedido = new Pedido();
        $pedido->idMozo = $datos->id;
        $pedido->codigoPedido = self::generarCodigoPedido();
        $pedido->idMesa = $mesa->id;
        $pedido->nombreCliente = $parametros['nombreCliente'];

        $pedido->productos = $parametros["productos"];
        $total = 0;
        foreach ($pedido->productos as $producto) {
            $productoActual = Producto::obtenerProducto($producto['idProducto']);
            $total += $productoActual->precio * $producto['cantidad'];
        }
        $pedido->importe = $total;

        $idPedido = $pedido->crearPedido();

        $mesa = Mesa::obtenerMesaCodigoMesa($parametros["codigoMesa"]);
        $mesa->estado = "con cliente esperando pedido";
        Mesa::modificarMesa($mesa);

        var_dump($idPedido);
        foreach ($pedido->productos as $producto) {
            $productoActual = Producto::obtenerProducto($producto['idProducto']);
            $sector = self::ChequearSector($productoActual->tipo);
            DetallePedido::crearDetallePedido(null, $idPedido, $productoActual->id, $producto["cantidad"], $sector);
            $total += $productoActual->precio * $producto['cantidad'];
        }

        $payload = json_encode(array("mensaje" => "Pedido creado con exito"));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args){
        $parametros = $request->getParsedBody();
        if(isset($parametros['id'])){
            $pedido = Pedido::obtenerPedidoIndividual($parametros['id']);
            if ($pedido->estado != "entregado" && $pedido->estado != "cancelado")
            {
                $detalles = DetallePedido::obtenerDetalleDeUnPedido($pedido->id);
                var_dump($detalles);
                for ($i=0; $i<count($detalles); $i++)
                {
                    DetallePedido::cancelarDetallePedido($detalles[$i]["id"]);
                }

                Pedido::borrarPedido($pedido);
                $payload = json_encode(array("mensaje" => "Pedido borrado con exito"));
            }
            else $payload = json_encode(array("mensaje" => "Pedido ya está entregado o cancelado. No se puede cancelar"));
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
                $lista = DetallePedido::obtenerTodosPorSector('cocina');
            }
            if($datos->rol == 'bartender'){
                $lista = DetallePedido::obtenerTodosPorSector('barra');
            }
            if($datos->rol == 'cervecero'){
                $lista = DetallePedido::obtenerTodosPorSector('cervezas');
            }
            if($datos->rol == 'candyman'){
                $lista = DetallePedido::obtenerTodosPorSector('candybar');
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

    public static function ComenzarPreparacion($request, $response, $args) {
        $parametros = $request->getQueryParams();
        $cookie = $request->getCookieParams();

        if (isset($parametros["tiempoPreparacion"]))
        {
            if(isset($cookie['JWT'])){
                $token = $cookie['JWT'];
                $datos = AutentificadorJWT::ObtenerData($token);

                $pedido = DetallePedido::obtenerDetalleIndividual($parametros['idDetalle']);

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

                if ($vale && $pedido->estado == "pendiente")
                {
                    DetallePedido::comenzarPreparacionDetallePedido((int)($parametros["idDetalle"]), $parametros["tiempoPreparacion"], $datos->id);
                    
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
        $cookie = $request->getCookieParams();
        $parametros = $request->getQueryParams();

        $detallePedido = DetallePedido::obtenerDetalleIndividual($parametros["idDetalle"]);

        $fechaActual = new DateTime();
        $fechaInicioPedido = new DateTime($detallePedido->fechaInicio);

        $diferencia = $fechaInicioPedido->diff($fechaActual);

        $minutosTranscurridos = $diferencia->days * 24 * 60;
        $minutosTranscurridos += $diferencia->h * 60;
        $minutosTranscurridos += $diferencia->i;

        $tiempoRestante = $detallePedido->tiempoPreparacion - $minutosTranscurridos; // Me fijo que haya pasado el tiempo antes
        var_dump($tiempoRestante);
        if ($detallePedido->estado == "en preparacion" && $tiempoRestante < 1)
        {
            $token = $cookie['JWT'];
            $datos = AutentificadorJWT::ObtenerData($token);

            $vale = false;
            if($datos->rol == 'cocinero' && $detallePedido->sector == "cocina"){
                $vale = true;
            }
            if($datos->rol == 'bartender' && $detallePedido->sector == "barra"){
                $vale = true;
            }
            if($datos->rol == 'candyman' && $detallePedido->sector == "candybar"){
                $vale = true;
            }
            if($datos->rol == 'cervecero' && $detallePedido->sector == "cerveza"){
                $vale = true;
            }
            
            if ($vale)
            {
                DetallePedido::updateDetallePedidoEnPreparacion($detallePedido);
                $payload = json_encode(array("mensaje" => 'Finalizo la preparacion del pedido'));
            }
            else
            {
                $payload = json_encode(array("mensaje" => 'Ud. no es del sector para agarrar el pedido'));
            }
        }
        else
        {
            $payload = json_encode(array("mensaje" => 'Pedido debe estar en preparacion antes'));
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function EntregarPedidoFinalizado($request, $response, $args) {
        $parametros = $request->getQueryParams();

        $pedido = Pedido::obtenerPedido($parametros["codigoPedido"]);

        if ($pedido != null && $pedido->estado == "preparado")
        {
            Pedido::LlevarPedido($pedido->id);
            $mesa = Mesa::obtenerMesa($pedido->idMesa);
            $mesa->estado = "con cliente comiendo";
            Mesa::modificarMesa($mesa);
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
        $pedidos = Pedido::obtenerTodosFinalizados('entregado');
        $filename = "pedidos_completados.csv";
    
        $response = $response->withHeader('Content-Type', 'text/csv')
                            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    
        $output = fopen('php://output', 'w');
        $encabezado = array('id', 'codigoPedido', 'idMesa', 'nombreCliente', 'estado', 'importe', 'fechaInicio', 'fechaCierre', 'tiempoPreparacion', 'productos');
        fputcsv($output, $encabezado);

        foreach($pedidos as $pedido) {
            $str = "[";
            $detalles = DetallePedido::obtenerDetalleDeUnPedido($pedido->id);
            foreach ($detalles as $producto)
            {
                $str .= $producto["id"] . "-";
                
            }
            $str .= "]";
            $linea = array(
                $pedido->id,
                $pedido->codigoPedido,
                $pedido->idMesa,
                $pedido->nombreCliente,
                $pedido->estado,
                $pedido->importe,
                $pedido->fechaInicio,
                $pedido->fechaCierre,
                $pedido->tiempoPreparacion,
                $str
            );
            fputcsv($output, $linea);
        }
    
        fclose($output);
    
        return $response;
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

    public function EstadisticasCalcularPromedioIngresos($request, $response, $args)
    {
        $fechaActual = date("Y-m-d H:i:s");  // Fecha y hora actuales en formato "YYYY-MM-DD HH:mm:ss"
        $fechaActualObj = new DateTime($fechaActual);
        $fechaLimite = $fechaActualObj->modify('-30 days');
        $pedidos = Pedido::obtenerTodosFinalizados("entregado");
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

    // public function CalcularPedidosDeEmpleado30Dias($request, $response, $args)
    // {
    //     $fechaActual = date("Y-m-d H:i:s");  // Fecha y hora actuales en formato "YYYY-MM-DD HH:mm:ss"
    //     $fechaActualObj = new DateTime($fechaActual);
    //     $fechaLimite = $fechaActualObj->modify('-30 days');
    //     $pedidos = Pedido::obtenerTodosFinalizados("entregado");
    //     $acumulador = 0;
    //     foreach ($pedidos as $pedido)
    //     {
    //         $fechaCierre = new DateTime($pedido->fechaCierre);
    //         if($fechaCierre >= $fechaLimite)
    //         {
    //             $acumulador += $pedido->importe;
    //         }
    //     }
    //     $promedio = $acumulador / 30;

    //     $payload = json_encode(array("mensaje" => "El importe promedio en los ultimos 30 dias fue de: " . $promedio));
    //     $response->getBody()->write($payload);
    //     return $response->withHeader('Content-Type', 'application/json');
    // }

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
            if ($pedidos[$i]->estado == "en preparacion")
            {
                $fechaActual = new DateTime();
                $fechaInicioPedido = new DateTime($pedidos[$i]->fechaInicio);

                $diferencia = $fechaInicioPedido->diff($fechaActual);

                $minutosTranscurridos = $diferencia->days * 24 * 60;
                $minutosTranscurridos += $diferencia->h * 60;
                $minutosTranscurridos += $diferencia->i;

                $tiempoRestante = $pedidos[$i]->tiempoPreparacion - $minutosTranscurridos;

                if ($tiempoRestante > 0) $msj = "El pedido tardará: " . $tiempoRestante;
                else $msj = "El pedido está tardío con demora de: " . str_replace("-", "", (string)($tiempoRestante)) . " minutos";

                $arrayFinal[$pedidos[$i]->codigoPedido] = $msj;
            }
        }

        $payload = json_encode(array("mensaje" => $arrayFinal));
        $response->getBody()->write($payload);        

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function PedidosVendidos($request, $response, $args){
        $parametros = $request->getQueryParams();
        $arrayFinal = [];

        $horaActual = date('H:i:s');
        $parametros["fecha"] .= ' ' . $horaActual;    
        $pedidos = DetallePedido::obtenerDetallePedidosFecha($parametros["fecha"]);

        for ($i=0; $i<count($pedidos); $i++)
        {
            if (isset($arrayFinal[Producto::obtenerProducto($pedidos[$i]["idProducto"])->nombre]) == false)
            {
                $contador = 0;
                for ($j=0; $j<count($pedidos); $j++)
                {
                    if ($pedidos[$j]["idProducto"] == $pedidos[$i]["idProducto"]) 
                    {
                        $contador += 1;
                    }
                }
                $arrayFinal[Producto::obtenerProducto($pedidos[$i]["idProducto"])->nombre] = $contador;
            }
        }
        return $arrayFinal;
    }

    public static function PedidoMasVendido($request, $response, $args){
        $arrayFinal = PedidoController::PedidosVendidos($request, $response, $args);
        arsort($arrayFinal);
        $valoresMasAltos = array_slice($arrayFinal, 0, 1, true);
                                                    // agarra primero

        $payload = json_encode(array("Valor mas alto" => $valoresMasAltos));
        $response->getBody()->write($payload);        

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function PedidoMenosVendido($request, $response, $args){
        $arrayFinal = PedidoController::PedidosVendidos($request, $response, $args);
        asort($arrayFinal);
        $valoresMasAltos = array_slice($arrayFinal, 0, 1, true);
                                                    // agarra primero

        $payload = json_encode(array("Valor mas alto" => $valoresMasAltos));
        $response->getBody()->write($payload);        

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function CancelarPedido($request, $response, $args){
        
        $arrayFinal = PedidoController::PedidosVendidos($request, $response, $args);
        asort($arrayFinal);
        $valoresMasAltos = array_slice($arrayFinal, 0, 1, true);
                                                    // agarra primero

        $payload = json_encode(array("Valor mas alto" => $valoresMasAltos));
        $response->getBody()->write($payload);        

        return $response->withHeader('Content-Type', 'application/json');
    }
}