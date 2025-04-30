<?php

namespace App\Models;

use CodeIgniter\Model;

class FriendModel extends Model
{
    protected $primaryKey = "id";

    protected $table = "friends";

    protected $allowedFields = ["user1", "user2"];

    protected $validationRules = [
        'user1' => 'required|is_natural_no_zero',
        'user2' => 'required|is_natural_no_zero|differs[user1]'
    ];

    protected $validationMessages = [
        'user1' => [
            'required' => 'El usuario 1 es obligatorio.',
            'is_natural_no_zero' => 'ID de usuario 1 inválido.'
        ],
        'user2' => [
            'required' => 'El usuario 2 es obligatorio.',
            'is_natural_no_zero' => 'ID de usuario 2 inválido.',
            'differs' => 'Un usuario no puede ser amigo de sí mismo.'
        ]
    ];

    public function __construct()
    {
        parent::__construct();
    }
}
