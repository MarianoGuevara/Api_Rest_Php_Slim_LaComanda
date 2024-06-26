<?php
require_once './models/Mesa.php';
require_once './models/Pedido.php';
require_once './interfaces/IApiUsable.php';
class MesaController extends Mesa implements IApiUsable{
    public function TraerUno($request, $response, $args){
        $parametros = $request->getParsedBody();
        $codigo = $parametros['id'];
        $mesa = Mesa::obtenerMesa($codigo);
        $payload = json_encode($mesa);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args){
        $lista = Mesa::obtenerTodos();
        $payload = json_encode(array("listaMesas" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerOrdenadaMenorFactura($request, $response, $args){
        $mesas = Mesa::obtenerTodos();

        usort($mesas, function($mesa1, $mesa2) {
            if ($mesa1->estado !== 'cerrada' || $mesa1->cobro === null) {
                return 1; 
            }
            if ($mesa2->estado !== 'cerrada' || $mesa2->cobro === null) {
                return -1;
            }
            
            if ($mesa1->cobro < $mesa2->cobro) {
                return -1;
            } elseif ($mesa1->cobro > $mesa2->cobro) {
                return 1;
            } else {
                return 0;
            }
        });

        $payload = json_encode(array("listaMesas" => $mesas));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CargarUno($request, $response, $args){
        $mesa = new Mesa();
        $mesa->codigo = self::generarCodigoMesa();
        $mesa->crearMesa();
        $payload = json_encode(array("mensaje" => "Mesa creada con exito - puede empezar a regitrar pedidos con el codigo [ $mesa->codigo ]"));
    
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args){
        $parametros = $request->getParsedBody();
        $mesa = Mesa::obtenerMesa($parametros['id']);
        Mesa::borrarMesa($mesa);
        $payload = json_encode(array("mensaje" => "Mesa borrada con exito"));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args){
        $parametros = $request->getParsedBody();
        $mesa = Mesa::obtenerMesa($parametros['id']);
        if(isset($parametros['nombreMozo'])){
            $mesa->nombreMozo = $parametros['nombreMozo'];
        }
        if(isset($parametros['estado'])){
            $mesa->estado = $parametros['estado'];
        }
        Mesa::modificarMesa($mesa);
        $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function generarCodigoMesa(){
        $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $longitud = 5;
        $codigo = '';
        for ($i = 0; $i < $longitud; $i++) {
            $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        return $codigo;
    }

    public static function CerrarMesa($request, $response, $args) {
        $parametros = $request->getParsedBody();

        if(isset($parametros['idMesa']))
        {
            $idMesa = $parametros['idMesa'];

            $pedido = Pedido::obtenerPedido($parametros["codigoPedido"]);
            $mesa = Mesa::obtenerMesa($idMesa);
        
            if ($pedido->fechaCierre != null)
            {
                $mesa->cobro += $pedido->importe;
                Mesa::modificarMesa($mesa);
                Mesa::CobrarYLiberarMesa($mesa->codigo);
                $payload = json_encode(array("mensaje" => "Mesa cobrada - Total a pagar: [ ".$pedido->importe." ]"));
            }
            else{
                $payload = json_encode(array("mensaje" => "Mesa no puede liberarse y cobrarse aún. Su pedido no fue entregado"));
            }
        }
        else{
            $payload = json_encode(array("mensaje" => "No se envio mesa"));
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function GetCobroEntreDosFechas($request, $response, $args){
        $parametros = $request->getQueryParams();
        $codigoMesa = $parametros['codigo'];
        $fechaEntrada = DateTime::createFromFormat('Y-m-d H:i:s', $parametros["fechaEntrada"]);
        $fechaSalida = DateTime::createFromFormat('Y-m-d H:i:s', $parametros["fechaSalida"]);
        if(isset($codigoMesa)){
            $listaPedidos = Pedido::obtenerPedidosPorMesa($codigoMesa);
            $mesa = Mesa::obtenerMesaCodigoMesa($codigoMesa);
            if ($mesa->estado == "cerrada")
            {
                $totalFacturado = 0;
                foreach($listaPedidos as $pedido){
                    if($pedido->estado == 'completado'){
                        $fechaPedido = DateTime::createFromFormat('Y-m-d H:i:s', $pedido->fechaInicio);
                        if ($fechaPedido >= $fechaEntrada && $fechaPedido <= $fechaSalida) {
                            $totalFacturado += $pedido->importe * $pedido->cantidad;
                        }
                    }
                }
                $payload = json_encode(array("mensaje" => "Total a facturado entre fechas: [ ".$totalFacturado." ]"));
            }
            else
            {
                $payload = json_encode(array("mensaje" => "La mesa no esta cerrada"));
            }
        }
        else{
            $payload = json_encode(array("mensaje" => "No se encontro la mesa"));
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function AsociarFoto($request, $response, $args){
        $parametros = $request->getParsedBody();
        $parametrosArchivos = $request->getUploadedFiles();
        if (isset($parametrosArchivos["foto"]))
        {
            $foto = $parametrosArchivos["foto"];
            $mesa = Mesa::obtenerMesaCodigoMesa($parametros['codigoMesa']);

            $nombre = $mesa->codigo . ".png";
            $foto->moveTo($nombre);

            $payload = json_encode(array("mensaje" => "Mesa asociada a foto con exito"));
            $response->getBody()->write($payload);
        }
        else
        {
            $payload = json_encode(array("mensaje" => "Foto no enviada"));
            $response->getBody()->write($payload);
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function TiempoRestante($request, $response, $args){
        $parametros = $request->getParsedBody();

        if (isset($parametros["codigoMesa"]))
        {
            // $codigoPedido = $parametros["codigoPedido"];

            // $mesa = Mesa::obtenerMesaCodigoMesa($parametros['codigoMesa']);
            // $lista = Pedido::obtenerPedidosPorMesa($mesa->id);
            $pedido = Pedido::obtenerPedido($parametros["numPedido"]);

            if ($pedido != false && $pedido->tiempoPreparacion != null)
            {
                $fechaActual = new DateTime();
                $fechaInicioPedido = new DateTime($pedido->fechaInicio);

    
                $diferencia = $fechaInicioPedido->diff($fechaActual);
    
                $minutosTranscurridos = $diferencia->days * 24 * 60;
                $minutosTranscurridos += $diferencia->h * 60;
                $minutosTranscurridos += $diferencia->i;
    
                $tiempoRestante = $pedido->tiempoPreparacion - $minutosTranscurridos;

                if ($tiempoRestante > 0) $payload = json_encode(array("mensaje" => "El pedido tardará: " . $tiempoRestante));
                else $payload = json_encode(array("mensaje" => "El pedido está tardío con demora de: " . str_replace("-", "", (string)($tiempoRestante)) . " minutos"));
            }
            else $payload = json_encode(array("mensaje" => "La mesa tiene una o mas partes del pedido que no estan en preparacion aun."));
            
            $response->getBody()->write($payload);        
        }
        else    
        {
            $payload = json_encode(array("mensaje" => "Parametros"));
            $response->getBody()->write($payload);
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function MesaEstados($request, $response, $args){
        $arrayFinal = [];
        $mesas = Mesa::obtenerTodos();

        for ($i=0; $i<count($mesas); $i++)
        {
            $arrayFinal[$mesas[$i]->codigo] = $mesas[$i]->estado;
        }

        $payload = json_encode(array("mensaje" => $arrayFinal));
        $response->getBody()->write($payload);        

        return $response->withHeader('Content-Type', 'application/json');
    }
    public static function AdminCerrarMesa($request, $response, $args){
        $cookies = $request->getCookieParams();
        $parametros = $request->getParsedBody();

        if(isset($cookies['JWT']) && isset($parametros["id"])){
            $token = $cookies['JWT'];
            $datos = AutentificadorJWT::ObtenerData($token);
            if ($datos->rol == "socio")
            {
                $mesa = Mesa::obtenerMesa($parametros["id"]);
                if($mesa != null && $mesa->estado != "cerrada")
                {
                    $mesa->estado = "cerrada";
                    Mesa::modificarMesa($mesa);
                    $payload = json_encode(array("mensaje" => "Mesa cerrada por socio"));                    
                }
                else
                {
                    $payload = json_encode(array("mensaje" => "Mesa no existe o ya está cerrada"));                    
                }
            }
            else
            {
                $payload = json_encode(array("mensaje" => "Acceso denegado"));
            }
        }
        $response->getBody()->write($payload);        
        return $response->withHeader('Content-Type', 'application/json');
    }
    public static function MesaMasUsada($request, $response, $args){
        $arrayFinal = [];
        $pedidos = Pedido::obtenerTodos();

        for ($i=0; $i<count($pedidos); $i++)
        {
            if (isset($arrayFinal[$pedidos[$i]->idMesa]) == false)
            {
                $contador = 0;
                for ($j=0; $j<count($pedidos); $j++)
                {
                    if ($pedidos[$j]->idMesa == $pedidos[$i]->idMesa) 
                    {
                        $contador += 1;
                    }
                }
                $arrayFinal[$pedidos[$i]->idMesa] = $contador;
            }
        }

        $flag = false;
        foreach ($arrayFinal as $mesa => $usos)
        {
            if (!$flag || $usos > $masUsos)
            {
                $flag = true;
                $masUsos = $usos;
                $masId = $mesa;
            }
        }
        $arrayMayores = [$masId => $masUsos];
        foreach ($arrayFinal as $mesa => $usos)
        {
            if ($mesa != $masId && $usos == $masUsos)
            {
                $arrayMayores[$mesa] = $usos;
            }
        }

        $payload = json_encode(array("Masa/s con mas usos" => $arrayMayores));
        $response->getBody()->write($payload);        

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function MesaMenosUsada($request, $response, $args){
        $arrayFinal = [];
        $pedidos = Pedido::obtenerTodos();

        for ($i=0; $i<count($pedidos); $i++)
        {
            if (isset($arrayFinal[$pedidos[$i]->idMesa]) == false)
            {
                $contador = 0;
                for ($j=0; $j<count($pedidos); $j++)
                {
                    if ($pedidos[$j]->idMesa == $pedidos[$i]->idMesa) 
                    {
                        $contador += 1;
                    }
                }
                $arrayFinal[$pedidos[$i]->idMesa] = $contador;
            }
        }

        $flag = false;
        foreach ($arrayFinal as $mesa => $usos)
        {
            if (!$flag || $usos < $masUsos)
            {
                $flag = true;
                $masUsos = $usos;
                $masId = $mesa;
            }
        }
        $arrayMayores = [$masId => $masUsos];
        foreach ($arrayFinal as $mesa => $usos)
        {
            if ($mesa != $masId && $usos == $masUsos)
            {
                $arrayMayores[$mesa] = $usos;
            }
        }

        $payload = json_encode(array("Masa/s con menos usos" => $arrayMayores));
        $response->getBody()->write($payload);        

        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function MasFacturo($request, $response, $args){
        $mesas = Mesa::obtenerTodos();
        $max = $mesas[0];
        foreach($mesas as $mesa){
            if($mesa->cobro > $max->cobro){
                $max = $mesa;
            }
        }
        $payload = json_encode(array("Mesa que mas facturó" => $max));
    
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
    public static function MenosFacturo($request, $response, $args){
        $mesas = Mesa::obtenerTodos();
        $min = $mesas[0];
        foreach($mesas as $mesa){
            if($mesa->cobro < $min->cobro){
                $min = $mesa;
            }
        }
        $payload = json_encode(array("Mesa que menos facturó" => $min));
    
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}