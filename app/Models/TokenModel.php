<?php
namespace App\Models;
use CodeIgniter\Model;

class TokenModel extends Model
{
    protected $primaryKey = "id";
    protected $table = "tokens";
    protected $allowedFields = ["username", "token_movil"];
    
    protected $validationRules = [
        'username' => 'required',
        'token_movil' => 'required'
    ];
    
    protected $validationMessages = [
        'username' => [
            'required' => 'El nombre de usuario es obligatorio.',
        ],
        'token_movil' => [
            'required' => 'El token móvil es obligatorio.'
        ]
    ];
    
    public function __construct()
    {
        parent::__construct();
    }
}