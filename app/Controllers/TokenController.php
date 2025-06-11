<?php
namespace App\Controllers;
use CodeIgniter\HTTP\ResponseInterface;

class TokenController extends BaseController
{
    /**
     * Registramos un nuevo usuario junto con su token móvil.
     */
    public function insertToken()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        
        $jsonBody = $this->request->getJSON();

        if(empty($jsonBody)) {
            $jsonBody = $this->testing($jsonBody);
        }        
        
        $username = trim($jsonBody->username ?? '');
        $tokenMovil = trim($jsonBody->token_movil ?? '');
        
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
        
        if (!isset($this->tokenModel)) {
            $this->tokenModel = new \App\Models\TokenModel();
        }
        
        $data = [
            'username' => $username,
            'token_movil' => $tokenMovil
        ];
        
        try {
            $this->tokenModel->transStart();
            
            $existingToken = $this->tokenModel->where('username', $username)->first();
            if ($existingToken) {
                $data['id'] = $existingToken['id'];
            }
            
            // Guardar los datos
            if ($this->tokenModel->save($data)) {
                $this->tokenModel->transCommit();
                
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