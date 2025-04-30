<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $primaryKey = "id";

    protected $table = "users";

    protected $allowedFields = ["nombre", "apellidos", "email", "password", "pfp", "username"];

    protected $validationRules = [
        'nombre'    => 'required',
        'apellidos' => 'required',
        'email'     => 'required|valid_email|is_unique[users.email]',
        'password'  => 'required|min_length[6]',
        'username'  => 'required|is_unique[users.username]',
    ];

    protected $validationMessages = [
        'nombre' => [
            'required' => 'El nombre es obligatorio.'
        ],
        'apellidos' => [
            'required' => 'Los apellidos son obligatorios.'
        ],
        'email' => [
            'required' => 'El email es obligatorio.',
            'valid_email' => 'El email no es válido.',
            'is_unique' => 'El email ya está en uso.'
        ],
        'password' => [
            'required' => 'La contraseña es obligatoria.',
            'min_length' => 'La contraseña debe tener al menos 6 caracteres.'
        ],
        'username' => [
            'required' => 'El nombre de usuario es obligatorio.',
            'is_unique' => 'El nombre de usuario ya está en uso.'
        ]
    ];

    public function __construct()
    {
        parent::__construct();
    }
}