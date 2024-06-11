<?php
class Producto
{
    public $id_producto;
    public $tipo; // vino, cerveza, empanadas, etc
    public $sector; // barra de tragos y vinos, barra de choperas, cocina, Candy Bar
    public $fecha_baja;
    public $precio;
    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Producto');
    }

    public static function obtenerProducto($id_producto)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos WHERE id_producto = :id_producto");
        $consulta->bindValue(':id_producto', $id_producto, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Producto');
    }
    public function crearProducto()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta
        ("INSERT INTO productos (tipo, sector, precio, fecha_baja) 
        VALUES (:tipo, :sector, :precio, :fecha_baja)"
        ); 
        $consulta->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
        $consulta->bindValue(':sector', $this->sector, PDO::PARAM_STR);
        $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
        $consulta->bindValue(':fecha_baja', $this->fecha_baja, PDO::PARAM_STR);

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function modificarProducto($producto)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta
        ("UPDATE productos SET tipo = :tipo, precio = :precio,
        sector = :sector, fecha_baja = :fecha_baja 
        WHERE id_producto = :id_producto");

        $consulta->bindValue(':tipo', $producto->tipo, PDO::PARAM_STR);
        $consulta->bindValue(':sector', $producto->sector, PDO::PARAM_STR);
        $consulta->bindValue(':fecha_baja', $producto->fecha_baja, PDO::PARAM_STR);
        $consulta->bindValue(':precio', $producto->precio, PDO::PARAM_INT);

        $consulta->bindValue(':id_producto', $producto->id_producto, PDO::PARAM_INT);

        $consulta->execute();
    }

    public static function borrarProducto($id_producto)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE productos SET fecha_baja = :fecha_baja 
                                                    WHERE id_producto = :id_producto");

        $fecha = new DateTime(date("Y-m-d"));
        $consulta->bindValue(':id_producto', $id_producto, PDO::PARAM_INT);
        $consulta->bindValue(':fecha_baja', date_format($fecha, 'Y-m-d'));
        $consulta->execute();
    }

    ######################################################################################################
}