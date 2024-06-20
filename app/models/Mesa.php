<?php

class Mesa{
    public $id;
    public $codigo;
    public $estado; // libre, en uso, cerrada(admin)
    public $nombreMozo;
    public $cobro;
    public $usos;
    
    public function crearMesa(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO mesas (codigo, estado, nombreMozo, usos) VALUES (:codigo, :estado, :nombreMozo, :usos)");
        $consulta->bindValue(':codigo', $this->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':estado', 'libre', PDO::PARAM_STR);
        $consulta->bindValue(':nombreMozo', $this->nombreMozo, PDO::PARAM_STR);
        $consulta->bindValue(':usos', $this->usos, PDO::PARAM_INT);

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function obtenerMesa($id){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Mesa');
    }

    public static function obtenerMesaCodigoMesa($codigoMesa){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas WHERE codigo = :codigoMesa");
        $consulta->bindValue(':codigoMesa', $codigoMesa, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Mesa');
    }

    public static function modificarMesa($mesa){
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE mesas SET nombreMozo = :nombreMozo, estado = :estado, cobro = :cobro WHERE id = :id");
        $consulta->bindValue(':id', $mesa->id, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $mesa->estado, PDO::PARAM_STR);
        $consulta->bindValue(':nombreMozo', $mesa->nombreMozo, PDO::PARAM_STR);
        $consulta->bindValue(':cobro', $mesa->cobro, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function borrarMesa($mesa){
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM mesas WHERE id = :id");
        $consulta->bindValue(':id', $mesa->id, PDO::PARAM_INT);
        $consulta->execute();
    }
    
    public static function ValidarMesa($mesa){
        if($mesa){
            return true;
        }
        return false;
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

    public static function CobrarYLiberarMesa($codigo){
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $mesaAntes = Mesa::obtenerMesaCodigoMesa($codigo);
        $consulta = $objAccesoDato->prepararConsulta("UPDATE mesas SET estado = :estado, usos = :usos WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->bindValue(':estado', 'libre', PDO::PARAM_STR);
        $consulta->bindValue(':usos', $mesaAntes->usos+=1, PDO::PARAM_STR);
        $consulta->execute();
    }
}