<?php
namespace App\Controllers;
use CodeIgniter\HTTP\ResponseInterface;

class TokenController extends BaseController
{
    /**
     * Register a new mobile token for a user
     *
     * @return ResponseInterface
     */
    public function insertToken()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        
        $jsonBody = $this->request->getJSON();
        
        // Agregar trim para eliminar espacios en blanco
        $username = trim($jsonBody->username ?? '');
        $tokenMovil = trim($jsonBody->token_movil ?? '');
        
        // Validación temprana con respuestas específicas
        if (empty($username)) {
            return $this->response->setJSON([
                'error' => 'El nombre de usuario no puede estar vacío'
            ])->setStatusCode(400);
        }
        
        if (empty($tokenMovil)) {
            return $this->response->setJSON([
                'error' => 'El token móvil no puede estar vacío'
            ])->setStatusCode(400);
        }
        
        // Verificar que el usuario existe antes de registrar el token
        $userExists = $this->userModel->where('username', $username)->countAllResults();
        if ($userExists === 0) {
            return $this->response->setJSON([
                'error' => 'El usuario especificado no existe'
            ])->setStatusCode(404);
        }
        
        // Crear modelo de Token si aún no está cargado
        if (!isset($this->tokenModel)) {
            $this->tokenModel = new \App\Models\TokenModel();
        }
        
        // Preparar los datos para guardar
        $data = [
            'username' => $username,
            'token_movil' => $tokenMovil
        ];
        
        try {
            $this->tokenModel->transStart();
            
            // Verificar si ya existe un token para este usuario y actualizarlo
            $existingToken = $this->tokenModel->where('username', $username)->first();
            if ($existingToken) {
                // Actualizar el token existente
                $data['id'] = $existingToken['id'];
            }
            
            // Guardar los datos
            if ($this->tokenModel->save($data)) {
                $this->tokenModel->transCommit();
                
                // Determinar el mensaje según si fue actualización o inserción
                $message = $existingToken
                    ? 'Token móvil actualizado correctamente'
                    : 'Token móvil registrado correctamente';
                    
                return $this->response->setJSON([
                    'message' => $message,
                    'token_id' => $existingToken ? $existingToken['id'] : $this->tokenModel->getInsertID()
                ]);
            } else {
                $this->tokenModel->transRollback();
                return $this->response->setJSON([
                    'error' => implode(", ", $this->tokenModel->errors())
                ])->setStatusCode(400);
            }
        } catch (\Throwable $th) {
            $this->tokenModel->transRollback();
            log_message('error', $th->getMessage());
            return $this->response->setJSON([
                'error' => 'Error al procesar la solicitud: ' . $th->getMessage()
            ])->setStatusCode(500);
        }
    }
}