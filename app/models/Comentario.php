<?php

class Comentario{
    public $id;
    public $codigoMesa;
    public $puntaje;
    public $comentario;
    public $fechaComentario;
    
    public function crearComentario(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO comentarios (codigoMesa, puntaje, comentario, fechaComentario) VALUES (:codigoMesa, :puntaje, :comentario, :fechaComentario)");
        $consulta->bindValue(':codigoMesa', $this->codigoMesa, PDO::PARAM_STR);
        $consulta->bindValue(':puntaje', $this->puntaje, PDO::PARAM_INT);
        $consulta->bindValue(':comentario', $this->comentario, PDO::PARAM_STR);
        $consulta->bindValue(':fechaComentario', $this->fechaComentario, PDO::PARAM_STR);

        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }
    public static function obtenerTodosFecha($fechaComentario){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM comentarios WHERE fechaComentario > :fechaComentario ");
        $consulta->bindValue(':fechaComentario', $fechaComentario, PDO::PARAM_STR);

        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Comentario');
    }
    public static function obtenerTodos(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM comentarios");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Comentario');
    }

    public static function obtenerComentario($id){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM comentarios WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Comentario');
    }

    public static function obtenerComentarioCodigoMesa($codigoMesa){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM comentarios WHERE codigoMesa = :codigoMesa");
        $consulta->bindValue(':codigoMesa', $codigoMesa, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Comentario');
    }
    public static function modificarComentario($comentario){
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE comentarios SET comentario = :comentario, puntaje = :puntaje WHERE id = :id");
        $consulta->bindValue(':id', $comentario->id, PDO::PARAM_INT);
        $consulta->bindValue(':puntaje', $comentario->puntaje, PDO::PARAM_INT);
        $consulta->bindValue(':comentario', $comentario->comentario, PDO::PARAM_STR);
        $consulta->execute();
    }

    public static function borrarComentario($comentario){
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM comentarios WHERE id = :id");
        $consulta->bindValue(':id', $comentario->id, PDO::PARAM_INT);
        $consulta->execute();
    }
}