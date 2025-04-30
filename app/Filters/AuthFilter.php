<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $key = 'W66jQhYGGzEIuCcAXfpTJkt7uH6GBGpcJLCSXo6O2WF1AZkxiMXpypFaKEfA';
        $header = $request->getHeaderLine('Authorization'); // Obtener el header de autorización

        if (!$header) {
            return service('response')->setJSON(['error' => 'Token requerido'])->setStatusCode(401);
        }

        // El token suele venir como "Bearer <token>", extraemos solo el token
        $token = explode(' ', $header)[1] ?? '';

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return;
        } catch (\Exception $e) {
            return service('response')->setJSON(['error' => 'Token inválido'])->setStatusCode(401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
