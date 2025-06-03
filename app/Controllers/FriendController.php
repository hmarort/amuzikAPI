<?php
namespace App\Controllers;
use CodeIgniter\HTTP\ResponseInterface;
class FriendController extends BaseController
{    
    public function saveFriendship(){
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        
        echo "\n\nBODY: ";print_r( $this->request->getPost()); echo"\n";

        $jsonBody = $this->request->getJSON();


        $jsonBody = $this->testing($jsonBody);

        if ($jsonBody) {
            $user_id = $jsonBody->user_id;
            $friend_id = $jsonBody->friend_id;
            
            if ($user_id < $friend_id) {
                $user1 = $user_id;
                $user2 = $friend_id;
            } else {
                $user1 = $friend_id;
                $user2 = $user_id;
            }
            
            $this->friendModel->save([
                'user1' => $user1,
                'user2' => $user2,
            ]);
            return $this->response->setJSON(['status' => 'success']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid input', 'input'=>$jsonBody]);
        }
    }
    
    public function deleteFriendship()
    {
        $tokenValidation = $this->validateToken();
        if ($tokenValidation !== true) {
            return $tokenValidation;
        }
        $jsonBody = $this->request->getJSON();
        
        $jsonBody = $this->testing($jsonBody);

        if (!$jsonBody) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid input']);
        }
        
        $user_id = $jsonBody->user_id;
        $friend_id = $jsonBody->friend_id;
        
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
            $this->friendModel->delete($friendship['id']);
            return $this->response->setJSON(['status' => 'success']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Friendship not found']);
        }
    }
}