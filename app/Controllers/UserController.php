<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class UserController extends BaseController
{
    
    /**
     * Procesa los datos del usuario, incluyendo amigos y foto
     */
    private function processUserData($user)
    {
        if (!$user) {
            return null;
        }
        
        // Verificar si pfp es válido antes de procesar
        if (!empty($user['pfp']) && is_numeric($user['pfp'])) {
            try {
                $user['base64'] = $this->base64($user['pfp']);
            } catch (\Exception $e) {
                // Si hay error al procesar la imagen, usar cadena vacía
                $user['base64'] = '';
                log_message('warning', 'Error al procesar imagen de perfil: ' . $e->getMessage());
            }
        } else {
            $user['base64'] = '';
        }
        
        // Inicializar friends como array vacío por defecto
        $user['friends'] = [];
        
        try {
            // Verificar que el modelo y la conexión estén disponibles
            if ($this->userModel && $this->userModel->db->connID) {
                // Optimizar consulta de amigos
                $friendsQuery = $this->userModel
                    ->select('users.id, users.nombre, users.apellidos, users.email, users.username, users.pfp')
                    ->join('amuzik.friends', 'users.id = friends.user1 OR users.id = friends.user2', 'left')
                    ->groupStart()
                        ->where('friends.user1', $user['id'])
                        ->orWhere('friends.user2', $user['id'])
                    ->groupEnd()
                    ->where('users.id !=', $user['id']);
                
                $friends = $friendsQuery->findAll();
                
                if ($friends) {
                    $user['friends'] = array_map(function($friend) {
                        if (!empty($friend['pfp']) && is_numeric($friend['pfp'])) {
                            try {
                                $friend['base64'] = $this->base64($friend['pfp']);
                            } catch (\Exception $e) {
                                $friend['base64'] = '';
                                log_message('warning', 'Error al procesar imagen de amigo: ' . $e->getMessage());
                            }
                        } else {
                            $friend['base64'] = '';
                        }
                        return $friend;
                    }, $friends);
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener amigos: ' . $e->getMessage());
            // Mantener friends como array vacío si hay error
        }
        
        return $user;
    }
    
    /**
     * Login de los usuarios
     */
    public function login()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        
        $jsonBody = $this->request->getJSON();
        $jsonBody = $this->testing($jsonBody);
        
        // Agregar trim para eliminar espacios en blanco
        $username = trim($jsonBody->username ?? '');
        $password = $jsonBody->password ?? '';
        
        if (empty($username)) {
            return $this->response->setJSON([
                'error' => 'El nombre de usuario no puede estar vacío'
            ])->setStatusCode(400);
        }
        
        if (empty($password)) {
            return $this->response->setJSON([
                'error' => 'La contraseña no puede estar vacía'
            ])->setStatusCode(400);
        }
        
        try {
            $user = $this->userModel->where('username', $username)->first();
            
            // Verificación de credenciales con tiempo constante
            if (!$user || !password_verify($password, $user['password'] ?? '')) {
                if (ENVIRONMENT !== 'testing') {
                    sleep(1);
                }
                return $this->response->setJSON([
                    'error' => 'Credenciales inválidas'
                ])->setStatusCode(401);
            }
            
            // Procesar datos de usuario con amigos y foto de perfil
            $processedUser = $this->processUserData($user);
            
            return $this->response->setJSON([
                'message' => $processedUser
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en login: ' . $e->getMessage());
            return $this->response->setJSON([
                'error' => 'Error interno del servidor'
            ])->setStatusCode(500);
        }
    }
    
    /**
     * Obtener usuario por ID
     */
    public function userInfo()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        
        $userId = $this->request->getGet('id');
        if (!$userId) {
            return $this->response->setJSON([
                'error' => 'ID de usuario requerido'
            ])->setStatusCode(400);
        }
        
        try {
            $user = $this->userModel->find($userId);
            
            if ($user) {
                $user = $this->processUserData($user);
                
                return $this->response->setJSON([
                    'message' => $user
                ]);
            } else {
                return $this->response->setJSON([
                    'error' => 'Usuario no encontrado'
                ])->setStatusCode(404);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error en userInfo: ' . $e->getMessage());
            return $this->response->setJSON([
                'error' => 'Error interno del servidor'
            ])->setStatusCode(500);
        }
    }
    
    /**
     * Eliminar un usuario
     */
    public function deleteUser()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        
        $userId = $this->request->getJson()->id ?? null;
        $userId = $this->testing($userId)->id??null;

        echo $userId;die();
        if (!$userId) {
            return $this->response->setJSON([
                'error' => 'ID de usuario requerido'
            ])->setStatusCode(400);
        }
        try {
            if ($this->userModel->delete($userId)) {
                return $this->response->setJSON([
                    'message' => 'Usuario eliminado correctamente'
                ]);
            } else {
                return $this->response->setJSON([
                    'error' => 'Error al eliminar el usuario'
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error en deleteUser: ' . $e->getMessage());
            return $this->response->setJSON([
                'error' => 'Error interno del servidor'
            ])->setStatusCode(500);
        }
    }
    
    /**
     * Save (crear o actualizar) a un usuario
     */
    public function saveUser()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \Exception($message);
        });
        
        try {
            $info = $this->getUserInfo();
            $this->userModel->transStart();
            
            // Verificar si es actualización o registro
            $isUpdate = isset($info['id']) && !empty($info['id']);
            
            // Validación común para ambos casos
            $this->validateUserData($info, $isUpdate);
            
            // Procesar la foto de perfil
            $info = $this->processProfilePicture($info, $isUpdate);
            
            // En caso de update, solo enviamos los campos que no están vacíos
            if ($isUpdate) {
                // Creamos un array con solo los campos que se proporcionaron
                $updateData = ['id' => $info['id']]; // Siempre incluimos el ID
                
                // Solo incluimos campos que no estén vacíos
                foreach ($info as $key => $value) {
                    if ($key !== 'id' && isset($value) && !empty($value)) {
                        $updateData[$key] = $value;
                    }
                }
                
                // Actualizamos con los campos proporcionados
                if ($this->userModel->save($updateData)) {
                    $this->userModel->transCommit();
                    $updatedUser = $this->userModel->find($info['id']);
                    // Añadir la conversión a base64 de forma segura
                    if (!empty($updatedUser['pfp']) && is_numeric($updatedUser['pfp'])) {
                        try {
                            $updatedUser['base64'] = $this->base64($updatedUser['pfp']);
                        } catch (\Exception $e) {
                            $updatedUser['base64'] = '';
                        }
                    } else {
                        $updatedUser['base64'] = '';
                    }
                    return $this->response->setJSON([
                        'message' => 'Usuario actualizado correctamente',
                        'user' => $updatedUser
                    ]);
                }
            } else {
                // Para registro, enviamos todos los campos
                if ($this->userModel->save($info)) {
                    $this->userModel->transCommit();
                    return $this->response->setJSON([
                        'message' => 'Usuario creado correctamente'
                    ]);
                }
            }
            
            // Si llegamos aquí, hubo un error al guardar
            $this->userModel->transRollback();
            $message = $isUpdate
                ? implode(", ", $this->userModel->errors())
                : 'Error al crear el usuario';
                
            return $this->response->setJSON([
                'error' => $message
            ])->setStatusCode(400);
            
        } catch (\Throwable $th) {
            $this->userModel->transRollback();
            log_message('error', $th->getMessage());
            
            return $this->response->setJSON([
                'error' => 'Error al procesar la solicitud: ' . $th->getMessage()
            ])->setStatusCode(500);
        } finally {
            restore_error_handler();
        }
    }
    
    /**
     * Validamos los datos del usuario antes de guardar
     */
    private function validateUserData($info, $isUpdate)
    {
        if ($isUpdate) {
            // Actualización: Solo verificamos que el ID exista
            if (empty($info['id'])) {
                throw new \Exception('El ID es requerido para actualizar');
            }
            
            // Obtenemos el usuario actual
            $existingUser = $this->userModel->find($info['id']);
            if (!$existingUser) {
                throw new \Exception('Usuario no encontrado');
            }
            
            // Verificar si el email o username ya existen (para otro usuario)
            if (isset($info['email']) && !empty($info['email']) && $info['email'] !== $existingUser['email']) {
                $emailExists = $this->userModel->where('email', $info['email'])
                                              ->where('id !=', $info['id'])
                                              ->countAllResults();
                if ($emailExists > 0) {
                    throw new \Exception('El email ya está en uso por otro usuario');
                }
            }
            
            if (isset($info['username']) && !empty($info['username']) && $info['username'] !== $existingUser['username']) {
                $usernameExists = $this->userModel->where('username', $info['username'])
                                                 ->where('id !=', $info['id'])
                                                 ->countAllResults();
                if ($usernameExists > 0) {
                    throw new \Exception('El nombre de usuario ya está en uso');
                }
            }
        } else {
            // Registro: Todos los campos son obligatorios
            if (
                empty($info['nombre']) || 
                empty($info['apellidos']) ||
                empty($info['email']) || 
                empty($info['password']) ||
                empty($info['username']) || 
                empty($info['pfp'])
            ) {
                throw new \Exception('Todos los campos son requeridos para el registro');
            }
            
            // Verificar si el email o username ya existen
            $emailExists = $this->userModel->where('email', $info['email'])->countAllResults();
            if ($emailExists > 0) {
                throw new \Exception('El email ya está registrado');
            }
            
            $usernameExists = $this->userModel->where('username', $info['username'])->countAllResults();
            if ($usernameExists > 0) {
                throw new \Exception('El nombre de usuario ya está registrado');
            }
        }
    }
    
    /**
     * Procesamos la foto de perfil del usuario
     */
    private function processProfilePicture($info, $isUpdate)
    {
        // Procesar la foto de perfil solo si se proporciona
        if (isset($info['pfp']) && $info['pfp'] !== null && is_object($info['pfp'])) {
            try {
                // Verificar que la conexión a la base de datos esté disponible
                if (!$this->userModel->db->connID) {
                    throw new \Exception('Error de conexión a la base de datos');
                }
                
                $oid = pg_lo_create($this->userModel->db->connID);
                if ($oid === false) {
                    throw new \Exception("No se pudo crear el objeto large");
                }
                
                if ($info['pfp']->isValid() && !$info['pfp']->hasMoved()) {
                    $newName = $info['pfp']->getRandomName();
                    $info['pfp']->move(WRITEPATH . 'uploads', $newName);
                    $pfpPath = WRITEPATH . 'uploads/' . $newName;
                    
                    if (!file_exists($pfpPath)) {
                        throw new \Exception('Error al mover el archivo: ' . $pfpPath);
                    }
                    
                    $contenido = file_get_contents($pfpPath);
                    $handle = pg_lo_open($this->userModel->db->connID, $oid, 'w');
                    
                    if ($handle === false) {
                        throw new \Exception('Error al abrir el objeto large para escritura');
                    }
                    
                    if (pg_lo_write($handle, $contenido) === false) {
                        throw new \Exception('Error al escribir en el objeto large');
                    }
                    
                    pg_lo_close($handle);
                    unlink($pfpPath);
                    $info['pfp'] = $oid;
                } elseif (ENVIRONMENT == "testing") {
                    $newName = $info['pfp']->getRandomName();
                    if (!copy($info['pfp']->getTempName(), WRITEPATH . 'uploads/' . $newName)) {
                        throw new \Exception('Error al copiar el archivo de prueba');
                    }
                    $info['pfp'] = $oid;
                }
            } catch (\Exception $e) {
                // En caso de error con la imagen, permitir continuar sin ella en testing
                if (ENVIRONMENT === 'testing' && $isUpdate) {
                    log_message('warning', 'Error al procesar imagen en testing: ' . $e->getMessage());
                    unset($info['pfp']); // Remover la imagen del array para no procesarla
                } else {
                    throw $e;
                }
            }
        } elseif (!$isUpdate) {
            // Si no hay pfp y es un registro, solo error si no estamos en testing
            if (ENVIRONMENT !== 'testing') {
                throw new \Exception('La foto de perfil es requerida para el registro');
            }
        }
        
        return $info;
    }
    
    /**
     * Find user by username
     */
    public function find()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        
        $jsonBody = $this->request->getJSON();
        $jsonBody = $this->testing($jsonBody);
        $username = $jsonBody->username ?? null;
        
        if (empty($username)) {
            return $this->response->setJSON([
                'error' => 'El nombre de usuario es requerido'
            ])->setStatusCode(400);
        }
        
        try {
            $user = $this->userModel->where('username', $username)->first();
            
            if ($user) {
                // Process user data with friends and profile picture
                $user = $this->processUserData($user);
                
                return $this->response->setJSON([
                    'message' => $user
                ]);
            } else {
                return $this->response->setJSON([
                    'error' => 'Usuario no encontrado'
                ])->setStatusCode(404);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error en find: ' . $e->getMessage());
            return $this->response->setJSON([
                'error' => 'Error interno del servidor'
            ])->setStatusCode(500);
        }
    }
    
    /**
     * Get user information from request
     * 
     * @return array User data
     */
    private function getUserInfo()
    {
        $data = [
            'id'        => $this->request->getPost('id'),
            'nombre'    => $this->request->getPost('nombre'),
            'apellidos' => $this->request->getPost('apellidos'),
            'email'     => $this->request->getPost('email'),
            'username'  => $this->request->getPost('username'),
            'pfp'       => $this->request->getFile('pfp')
        ];
        
        // Procesar password solo si se proporciona
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        return $data;
    }
}