<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class UserController extends BaseController
{
    
    /**
     * Process user data with friends and profile picture
     * 
     * @param array $user User data
     * @return array|null Processed user data
     */
    private function processUserData($user)
    {
        if (!$user) {
            return null;
        }
        
        // Utilizar ?? para manejar casos de pfp nulo
        $user['base64'] = $this->base64($user['pfp'] ?? '');
        
        // Optimizar consulta de amigos
        $friendsQuery = $this->userModel
            ->select('users.id, users.nombre, users.apellidos, users.email, users.username, users.pfp')
            ->join('amuzik.friends', 'users.id = friends.user1 OR users.id = friends.user2')
            ->groupStart()
                ->where('friends.user1', $user['id'])
                ->orWhere('friends.user2', $user['id'])
            ->groupEnd()
            ->where('users.id !=', $user['id']);
        
        // Usar caché para la consulta de amigos si es posible
        $user['friends'] = $friendsQuery->findAll();
        
        $user['friends'] = array_map(function($friend) {
            $friend['base64'] = $this->base64($friend['pfp'] ?? '');
            return $friend;
        }, $user['friends']);
        
        return $user;
    }
    
    /**
     * User login endpoint
     * 
     * @return ResponseInterface
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
        
        // Validación temprana con respuestas específicas
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
        
        // Usar findByUsername como método optimizado si es posible
        $user = $this->userModel->where('username', $username)->first();
        
        // Verificación de credenciales con tiempo constante
        if (!$user || !password_verify($password, $user['password'] ?? '')) {
            // Usar tiempo de espera para prevenir ataques de fuerza bruta
            sleep(1);
            return $this->response->setJSON([
                'error' => 'Credenciales inválidas'
            ])->setStatusCode(401);
        }
        
        // Procesar datos de usuario con amigos y foto de perfil
        $processedUser = $this->processUserData($user);
        
        return $this->response->setJSON([
            'message' => $processedUser
        ]);
    }
    
    /**
     * Get user info by ID
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
        
        $user = $this->userModel->find($userId);
        
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
    }
    
    /**
     * Delete a user
     */
    public function deleteUser()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        
        $userId = $this->request->getJson()->id??null;
        if (!$userId) {
            return $this->response->setJSON([
                'error' => 'ID de usuario requerido'
            ])->setStatusCode(400);
        }
        
        if ($this->userModel->delete($userId)) {
            return $this->response->setJSON([
                'message' => 'Usuario eliminado correctamente'
            ]);
        } else {
            return $this->response->setJSON([
                'error' => 'Error al eliminar el usuario'
            ])->setStatusCode(400);
        }
    }
    
    /**
     * Save (create or update) a user
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
                    // Añadir la conversión a base64
                    $updatedUser['base64'] = $this->base64($updatedUser['pfp']);
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
     * Validate user data for creation or update
     * 
     * @param array $info User data
     * @param bool $isUpdate Whether this is an update or new record
     * @throws \Exception If validation fails
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
     * Process profile picture upload
     * 
     * @param array $info User data
     * @param bool $isUpdate Whether this is an update or new record
     * @return array Updated user data
     * @throws \Exception If file processing fails
     */
    private function processProfilePicture($info, $isUpdate)
    {
        // Procesar la foto de perfil solo si se proporciona
        if (isset($info['pfp']) && $info['pfp'] !== null && is_object($info['pfp'])) {
            $oid = pg_lo_create($this->userModel->connID);
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
                $handle = pg_lo_open($this->userModel->connID, $oid, 'w');
                
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
        } elseif (!$isUpdate) {
            // Si no hay pfp y es un registro, error
            throw new \Exception('La foto de perfil es requerida para el registro');
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
        
        if (!$username) {
            return $this->response->setJSON([
                'error' => 'El nombre de usuario es requerido'
            ])->setStatusCode(400);
        }
        
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