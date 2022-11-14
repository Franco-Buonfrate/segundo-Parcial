<?php

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;

require_once __DIR__ . "/autentificadora.php";
require_once "Usuario.php";
require_once "Auto.php";

class MW
{
    // Middleware Usuario
    public function ValidarCorreoYClave(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";
        $arrayDeParametros = $request->getParsedBody();
        $obj_respuesta = new stdClass();
        $obj_respuesta->status = 403;
        $obj = null;

        if (isset($arrayDeParametros["user"])) {
            $obj = json_decode(($arrayDeParametros["user"]));
        } else if (isset($arrayDeParametros["usuario"])) {
            $obj = json_decode(($arrayDeParametros["usuario"]));
        }

        if ($obj) {
            if (isset($obj->correo) && isset($obj->clave)) {
                $response = $handler->handle($request);
                $contenidoAPI = (string) $response->getBody();
                $api_respuesta = json_decode($contenidoAPI);
                $obj_respuesta->status = $api_respuesta->status;
            } else {
                $mensaje_error = "Parametros faltantes: \n";
                if (!isset($obj->correo)) {
                    $mensaje_error .= "- correo \n";
                }
                if (!isset($obj->clave)) {
                    $mensaje_error .= "- clave \n";
                }
                $obj_respuesta->mensaje = $mensaje_error;
                $contenidoAPI = json_encode($obj_respuesta);
            }
        } else {
            $obj_respuesta->mensaje = "No se envio el obj json 'user' o 'usuario";
            $contenidoAPI = json_encode($obj_respuesta);
        }

        $response = new ResponseMW();
        $response = $response->withStatus($obj_respuesta->status);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function ValidarParametrosVacios(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";
        $arrayDeParametros = $request->getParsedBody();
        $obj_respuesta = new stdClass();
        $obj_respuesta->status = 409;
        $obj = null;

        if (isset($arrayDeParametros["user"])) {
            $obj = json_decode(($arrayDeParametros["user"]));
        } else if (isset($arrayDeParametros["usuario"])) {
            $obj = json_decode(($arrayDeParametros["usuario"]));
        }

        if ($obj->correo != "" && $obj->clave != "") {
            $response = $handler->handle($request);
            $contenidoAPI = (string) $response->getBody();
            $api_respuesta = json_decode($contenidoAPI);
            $obj_respuesta->status = $api_respuesta->status;
        } else {
            $mensaje_error = "Parametros vacios: \n";
            if ($obj->correo == "") {
                $mensaje_error .= "- correo \n";
            }
            if ($obj->clave == "") {
                $mensaje_error .= "- clave \n";
            }
            $obj_respuesta->mensaje = $mensaje_error;
            $contenidoAPI = json_encode($obj_respuesta);
        }

        $response = new ResponseMW();
        $response = $response->withStatus($obj_respuesta->status);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function VerificarSiExisteUsuario(Request $request, RequestHandler $handler): ResponseMW
    {
        $arrayDeParametros = $request->getParsedBody();
        $obj_respuesta = new stdClass();
        $obj_respuesta->mensaje = "El usuario no existe!";
        $obj_respuesta->status = 403;
        $obj = null;

        if (isset($arrayDeParametros["user"])) {
            $obj = json_decode(($arrayDeParametros["user"]));
        } else if (isset($arrayDeParametros["usuario"])) {
            $obj = json_decode(($arrayDeParametros["usuario"]));
        }

        if ($obj) {
            if (Usuario::TraerUsuario($obj)) {
                $response = $handler->handle($request);
                $contenidoAPI = (string) $response->getBody();
                $api_respuesta = json_decode($contenidoAPI);
                $obj_respuesta->status = $api_respuesta->status;
            } else {
                $contenidoAPI = json_encode($obj_respuesta);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus($obj_respuesta->status);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function VerificarCorreo(Request $request, RequestHandler $handler): ResponseMW
    {
        $arrayDeParametros = $request->getParsedBody();
        $obj_respuesta = new stdClass();
        $obj_respuesta->mensaje = "El correo existe!";
        $obj_respuesta->status = 403;
        $obj = null;

        if (isset($arrayDeParametros["user"])) {
            $obj = json_decode(($arrayDeParametros["user"]));
        } else if (isset($arrayDeParametros["usuario"])) {
            $obj = json_decode(($arrayDeParametros["usuario"]));
        }

        if ($obj) {
            if (!Usuario::TraerUsuarioPorCorreo($obj->correo)) {
                $response = $handler->handle($request);
                $contenidoAPI = (string) $response->getBody();
                $api_respuesta = json_decode($contenidoAPI);
                $obj_respuesta->status = $api_respuesta->status;
            } else {
                $contenidoAPI = json_encode($obj_respuesta);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus($obj_respuesta->status);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function VerificarRangoPrecio(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";
        $arrayDeParametros = $request->getParsedBody();
        $obj_respuesta = new stdClass();
        $obj_respuesta->status = 409;
        $obj = null;

        if (isset($arrayDeParametros["auto"])) {
            $obj = json_decode(($arrayDeParametros["auto"]));
        } else if (isset($arrayDeParametros["Auto"])) {
            $obj = json_decode(($arrayDeParametros["Auto"]));
        }

        if ($obj) {
            if ($obj->precio>=50000 && $obj->precio<=600000 && $obj->color!="azul") {
                $response = $handler->handle($request);
                $contenidoAPI = (string) $response->getBody();
                $api_respuesta = json_decode($contenidoAPI);
                $obj_respuesta->status = $api_respuesta->status;
            } else {
                $mensaje_error = "ERROR: \n";
                if ($obj->precio<50000) {
                    $mensaje_error .= "El precio es muy bajo \n";
                }
                if ($obj->precio>600000) {
                    $mensaje_error .= "El precio es muy alto \n";
                }
                if ($obj->color == "azul") {
                    $mensaje_error .= "El color es incorrecto \n";
                }
                $obj_respuesta->mensaje = $mensaje_error;
                $contenidoAPI = json_encode($obj_respuesta);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus($obj_respuesta->status);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function VerificarToken(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";
        $obj_respuesta = new stdClass();
        $obj_respuesta->status = 409;

        if (isset($request->getHeader("token")[0])) {
            $token = $request->getHeader("token")[0];
            $datos_token = Autentificadora::verificarJWT($token);
            if($datos_token->verificado)
            {
                $response = $handler->handle($request);
                $contenidoAPI = (string) $response->getBody();
                $api_respuesta = json_decode($contenidoAPI);
                $obj_respuesta->status = $api_respuesta->status;
            }
            else
            {
                $obj_respuesta->mensaje = "Token invalido";
                $contenidoAPI = json_encode($obj_respuesta);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus($obj_respuesta->status);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function VerificarPropietario(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";
        $obj_respuesta = new stdClass();
        $obj_respuesta->status = 409;

        if (isset($request->getHeader("token")[0])) {
            $token = $request->getHeader("token")[0];
            $datos_token = Autentificadora::obtenerPayLoad($token);
            if($datos_token->exito)
            {
                $usuario_token = $datos_token->payload->data;
            }

            if($datos_token->exito && $usuario_token->perfil == "propietario")
            {
                $response = $handler->handle($request);
                $contenidoAPI = (string) $response->getBody();
                $api_respuesta = json_decode($contenidoAPI);
                $obj_respuesta->status = $api_respuesta->status;
            }
            else
            {
                if($datos_token->exito)
                {
                    $obj_respuesta->mensaje = "Usuario no autorizado para realizar la accion. {$usuario_token->nombre} - {$usuario_token->apellido} - {$usuario_token->perfil}";
                }
                else
                {
                    $obj_respuesta->mensaje = "Token invalido";
                }
                $contenidoAPI = json_encode($obj_respuesta);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus($obj_respuesta->status);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function VerificarEncargado(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";
        $obj_respuesta = new stdClass();
        $obj_respuesta->mensaje = "";
        $obj_respuesta->status = 409;

        if (isset($request->getHeader("token")[0])) {
            $token = $request->getHeader("token")[0];
            $datos_token = Autentificadora::obtenerPayLoad($token);

            if($datos_token->exito)
            {
                $usuario_token = $datos_token->payload->data;
            }

            if($datos_token->exito && $usuario_token->perfil == "encargado")
            {
                $response = $handler->handle($request);
                $contenidoAPI = (string) $response->getBody();
                $api_respuesta = json_decode($contenidoAPI);
                $obj_respuesta->status = $api_respuesta->status;
                $obj_respuesta->encargado = true;
                $obj_respuesta->mensaje = "Usuario Autorizado.";
            }
            else
            {
                if($datos_token->exito)
                {
                    $obj_respuesta->mensaje = "Usuario no autorizado para realizar la accion. {$usuario_token->nombre} - {$usuario_token->apellido} - {$usuario_token->perfil}";
                }
                else
                {
                    $obj_respuesta->mensaje = "Token invalido";
                }
                $contenidoAPI = json_encode($obj_respuesta);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus($obj_respuesta->status);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ListadoParaEncargado(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";

        if (isset($request->getHeader("token")[0])) {
            $token = $request->getHeader("token")[0];

            $datos_token = Autentificadora::obtenerPayLoad($token);

            if($datos_token->exito)
            {
                $usuario_token = $datos_token->payload->data;
                $perfil_usuario = $usuario_token->perfil;
            }

            if($datos_token->exito && $perfil_usuario == "encargado")
            {
                $api_respuesta = json_decode($contenidoAPI);
                $array_autos = json_decode($api_respuesta->tabla);

                foreach ($array_autos as $auto) {
                    unset($auto->id);
                }

                $contenidoAPI = json_encode($array_autos);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus(200);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function ListadoParaEmpleado(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";

        if (isset($request->getHeader("token")[0])) {
            $token = $request->getHeader("token")[0];
            
            $datos_token = Autentificadora::obtenerPayLoad($token);

            if($datos_token->exito)
            {
                $usuario_token = $datos_token->payload->data;
                $perfil_usuario = $usuario_token->perfil;
            }

            if($datos_token->exito && $perfil_usuario == "empleado")
            {
                $api_respuesta = json_decode($contenidoAPI);
                $array_autos = json_decode($api_respuesta->tabla);

                $colores = [];

                foreach ($array_autos as $item) {
                    array_push($colores, $item->color);
                }

                $cantColores = array_count_values($colores);

                $obj_respuesta = new stdClass();
                $obj_respuesta->mensaje = "Hay " . count($cantColores) . " colores distintos en el listado de autos.";
                $obj_respuesta->colores = $cantColores;

                $contenidoAPI = json_encode($array_autos);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus(200);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function ListadoParaPropietario(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";
        $id = isset($request->getHeader("id_auto")[0]) ? $request->getHeader("id_auto")[0] : null;

        if (isset($request->getHeader("token")[0])) {
            $token = $request->getHeader("token")[0];

            $datos_token = Autentificadora::obtenerPayLoad($token);
            $usuario_token = $datos_token->payload->data;
            $perfil_usuario = $usuario_token->perfil;

            $response = $handler->handle($request);
            $contenidoAPI = (string) $response->getBody();

            if ($perfil_usuario == "propietario") {
                $api_respuesta = json_decode($contenidoAPI);
                $array_autos = json_decode($api_respuesta->tabla);

                if ($id != null) {
                    foreach ($array_autos as $auto) {
                        if ($auto->id == $id) {
                            $array_autos = $auto; // el array pasa a ser un solo obj json
                            break;
                        }
                    }
                }

                $contenidoAPI = json_encode($array_autos);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus(200);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ListadoDeUsuariosAEncargado(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";

        if (isset($request->getHeader("token")[0])) {
            $token = $request->getHeader("token")[0];

            $datos_token = Autentificadora::obtenerPayLoad($token);
            if($datos_token->exito)
            {
                $usuario_token = $datos_token->payload->data;
                $perfil_usuario = $usuario_token->perfil;
            }

            $response = $handler->handle($request);
            $contenidoAPI = (string) $response->getBody();

            if ($datos_token->exito && $perfil_usuario == "encargado") {
                $api_respuesta = json_decode($contenidoAPI);
                $array_usuarios = json_decode($api_respuesta->tabla);

                foreach ($array_usuarios as $usuario) {
                    unset($usuario->id);
                    unset($usuario->clave);
                }

                $contenidoAPI = json_encode($array_usuarios);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus(200);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ListadoDeUsuariosAEmpleado(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";

        if (isset($request->getHeader("token")[0])) {
            $token = $request->getHeader("token")[0];

            $datos_token = Autentificadora::obtenerPayLoad($token); 
            if($datos_token->exito)
            {
                $usuario_token = $datos_token->payload->data;
                $perfil_usuario = $usuario_token->perfil;
            }   

            $response = $handler->handle($request);
            $contenidoAPI = (string) $response->getBody();

            if ($datos_token->exito && $perfil_usuario == "empleado") {
                $api_respuesta = json_decode($contenidoAPI);
                $array_usuarios = json_decode($api_respuesta->tabla);

                foreach ($array_usuarios as $usuario) {
                    unset($usuario->id);
                    unset($usuario->clave);
                    unset($usuario->correo);
                    unset($usuario->perfil);
                }

                $contenidoAPI = json_encode($array_usuarios);
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus(200);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ListadoDeUsuariosAPropietario(Request $request, RequestHandler $handler): ResponseMW
    {
        $contenidoAPI = "";
        $apellido = isset($request->getHeader("apellido")[0]) ? $request->getHeader("apellido")[0] : null;

        if (isset($request->getHeader("token")[0])) {
            $token = $request->getHeader("token")[0];

            $datos_token = Autentificadora::obtenerPayLoad($token);
            if($datos_token->exito)
            {
                $usuario_token = $datos_token->payload->data;
                $perfil_usuario = $usuario_token->perfil;
            }

            $response = $handler->handle($request);
            $contenidoAPI = (string) $response->getBody();
            
            if ($datos_token->exito && $perfil_usuario == "propietario") {
                $api_respuesta = json_decode($contenidoAPI);
                $array_usuarios = json_decode($api_respuesta->tabla);

                $apellidosIguales = [];
                $todosLosApellidos = [];

                if($apellido != NULL){

                    foreach($array_usuarios as $item){
                        if($item->apellido == $apellido){
                            array_push($apellidosIguales,$item);
                        }
                    }

                    if(count($apellidosIguales) == 0){
                        $cantidad = 0;
                    }else{
                        $cantidad = count($apellidosIguales);
                    }
                    
                    $contenidoAPI = "La cantidad de apellidos iguales es : {$cantidad} - {$apellido}";
                } else {
                    
                    foreach($array_usuarios as $item){
                        array_push($todosLosApellidos,$item->apellido);
                    }

                    $todosLosApellidos = array_count_values($todosLosApellidos);
                    $contenidoAPI = json_encode($todosLosApellidos);
                }         
            }
        }

        $response = new ResponseMW();
        $response = $response->withStatus(200);
        $response->getBody()->write($contenidoAPI);
        return $response->withHeader('Content-Type', 'application/json');
    }

}


