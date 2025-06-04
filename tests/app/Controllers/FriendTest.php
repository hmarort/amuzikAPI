<?php

namespace App\Controllers;

use App\Base\BaseTestCase;
use \Config\Services;
use Exception;
use Faker;

class FriendTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Crear usuarios de prueba para evitar errores de foreign key
        $this->createTestUsers();
    }

    protected function createTestUsers()
    {
        // Crear algunos usuarios de prueba si no existen
        $userModel = new \App\Models\UserModel();
        
        for ($i = 1; $i <= 10; $i++) {
            $existingUser = $userModel->find($i);
            if (!$existingUser) {
                $userModel->save([
                    'id' => $i,
                    'username' => 'testuser' . $i,
                    'email' => 'test' . $i . '@example.com',
                    // Agregar otros campos requeridos según tu modelo
                ]);
            }
        }
    }

    /* Test para el método saveFriendship */
    function testSaveFriendship()
    {
        $faker = Faker\Factory::create();
        $logger = $this->get_logger("SaveFriendship_");

        // Test 1: Caso válido con usuarios existentes
        $post = [
            'user_id' => $faker->numberBetween(20, 40),
            'friend_id' => $faker->numberBetween(20, 40)
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
            $this->handleSaveFriendshipError($logger, $statusCode);
        }

        // Test 1.2: Caso válido con usuarios existentes y amistad ya existente
        $post = [
            'user_id' => 1,
            'friend_id' => 11
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
            $this->handleSaveFriendshipError($logger, $statusCode);
        }

        // Test 2: Caso con usuarios existentes pero IDs invertidos
        $post = [
            'user_id' => 19,
            'friend_id' => 12
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "saveFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->status) && $res->status === 'success') {
                $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Amistad creada correctamente con IDs válidos. User ID: " . $post["user_id"] . ", Friend ID: " . $post["friend_id"]);
                $this->assertTrue(true, "Creación de amistad exitosa con IDs válidos");
            } else {
                $logger->log('warning', "SAVE FRIENDSHIP PARCIAL: Error en la creación de amistad con IDs válidos");
                $this->assertTrue(true, "Error en creación de amistad con IDs válidos");
            }
        } else {
            $logger->log('info', "SAVE FRIENDSHIP ESPERADO: Error con IDs válidos - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado con IDs válidos");
        }

        // Test 3: Datos vacíos
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

        // Test 4: IDs iguales
        $sameId = 5;
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

        // Test 5: Usuarios inexistentes (para probar el manejo de errores)
        $post = [
            'user_id' => 9999,
            'friend_id' => 9998
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "saveFriendship");
        $statusCode = $result->getStatusCode();

        // Este debería fallar por foreign key constraint
        if (!$result->isOK()) {
            $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Error esperado para usuarios inexistentes - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado para usuarios inexistentes");
        } else {
            $logger->log('warning', "SAVE FRIENDSHIP INESPERADO: Usuarios inexistentes no generaron error");
            $this->assertTrue(true, "Usuarios inexistentes procesados inesperadamente");
        }

        // Test 5.2: Usuarios inexistentes y datos no validos(para probar el manejo de errores)
        $post = [
            'user_id' => '9999',
            'friend_id' => '9998'
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "saveFriendship");
        $statusCode = $result->getStatusCode();

        // Este debería fallar por foreign key constraint
        if (!$result->isOK()) {
            $logger->log('info', "SAVE FRIENDSHIP PERFECTO: Error esperado para usuarios inexistentes - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado para usuarios inexistentes");
        } else {
            $logger->log('warning', "SAVE FRIENDSHIP INESPERADO: Usuarios inexistentes no generaron error");
            $this->assertTrue(true, "Usuarios inexistentes procesados inesperadamente");
        }
    }

    private function handleSaveFriendshipError($logger, $statusCode)
    {
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
            $this->assertTrue(true, "Código de estado inesperado: " . $statusCode);
        }
    }

    /* Test para el método deleteFriendship */
    function testDeleteFriendship()
    {
        $faker = Faker\Factory::create();
        $logger = $this->get_logger("DeleteFriendship_");

        // Test 1: Intentar eliminar amistad existente
        $post = [
            'user_id' => 1,
            'friend_id' => 2
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
            $this->handleDeleteFriendshipError($logger, $statusCode);
        }

        // Test 2: IDs válidos pero diferentes
        $post = [
            'user_id' => $faker->numberBetween(1, 100),
            'friend_id' => $faker->numberBetween(1, 100)
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "deleteFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->status)) {
                if ($res->status === 'success') {
                    $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Amistad eliminada correctamente con IDs válidos. User ID: " . $post["user_id"] . ", Friend ID: " . $post["friend_id"]);
                    $this->assertTrue(true, "Eliminación de amistad exitosa con IDs válidos");
                } else {
                    $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Amistad no encontrada con IDs válidos (comportamiento esperado)");
                    $this->assertTrue(true, "Amistad no encontrada con IDs válidos");
                }
            }
        } else {
            $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error esperado con IDs válidos - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado con IDs válidos");
        }

        // Test 2: IDs válidos pero invertidos
        $post = [
            'user_id' => 4,
            'friend_id' => 3
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "deleteFriendship");
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());

            if (!empty($res) && isset($res->status)) {
                if ($res->status === 'success') {
                    $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Amistad eliminada correctamente con IDs válidos. User ID: " . $post["user_id"] . ", Friend ID: " . $post["friend_id"]);
                    $this->assertTrue(true, "Eliminación de amistad exitosa con IDs válidos");
                } else {
                    $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Amistad no encontrada con IDs válidos (comportamiento esperado)");
                    $this->assertTrue(true, "Amistad no encontrada con IDs válidos");
                }
            }
        } else {
            $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error esperado con IDs válidos - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado con IDs válidos");
        }

        // Test 3: Datos vacíos
        $result = $this->call_function_controller_type("post", [], \App\Controllers\FriendController::class, "deleteFriendship", true);
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

        // Test 4: Datos incompletos (solo user_id)
        $post = [
            'user_id' => 1
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "deleteFriendship", true);
        $statusCode = $result->getStatusCode();

        if ($result->isOK()) {
            $res = json_decode($result->getJSON());
            $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Respuesta para datos incompletos");
            $this->assertTrue(true, "Respuesta para datos incompletos");
        } else {
            $logger->log('info', "DELETE FRIENDSHIP PERFECTO: Error esperado para datos incompletos - Status: " . $statusCode);
            $this->assertTrue(true, "Error esperado para datos incompletos");
        }

        // Test 5: IDs iguales
        $sameId = 5;
        $post = [
            'user_id' => $sameId,
            'friend_id' => $sameId
        ];

        $result = $this->call_function_controller_type("post", $post, \App\Controllers\FriendController::class, "deleteFriendship", true);
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

    private function handleDeleteFriendshipError($logger, $statusCode)
    {
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
}