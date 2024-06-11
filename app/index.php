<?php
// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as ResponseMw;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

require __DIR__ . '/../vendor/autoload.php';

require_once './middlewares/MW_Usuario.php';
require_once './middlewares/MW_Producto.php';
require_once './middlewares/MW_Pedido.php';
require_once './middlewares/MW_Mesa.php';

require_once './db/AccesoDatos.php';
require_once './controllers/UsuarioController.php';
require_once './controllers/MesaController.php';
require_once './controllers/PedidoController.php';
require_once './controllers/ProductoController.php';

// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add parse body
$app->addBodyParsingMiddleware();

// Routes
$app->group('/usuarios', function (RouteCollectorProxy $group) 
{
    $group->get('[/]', \UsuarioController::class . ':TraerTodos');
    $group->get('/{id_usuario}', \UsuarioController::class . ':TraerUno');
    $group->post('[/]', \UsuarioController::class . ':CargarUno')->add(MW_Usuario::class . ':ValidarRol');
    $group->put('[/]', \UsuarioController::class . ':ModificarUno');
    $group->delete('[/]', \UsuarioController::class . ':BorrarUno');
});
//   ->add($usuarioMiddleWare)
//   ->add(new Auth("admin")); // le agrego los middlewares a esta ruta especifica

$app->group('/mesas', function (RouteCollectorProxy $group) 
{
    $group->get('[/]', \MesaController::class . ':TraerTodos');
    $group->get('/{id_mesa}', \MesaController::class . ':TraerUno')->add(MW_Mesa::class . ':ValidarCodigoNoExistente');
    $group->post('[/]', \MesaController::class . ':CargarUno')->add(MW_Mesa::class . ':ValidarCodigoExistente')
    ->add(MW_Mesa::class . ':ValidarCampos');
    $group->put('[/]', \MesaController::class . ':ModificarUno')->add(MW_Mesa::class . ':CambiarEstadoMesa')->add(new MW_Usuario("mozo"))
    ->add(MW_Pedido::class . ':ValidarCodigoNoExistente')->add(MW_Mesa::class . ':ValidarCodigoNoExistente');
    $group->delete('[/]', \MesaController::class . ':BorrarUno');
});

$app->group('/productos', function (RouteCollectorProxy $group) 
{
    $group->get('[/]', \ProductoController::class . ':TraerTodos');
    $group->get('/{id_producto}', \ProductoController::class . ':TraerUno')->add(MW_Producto::class . ':ValidarCodigoNoExistente');
    $group->post('[/]', \ProductoController::class . ':CargarUno')->add(MW_Mesa::class . ':CambiarEstadoMesa')->add(new MW_Usuario("cliente"))
    ->add(MW_Mesa::class . ':ValidarCodigoNoExistente')->add(MW_Producto::class . ':ValidarTipo')->add(MW_Producto::class . ':ValidarCampos');
    $group->put('[/]', \ProductoController::class . ':ModificarUno')->add(MW_Usuario::class . ':ValidarCambioEstadoProducto')->add(MW_Usuario::class . ':ValidarRol')
    ->add(MW_Producto::class . ':ValidarCodigoNoExistente');
    $group->delete('[/]', \ProductoController::class . ':BorrarUno');
});

$app->group('/pedidos', function (RouteCollectorProxy $group) 
{
    $group->get('[/]', \PedidoController::class . ':TraerTodos')->add(new MW_Usuario("socio"));
    $group->get('/{id_pedido}', \PedidoController::class . ':TraerUno')->add(MW_Pedido::class . ':ValidarCodigoNoExistente');
    $group->post('[/]', \PedidoController::class . ':CargarUno')->add(MW_Mesa::class . ':ValidarEstadoMesa')->add(MW_Mesa::class . ':ValidarCodigoNoExistente')
    ->add(new MW_Usuario("mozo"))->add(MW_Pedido::class . ':ValidarCampos');
    $group->put('[/]', \PedidoController::class . ':ModificarUno')->add(MW_Pedido::class . ':ValidarProductosListos')->add(new MW_Usuario("mozo"))->add(MW_Usuario::class . ':ValidarRol')
    ->add(MW_Pedido::class . ':ValidarCodigoNoExistente');
    $group->delete('[/]', \PedidoController::class . ':BorrarUno');
});





$app->get('[/]', function (Request $request, Response $response) 
{    
    $payload = json_encode(array("mensaje" => "Slim Framework 4 PHP"));
    
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();