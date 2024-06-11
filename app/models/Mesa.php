<?php
class Mesa
{
    public $id_mesa;
    public $codigo;
    public $estado;
    public $fecha_baja;
    #imagen??
    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function obtenerMesa($id_mesa)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas WHERE id_mesa = :id_mesa");
        $consulta->bindValue(':id_mesa', $id_mesa, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Mesa');
    }
    public function crearMesa()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO mesas (codigo, estado, fecha_baja) 
                                                        VALUES (:codigo, :estado, :fecha_baja)"); 
        $consulta->bindValue(':codigo', $this->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->bindValue(':fecha_baja', $this->fecha_baja, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }
    public static function modificarMesa($mesa)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE mesas SET codigo = :codigo, 
                                                    estado = :estado, fecha_baja = :fecha_baja 
                                                    WHERE id_mesa = :id_mesa");

        $consulta->bindValue(':codigo', $mesa->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $mesa->estado, PDO::PARAM_STR);
        $consulta->bindValue(':fecha_baja', $mesa->fecha_baja, PDO::PARAM_STR);

        $consulta->bindValue(':id_mesa', $mesa->id_mesa, PDO::PARAM_INT);

        $consulta->execute();
    }

    public static function borrarMesa($id_mesa)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE mesas SET fecha_baja = :fecha_baja WHERE id_mesa = :id_mesa");

        $fecha = new DateTime(date("Y-m-d"));
        $consulta->bindValue(':id_mesa', $id_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':fecha_baja', date_format($fecha, 'Y-m-d'));
        $consulta->execute();
    }

    ######################################################################################################
    function generateRandomCode($length = 5) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = strlen($chars);
        $retorno = '';
        for ($i = 0; $i < $length; $i++) {
            $retorno .= $chars[rand(0, $len-1)];
        }
        return $retorno;
    }
}