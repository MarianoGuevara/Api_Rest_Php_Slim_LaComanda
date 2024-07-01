<?php
require_once './models/Comentario.php';
require_once './models/Usuario.php';
require_once './interfaces/IApiUsable.php';
class ComentarioController extends Comentario implements IApiUsable{
    
    public function TraerUno($request, $response, $args){
        $parametros = $request->getQueryParams();
        $id = $parametros['id'];
        $prd = Comentario::obtenerComentario($id);
        $payload = json_encode($prd);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args){
        $lista = Comentario::obtenerTodos();
        $payload = json_encode(array("listaComentario" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerMejores($request, $response, $args){
        $parametros = $request->getQueryParams();
        
        $horaActual = date('H:i:s');
        $parametros["fecha"] .= ' ' . $horaActual;    
        $lista = Comentario::obtenerTodosFecha($parametros["fecha"]);
        $lista_mejores = [];
        $max_puntaje = 0; // el puntaje esta validado a ser entre (1-5)

        foreach ($lista as $comentario) {
            if ($comentario->puntajeGeneral > $max_puntaje) {
                $max_puntaje = $comentario->puntajeGeneral;
            }
        }
        foreach ($lista as $comentario)
        {
            if ($comentario->puntajeGeneral === $max_puntaje)
                $lista_mejores[] = $comentario;    
        }

        $payload = json_encode(array("listaComentario" => $lista_mejores));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerPeores($request, $response, $args){
        $parametros = $request->getQueryParams();
        
        $horaActual = date('H:i:s');
        $parametros["fecha"] .= ' ' . $horaActual;    

        $lista = Comentario::obtenerTodosFecha($parametros["fecha"]);

        $lista_mejores = [];
        $min_puntaje = 6; // el puntaje esta validado a ser entre (1-5)

        foreach ($lista as $comentario) {
            if ($comentario->puntajeGeneral < $min_puntaje) {
                $min_puntaje = $comentario->puntajeGeneral;
            }
        }
        foreach ($lista as $comentario)
        {
            if ($comentario->puntajeGeneral === $min_puntaje)
                $lista_mejores[] = $comentario;    
        }

        $payload = json_encode(array("listaComentario" => $lista_mejores));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function CargarUno($request, $response, $args){
        $parametros = $request->getParsedBody();

        $fecha = new DateTime(date('Y-m-d H:i:s'));

        $prd = new Comentario();
        $prd->idMesa = $parametros['idMesa'];
        $prd->idPedido = $parametros['idPedido'];
        $prd->comentario = $parametros['comentario'];
        $prd->puntajeResto = $parametros['puntajeResto'];
        $prd->puntajeMesa = $parametros['puntajeMesa'];
        $prd->puntajeMozo = $parametros['puntajeMozo'];
        $prd->puntajeComida = $parametros['puntajeComida'];
        $prd->puntajeGeneral = ($prd->puntajeResto + $prd->puntajeMesa + $prd->puntajeMozo + $prd->puntajeComida) / 4;
        $prd->fechaComentario = date_format($fecha, 'Y-m-d H:i:s');

        $cookie = $request->getCookieParams();
        $token = $cookie['JWT'];
        $datos = AutentificadorJWT::ObtenerData($token);
        $prd->idCliente = $datos->id;

        $prd->crearComentario();
        $payload = json_encode(array("mensaje" => "Comentario creado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args){
        $parametros = $request->getParsedBody();
        Comentario::borrarComentario($parametros['id']);
        $payload = json_encode(array("mensaje" => "Comentario borrado con exito"));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args){
        $parametros = $request->getParsedBody();
        
        $comentario = Comentario::obtenerComentario($parametros['id']);

        $cookie = $request->getCookieParams();
        $token = $cookie['JWT'];
        $datos = AutentificadorJWT::ObtenerData($token);
        $comentario->idCliente = $datos->id;

        if(isset($parametros['idPedido'])){
            $comentario->idPedido = $parametros['idPedido'];
        }
        if(isset($parametros['idMesa'])){
            $comentario->idMesa = $parametros['idMesa'];
        }
        if(isset($parametros['comentario'])){
            $comentario->comentario = $parametros['comentario'];
        }
        if(isset($parametros['nombreCliente'])){
            $comentario->nombreCliente = $parametros['nombreCliente'];
        }
        if(isset($parametros['puntajeResto'])){
            $comentario->puntajeResto = $parametros['puntajeResto'];
        }
        if(isset($parametros['puntajeMesa'])){
            $comentario->puntajeMesa = $parametros['puntajeMesa'];
        }
        if(isset($parametros['puntajeMozo'])){
            $comentario->puntajeMozo = $parametros['puntajeMozo'];
        }
        if(isset($parametros['puntajeComida'])){
            $comentario->puntajeComida = $parametros['puntajeComida'];
        }

        $fecha = new DateTime(date('Y-m-d H:i:s'));
        $comentario->puntajeGeneral = ($comentario->puntajeResto + $comentario->puntajeMesa + $comentario->puntajeMozo + $comentario->puntajeComida) / 4;
        $comentario->fechaComentario = date_format($fecha, 'Y-m-d H:i:s');

        Comentario::modificarComentario($comentario);
        $payload = json_encode(array("mensaje" => "Comentario modificado con exito"));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}