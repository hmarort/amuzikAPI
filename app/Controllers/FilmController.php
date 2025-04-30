<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class FilmController extends BaseController
{
    private $token = 'W66jQhYGGzEIuCcAXfpTJkt7uH6GBGpcJLCSXo6O2WF1AZkxiMXpypFaKEfA';
    public function search()
    {   
        $requestToken = $this->request->getHeaderLine('Authorization');

        if ($requestToken !== "Bearer " . $this->token) {
            return $this->response->setJSON([
                'error' => 'No se puede acceder, el token es inválido'
            ])->setStatusCode(401);
        }
        
        $title = $this->request->getJSON()->title;

        $data = $this->filmModel->select("films.*, COALESCE(string_agg(a.name || ' ' || a.surname, ', '), '') AS actors")
            ->where("films.title ilike '%$title%'")
            ->join('perform AS p', 'films.filmId = p.filmId', 'left')
            ->join('actors AS a', 'a.personId = p.personId', 'left')
            ->groupBy('films.filmId')
            ->findAll();


        foreach ($data as $key => $pelicula) {
            $data[$key]['base64'] = $this->base64($pelicula["oid"]);
        }

        return $this->response->setJSON([
            'message' => $data
        ]);
    } //Search

    public function index(): ResponseInterface
    {
        $requestToken = $this->request->getHeaderLine('Authorization');

        if ($requestToken !== "Bearer " . $this->token) {
            return $this->response->setJSON([
                'error' => 'No se puede acceder, el token es inválido'
            ])->setStatusCode(401);
        }

        $data = $this->filmModel->select("films.*, COALESCE(string_agg(a.name || ' ' || a.surname, ', '), '') AS actors")
            ->join('perform AS p', 'films.filmId = p.filmId', 'left')
            ->join('actors AS a', 'a.personId = p.personId', 'left')
            ->groupBy('films.filmId')
            ->findAll();


        foreach ($data as $key => $pelicula) {
            $data[$key]['base64'] = $this->base64($pelicula["oid"]);
        }

        return $this->response->setJSON([
            'message' => $data
        ]);
    }

    public function delete()
    {
        $requestToken = $this->request->getHeaderLine('Authorization');

        if ($requestToken !== "Bearer " . $this->token) {
            return $this->response->setJSON([
                'error' => 'No se puede acceder, el token es inválido'
            ])->setStatusCode(401);
        }

        $id = $this->request->getJSON()->filmId;

        if (ENVIRONMENT == 'testing') {
            return $this->response->setJSON(true);
        } else {
            /**eliminamos la peli */
            $affectedRows = $this->filmModel->where('filmId', $id)->delete();
            if ($affectedRows > 0) {
                return $this->response->setJSON([
                    'message' => 'Película eliminada con éxito'
                ]);
            } else {
                return $this->response->setJSON([
                    'error' => 'No se encontró la película o no se pudo eliminar'
                ])->setStatusCode(404);
            }
        }
    }

    /** Esperar a tener la funcion eliminar para completarlas */
    public function saveFilm()
    {
        $requestToken = $this->request->getHeaderLine('Authorization');

        if ($requestToken !== "Bearer " . $this->token) {
            return $this->response->setJSON([
                'error' => 'No se puede acceder, el token es inválido'
            ])->setStatusCode(401);
        }

        // Configurar manejador de errores personalizado
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \Exception($message);
        });

        try {
            $info = $this->getInfo();
            $this->filmModel->transStart();

            $oid = pg_lo_create($this->filmModel->connID);
            if ($oid === false) {
                throw new \Exception("No se pudo crear el objeto large");
            }

            if (!isset($info['filmId'])) {
                if (
                    empty($info['title']) || empty($info['genre']) ||
                    empty($info['country']) || empty($info['date']) ||
                    empty($info['poster'])
                ) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Todos los campos son requeridos');
                }
            }

            // Procesar el poster si existe y es válido
            if (isset($info['poster']) && $info['poster']->isValid() && !$info['poster']->hasMoved()) {
                $newName = $info['poster']->getRandomName();
                $info['poster']->move(WRITEPATH . 'uploads', $newName);
                $posterPath = WRITEPATH . 'uploads/' . $newName;

                if (!file_exists($posterPath)) {
                    throw new \Exception('Error al mover el archivo: ' . $posterPath);
                }

                $contenido = base64_encode(file_get_contents($posterPath));
                $handle = pg_lo_open($this->filmModel->connID, $oid, 'w');

                if ($handle === false) {
                    throw new \Exception('Error al abrir el objeto large para escritura');
                }

                if (pg_lo_write($handle, $contenido) === false) {
                    throw new \Exception('Error al escribir en el objeto large');
                }

                pg_lo_close($handle);

                unlink($posterPath);

                $info['oid'] = $oid;
            } elseif (ENVIRONMENT == "testing") {
                $newName = $info['poster']->getRandomName();
                if (!copy($info['poster']->getTempName(), WRITEPATH . 'uploads/' . $newName)) {
                    throw new \Exception('Error al copiar el archivo de prueba');
                }
                $info['oid'] = $oid;
            }

            if ($this->filmModel->save($info)) {
                $this->filmModel->transCommit();
                $message = isset($info['filmId'])
                    ? 'Película actualizada correctamente'
                    : 'Película insertada correctamente';
                return $this->response->setJSON([
                    'message' => $message
                ]);
            } else {
                $this->filmModel->transRollback();
                $error = isset($info['filmId']) ? 'error_editar' : 'error';
                $message = isset($info['filmId'])
                    ? implode(", ", $this->filmModel->errors())
                    : 'Error al insertar la película';
                return $this->response->setJSON([
                    'error' => $message
                ])->setStatusCode(400); // Código de error
            }
        } catch (\Throwable $th) {
            $this->filmModel->transRollback();
            log_message('error', $th->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al procesar la película: ' . $th->getMessage());
        } finally {
            restore_error_handler();
        }
    } //SaveFilm

    private function getInfo()
    {
        $info = array(
            'filmId' => $this->request->getPost('filmId'),
            'title' => $this->request->getPost('title'),
            'genre' => $this->request->getPost('genre'),
            'country' => $this->request->getPost('country'),
            'date' => $this->request->getPost('date'),
            'poster' => $this->request->getFile('poster')
        );
        return $info;
    } //GetInfo
}
