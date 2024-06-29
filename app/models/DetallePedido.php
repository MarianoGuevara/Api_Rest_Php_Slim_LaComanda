<?php
require_once 'Producto.php';
require_once 'Pedido.php';
// require_once 'Producto.php';
class DetallePedido
{
    public $id;
    public $idUsuario;
    public $idPedido;
    public $idProducto;
    public $sector;
    public $estado;
    public $tiempoPreparacion;
    public $fechaInicio;
    public $fechaCierre;
    
    public static function crearDetallePedido($idUsuario, $idPedido, $idProducto, $cantidad, $sector)
        {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();

            for ($i=0; $i<$cantidad; $i++)
            {
                $fecha = new DateTime(date('Y-m-d H:i:s'));
                $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO pedido_detalle (idUsuario, idPedido, idProducto, sector, tiempoPreparacion, estado, fechaInicio, fechaCierre) VALUES (:idUsuario, :idPedido, :idProducto, :sector, :tiempoPreparacion, :estado, :fechaInicio, :fechaCierre)");
                $consulta->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
                $consulta->bindValue(':idPedido', $idPedido, PDO::PARAM_INT);
                $consulta->bindValue(':idProducto', $idProducto, PDO::PARAM_INT);
                $consulta->bindValue(':sector', $sector, PDO::PARAM_STR);
                $consulta->bindValue(':estado', 'pendiente', PDO::PARAM_STR);     
                $consulta->bindValue(':tiempoPreparacion', null, PDO::PARAM_INT);
                $consulta->bindValue(':fechaInicio', date_format($fecha, 'Y-m-d H:i:s'), PDO::PARAM_STR);
                $consulta->bindValue(':fechaCierre', null, PDO::PARAM_STR);
                $consulta->execute();
            }

            return $objAccesoDatos->obtenerUltimoId();
        }

        public static function obtenerDetallePedidos()
        {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedido_detalle");
            $consulta->execute();
            $resultado = $consulta->fetchAll(PDO::FETCH_ASSOC);
            return $resultado;  
        }
        public static function obtenerDetallePedidosFecha($fechaInicio)
        {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedido_detalle WHERE fechaInicio > :fechaInicio");
            $consulta->bindValue(':fechaInicio', $fechaInicio, PDO::PARAM_STR);     
            $consulta->execute();
            $resultado = $consulta->fetchAll(PDO::FETCH_ASSOC);
            return $resultado;  
        }

        public static function obtenerDetalleIndividual($idDetalle)
        {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedido_detalle WHERE id = :idDetalle");
            $consulta->bindValue(':idDetalle', $idDetalle, PDO::PARAM_INT);
            $consulta->execute();
            return $consulta->fetchObject('DetallePedido');
        }
        public static function obtenerDetalleDeUnPedido($idPedido)
        {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedido_detalle WHERE idPedido = :idPedido");
            $consulta->bindValue(':idPedido', $idPedido, PDO::PARAM_INT);
            $consulta->execute();
            $resultado = $consulta->fetchAll(PDO::FETCH_ASSOC);
            return $resultado;  
        }
        public static function comenzarPreparacionDetallePedido($idDetalle, $tiempoPreparacion, $idUsuario)
        {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("UPDATE pedido_detalle SET estado = :estado, tiempoPreparacion = :tiempoPreparacion , idUsuario = :idUsuario  WHERE id = :id");

            $consulta->bindValue(':id', $idDetalle, PDO::PARAM_INT);
            $consulta->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
            $consulta->bindValue(':estado', 'en preparacion', PDO::PARAM_STR);     
            $consulta->bindValue(':tiempoPreparacion', $tiempoPreparacion, PDO::PARAM_INT);
            $consulta->execute();
            
            $detalle = DetallePedido::obtenerDetalleIndividual($idDetalle);
            $pedido = Pedido::obtenerPedidoIndividual($detalle->idPedido);
            $detalle = self::obtenerDetalleDeUnPedido($pedido->id);
            if (count($detalle) > 0)
            {
                $pedido = Pedido::obtenerPedidoIndividual($detalle[0]["idPedido"]);
                $flag = true;
                $maxTime = $detalle[0];
                for ($i=0; $i<count($detalle); $i++)
                {
                    // var_dump($detalle[$i]);
                    if ($detalle[$i]["estado"] != "pendiente")
                    {
                        if ($detalle[$i]["tiempoPreparacion"] > $maxTime["tiempoPreparacion"])
                        {
                            $maxTime = $detalle[$i];
                        }
                    }
                    else 
                    {
                        var_dump("has");
                        $flag = false;
                    }
                }
            
                if ($flag)
                {
                    Pedido::updatePedidoPendiente($pedido, $maxTime["tiempoPreparacion"]);
                }
            }
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

        public static function obtenerTodosPorSector($sector){
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedido_detalle WHERE sector = :sector AND estado = :estado");
            $consulta->bindValue(':sector', $sector, PDO::PARAM_STR);
            $consulta->bindValue(':estado', 'pendiente', PDO::PARAM_STR);
            $consulta->execute();
            return $consulta->fetchAll(PDO::FETCH_CLASS, 'DetallePedido');
        }
        public static function updateDetallePedidoEnPreparacion($detallePedido) {
            $objAccesoDato = AccesoDatos::obtenerInstancia();
            $fecha = new DateTime(date('Y-m-d H:i:s'));
            $consulta = $objAccesoDato->prepararConsulta("UPDATE pedido_detalle SET estado = :estado, fechaCierre = :fechaCierre WHERE id = :id");
            $consulta->bindValue(':id', $detallePedido->id, PDO::PARAM_INT);
            $consulta->bindValue(':estado', 'preparado', PDO::PARAM_STR);
            $consulta->bindValue(':fechaCierre', date_format($fecha, 'Y-m-d H:i:s'), PDO::PARAM_STR);
            $consulta->execute();

            $detalle = DetallePedido::obtenerDetalleIndividual($detallePedido->id);
            $pedido = Pedido::obtenerPedidoIndividual($detalle->idPedido);
            $detalle = self::obtenerDetalleDeUnPedido($pedido->id);
            if (count($detalle) > 0)
            {
                $pedido = Pedido::obtenerPedidoIndividual($detalle[0]["idPedido"]);
                $flag = true;
                $maxTime = $detalle[0];
                for ($i=0; $i<count($detalle); $i++)
                {
                    if ($detalle[$i]["estado"] != "preparado")
                    {
                        $flag = false;
                    }
                }
            
                if ($flag)
                {
                    Pedido::updatePedidoEnPreparacion($pedido);
                }
            }
        }
        public static function cancelarDetallePedido($id)
        {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("UPDATE pedido_detalle SET estado = :estado, fechaCierre = :fechaCierre WHERE id = :id");

            $fecha = new DateTime(date('Y-m-d H:i:s'));

            $consulta->bindValue(':id', $id, PDO::PARAM_INT);
            $consulta->bindValue(':estado', 'cancelado', PDO::PARAM_STR);     
            $consulta->bindValue(':fechaCierre', date_format($fecha, 'Y-m-d H:i:s'), PDO::PARAM_STR);
            $consulta->execute();
        }

}