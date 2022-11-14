<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use \Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . "/../poo/Usuario.php";
require_once __DIR__ . "/../poo/Auto.php";
require_once __DIR__ . "/../poo/MW.php";


$app = AppFactory::create();

$app->post('/usuarios', \Usuario::class . ':AgregarUno')
->add(\MW::class . '::VerificarCorreo')
->add(\MW::class . '::ValidarParametrosVacios')
->add(\MW::class . ':ValidarCorreoYClave');

$app->get('/', \Usuario::class . ':TraerTodos')
->add(\MW::class . ':ListadoDeUsuariosAEncargado')
->add(\MW::class . ':ListadoDeUsuariosAEmpleado')
->add(\MW::class . ':ListadoDeUsuariosAPropietario');

$app->post("/", \Auto::class . ':AgregarUno')
  ->add(\MW::class . ':VerificarRangoPrecio');

$app->get('/autos', \Auto::class . ':TraerTodos');

$app->group('/autos', function (RouteCollectorProxy $grupo){
  $grupo->get('/', \Auto::class . ':TraerTodos')
  ->add(\MW::class . ':ListadoParaEncargado')
  ->add(\MW::class . ':ListadoParaEmpleado')
  ->add(\MW::class . '::ListadoParaPropietario');
});

$app->post("/login", \Usuario::class . ':VerificarUsuario')
  ->add(\MW::class . ':VerificarSiExisteUsuario')
  ->add(\MW::class . '::ValidarParametrosVacios')
  ->add(\MW::class . ':ValidarCorreoYClave');

$app->get("/login", \Usuario::class . ':ChequearJWT');

$app->delete("/", \Auto::class . ':BorrarUno')
->add(\MW::class . ':VerificarToken')
->add(\MW::class . '::VerificarPropietario');

$app->put("/", \Auto::class . ':ModificarUno')
->add(\MW::class . ':VerificarToken')
->add(\MW::class . ':VerificarEncargado');

try {
  $app->run();
} catch (Exception $e) {
  die(json_encode(array("status" => "failed", "message" => "This action is not allowed")));
}
