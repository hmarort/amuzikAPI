<?php
namespace App\Controllers;
use CodeIgniter\HTTP\ResponseInterface;

class FriendController extends BaseController
{
    public function saveFriendship()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        
        $jsonBody = $this->request->getJSON();
        if(empty($jsonBody)) {
            $jsonBody = $this->testing($jsonBody);
        }

        if ($jsonBody && isset($jsonBody->user_id) && isset($jsonBody->friend_id)) {
            $user_id = $jsonBody->user_id;
            $friend_id = $jsonBody->friend_id;

            // Validar que no sean el mismo usuario
            if ($user_id == $friend_id) {
                return $this->response->setJSON([
                    'status' => 'error', 
                    'message' => 'Cannot create friendship with yourself'
                ]);
            }

            // Validar que los usuarios existan antes de intentar crear la amistad
            if (!$this->validateUsersExist($user_id, $friend_id)) {
                return $this->response->setJSON([
                    'status' => 'error', 
                    'message' => 'One or both users do not exist'
                ]);
            }

            if ($user_id < $friend_id) {
                $user1 = $user_id;
                $user2 = $friend_id;
            } else {
                $user1 = $friend_id;
                $user2 = $user_id;
            }

            // Verificar si la amistad ya existe
            $existingFriendship = $this->friendModel->where('user1', $user1)
                                                   ->where('user2', $user2)
                                                   ->first();

            if ($existingFriendship) {
                return $this->response->setJSON([
                    'status' => 'error', 
                    'message' => 'Friendship already exists'
                ]);
            }

            try {
                $this->friendModel->save([
                    'user1' => $user1,
                    'user2' => $user2,
                ]);
                return $this->response->setJSON(['status' => 'success']);
            } catch (\Exception $e) {
                return $this->response->setStatusCode(500)
                                     ->setJSON([
                                         'status' => 'error', 
                                         'message' => 'Database error: ' . $e->getMessage()
                                     ]);
            }
        } else {
            return $this->response->setStatusCode(400)
                                 ->setJSON([
                                     'status' => 'error', 
                                     'message' => 'Invalid input - user_id and friend_id are required',
                                 ]);
        }
    }

    public function deleteFriendship()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }

        $jsonBody = $this->request->getJSON();
        if(empty($jsonBody)) {
            $jsonBody = $this->testing($jsonBody);
        }

        if (!$jsonBody || !isset($jsonBody->user_id) || !isset($jsonBody->friend_id)) {
            return $this->response->setStatusCode(400)
                                 ->setJSON([
                                     'status' => 'error', 
                                     'message' => 'Invalid input - user_id and friend_id are required'
                                 ]);
        }

        $user_id = $jsonBody->user_id;
        $friend_id = $jsonBody->friend_id;

        // Validar que no sean el mismo usuario
        if ($user_id == $friend_id) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => 'Cannot delete friendship with yourself'
            ]);
        }

        $friendship = null;

        if ($user_id < $friend_id) {
            $friendship = $this->friendModel->where('user1', $user_id)
                                           ->where('user2', $friend_id)
                                           ->first();
        } else {
            $friendship = $this->friendModel->where('user1', $friend_id)
                                           ->where('user2', $user_id)
                                           ->first();
        }

        if ($friendship) {
            try {
                $this->friendModel->delete($friendship['id']);
                return $this->response->setJSON(['status' => 'success']);
            } catch (\Exception $e) {
                return $this->response->setStatusCode(500)
                                     ->setJSON([
                                         'status' => 'error', 
                                         'message' => 'Database error: ' . $e->getMessage()
                                     ]);
            }
        } else {
            return $this->response->setStatusCode(404)
                                 ->setJSON([
                                     'status' => 'error', 
                                     'message' => 'Friendship not found'
                                 ]);
        }
    }

    /**
     * Validar que ambos usuarios existan en la base de datos
     */
    private function validateUsersExist($user_id, $friend_id)
    {
        try {
            $userModel = $this->userModel;
            
            $user1 = $userModel->find($user_id);
            $user2 = $userModel->find($friend_id);
            
            return ($user1 !== null && $user2 !== null);
        } catch (\Exception $e) {
            return false;
        }
    }
}