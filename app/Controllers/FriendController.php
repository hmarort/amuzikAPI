<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class FriendController extends BaseController
{
    private $token = 'W66jQhYGGzEIuCcAXfpTJkt7uH6GBGpcJLCSXo6O2WF1AZkxiMXpypFaKEfA';

    public function saveFriendship(){

        $jsonBody = $this->request->getJSON();

        if ($jsonBody) {
            $this->friendModel->save([
                'user1'=>$jsonBody->user_id,
                'user2'=>$jsonBody->friend_id,
            ]);

            return $this->response->setJSON(['status' => 'success']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid input']);
        }
    }

    public function deleteFriendship()
    {
        $jsonBody = $this->request->getJSON();
        if (!$jsonBody) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid input']);
        }
        $friendId = $jsonBody->friend_id;
        $userId = $jsonBody->user_id;   
        $friendship = $this->friendModel->where('user1', $userId)->where('user2', $friendId)->first();
        if ($friendship) {
            $this->friendModel->delete($friendship['id']);
            return $this->response->setJSON(['status' => 'success']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Friendship not found']);
        }
    }

}
