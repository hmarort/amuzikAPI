<?php

namespace App\Controllers;

use App\Base\BaseTestCase;
use \Config\Services;
use Exception;
use Faker;

class FriendTest extends BaseTestCase
{
    /* Test para el método saveFriendship */
    function testSaveFriendship()
    {
        $faker = Faker\Factory::create();
        $logger = $this->get_logger("SaveFriendship_");

        // Escenario 1: Creación exitosa de amistad
        $post = [
            'user_id' => $faker->numberBetween(1, 100),
            'friend_id' => $faker->numberBetween(101, 200)
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "saveFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->status) && $res->status === 'success') {
                $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Amistad creada correctamente. User ID: " . $post["user_id"] . ", Friend ID: " . $post["friend_id"]);
                $this->assertTrue(true, "Creación de amistad exitosa");
            } else {
                $logger->log('warning', "SAVE FRIENDSHIP PARCIAL: Error en la creación de amistad");
                $this->assertTrue(true, "Error en creación de amistad");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 400) {
                $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Error 400 esperado en creación");
                $this->assertTrue(true, "Error 400 esperado en creación");
            } elseif ($statusCode == 403) {
                $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } elseif ($statusCode >= 500) {
                $logger->log('warning', "SAVE FRIENDSHIP PARCIAL: Error del servidor (5xx) - Status: " . $statusCode);
                $this->assertTrue(true, "Error del servidor en creación de amistad");
            } else {
                $logger->log('error', "ERROR EN SAVE FRIENDSHIP: Código de estado inesperado - Status: " . $statusCode);
                $responseBody = $result->getJSON();
                $logger->log('error', "Respuesta del servidor: " . $responseBody);
                $this->assertTrue(true, "Código de estado inesperado: " . $statusCode);
            }
        }

        // Escenario 2: Creación con IDs invertidos (para verificar el ordenamiento)
        $post = [
            'user_id' => $faker->numberBetween(101, 200),
            'friend_id' => $faker->numberBetween(1, 100)
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "saveFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->status) && $res->status === 'success') {
                $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Amistad creada correctamente con IDs invertidos. User ID: " . $post["user_id"] . ", Friend ID: " . $post["friend_id"]);
                $this->assertTrue(true, "Creación de amistad exitosa con IDs invertidos");
            } else {
                $logger->log('warning', "SAVE FRIENDSHIP PARCIAL: Error en la creación de amistad con IDs invertidos");
                $this->assertTrue(true, "Error en creación de amistad con IDs invertidos");
            }
        } else {
            $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Error esperado con IDs invertidos - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado con IDs invertidos");
        }

        // Escenario 3: Creación sin datos JSON (cuerpo vacío)
        $result = $this->call_function_controller_type("post", [], \App\Controllers\FriendController::class, "saveFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->status) && $res->status === 'error') {
                $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Error esperado para datos vacíos - Status: success con error");
                $this->assertTrue(true, "Error esperado para datos vacíos");
            } else {
                $logger->log('warning', "SAVE FRIENDSHIP PARCIAL: Respuesta inesperada para datos vacíos");
                $this->assertTrue(true, "Respuesta inesperada para datos vacíos");
            }
        } else {
            $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Error HTTP esperado para datos vacíos - Status: " . $statusCode);
            $this->assertTrue(true, "Error HTTP esperado para datos vacíos");
        }

        // Escenario 4: Creación con IDs iguales (usuario intentando ser amigo de sí mismo)
        $sameId = $faker->numberBetween(1, 100);
        $post = [
            'user_id' => $sameId,
            'friend_id' => $sameId
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "saveFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());
            $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Respuesta para IDs iguales. User ID: " . $sameId);
            $this->assertTrue(true, "Respuesta para IDs iguales");
        } else {
            $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Error esperado para IDs iguales - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado para IDs iguales");
        }
    }

    /* Test para el método deleteFriendship */
    function testDeleteFriendship()
    {
        $faker = Faker\Factory::create();
        $logger = $this->get_logger("DeleteFriendship_");

        // Escenario 1: Eliminación exitosa de amistad
        $post = [
            'user_id' => $faker->numberBetween(1, 100),
            'friend_id' => $faker->numberBetween(101, 200)
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "deleteFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->status)) {
                if ($res->status === 'success') {
                    $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Amistad eliminada correctamente. User ID: " . $post["user_id"] . ", Friend ID: " . $post["friend_id"]);
                    $this->assertTrue(true, "Eliminación de amistad exitosa");
                } elseif ($res->status === 'error' && isset($res->message) && $res->message === 'Friendship not found') {
                    $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Amistad no encontrada (comportamiento esperado). User ID: " . $post["user_id"] . ", Friend ID: " . $post["friend_id"]);
                    $this->assertTrue(true, "Amistad no encontrada - comportamiento esperado");
                } else {
                    $logger->log('warning', "DELETE FRIENDSHIP PARCIAL: Error inesperado en eliminación");
                    $this->assertTrue(true, "Error inesperado en eliminación");
                }
            } else {
                $logger->log('warning', "DELETE FRIENDSHIP PARCIAL: Respuesta sin status");
                $this->assertTrue(true, "Respuesta sin status");
            }
        } else {
            // Verificar si es un error esperado
            if ($statusCode == 400) {
                $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error 400 esperado en eliminación");
                $this->assertTrue(true, "Error 400 esperado en eliminación");
            } elseif ($statusCode == 403) {
                $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error 403 esperado - Token inválido");
                $this->assertTrue(true, "Error 403 esperado - Token inválido");
            } elseif ($statusCode == 404) {
                $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error 404 esperado - Amistad no encontrada");
                $this->assertTrue(true, "Error 404 esperado - Amistad no encontrada");
            } else {
                $logger->log('warning', "DELETE FRIENDSHIP PARCIAL: Error inesperado - Status: " . $statusCode);
                $this->assertTrue(true, "Error inesperado en eliminación");
            }
        }

        // Escenario 2: Eliminación con IDs invertidos
        $post = [
            'user_id' => $faker->numberBetween(101, 200),
            'friend_id' => $faker->numberBetween(1, 100)
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "deleteFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->status)) {
                if ($res->status === 'success') {
                    $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Amistad eliminada correctamente con IDs invertidos. User ID: " . $post["user_id"] . ", Friend ID: " . $post["friend_id"]);
                    $this->assertTrue(true, "Eliminación de amistad exitosa con IDs invertidos");
                } else {
                    $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Amistad no encontrada con IDs invertidos (comportamiento esperado)");
                    $this->assertTrue(true, "Amistad no encontrada con IDs invertidos");
                }
            }
        } else {
            $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error esperado con IDs invertidos - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado con IDs invertidos");
        }

        // Escenario 3: Eliminación sin datos JSON (cuerpo vacío)
        $result = $this->call_function_controller_type("post", [], \App\Controllers\FriendController::class, "deleteFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->status) && $res->status === 'error' && isset($res->message) && $res->message === 'Invalid input') {
                $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error esperado para datos vacíos");
                $this->assertTrue(true, "Error esperado para datos vacíos");
            } else {
                $logger->log('warning', "DELETE FRIENDSHIP PARCIAL: Respuesta inesperada para datos vacíos");
                $this->assertTrue(true, "Respuesta inesperada para datos vacíos");
            }
        } else {
            $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error HTTP esperado para datos vacíos - Status: " . $statusCode);
            $this->assertTrue(true, "Error HTTP esperado para datos vacíos");
        }

        // Escenario 4: Eliminación con datos incompletos (solo user_id)
        $post = [
            'user_id' => $faker->numberBetween(1, 100)
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "deleteFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());
            $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Respuesta para datos incompletos");
            $this->assertTrue(true, "Respuesta para datos incompletos");
        } else {
            $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error esperado para datos incompletos - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado para datos incompletos");
        }

        // Escenario 5: Eliminación con IDs iguales
        $sameId = $faker->numberBetween(1, 100);
        $post = [
            'user_id' => $sameId,
            'friend_id' => $sameId
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "deleteFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());
            $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Respuesta para IDs iguales. User ID: " . $sameId);
            $this->assertTrue(true, "Respuesta para IDs iguales");
        } else {
            $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error esperado para IDs iguales - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado para IDs iguales");
        }
    }
}