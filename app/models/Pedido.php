<?php
class Pedido
{
    public $id_pedido;
    public $codigo;
    public $nombre_cliente; # cliente que hace el pedido
    public $id_mesa; # id de la mesa del cual es el pedido
    public $estado; # pendiente, en preparación, listo para servir
    public $tiempo_estimado;	
    public $precio_final;	
    public $fecha_baja; 
    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedidos");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Pedido');
    }
    public static function obtenerPedido($id_pedido)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedidos WHERE id_pedido = :id_pedido");
        $consulta->bindValue(':id_pedido', $id_pedido, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Pedido');
    }
    public function crearPedido()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta
        ("INSERT INTO pedidos (codigo, nombre_cliente, id_mesa, estado, tiempo_estimado, precio_final, fecha_baja) 
        VALUES (:codigo, :nombre_cliente, :id_mesa, :estado, :tiempo_estimado, :precio_final, :fecha_baja)"
        ); 
        $consulta->bindValue(':codigo', $this->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':nombre_cliente', $this->nombre_cliente, PDO::PARAM_STR);
        $consulta->bindValue(':id_mesa', $this->id_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $this->estado);
        $consulta->bindValue(':tiempo_estimado', $this->tiempo_estimado, PDO::PARAM_INT);
        $consulta->bindValue(':precio_final', $this->precio_final, PDO::PARAM_INT);
        $consulta->bindValue(':fecha_baja', $this->fecha_baja, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }
    public static function modificarPedido($pedido)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta
        ("UPDATE pedidos SET codigo = :codigo, 
        nombre_cliente = :nombre_cliente, id_mesa = :id_mesa,
        estado = :estado, tiempo_estimado = :tiempo_estimado,
        precio_final = :precio_final, fecha_baja = :fecha_baja
        WHERE id_pedido = :id_pedido");

        $consulta->bindValue(':codigo', $pedido->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':nombre_cliente', $pedido->nombre_cliente, PDO::PARAM_STR);
        $consulta->bindValue(':id_mesa', $pedido->id_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $pedido->estado, PDO::PARAM_STR);
        $consulta->bindValue(':tiempo_estimado', $pedido->tiempo_estimado, PDO::PARAM_INT);
        $consulta->bindValue(':precio_final', $pedido->precio_final, PDO::PARAM_INT);
        $consulta->bindValue(':fecha_baja', $pedido->fecha_baja, PDO::PARAM_STR);

        $consulta->bindValue(':id_pedido', $pedido->id_pedido, PDO::PARAM_INT);

        $consulta->execute();
    }

    public static function borrarPedido($id_pedido)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE pedidos SET fecha_baja = :fecha_baja WHERE id_pedido = :id_pedido");

        $fecha = new DateTime(date("Y-m-d"));
        $consulta->bindValue(':id_pedido', $id_pedido, PDO::PARAM_INT);
        $consulta->bindValue(':fecha_baja', date_format($fecha, 'Y-m-d'));
        $consulta->execute();
    }

    ######################################################################################################
}