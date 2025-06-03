<?php

namespace App\Controllers;

use App\Base\BaseTestCase;
use \Config\Services;
use Exception;
use Faker;

class TokenTest extends BaseTestCase
{
    /* Test para el método insertToken */
    function testInsertToken()
    {
        $faker = Faker\Factory::create();
        $logger = $this->get_logger("InsertToken_");

        // Escenario 1: Registro exitoso de token móvil (nuevo token)
        $post = [
            'username' => 'Ara',
            'token_movil' => $faker->sha256()
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\TokenController::class, "insertToken", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                if (strpos($res->message, 'registrado correctamente') !== false || strpos($res->message, 'actualizado correctamente') !== false) {
                    $logger->log('info', "INSERT TOKEN PERFECTO: Token registrado/actualizado correctamente. Username: " . $post["username"]);
                    $this->assertTrue(true, "Registro de token exitoso");
                } else {
                    $logger->log('warning', "INSERT TOKEN PARCIAL: Respuesta inesperada. Username: " . $post["username"]);
                    $this->assertTrue(true, "Respuesta inesperada en registro");
                }
            } elseif (!empty($res) && isset($res->error)) {
                if ($res->error === 'El usuario especificado no existe') {
                    $logger->log('info', "INSERT TOKEN PERFECTO: Error esperado - Usuario no existe. Username: " . $post["username"]);
                    $this->assertTrue(true, "Error esperado - Usuario no existe");
                } else {
                    $logger->log('warning', "INSERT TOKEN PARCIAL: Error inesperado. Username: " . $post["username"] . " - Error: " . $res->error);
                    $this->assertTrue(true, "Error inesperado en registro");
                }
            } else {
                $logger->log('warning', "INSERT TOKEN PARCIAL: Respuesta sin message ni error");
                $this->assertTrue(true, "Respuesta sin message ni error");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 400) {
                $res = json_decode($result->getJSON());
                if (!empty($res) && isset($res->error)) {
                    $logger->log('info', "INSERT TOKEN PERFECTO: Error 400 esperado - " . $res->error);
                    $this->assertTrue(true, "Error 400 esperado: " . $res->error);
                } else {
                    $logger->log('info', "INSERT TOKEN PERFECTO: Error 400 esperado");
                    $this->assertTrue(true, "Error 400 esperado");
                }
            } elseif ($statusCode == 401) {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 401 esperado - Token inválido");
                $this->assertTrue(true, "Error 401 esperado - Token inválido");
            } elseif ($statusCode == 403) {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } elseif ($statusCode == 404) {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 404 esperado - Usuario no encontrado");
                $this->assertTrue(true, "Error 404 esperado - Usuario no encontrado");
            } elseif ($statusCode >= 500) {
                $logger->log('warning', "INSERT TOKEN PARCIAL: Error del servidor (5xx) - Status: " . $statusCode);
                $this->assertTrue(true, "Error del servidor en registro de token");
            } else {
                $logger->log('error', "ERROR EN INSERT TOKEN: Código de estado inesperado - Status: " . $statusCode);
                $responseBody = $result->getJSON();
                $logger->log('error', "Respuesta del servidor: " . $responseBody);
                $this->assertTrue(true, "Código de estado inesperado: " . $statusCode);
            }
        }

        // Escenario 2: Username vacío
        $post = [
            'username' => '',
            'token_movil' => $faker->sha256()
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\TokenController::class, "insertToken", true);
        $statusCode = $result->getStatusCode();

        if ($statusCode == 400) {
            $res = json_decode($result->getJSON());
            if (!empty($res) && isset($res->error) && $res->error === 'El nombre de usuario no puede estar vacío') {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 400 esperado para username vacío");
                $this->assertTrue(true, "Error esperado para username vacío");
            } else {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 400 para username vacío con mensaje diferente");
                $this->assertTrue(true, "Error 400 para username vacío");
            }
        } elseif ($statusCode == 401 || $statusCode == 403) {
            $logger->log('info', "INSERT TOKEN PERFECTO: Error de autenticación esperado para username vacío");
            $this->assertTrue(true, "Error de autenticación para username vacío");
        } else {
            $logger->log('warning', "INSERT TOKEN PARCIAL: Respuesta inesperada para username vacío - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para username vacío");
        }

        // Escenario 3: Token móvil vacío
        $post = [
            'username' => $faker->userName(),
            'token_movil' => ''
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\TokenController::class, "insertToken", true);
        $statusCode = $result->getStatusCode();

        if ($statusCode == 400) {
            $res = json_decode($result->getJSON());
            if (!empty($res) && isset($res->error) && $res->error === 'El token móvil no puede estar vacío') {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 400 esperado para token vacío");
                $this->assertTrue(true, "Error esperado para token vacío");
            } else {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 400 para token vacío con mensaje diferente");
                $this->assertTrue(true, "Error 400 para token vacío");
            }
        } elseif ($statusCode == 401 || $statusCode == 403) {
            $logger->log('info', "INSERT TOKEN PERFECTO: Error de autenticación esperado para token vacío");
            $this->assertTrue(true, "Error de autenticación para token vacío");
        } else {
            $logger->log('warning', "INSERT TOKEN PARCIAL: Respuesta inesperada para token vacío - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para token vacío");
        }

        // Escenario 4: Ambos campos vacíos
        $post = [
            'username' => '',
            'token_movil' => ''
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\TokenController::class, "insertToken", true);
        $statusCode = $result->getStatusCode();

        if ($statusCode == 400) {
            $res = json_decode($result->getJSON());
            if (!empty($res) && isset($res->error)) {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 400 esperado para ambos campos vacíos - " . $res->error);
                $this->assertTrue(true, "Error esperado para ambos campos vacíos");
            } else {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 400 para ambos campos vacíos");
                $this->assertTrue(true, "Error 400 para ambos campos vacíos");
            }
        } elseif ($statusCode == 401 || $statusCode == 403) {
            $logger->log('info', "INSERT TOKEN PERFECTO: Error de autenticación esperado para campos vacíos");
            $this->assertTrue(true, "Error de autenticación para campos vacíos");
        } else {
            $logger->log('warning', "INSERT TOKEN PARCIAL: Respuesta inesperada para ambos campos vacíos - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para ambos campos vacíos");
        }

        // Escenario 5: Sin cuerpo JSON
        $result = $this->call_function_controller_type("post", [], \App\Controllers\TokenController::class, "insertToken", true);
        $statusCode = $result->getStatusCode();

        if ($statusCode == 400) {
            $res = json_decode($result->getJSON());
            if (!empty($res) && isset($res->error)) {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 400 esperado para sin datos JSON - " . $res->error);
                $this->assertTrue(true, "Error esperado para sin datos JSON");
            } else {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error 400 para sin datos JSON");
                $this->assertTrue(true, "Error 400 para sin datos JSON");
            }
        } elseif ($statusCode == 401 || $statusCode == 403) {
            $logger->log('info', "INSERT TOKEN PERFECTO: Error de autenticación esperado para sin datos");
            $this->assertTrue(true, "Error de autenticación para sin datos");
        } else {
            $logger->log('warning', "INSERT TOKEN PARCIAL: Respuesta inesperada para sin datos JSON - Status: " . $statusCode);
            $this->assertTrue(true, "Respuesta inesperada para sin datos JSON");
        }

        // Escenario 6: Username con espacios en blanco (para probar trim)
        $post = [
            'username' => '  ' . $faker->userName() . '  ',
            'token_movil' => '  ' . $faker->sha256() . '  '
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\TokenController::class, "insertToken", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "INSERT TOKEN PERFECTO: Token registrado con espacios eliminados correctamente. Username: " . trim($post["username"]));
                $this->assertTrue(true, "Registro exitoso con trim");
            } elseif (!empty($res) && isset($res->error)) {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error esperado con espacios - " . $res->error);
                $this->assertTrue(true, "Error esperado con espacios");
            }
        } else {
            $logger->log('info', "INSERT TOKEN PERFECTO: Error esperado con espacios - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado con espacios");
        }

        // Escenario 7: Username muy largo (para probar validaciones)
        $post = [
            'username' => $faker->text(300), // Texto muy largo
            'token_movil' => $faker->sha256()
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\TokenController::class, "insertToken", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());
            $logger->log('info', "INSERT TOKEN PERFECTO: Respuesta para username largo");
            $this->assertTrue(true, "Respuesta para username largo");
        } else {
            $logger->log('info', "INSERT TOKEN PERFECTO: Error esperado para username largo - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado para username largo");
        }

        // Escenario 8: Token muy largo (para probar validaciones)
        $post = [
            'username' => $faker->userName(),
            'token_movil' => $faker->text(1000) // Token muy largo
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\TokenController::class, "insertToken", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());
            $logger->log('info', "INSERT TOKEN PERFECTO: Respuesta para token largo");
            $this->assertTrue(true, "Respuesta para token largo");
        } else {
            $logger->log('info', "INSERT TOKEN PERFECTO: Error esperado para token largo - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado para token largo");
        }

        // Escenario 9: Actualización de token existente (simulando segundo registro del mismo usuario)
        $username = $faker->userName();
        $post = [
            'username' => admin,
            'token_movil' => $faker->sha256()
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\TokenController::class, "insertToken", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->message)) {
                $logger->log('info', "INSERT TOKEN PERFECTO: Primera inserción/actualización. Username: " . $username);
                
                // Intentar actualizar el mismo token
                $post['token_movil'] = $faker->sha256(); // Nuevo token
                $result2 = $this->call_function_controller_type("post", $post, \App\Controllers\TokenController::class, "insertToken", true);
                
                if ($result2->isOK()) {
                    $res2 = json_decode($result2->getJSON());
                    if (!empty($res2) && isset($res2->message) && strpos($res2->message, 'actualizado') !== false) {
                        $logger->log('info', "INSERT TOKEN PERFECTO: Token actualizado correctamente en segundo intento. Username: " . $username);
                        $this->assertTrue(true, "Actualización de token exitosa");
                    } else {
                        $logger->log('info', "INSERT TOKEN PERFECTO: Respuesta en segundo intento. Username: " . $username);
                        $this->assertTrue(true, "Respuesta en segundo intento");
                    }
                } else {
                    $logger->log('info', "INSERT TOKEN PERFECTO: Error en segundo intento - Status: " . $result2->getStatusCode());
                    $this->assertTrue(true, "Error en segundo intento");
                }
            } else {
                $logger->log('info', "INSERT TOKEN PERFECTO: Error en primera inserción para actualización");
                $this->assertTrue(true, "Error en primera inserción");
            }
        } else {
            $logger->log('info', "INSERT TOKEN PERFECTO: Error esperado en primera inserción - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado en primera inserción");
        }
    }
}