<?php
require_once './models/Usuario.php';
require_once './interfaces/IApiUsable.php';

class UsuarioController extends Usuario implements IApiUsable
{
    public function TraerUno($request, $response, $args)
    {
        $usr_id = $args['id_usuario'];
        $usuario = Usuario::obtenerUsuario($usr_id);
        $payload = json_encode($usuario);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Usuario::obtenerTodos();
        $payload = json_encode(array("listaUsuario" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        
        // DATOS DEL USUARIO
        $nombre = $parametros['nombre'];
        $clave = $parametros['clave'];
        $rol = $parametros['rol'];
        $estado = "activo";
        $fecha_baja = null;

        // Creamos el usuario
        $usr = new Usuario();
        $usr->nombre = $nombre;
        $usr->clave = $clave;
        $usr->rol = $rol;
        $usr->estado = $estado;
        $usr->fecha_baja = $fecha_baja;
        $usr->crearUsuario();

        $payload = json_encode(array("mensaje" => "Usuario creado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $usuario = Usuario::obtenerUsuario($parametros['id_usuario']);

        $usuario->nombre = $parametros['nombre'];
        $usuario->clave = $parametros['clave'];
        $usuario->rol = $parametros['rol'];
        $usuario->estado = $parametros['estado'];
        $usuario->fecha_baja = $parametros['fecha_baja'];

        Usuario::modificarUsuario($usuario);

        $payload = json_encode(array("mensaje" => "Usuario modificado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $usuarioId = $parametros['id_usuario'];
        Usuario::borrarUsuario($usuarioId);

        $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
