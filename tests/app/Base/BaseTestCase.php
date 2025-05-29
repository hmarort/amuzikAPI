<?php

namespace App\Base;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;

use CodeIgniter\Session\Handlers\ArrayHandler;
use CodeIgniter\Test\Mock\MockSession;
use CodeIgniter\Log\Handlers\FileHandler;
use Config\Logger;
use CodeIgniter\Test\TestLogger;
use Faker;

class BaseTestCase extends CIUnitTestCase
{
    use ControllerTestTrait;
    var $base;
    protected $setUpMethods = [
        'resetServices',
        'mockSession',
    ];
    public function test()
    {
        $this->assertTrue(true);
    }

    public function call_function_controller_type($type = "post", $array_request = array(), $controller = NULL, $function = "index")
    {
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\SiteURI(new \Config\App()),
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        $request->setGlobal($type, $array_request);
        $request->setMethod($type);

        return $this->withRequest($request)->controller($controller)->execute($function);
    }

    public function call_function_controller_type_without_function($type = "post", $array_request = array(), $controller = NULL)
    {
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\SiteURI(new \Config\App()),
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        $request->setGlobal($type, $array_request);
        $request->setMethod($type);

        return $this->withRequest($request)->controller($controller);
    }

    public function get_logger($name)
    {
        $logger = new Logger();
        $logger->handlers['CodeIgniter\Log\Handlers\FileHandler']['path'] = WRITEPATH . "test_logs/$name";
        return (new TestLogger($logger));
    }

    protected function subir_archivos($faker, $controller, $function)
    {
        $tmp_file = tempnam(WRITEPATH . "/tmp", "tmp_");
        file_put_contents($tmp_file, $faker->paragraphs());



        $_FILES['upload'] =
            [
                'name' => $faker->word() . "." . $faker->fileExtension(),
                'type' => $faker->mimeType(),
                'tmp_name' => $tmp_file,
                'error' => 0,
                'size' => filesize($tmp_file)
            ];
        $all_post["action"] = "upload";
        $result = $this->call_function_controller_type("post", $all_post, $controller, $function);
        $res = json_decode($result->getJSON());
        return array($res, $_FILES['upload']["name"]);
    }

    /* Version priopia del subir archivos */
    protected function uploadTmp($faker){
        $tmp_file=tempnam(WRITEPATH,"tmp_");
        file_put_contents($tmp_file, $faker->paragraphs());
       
        return [
            'name' => $faker->word() . "." . $faker->randomElement(["jpg","png"]),
            'type' => $faker->mimeType(),
            'tmp_name' => $tmp_file,
            'error' => 0,
            'size' => filesize($tmp_file)
        ];  
    } 
}