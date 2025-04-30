<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'FilmController::index');//,['filter' => 'auth']
$routes->post('/delete', 'FilmController::delete');//,['filter' => 'auth']
$routes->post('/save', 'FilmController::saveFilm');//,['filter' => 'auth']
$routes->post('/search', 'FilmController::search');//,['filter' => 'auth']


$routes->post('/login', 'UserController::login');        // User login
$routes->post('/register', 'UserController::saveUser');  // User registration
$routes->get('/info', 'UserController::userInfo');       // Get user by ID (changed to GET)
$routes->post('/update', 'UserController::saveUser');    // Update user
$routes->post('/delete', 'UserController::deleteUser');  // Delete user
$routes->post('/find', 'UserController::find');          // Find user by username

// Friend routes
$routes->post('/saveFriendship', 'FriendController::saveFriendship');
$routes->post('/deleteFriendship', 'FriendController::deleteFriendship');