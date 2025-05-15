<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;
    protected $filmModel;
    protected $userModel;
    protected $friendModel;
    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        $this->userModel = new \App\Models\UserModel();
        $this->friendModel = new \App\Models\FriendModel();
        $this->token = env('JWT_SECRET');
        // E.g.: $this->session = \Config\Services::session();
    }

    public function getResponse(array $responseBody, int $code = ResponseInterface::HTTP_OK)
    {
        return $this->response->setStatusCode($code)->setJSON($responseBody);
    }

    function base64($oid)
    {
        if (!empty($oid)) {
            $this->userModel->transBegin();
            $handle = pg_lo_open($this->userModel->connID, $oid, 'r');
            if ($handle !== false) {
                $data = '';
                $chunkSize = 8192;

                while (($chunk = pg_lo_read($handle, $chunkSize)) !== false && $chunk !== '') {
                    $data .= $chunk;
                }

                pg_lo_close($handle);
                $this->userModel->transCommit();
                return base64_encode($data);
            }
            $this->userModel->transRollback();
        }
        return base64_encode('Imagen no disponible');
    }

    /**
     * Validates request token against API token
     * 
     * @return bool|ResponseInterface Returns true if token is valid or error response
     */
    private function validateToken()
    {
        $requestToken = trim($this->request->getHeaderLine('Authorization'));
        
        if ($requestToken !== "Bearer " . $this->token) {
            return $this->response->setJSON([
                'error' => 'No se puede acceder, el token es inválido'
            ])->setStatusCode(401);
        }
        
        return true;
    }

}
