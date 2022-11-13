<?php

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;

require_once "accesoDatos.php";
require_once "Usuario.php";
require_once __DIR__ . "/autentificadora.php";

class Auto 
{
    public string $marca;
    public string $color;
    public string $modelo;
    public float $precio;

    // IMPLEMENTACION DE INTERFACE ICRUDSLIM

    public function AgregarUno(Request $request, Response $response, array $args): Response
    {
        $parametros = $request->getParsedBody();

        $obj_respuesta = new stdclass();
        $obj_respuesta->exito = false;
        $obj_respuesta->mensaje = "No se pudo agregar";
        $obj_respuesta->status = 418;

        if (isset($parametros["auto"])) {
            $obj = json_decode($parametros["auto"]);

            $auto = new Auto();
            $auto->marca = $obj->marca;
            $auto->color = $obj->color;
            $auto->modelo= $obj->modelo;
            $auto->precio = $obj->precio;

            $id_agregado = $auto->Agregar();
            $auto->id = $id_agregado;

            if ($id_agregado) {
                $obj_respuesta->exito = true;
                $obj_respuesta->mensaje = "Se agrego con exito";
                $obj_respuesta->status = 200;
            }
        }

        $newResponse = $response->withStatus($obj_respuesta->status);
        $newResponse->getBody()->write(json_encode($obj_respuesta));

        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos(Request $request, Response $response, array $args): Response
    {
        $obj_respuesta = new stdClass();
        $obj_respuesta->exito = false;
        $obj_respuesta->mensaje = "No se encontro";
        $obj_respuesta->tabla = "{}";
        $obj_respuesta->status = 424;

        $autos = Auto::Traer();

        if (count($autos)) {
            $obj_respuesta->exito = true;
            $obj_respuesta->mensaje = "se encontro";
            $obj_respuesta->tabla = json_encode($autos);
            $obj_respuesta->status = 200;
        }

        $newResponse = $response->withStatus($obj_respuesta->status);
        $newResponse->getBody()->write(json_encode($obj_respuesta));
        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno(Request $request, Response $response, array $args): Response
    {
        $obj_respuesta = new stdclass();
        $obj_respuesta->exito = false;
        $obj_respuesta->mensaje = "No se pudo borrar";
        $obj_respuesta->status = 418;

        if (
            isset($request->getHeader("token")[0]) &&
            isset($request->getHeader("id_auto")[0])
        ) {
            $token = $request->getHeader("token")[0];
            $id = $request->getHeader("id_auto")[0];

            $datos_token = Autentificadora::obtenerPayLoad($token);
            $usuario_token = $datos_token->payload->data;
            $perfil_usuario = $usuario_token->perfil;

            if ($perfil_usuario == "propietario") {
                if (Auto::BorrarDB($id)) {
                    $obj_respuesta->exito = true;
                    $obj_respuesta->mensaje = "se ha borrado con exito";
                    $obj_respuesta->status = 200;
                } else {
                    $obj_respuesta->mensaje = "No existe en el listado";
                }
            } else {
                if($datos_token->exito == true)
                {
                    $obj_respuesta->mensaje = "Usuario no autorizado para realizar la accion. {$usuario_token->nombre} - {$usuario_token->apellido} - {$usuario_token->perfil}";
                }
            }
        }

        $newResponse = $response->withStatus($obj_respuesta->status);
        $newResponse->getBody()->write(json_encode($obj_respuesta));
        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno(Request $request, Response $response, array $args): Response
    {
        $obj_respuesta = new stdclass();
        $obj_respuesta->exito = false;
        $obj_respuesta->mensaje = "No se pudo modificar";
        $obj_respuesta->status = 418;

        if (
            isset($request->getHeader("token")[0]) &&
            isset($request->getHeader("auto")[0])
        ) {
            $token = $request->getHeader("token")[0];
            $obj_json = json_decode($request->getHeader("auto")[0]);
            $idModificar = $request->getHeader("id_auto")[0];
            $datos_token = Autentificadora::obtenerPayLoad($token);
            $usuario_token = $datos_token->payload->data;
            $perfil_usuario = $usuario_token->perfil;

            if ($perfil_usuario == "encargado") {
                if ($auto = Auto::TraerPorId($idModificar)) {
                    $auto->color = $obj_json->color;
                    $auto->marca = $obj_json->marca;
                    $auto->precio = $obj_json->precio;
                    $auto->modelo = $obj_json->modelo;
                    if ($auto->Modificar()) {
                        $obj_respuesta->exito = true;
                        $obj_respuesta->mensaje = "se ha modificado con exito";
                        $obj_respuesta->status = 200;
                    } else {
                    }
                } else {
                    $obj_respuesta->mensaje = "No se ha encontrado en el listado";
                }
            } else {
                if($datos_token->exito)
                {
                    $obj_respuesta->mensaje = "Usuario no autorizado para realizar la accion. {$usuario_token->nombre} - {$usuario_token->apellido} - {$usuario_token->perfil}";
                }
            }
        }

        $newResponse = $response->withStatus($obj_respuesta->status);
        $newResponse->getBody()->write(json_encode($obj_respuesta));
        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    
    // METODOS PARA INTERACTUAR CON EL ORIGEN DE LOS DATOS, EN ESTE CASO UNA BASE DE DATOS

    public function Agregar()
    {
        $accesoDatos = AccesoDatos::obtenerObjetoAccesoDatos();

        $consulta = $accesoDatos->retornarConsulta(
            "INSERT INTO autos (color, marca, precio, modelo) 
             VALUES(:color, :marca, :precio, :modelo)"
        );

        $consulta->bindValue(":color", $this->color, PDO::PARAM_STR);
        $consulta->bindValue(":marca", $this->marca, PDO::PARAM_STR);
        $consulta->bindValue(":precio", $this->precio, PDO::PARAM_INT);
        $consulta->bindValue(":modelo", $this->modelo, PDO::PARAM_STR);
        $consulta->execute();

        return $accesoDatos->retornarUltimoIdInsertado();
    }

    public static function Traer()
    {
        $accesoDatos = AccesoDatos::obtenerObjetoAccesoDatos();
        $consulta = $accesoDatos->retornarConsulta(
            "SELECT * FROM autos"
        );
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, "auto");
    }

    public static function TraerPorId(int $id)
    {
        $accesoDatos = AccesoDatos::obtenerObjetoAccesoDatos();
        $consulta = $accesoDatos->retornarConsulta(
            "SELECT * FROM autos  
             WHERE id = :id"
        );
        $consulta->bindValue(":id", $id, PDO::PARAM_INT);
        $consulta->execute();

        $auto = $consulta->fetchObject('auto');

        return $auto;
    }

    public static function BorrarDB(int $id)
    {
        $retorno = false;
        $accesoDatos = AccesoDatos::obtenerObjetoAccesoDatos();
        $consulta = $accesoDatos->retornarConsulta("DELETE FROM autos WHERE id = :id");
        $consulta->bindValue(":id", $id, PDO::PARAM_INT);
        $consulta->execute();

        $total_borrado = $consulta->rowCount(); // verifico las filas afectadas por la consulta
        if ($total_borrado == 1) {
            $retorno = true;
        }

        return $retorno;
    }

    public function Modificar()
    {
        $retorno = false;

        $accesoDatos = AccesoDatos::obtenerObjetoAccesoDatos();

        $consulta = $accesoDatos->retornarConsulta(
            "UPDATE autos
             SET color = :color, marca = :marca, precio = :precio, modelo = :modelo
             WHERE id = :id"
        );

        $consulta->bindValue(":id", $this->id, PDO::PARAM_INT);
        $consulta->bindValue(":color", $this->color, PDO::PARAM_STR);
        $consulta->bindValue(":marca", $this->marca, PDO::PARAM_STR);
        $consulta->bindValue(":precio", $this->precio, PDO::PARAM_INT);
        $consulta->bindValue(":modelo", $this->modelo, PDO::PARAM_STR);
        $consulta->execute();

        $total_modificado = $consulta->rowCount(); // verifico las filas afectadas por la consulta
        if ($total_modificado == 1) {
            $retorno = true;
        }

        return $retorno;
    }
}
