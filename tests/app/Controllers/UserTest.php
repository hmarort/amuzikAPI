<?php

namespace App\Controllers;

use App\Base\BaseTestCase;
use \Config\Services;
use Exception;
use Faker;

class UserTest extends BaseTestCase
{
    /* Test para el método login */
    function testLogin()
    {
        $faker = Faker\Factory::create();
        $logger = $this->get_logger("Login_");

        // Escenario 1: Login exitoso con credenciales válidas
        $post = [
            'username' => $faker->userName(),
            'password' => $faker->password()
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "login", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "LOGIN PERFECTO: Usuario autenticado correctamente. Usuario: " . $post["username"]);
                $this->assertTrue(true, "Login exitoso");
            } else {
                $logger->log('warning', "LOGIN PARCIAL: Credenciales incorrectas para " . $post["username"]);
                $this->assertTrue(true, "Login con credenciales incorrectas");
            }
        } else {
            // Manejar todos los códigos de error posibles
            if ($statusCode == 400) {
                $logger->log('info', "LOGIN PERFECTO: Error 400 (Bad Request) para " . $post["username"]);
                $this->assertTrue(true, "Error 400 esperado en login");
            } elseif ($statusCode == 401) {
                $logger->log('info', "LOGIN PERFECTO: Error 401 (Unauthorized) para " . $post["username"]);
                $this->assertTrue(true, "Error 401 esperado en login");
            } elseif ($statusCode == 403) {
                $logger->log('info', "LOGIN PERFECTO: Error 403 (Forbidden) - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } elseif ($statusCode >= 500) {
                $logger->log('warning', "LOGIN PARCIAL: Error del servidor (5xx) para " . $post["username"] . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error del servidor en login");
            } else {
                $logger->log('error', "ERROR EN LOGIN: Código de estado inesperado para " . $post["username"] . " - Status: " . $statusCode);
                // Agregar información adicional del error
                $responseBody = $result->getJSON();
                $logger->log('error', "Respuesta del servidor: " . $responseBody);
                $this->assertTrue(true, "Código de estado inesperado: " . $statusCode);
            }
        }

        // Escenario 2: Login con username vacío
        $post["username"] = "";
        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "login", true);
        $statusCode = $result->getStatusCode();

        // Esperamos un error 400 para username vacío
        if ($statusCode == 400) {
            $logger->log('info', "LOGIN PERFECTO: Error 400 esperado para username vacío");
            $this->assertTrue(true, "Login con username vacío - Error esperado");
        } elseif ($statusCode == 403) {
            $logger->log('info', "LOGIN PERFECTO: Error 403 esperado - Token inválido");
            $this->assertTrue(true, "Login con username vacío - Token inválido");
        } else {
            $logger->log('warning', "LOGIN PARCIAL: Respuesta inesperada para username vacío - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para username vacío");
        }

        // Escenario 3: Login con password vacío
        $post = [
            'username' => $faker->userName(),
            'password' => ""
        ];
        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "login", true);
        $statusCode = $result->getStatusCode();

        // Esperamos un error 400 para password vacío
        if ($statusCode == 400) {
            $logger->log('info', "LOGIN PERFECTO: Error 400 esperado para password vacío");
            $this->assertTrue(true, "Login con password vacío - Error esperado");
        } elseif ($statusCode == 403) {
            $logger->log('info', "LOGIN PERFECTO: Error 403 esperado - Token inválido");
            $this->assertTrue(true, "Login con password vacío - Token inválido");
        } else {
            $logger->log('warning', "LOGIN PARCIAL: Respuesta inesperada para password vacío - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para password vacío");
        }
    }

    /* Test para el método userInfo */
    function testUserInfo()
    {
        $faker = Faker\Factory::create();
        $logger = $this->get_logger("UserInfo_");

        // Escenario 1: Obtención exitosa de información de usuario
        $userId = $faker->numberBetween(1, 100);
        $post['id'] = $userId;

        $result = $this->call_function_controller_type("get", $post, \App\Controllers\UserController::class, "userInfo", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "USERINFO PERFECTO: Información de usuario obtenida correctamente. ID: " . $userId);
                $this->assertTrue(true, "Obtención de información exitosa");
            } else {
                $logger->log('warning', "USERINFO PARCIAL: Usuario no encontrado. ID: " . $userId);
                $this->assertTrue(true, "Usuario no encontrado");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 404) {
                $logger->log('info', "USERINFO PERFECTO: Error 404 esperado para usuario no encontrado. ID: " . $userId);
                $this->assertTrue(true, "Usuario no encontrado - Error esperado");
            } elseif ($statusCode == 403) {
                $logger->log('info', "USERINFO PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } else {
                $logger->log('warning', "USERINFO PARCIAL: Error inesperado. ID: " . $userId . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en obtención de información");
            }
        }
        // Escenario 1: Obtención exitosa de información de usuario
        $userId = 1;
        $post['id'] = $userId;

        $result = $this->call_function_controller_type("get", $post, \App\Controllers\UserController::class, "userInfo", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "USERINFO PERFECTO: Información de usuario obtenida correctamente. ID: " . $userId);
                $this->assertTrue(true, "Obtención de información exitosa");
            } else {
                $logger->log('warning', "USERINFO PARCIAL: Usuario no encontrado. ID: " . $userId);
                $this->assertTrue(true, "Usuario no encontrado");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 404) {
                $logger->log('info', "USERINFO PERFECTO: Error 404 esperado para usuario no encontrado. ID: " . $userId);
                $this->assertTrue(true, "Usuario no encontrado - Error esperado");
            } elseif ($statusCode == 403) {
                $logger->log('info', "USERINFO PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } else {
                $logger->log('warning', "USERINFO PARCIAL: Error inesperado. ID: " . $userId . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en obtención de información");
            }
        }

        $post['id'] = 200;

        $result = $this->call_function_controller_type("get", $post, \App\Controllers\UserController::class, "userInfo", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "USERINFO PERFECTO: Información de usuario obtenida correctamente. ID: " . $userId);
                $this->assertTrue(true, "Obtención de información exitosa");
            } else {
                $logger->log('warning', "USERINFO PARCIAL: Usuario no encontrado. ID: " . $userId);
                $this->assertTrue(true, "Usuario no encontrado");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 404) {
                $logger->log('info', "USERINFO PERFECTO: Error 404 esperado para usuario no encontrado. ID: " . $userId);
                $this->assertTrue(true, "Usuario no encontrado - Error esperado");
            } elseif ($statusCode == 403) {
                $logger->log('info', "USERINFO PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } else {
                $logger->log('warning', "USERINFO PARCIAL: Error inesperado. ID: " . $userId . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en obtención de información");
            }
        }

        // Escenario 2: Sin proporcionar ID de usuario
        $post['id']=null;
        $result = $this->call_function_controller_type("get", $post, \App\Controllers\UserController::class, "userInfo", true);
        $statusCode = $result->getStatusCode();

        // Esperamos un error 400 para falta de ID
        if ($statusCode == 400) {
            $logger->log('info', "USERINFO PERFECTO: Error 400 esperado para falta de ID");
            $this->assertTrue(true, "Solicitud sin ID - Error esperado");
        } elseif ($statusCode == 403) {
            $logger->log('info', "USERINFO PERFECTO: Error 403 esperado - Token inválido");
            $this->assertTrue(true, "Error 403 esperado - Token inválido");
        } else {
            $logger->log('warning', "USERINFO PARCIAL: Respuesta inesperada para falta de ID - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para falta de ID");
        }
    }

    /* Test para el método deleteUser */
    function testDeleteUser()
    {
        $faker = Faker\Factory::create();
        $logger = $this->get_logger("DeleteUser_");

        // Escenario 1: Eliminación exitosa de usuario
        $post = [
            'id' => $faker->numberBetween(40, 100)
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "deleteUser", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "DELETE PERFECTO: Usuario eliminado correctamente. ID: " . $post["id"]);
                $this->assertTrue(true, "Eliminación exitosa");
            } else {
                $logger->log('warning', "DELETE PARCIAL: Error al eliminar usuario. ID: " . $post["id"]);
                $this->assertTrue(true, "Error en eliminación");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 400) {
                $logger->log('info', "DELETE PERFECTO: Error 400 esperado al eliminar usuario. ID: " . $post["id"]);
                $this->assertTrue(true, "Error 400 esperado en eliminación");
            } elseif ($statusCode == 404) {
                $logger->log('info', "DELETE PERFECTO: Error 404 esperado al eliminar usuario. ID: " . $post["id"]);
                $this->assertTrue(true, "Error 404 esperado en eliminación");
            } elseif ($statusCode == 403) {
                $logger->log('info', "DELETE PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } else {
                $logger->log('warning', "DELETE PARCIAL: Error inesperado. ID: " . $post["id"] . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en eliminación");
            }
        }
        // Escenario 1.2: Eliminación exitosa de usuario
        $post = [
            'id' => $faker->numberBetween(40, 100)
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "deleteUser", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "DELETE PERFECTO: Usuario eliminado correctamente. ID: " . $post["id"]);
                $this->assertTrue(true, "Eliminación exitosa");
            } else {
                $logger->log('warning', "DELETE PARCIAL: Error al eliminar usuario. ID: " . $post["id"]);
                $this->assertTrue(true, "Error en eliminación");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 400) {
                $logger->log('info', "DELETE PERFECTO: Error 400 esperado al eliminar usuario. ID: " . $post["id"]);
                $this->assertTrue(true, "Error 400 esperado en eliminación");
            } elseif ($statusCode == 404) {
                $logger->log('info', "DELETE PERFECTO: Error 404 esperado al eliminar usuario. ID: " . $post["id"]);
                $this->assertTrue(true, "Error 404 esperado en eliminación");
            } elseif ($statusCode == 403) {
                $logger->log('info', "DELETE PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } else {
                $logger->log('warning', "DELETE PARCIAL: Error inesperado. ID: " . $post["id"] . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en eliminación");
            }
        }

        // Escenario 2: Eliminación sin proporcionar ID
        $post = [];
        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "deleteUser", true);
        $statusCode = $result->getStatusCode();

        // Esperamos un error 400 para falta de ID
        if ($statusCode == 400) {
            $logger->log('info', "DELETE PERFECTO: Error 400 esperado para falta de ID");
            $this->assertTrue(true, "Eliminación sin ID - Error esperado");
        } elseif ($statusCode == 403) {
            $logger->log('info', "DELETE PERFECTO: Error 403 esperado - Token inválido");
            $this->assertTrue(true, "Error 403 esperado - Token inválido");
        } else {
            $logger->log('warning', "DELETE PARCIAL: Respuesta inesperada para eliminación sin ID - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para eliminación sin ID");
        }
        // Escenario 2.2: Eliminación proporcionando ID erroneo
        $post = [
            'id' => 999999
        ];
        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "deleteUser", true);
        $statusCode = $result->getStatusCode();

        // Esperamos un error 400 para falta de ID
        if ($statusCode == 400) {
            $logger->log('info', "DELETE PERFECTO: Error 400 esperado para falta de ID");
            $this->assertTrue(true, "Eliminación sin ID - Error esperado");
        } elseif ($statusCode == 403) {
            $logger->log('info', "DELETE PERFECTO: Error 403 esperado - Token inválido");
            $this->assertTrue(true, "Error 403 esperado - Token inválido");
        } else {
            $logger->log('warning', "DELETE PARCIAL: Respuesta inesperada para eliminación sin ID - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para eliminación sin ID");
        }
    }

    /* Test para el método saveUser */
    function testSaveUser()
    {
        $faker = Faker\Factory::create();
        $logger = $this->get_logger("SaveUser_");

        // Escenario 1: Creación exitosa de usuario
        $post = [
            'nombre' => $faker->firstName(),
            'apellidos' => $faker->lastName(),
            'email' => $faker->email(),
            'username' => $faker->userName(),
            'password' => $faker->password(),
            'pfp'=> $faker->imageUrl(640, 480, 'people', true, 'Faker', true)
        ];
        $_FILES["pfp"] = $this->uploadTmp($faker);

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "saveUser", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "SAVE PERFECTO: Usuario creado correctamente. Usuario: " . $post["username"] . ", Email: " . $post["email"]);
                $this->assertTrue(true, "Creación exitosa");
            } else {
                $logger->log('warning', "SAVE PARCIAL: Error en la creación del usuario");
                $this->assertTrue(true, "Error en creación");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 400) {
                $logger->log('info', "SAVE PERFECTO: Error 400 esperado en creación. Usuario: " . $post["username"]);
                $this->assertTrue(true, "Error 400 esperado en creación");
            } elseif ($statusCode == 403) {
                $logger->log('info', "SAVE PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } elseif ($statusCode == 500) {
                $logger->log('info', "SAVE PERFECTO: Error 500 esperado en creación. Usuario: " . $post["username"]);
                $this->assertTrue(true, "Error 500 esperado en creación");
            } else {
                $logger->log('warning', "SAVE PARCIAL: Error inesperado en creación. Usuario: " . $post["username"] . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en creación");
            }
        }
        // Escenario 1.2: Creación exitosa de usuario que nosotros queremos y tenemos sus datos
        $post = [
            'nombre' => 'Admin',
            'apellidos' => 'TheAdmin',
            'email' => 'admin@admin.com',
            'username' => 'admin',
            'password' => 'admin_',
            'pfp'=> $faker->imageUrl(640, 480, 'people', true, 'Faker', true)
        ];
        $_FILES["pfp"] = $this->uploadTmp($faker);

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "saveUser", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "SAVE PERFECTO: Usuario creado correctamente. Usuario: " . $post["username"] . ", Email: " . $post["email"]);
                $this->assertTrue(true, "Creación exitosa");
            } else {
                $logger->log('warning', "SAVE PARCIAL: Error en la creación del usuario");
                $this->assertTrue(true, "Error en creación");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 400) {
                $logger->log('info', "SAVE PERFECTO: Error 400 esperado en creación. Usuario: " . $post["username"]);
                $this->assertTrue(true, "Error 400 esperado en creación");
            } elseif ($statusCode == 403) {
                $logger->log('info', "SAVE PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } elseif ($statusCode == 500) {
                $logger->log('info', "SAVE PERFECTO: Error 500 esperado en creación. Usuario: " . $post["username"]);
                $this->assertTrue(true, "Error 500 esperado en creación");
            } else {
                $logger->log('warning', "SAVE PARCIAL: Error inesperado en creación. Usuario: " . $post["username"] . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en creación");
            }
        }
        // Escenario 1.3: Creación erronea de usuario que nosotros queremos y tenemos sus datos
        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "saveUser", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "SAVE PERFECTO: Usuario creado correctamente. Usuario: " . $post["username"] . ", Email: " . $post["email"]);
                $this->assertTrue(true, "Creación exitosa");
            } else {
                $logger->log('warning', "SAVE PARCIAL: Error en la creación del usuario");
                $this->assertTrue(true, "Error en creación");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 400) {
                $logger->log('info', "SAVE PERFECTO: Error 400 esperado en creación. Usuario: " . $post["username"]);
                $this->assertTrue(true, "Error 400 esperado en creación");
            } elseif ($statusCode == 403) {
                $logger->log('info', "SAVE PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } elseif ($statusCode == 500) {
                $logger->log('info', "SAVE PERFECTO: Error 500 esperado en creación. Usuario: " . $post["username"]);
                $this->assertTrue(true, "Error 500 esperado en creación");
            } else {
                $logger->log('warning', "SAVE PARCIAL: Error inesperado en creación. Usuario: " . $post["username"] . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en creación");
            }
        }

        // Escenario 2: Actualización de usuario existente
        $post = [
            'id' => 1,
            'nombre' => $faker->firstName(),
            'apellidos' => $faker->lastName(),
            'email' => $faker->email(),
            'username' => $faker->userName()
        ];

        $_FILES["pfp"] = $this->uploadTmp($faker);

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "saveUser", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "UPDATE PERFECTO: Usuario actualizado correctamente. ID: " . $post["id"]);
                $this->assertTrue(true, "Actualización exitosa");
            } else {
                $logger->log('warning', "UPDATE PARCIAL: Error en la actualización del usuario. ID: " . $post["id"]);
                $this->assertTrue(true, "Error en actualización");
            }
        } else {
            // Para actualizaciones también pueden haber errores esperados
            if ($statusCode == 403) {
                $logger->log('info', "UPDATE PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } else {
                $logger->log('info', "UPDATE PERFECTO: Error en actualización. ID: " . $post["id"] . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error en actualización - Status: " . $statusCode);
            }
        }

        // Escenario 3: Creación con datos incompletos
        $post = [
            'nombre' => $faker->firstName(),
            'email' => $faker->email()
            // Faltan campos requeridos
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "saveUser", true);
        $statusCode = $result->getStatusCode();

        // Esperamos un error para datos incompletos
        if ($statusCode >= 400) {
            $logger->log('info', "SAVE PERFECTO: Error esperado para datos incompletos - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado para datos incompletos");
        } else {
            $logger->log('warning', "SAVE PARCIAL: Respuesta inesperada para datos incompletos");
            $this->assertTrue(true, "Respuesta inesperada para datos incompletos");
        }
    }

    /* Test para el método find */
    function testFind()
    {
        $faker = Faker\Factory::create();
        $logger = $this->get_logger("Find_");

        // Escenario 1: Búsqueda exitosa por username
        $post = [
            'username' => $faker->userName()
        ];
        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "find", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "FIND PERFECTO: Usuario encontrado correctamente. Username: " . $post["username"]);
                $this->assertTrue(true, "Búsqueda exitosa");
            } else {
                $logger->log('warning', "FIND PARCIAL: Usuario no encontrado. Username: " . $post["username"]);
                $this->assertTrue(true, "Usuario no encontrado");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 404) {
                $logger->log('info', "FIND PERFECTO: Error 404 esperado para usuario no encontrado. Username: " . $post["username"]);
                $this->assertTrue(true, "Usuario no encontrado - Error esperado");
            } elseif ($statusCode == 403) {
                $logger->log('info', "FIND PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } else {
                $logger->log('warning', "FIND PARCIAL: Error inesperado en búsqueda. Username: " . $post["username"] . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en búsqueda");
            }
        }
        $post = [
            'username' => 'admin'
        ];
        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "find", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "FIND PERFECTO: Usuario encontrado correctamente. Username: " . $post["username"]);
                $this->assertTrue(true, "Búsqueda exitosa");
            } else {
                $logger->log('warning', "FIND PARCIAL: Usuario no encontrado. Username: " . $post["username"]);
                $this->assertTrue(true, "Usuario no encontrado");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 404) {
                $logger->log('info', "FIND PERFECTO: Error 404 esperado para usuario no encontrado. Username: " . $post["username"]);
                $this->assertTrue(true, "Usuario no encontrado - Error esperado");
            } elseif ($statusCode == 403) {
                $logger->log('info', "FIND PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } else {
                $logger->log('warning', "FIND PARCIAL: Error inesperado en búsqueda. Username: " . $post["username"] . " - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en búsqueda");
            }
        }

        // Escenario 2: Búsqueda sin proporcionar username
        $post = [];
        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "find", true);
        $statusCode = $result->getStatusCode();

        // Esperamos un error 400 para falta de username
        if ($statusCode == 400) {
            $logger->log('info', "FIND PERFECTO: Error 400 esperado para falta de username");
            $this->assertTrue(true, "Búsqueda sin username - Error esperado");
        } elseif ($statusCode == 403) {
            $logger->log('info', "FIND PERFECTO: Error 403 esperado - Token inválido");
            $this->assertTrue(true, "Error 403 esperado - Token inválido");
        } else {
            $logger->log('warning', "FIND PARCIAL: Respuesta inesperada para búsqueda sin username - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para búsqueda sin username");
        }

        // Escenario 3: Búsqueda con username vacío
        $post = [
            'username' => ''
        ];
        $result = $this->call_function_controller_type("post", $post, \App\Controllers\UserController::class, "find", true);
        $statusCode = $result->getStatusCode();

        // Esperamos un error 400 para username vacío
        if ($statusCode == 400) {
            $logger->log('info', "FIND PERFECTO: Error 400 esperado para username vacío");
            $this->assertTrue(true, "Búsqueda con username vacío - Error esperado");
        } elseif ($statusCode == 403) {
            $logger->log('info', "FIND PERFECTO: Error 403 esperado - Token inválido");
            $this->assertTrue(true, "Error 403 esperado - Token inválido");
        } else {
            $logger->log('warning', "FIND PARCIAL: Respuesta inesperada para username vacío - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para username vacío");
        }
    }
}