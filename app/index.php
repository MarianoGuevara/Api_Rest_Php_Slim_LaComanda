<?php
// php -S localhost:666 -t app
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

require __DIR__ . '/../vendor/autoload.php';
require_once './db/AccesoDatos.php';

require_once './middlewares/AutentificadorJWT.php';
require_once './middlewares/AutenticadorUsuarios.php';
require_once './middlewares/AutenticadorProductos.php';
require_once './middlewares/AutenticadorMesas.php';
require_once './middlewares/AutenticadorPedidos.php';
require_once './middlewares/AutenticadorComentarios.php';
require_once './middlewares/log_middleware.php';
require_once './middlewares/Logger.php';

require_once './controllers/UsuarioController.php';
require_once './controllers/ProductoController.php';
require_once './controllers/log_transacciones_controller.php';
require_once './controllers/MesaController.php';
require_once './controllers/PedidoController.php';
require_once './controllers/ComentarioController.php';
require_once './utilidades/class_pdf.php';

// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Instantiate App
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Add error middleware
$errorMiddleware = function ($request, $exception, $displayErrorDetails) use ($app) {
    $statusCode = 400;
    $errorMessage = $exception->getMessage();  
    $response = $app->getResponseFactory()->createResponse($statusCode);
    $response->getBody()->write(json_encode(['error' => $errorMessage]));
    return $response->withHeader('Content-Type', 'application/json');
};

$app->addErrorMiddleware(true, true, true)->setDefaultErrorHandler($errorMiddleware);

$app->get('/admin', function (Request $request, Response $response){
    $usuario = new Usuario();
    $usuario->nombre = 'admin';
    $usuario->email = 'ejemplo@gmail.com';
    $usuario->clave = '1234';
    $usuario->rol = 'socio';
    $usuario->estado = 'activo';
    $usuario->crearUsuario();
    $response->getBody()->write('Creado super usario');
    return $response->withHeader('Content-Type', 'application/json');
});


$app->group('/sesion', function (RouteCollectorProxy $group) {
    $group->post('[/]', \Logger::class.'::Loguear');

    $group->get('[/]', \Logger::class.'::Salir');
})
->add(\Logger::class.'::LimpiarCoockieUsuario');


$app->group('/usuarios', function (RouteCollectorProxy $group) {
    $group->get('[/]', \UsuarioController::class . ':TraerTodos');

    $group->get('/id', \UsuarioController::class . ':TraerUno');

    $group->post('[/]', \UsuarioController::class . ':CargarUno')
    ->add(\AutenticadorUsuario::class.':ValidarCampos');

    $group->put('[/]', \UsuarioController::class . ':ModificarUno')
    ->add(\AutenticadorUsuario::class.':ValidarCampos');

    $group->delete('[/]', \UsuarioController::class . ':BorrarUno');
})
->add(\LogMiddleware::class.':LogTransaccion')
->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol')
->add(\Logger::class.':ValidarSesionIniciada');


$app->group('/productos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \ProductoController::class.':TraerTodos')
    ->add(\LogMiddleware::class.':LogTransaccion')
    ->add(\Logger::class.':ValidarSesionIniciada');

    $group->get('/id', \ProductoController::class.':TraerUno')
    ->add(\LogMiddleware::class.':LogTransaccion')
    ->add(\Logger::class.':ValidarSesionIniciada');

    $group->post('[/]', \ProductoController::class.':CargarUno')
    ->add(\LogMiddleware::class.':LogTransaccion')
    ->add(\AutenticadorProductos::class.':ValidarCamposProductos')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol')
    ->add(\Logger::class.':ValidarSesionIniciada');

    $group->put('[/]', ProductoController::class.':ModificarUno')
    ->add(\LogMiddleware::class.':LogTransaccion')
    ->add(\AutenticadorProductos::class.':ValidarCamposProductos')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol')
    ->add(\Logger::class.':ValidarSesionIniciada');    

    $group->delete('[/]', \ProductoController::class.':BorrarUno')
    ->add(\LogMiddleware::class.':LogTransaccion')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol')
    ->add(\Logger::class.':ValidarSesionIniciada');
})
->add(\LogMiddleware::class.':LogTransaccion')
->add(\Logger::class.':ValidarSesionIniciada');


$app->group('/mesas', function (RouteCollectorProxy $group) {
    $group->get('[/]', \MesaController::class.':TraerTodos')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRolDoble');

    $group->get('/id', \MesaController::class.':TraerUno')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRolDoble');

    $group->post('[/]', \MesaController::class.':CargarUno')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRolDoble');

    $group->put('[/]', \MesaController::class.':ModificarUno')
    ->add(\AutenticadorMesas::class.':ValidarMesa')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol');

    $group->delete('[/]', \MesaController::class.':BorrarUno')
    ->add(\AutenticadorMesas::class.':ValidarMesa')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol');

    $group->get('/orden-menor-factura', \MesaController::class.':TraerOrdenadaMenorFactura')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol');

    $group->get('/facturacion-entre-fechas', \MesaController::class.':GetCobroEntreDosFechas')
    ->add(\AutenticadorMesas::class.':ValidarCamposCobroEntreFechas')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol');

    $group->post('/foto', \MesaController::class.':AsociarFoto')
    ->add(\AutenticadorMesas::class.':ValidarMesaCodigoMesa');

    $group->post('/tiempo', \MesaController::class.':TiempoRestante');

    $group->get('/estados', \MesaController::class.':MesaEstados')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol');

    $group->post('/cerrarMesa', \MesaController::class.':AdminCerrarMesa')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol');
})
->add(\LogMiddleware::class.':LogTransaccion')
->add(\Logger::class.':ValidarSesionIniciada');


$app->group('/pedidos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \PedidoController::class.':TraerTodos')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRolDoble');

    $group->get('/codigo', \PedidoController::class.':TraerUno')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRolDoble');

    $group->post('[/]', \PedidoController::class.':CargarUno')
    ->add(\AutenticadorPedidos::class.':ValidarCamposAlta')
    ->add(function ($request, $handler){
        return \AutenticadorUsuario::ValidarPermisosDeRol($request, $handler, 'mozo');
    });

    $group->put('[/]', \PedidoController::class.':ModificarUno')
    ->add(\AutenticadorPedidos::class.':ValidarCamposModificar')
    ->add(\AutenticadorPedidos::class.':ValidarEstado')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRolDoble');

    $group->delete('[/]', \PedidoController::class.':BorrarUno') // borra pedido y detalles asociados; lo cancela
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol');

    $group->get('/por/sector', \PedidoController::class.':TraerTodosPorSector');

    $group->get('/sector/preparar', \PedidoController::class.':ComenzarPreparacion');

    $group->get('/sector/preparado', \PedidoController::class.':PrepararPedido')
    ->add(\AutenticadorUsuario::class.':VerificarUsuario');

    $group->get('/entregar/pedido', \PedidoController::class.':EntregarPedidoFinalizado')
    ->add(\AutenticadorUsuario::class.':VerificarUsuario');   

    $group->get('/pedidosListos', \PedidoController::class.':ListarPedidosListos')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRolDoble');    

    $group->get('/pedidosTiempos', \PedidoController::class.':PedidosTiempos')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol'); 
})
->add(\LogMiddleware::class.':LogTransaccion')
->add(\Logger::class.':ValidarSesionIniciada');


$app->group('/cobrar', function (RouteCollectorProxy $group) {
    $group->post('[/]', \MesaController::class.':CerrarMesa')
    ->add(\AutenticadorUsuario::class.':ValidarPermisosDeRolDoble');    
})
->add(\LogMiddleware::class.':LogTransaccion')
->add(\Logger::class.':ValidarSesionIniciada');


$app->group('/archivos', function (RouteCollectorProxy $group) {
    $group->post('/cargarProductos', \ProductoController::class.'::CargarCSV');

    $group->get('/descargarPedidos', \PedidoController::class.'::DescargarCSV')
    ->add(\LogMiddleware::class.':LogTransaccion');

    $group->get('/descargarUsuarios', \UsuarioController::class.':DescargarPDF')
    ->add(\LogMiddleware::class.':LogTransaccion');
})
->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol')
->add(\Logger::class.':ValidarSesionIniciada');


$app->post('/comentar', \ComentarioController::class.':CargarUno')
->add(\AutenticadorMesas::class.':ValidarMesaCerrada') // esta de mas capaz
->add(\AutenticadorComentarios::class.':ValidarBindeo')
->add(\AutenticadorComentarios::class.':ValidarCamposComentario')
->add(\AutenticadorUsuario::class.':ValidarPermisosDeRolCliente')
->add(\LogMiddleware::class.':LogTransaccion')
->add(\Logger::class.':ValidarSesionIniciada');


$app->group('/estadisticas', function (RouteCollectorProxy $group) {
    $group->get('/promedioIngresos', \PedidoController::class.':EstadisticasCalcularPromedioIngresos');
    
    $group->get('/registro-login', \UsuarioController::class . ':ObtenerRegistroLogin');

    $group->get('/cantidad-operaciones', \LogTransaccionesController::class . ':CalcularCantidadOperaciones');

    $group->get('/cantidad-operaciones-usuarios', \LogTransaccionesController::class . ':CalcularCantidadOperacionesUsuarios');

    $group->get('/cantidad-operaciones-uno', \LogTransaccionesController::class . ':CalcularCantidadOperacionesUno');
    
    $group->get('/mas-vendidos', \PedidoController::class.':PedidoMasVendido');
    
    $group->get('/menos-vendidos', \PedidoController::class.':PedidoMenosVendido');
    
    $group->get('/fuera-de-tiempo', \PedidoController::class.':TraerFueraDeTiempo');
    
    $group->get('/mas-usada', \MesaController::class.':MesaMasUsada');
    
    $group->get('/menos-usada', \MesaController::class.':MesaMenosUsada');
    
    $group->get('/mas-facturo', \MesaController::class.':MasFacturo');
    
    $group->get('/menos-facturo', \MesaController::class.':MenosFacturo');
    
    $group->get('/mesa-pedido-caro', \MesaController::class.':MesaConPedidoMasCaro');

    $group->get('/mesa-pedido-barato', \MesaController::class.':MesaConPedidoMasBarato');

    $group->get('/mejores-comentarios', \ComentarioController::class.':TraerMejores');

    $group->get('/peores-comentarios', \ComentarioController::class.':TraerPeores');
})
->add(\AutenticadorUsuario::class.':ValidarFecha')
->add(\LogMiddleware::class.':LogTransaccion')
->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol')
->add(\Logger::class.':ValidarSesionIniciada');


$app->get('/transacciones', \LogTransaccionesController::class.':GetTransacciones')
->add(\AutenticadorUsuario::class.':ValidarPermisosDeRol')
->add(\Logger::class.':ValidarSesionIniciada');

$app->run();