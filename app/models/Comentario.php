<?php

class Comentario{
    public $id;
    public $idMesa;
    public $idPedido;
    public $puntajeResto;
    public $puntajeMesa;
    public $puntajeMozo;
    public $puntajeComida;
    public $puntajeGeneral;
    public $comentario;
    public $fechaComentario;
    public $idCliente;
    
    public function crearComentario(){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO comentarios (idMesa, idPedido, puntajeResto, puntajeMesa, puntajeMozo, puntajeComida, comentario, fechaComentario, puntajeGeneral, idCliente) VALUES (:idMesa, :idPedido, :puntajeResto, :puntajeMesa, :puntajeMozo, :puntajeComida, :comentario, :fechaComentario, :puntajeGeneral, :idCliente)");
        $consulta->bindValue(':idMesa', $this->idMesa, PDO::PARAM_INT);
        $consulta->bindValue(':idPedido', $this->idMesa, PDO::PARAM_INT);
        $consulta->bindValue(':puntajeResto', $this->puntajeResto, PDO::PARAM_INT);
        $consulta->bindValue(':puntajeMesa', $this->puntajeMesa, PDO::PARAM_INT);
        $consulta->bindValue(':puntajeMozo', $this->puntajeMozo, PDO::PARAM_INT);
        $consulta->bindValue(':puntajeComida', $this->puntajeComida, PDO::PARAM_INT);
        $consulta->bindValue(':comentario', $this->comentario, PDO::PARAM_STR);
        $consulta->bindValue(':fechaComentario', $this->fechaComentario, PDO::PARAM_STR);
        $consulta->bindValue(':puntajeGeneral', $this->puntajeGeneral, PDO::PARAM_INT);
        $consulta->bindValue(':idCliente', $this->idCliente, PDO::PARAM_INT);
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

    public static function obtenerComentarioidMesa($idMesa){
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM comentarios WHERE idMesa = :idMesa");
        $consulta->bindValue(':idMesa', $idMesa, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Comentario');
    }
    public static function modificarComentario($comentario){
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE comentarios SET idMesa = :idMesa, idPedido = :idPedido,
                                                    comentario = :comentario, puntajeResto = :puntajeResto,
                                                    puntajeMesa = :puntajeMesa, puntajeMozo = :puntajeMozo, puntajeComida = :puntajeComida,
                                                    puntajeGeneral = :puntajeGeneral, idCliente = :idCliente, fechaComentario = :fechaComentario
                                                    WHERE id = :id");

        $consulta->bindValue(':id', $comentario->id, PDO::PARAM_INT);

        $consulta->bindValue(':idMesa', $comentario->idMesa, PDO::PARAM_INT);
        $consulta->bindValue(':idPedido', $comentario->idPedido, PDO::PARAM_INT);
        $consulta->bindValue(':puntajeResto', $comentario->puntajeResto, PDO::PARAM_INT);
        $consulta->bindValue(':puntajeMesa', $comentario->puntajeMesa, PDO::PARAM_INT);
        $consulta->bindValue(':puntajeMozo', $comentario->puntajeMozo, PDO::PARAM_INT);
        $consulta->bindValue(':puntajeComida', $comentario->puntajeComida, PDO::PARAM_INT);
        $consulta->bindValue(':comentario', $comentario->comentario, PDO::PARAM_STR);
        $consulta->bindValue(':fechaComentario', $comentario->fechaComentario, PDO::PARAM_STR);
        $consulta->bindValue(':puntajeGeneral', $comentario->puntajeGeneral, PDO::PARAM_INT);
        $consulta->bindValue(':idCliente', $comentario->idCliente, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function borrarComentario($comentario){
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM comentarios WHERE id = :id");
        $consulta->bindValue(':id', $comentario->id, PDO::PARAM_INT);
        $consulta->execute();
    }
}