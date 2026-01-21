<?php

namespace Controllers;

use MVC\Router;
use Model\Usuario;
use Model\Proyecto;

class DashBoardController
{
    public static function index(Router $router)
    {
        session_start();
        isAuth();

        $id = $_SESSION['id'];

        $proyecto = Proyecto::belongsTo('propietarioId', $id);

        $router->render('dashboard/index', [
            'titulo' => 'Proyectos',
            'proyectos' => $proyecto
        ]);
    }
    public static function crear_proyecto(Router $router)
    {
        session_start();
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proyecto = new Proyecto($_POST);

            $alertas = $proyecto->validarProyecto();

            if (empty($alertas)) {
                // Generar una Url unica 
                $hash = md5(uniqid());
                $proyecto->url = $hash;

                // Almacenar el creador del proyecto 
                $proyecto->propietarioId = $_SESSION['id'];

                // Guardar proyecto 
                $proyecto->guardar();

                // Redireccionar
                header('Location: /proyecto?id=' . $proyecto->url);
            }
        }

        $router->render('dashboard/crear-proyecto', [
            'titulo' => 'Crear Proyectos',
            'alertas' => $alertas
        ]);
    }

    public static function proyecto(Router $router)
    {
        session_start();
        isAuth();

        $token = $_GET['id'];
        if (!$token)
            header('Location: /dashboard');
        // Reviar que la persona que visita el proyecto es el creador
        $proyecto = Proyecto::where('url', $token);
        if ($proyecto->propietarioId !== $_SESSION['id']) {
            header('Location: /dashboard');
        }


        $router->render('dashboard/proyecto', [
            'titulo' => $proyecto->proyecto
        ]);
    }

    public static function perfil(Router $router)
    {
        session_start();
        isAuth();
        $alertas = [];

        $usuario = Usuario::find($_SESSION['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $usuario->sincronizar($_POST);

            $alertas = $usuario->validarPerfil();

            if (empty($alertas)) {

                $existeUsuario = Usuario::where('email', $usuario->email);

                if ($existeUsuario && $existeUsuario->id !== $usuario->id) {
                    // Mensaje de error
                    Usuario::setAlerta('error', 'El Email ya está en uso');
                    $alertas = Usuario::getAlertas();
                } else {
                    // Guardar el registro
                    $usuario->guardar();

                    Usuario::setAlerta('exito', 'Perfil Actualizado Correctamente');
                    $alertas = Usuario::getAlertas();
                    // Actualizar el nuevo nombre a la barra
                    $_SESSION['nombre'] = $usuario->nombre;
                }
            }
        }

        $router->render('dashboard/perfil', [
            'titulo' => 'Perfil',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function cambiar_password(Router $router) {
        session_start();
        isAuth();

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = Usuario::find($_SESSION['id']);

            // Sincronizar con los datos del usuario
            $usuario->sincronizar($_POST);

            $alertas = $usuario->nuevo_password();

            if(empty($alertas)) {
                $resultado = $usuario->comprobar_password();

                if($resultado) {
    
                $usuario->password = $usuario->password_nuevo;
                // Eliminar propiedades no necesarias
                unset($usuario->password_actual);
                unset($usuario->password_nuevo);

                // Hashear el Nuevo Password
                $usuario->hashPassword();

                // Actualizar 
                $resultado = $usuario->guardar();

                if($resultado) {
                    Usuario::setAlerta('exito', 'Password Guardado Correctamente');
                    $alertas = $usuario->getAlertas(); 
                }
                    // Asignar el nuevo password
                } else {
                    Usuario::setAlerta('error', 'Password Incorrecto');
                    $alertas = $usuario->getAlertas(); 
                }
            }
        }

        $router->render('dashboard/cambiar-password', [
            'titulo' => 'Cambiar Password',
            'alertas' => $alertas
            
        ]);
    }
}